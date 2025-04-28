<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Location: ../index.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Handle SSE
if (isset($_GET['sse'])) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    while (true) {
        if (connection_aborted()) break;

        // Check for new messages
        $stmt = $pdo->prepare("
            SELECT 
                m.*, 
                u.username as sender_name,
                u.avatar as sender_avatar,
                c.name as class_name,
                CASE WHEN rm.id IS NOT NULL THEN TRUE ELSE FALSE END as is_read,
                TIMESTAMPDIFF(MINUTE, m.created_at, NOW()) as minutes_ago
            FROM messages m 
            LEFT JOIN users u ON m.sender_id = u.id
            LEFT JOIN classes c ON m.class_id = c.id
            LEFT JOIN read_messages rm ON m.id = rm.message_id AND rm.user_id = :user_id
            WHERE m.receiver_id = :user_id2 
            AND m.created_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)
            ORDER BY m.created_at DESC
        ");
        
        $stmt->execute(['user_id' => $userId, 'user_id2' => $userId]);
        $newMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($newMessages as $message) {
            echo "data: " . json_encode(['type' => 'message', 'data' => $message]) . "\n\n";
            flush();
        }

        // Check for new assignments
        $stmt = $pdo->prepare("
            SELECT 
                d.*, 
                c.name as class_name,
                TIMESTAMPDIFF(HOUR, NOW(), d.due_date) as hours_remaining
            FROM dues d
            JOIN classes c ON d.class_id = c.id
            WHERE d.class_id IN (
                SELECT class_id FROM class_requests 
                WHERE user_id = :user_id AND status = 'approved'
            )
            AND d.created_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)
            ORDER BY d.due_date ASC
        ");
        
        $stmt->execute(['user_id' => $userId]);
        $newDues = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($newDues as $due) {
            echo "data: " . json_encode(['type' => 'due', 'data' => $due]) . "\n\n";
            flush();
        }

        sleep(5);
    }
    exit;
}

// Handle mark as read
if (isset($_POST['mark_read'], $_POST['message_id'])) {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO read_messages (user_id, message_id) 
        VALUES (:user_id, :message_id)
    ");
    $result = $stmt->execute([
        'user_id' => $userId,
        'message_id' => $_POST['message_id']
    ]);
    echo json_encode(['success' => $result]);
    exit;
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO read_messages (user_id, message_id)
        SELECT :user_id, id FROM messages 
        WHERE receiver_id = :user_id2 
        AND id NOT IN (SELECT message_id FROM read_messages WHERE user_id = :user_id3)
    ");
    $result = $stmt->execute([
        'user_id' => $userId,
        'user_id2' => $userId,
        'user_id3' => $userId
    ]);
    echo json_encode(['success' => $result]);
    exit;
}

// Get recent messages
$stmt = $pdo->prepare("
    SELECT 
        m.*, 
        u.username as sender_name,
        u.avatar as sender_avatar,
        c.name as class_name,
        CASE WHEN rm.id IS NOT NULL THEN TRUE ELSE FALSE END as is_read,
        TIMESTAMPDIFF(MINUTE, m.created_at, NOW()) as minutes_ago
    FROM messages m 
    LEFT JOIN users u ON m.sender_id = u.id
    LEFT JOIN classes c ON m.class_id = c.id
    LEFT JOIN read_messages rm ON m.id = rm.message_id AND rm.user_id = :user_id
    WHERE m.receiver_id = :user_id2
    ORDER BY m.created_at DESC
    LIMIT 50
");

$stmt->execute(['user_id' => $userId, 'user_id2' => $userId]);
$recentMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM messages m 
    WHERE m.receiver_id = :user_id 
    AND m.id NOT IN (SELECT message_id FROM read_messages WHERE user_id = :user_id2)
");
$stmt->execute(['user_id' => $userId, 'user_id2' => $userId]);
$unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get pending assignments
$stmt = $pdo->prepare("
    SELECT 
        d.*, 
        c.name as class_name,
        TIMESTAMPDIFF(HOUR, NOW(), d.due_date) as hours_remaining
    FROM dues d
    JOIN classes c ON d.class_id = c.id
    WHERE d.class_id IN (
        SELECT class_id FROM class_requests 
        WHERE user_id = :user_id AND status = 'approved'
    )
    AND d.due_date > NOW()
    ORDER BY d.due_date ASC
");
$stmt->execute(['user_id' => $userId]);
$pendingAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | School Management System</title>
    <link rel="icon" type="image/png" href="https://cdn-icons-png.flaticon.com/512/352/352723.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">
    
    <style>
        .message-card {
            transition: all 0.3s ease;
        }
        .message-card:hover {
            transform: translateY(-2px);
        }
        .unread-indicator {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .toast {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            z-index: 50;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-8">
                    <a href="dashboard.php" class="text-gray-800 hover:text-blue-500">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="clearAll" class="text-blue-500 hover:text-blue-700">
                        <i class="fas fa-trash mr-2"></i>Clear All
                    </button>
                    <button id="markAllRead" class="text-blue-500 hover:text-blue-700">
                        <i class="fas fa-check-double mr-2"></i>Mark All as Read
                    </button>
                    <button id="refreshNotifications" class="text-blue-500 hover:text-blue-700">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Messages Section -->
            

            <!-- Assignments Section -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">
                        <i class="fas fa-tasks text-blue-500 mr-2"></i>Pending Assignments
                    </h2>
                    <div id="assignments-container" class="space-y-4">
                        <?php foreach ($pendingAssignments as $assignment): ?>
                            <div class="assignment-card animate__animated animate__fadeIn bg-gradient-to-r from-gray-50 to-white p-6 rounded-lg shadow-md">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($assignment['title']) ?></h3>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($assignment['class_name']) ?></p>
                                    </div>
                                    <span class="<?= $assignment['hours_remaining'] < 24 ? 'text-red-500' : 'text-green-500' ?>">
                                        <?= $assignment['hours_remaining'] ?> hours left
                                    </span>
                                </div>
                                <div class="mt-4">
                                    <p class="text-gray-700"><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                                </div>
                                <div class="mt-4">
                                    <a href="<?= htmlspecialchars($assignment['link']) ?>" 
                                       target="_blank"
                                       class="text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-external-link-alt mr-2"></i>View Assignment
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50"></div>

    <!-- Audio Elements -->
    <audio id="notificationSound" preload="auto">
        <source src="../assets/sounds/notification.mp3" type="audio/mpeg">
    </audio>
    <audio id="clearSound" preload="auto">
        <source src="../assets/sounds/clear.mp3" type="audio/mpeg">
    </audio>


    <script>
        // Initialize SSE and notification handling
        const evtSource = new EventSource('notifications.php?sse=1');
        const notificationSound = document.getElementById('notificationSound');
        const clearSound = document.getElementById('clearSound');
        let soundEnabled = localStorage.getItem('notificationSound') !== 'disabled';

        // Add sound toggle button
        const toggleSoundBtn = document.createElement('button');
        toggleSoundBtn.className = 'text-blue-500 hover:text-blue-700';
        toggleSoundBtn.innerHTML = `
            <i class="fas ${soundEnabled ? 'fa-volume-up' : 'fa-volume-mute'} mr-2"></i>
            ${soundEnabled ? 'Sound On' : 'Sound Off'}
        `;
        toggleSoundBtn.onclick = function() {
            soundEnabled = !soundEnabled;
            localStorage.setItem('notificationSound', soundEnabled ? 'enabled' : 'disabled');
            this.innerHTML = `
                <i class="fas ${soundEnabled ? 'fa-volume-up' : 'fa-volume-mute'} mr-2"></i>
                ${soundEnabled ? 'Sound On' : 'Sound Off'}
            `;
            showToast(soundEnabled ? 'Notification sound enabled' : 'Notification sound disabled');
        };
        document.querySelector('.flex.items-center.space-x-4').appendChild(toggleSoundBtn);

        // Toast notification function
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `animate__animated animate__fadeIn toast bg-white p-4 rounded-lg shadow-lg text-gray-800 flex items-center space-x-2`;
            toast.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle text-green-500' : 
                              type === 'error' ? 'fa-times-circle text-red-500' : 
                              'fa-info-circle text-blue-500'} text-lg"></i>
                <span>${message}</span>
            `;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => {
                toast.classList.replace('animate__fadeIn', 'animate__fadeOut');
                setTimeout(() => toast.remove(), 1000);
            }, 3000);
        }

        // Handle SSE messages
        evtSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if (data.type === 'message') {
                const message = data.data;
                const messageHtml = `
                    <div class="message-card animate__animated animate__fadeIn bg-gradient-to-r from-blue-50 to-blue-100 p-6 rounded-lg shadow-md" data-id="${message.id}">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <img src="${message.sender_avatar || '../assets/images/default-avatar.png'}" 
                                         alt="${message.sender_name}" 
                                         class="w-10 h-10 rounded-full">
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800">${message.sender_name}</h3>
                                    <p class="text-sm text-gray-500">
                                        ${message.class_name ? `in ${message.class_name}` : 'Direct message'}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="unread-indicator text-blue-500">
                                    <i class="fas fa-circle text-xs"></i>
                                </span>
                                <button class="mark-read-btn text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-gray-700">${message.message}</p>
                        </div>
                        <div class="mt-4 flex justify-between items-center text-sm text-gray-500">
                            <span>just now</span>
                        </div>
                    </div>
                `;
                document.getElementById('messages-container').insertAdjacentHTML('afterbegin', messageHtml);
                if (soundEnabled) notificationSound.play();
                showToast('New message received', 'success');
            } else if (data.type === 'due') {
                const assignment = data.data;
                const assignmentHtml = `
                    <div class="assignment-card animate__animated animate__fadeIn bg-gradient-to-r from-gray-50 to-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-semibold text-gray-800">${assignment.title}</h3>
                                <p class="text-sm text-gray-500">${assignment.class_name}</p>
                            </div>
                            <span class="${assignment.hours_remaining < 24 ? 'text-red-500' : 'text-green-500'}">
                                ${assignment.hours_remaining} hours left
                            </span>
                        </div>
                        <div class="mt-4">
                            <p class="text-gray-700">${assignment.description}</p>
                        </div>
                        <div class="mt-4">
                            <a href="${assignment.link}" 
                               target="_blank"
                               class="text-blue-500 hover:text-blue-700">
                                <i class="fas fa-external-link-alt mr-2"></i>View Assignment
                            </a>
                        </div>
                    </div>
                `;
                document.getElementById('assignments-container').insertAdjacentHTML('afterbegin', assignmentHtml);
                if (soundEnabled) notificationSound.play();
                showToast('New assignment posted', 'info');
            }
        };

        // Mark single message as read
        document.addEventListener('click', function(e) {
            if (e.target.closest('.mark-read-btn')) {
                const messageCard = e.target.closest('.message-card');
                const messageId = messageCard.dataset.id;
                fetch('notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `mark_read=1&message_id=${messageId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageCard.classList.remove('from-blue-50', 'to-blue-100');
                        messageCard.classList.add('from-gray-50', 'to-white');
                        const indicators = messageCard.querySelectorAll('.unread-indicator, .mark-read-btn');
                        indicators.forEach(el => el.remove());
                        showToast('Message marked as read', 'success');
                    }
                });
            }
        });

        // Mark all as read
        document.getElementById('markAllRead').onclick = function() {
            fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'mark_all_read=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.message-card').forEach(card => {
                        card.classList.remove('from-blue-50', 'to-blue-100');
                        card.classList.add('from-gray-50', 'to-white');
                        const indicators = card.querySelectorAll('.unread-indicator, .mark-read-btn');
                        indicators.forEach(el => el.remove());
                    });
                    showToast('All messages marked as read', 'success');
                }
            });
        };

        // Clear all notifications
        document.getElementById('clearAll').onclick = function() {
            if (confirm('Are you sure you want to clear all notifications?')) {
                document.querySelectorAll('.message-card').forEach(card => {
                    card.classList.add('animate__fadeOut');
                    setTimeout(() => card.remove(), 1000);
                });
                if (soundEnabled) clearSound.play();
                showToast('All notifications cleared', 'success');
            }
        };

        // Refresh notifications
        document.getElementById('refreshNotifications').onclick = function() {
            location.reload();
        };
    </script>
</body>
</html>
