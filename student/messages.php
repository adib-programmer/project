<?php
require_once '../includes/auth.php';
require_once '../includes/UrlMeta.php';
requireLogin();

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$class_id]);
$class = $stmt->fetch();

if (!$class) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM class_requests 
    WHERE class_id = ? AND user_id = ? AND status = 'approved'
");
$stmt->execute([$class_id, $_SESSION['user']['id']]);
if (!$stmt->fetch()) {
    header('Location: dashboard.php');
    exit;
}

if (isset($_POST['delete_message'])) {
    $message_id = (int)$_POST['message_id'];
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET message = '[Message deleted]', is_deleted = 1 
        WHERE id = ? AND sender_id = ?
    ");
    $stmt->execute([$message_id, $_SESSION['user']['id']]);
    exit(json_encode(['success' => true]));
}

if (isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, class_id, message) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user']['id'], $class_id, $message]);
        
        if (preg_match('/https?:\/\/[^\s<]+[^<.,:;\'")\]\s]/', $message, $matches)) {
            try {
                $urlMeta = new UrlMeta($matches[0]);
                $preview = json_decode($urlMeta->getWebsiteData(), true);
                $stmt = $pdo->prepare("
                    UPDATE messages 
                    SET preview_data = ? 
                    WHERE id = LAST_INSERT_ID()
                ");
                $stmt->execute([json_encode($preview)]);
            } catch (Exception $e) {}
        }
    }
    exit(json_encode(['success' => true]));
}

if (isset($_GET['fetch_messages'])) {
    $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    $stmt = $pdo->prepare("
        SELECT m.*, u.first_name, u.last_name, u.avatar, u.role
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.class_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$class_id]);
    $messages = $stmt->fetchAll();
    
    foreach ($messages as &$message) {
        $message['avatar'] = $message['avatar'] ?? 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png';
        $message['created_at'] = date('g:i A', strtotime($message['created_at']));
        
        if ($message['first_name']) {
            $name = $message['first_name'];
            if ($message['last_name']) {
                $name .= ' ' . $message['last_name'];
            }
            $message['sender_name'] = htmlspecialchars($name);
        } else {
            $message['sender_name'] = 'Unknown User';
        }
    }
    
    exit(json_encode($messages));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($class['name']) ?> - Messages</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        chatDark: {
                            primary: '#111111',
                            secondary: '#1a1a1a',
                            tertiary: '#2d2d2d'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-chatDark-primary text-gray-100 h-screen flex flex-col">
    <div class="bg-chatDark-secondary p-4 flex items-center gap-4 border-b border-chatDark-tertiary sticky top-0 z-50">
        <a href="dashboard.php" class="text-gray-100">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M19 12H5M12 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
        <div class="text-xl font-semibold"><?= htmlspecialchars($class['name']) ?></div>
    </div>

    <div id="messages" class="flex-1 overflow-y-auto p-4 flex flex-col gap-2 bg-chatDark-primary"></div>

    <div class="bg-chatDark-secondary p-4 border-t border-chatDark-tertiary sticky bottom-0">
        <input type="text" id="messageInput" 
               class="w-full p-3 rounded-full border-none bg-chatDark-tertiary text-gray-100 text-base focus:outline-none focus:ring-2 focus:ring-green-500"
               placeholder="Type a message..." autocomplete="off">
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let lastMessageId = 0;
            const messagesDiv = document.getElementById('messages');
            const messageInput = document.getElementById('messageInput');
            const currentUserId = <?= $_SESSION['user']['id'] ?>;
            let touchStartTime = 0;
            let touchEndTime = 0;
            
            function formatMessage(message) {
                const isOwn = message.sender_id == currentUserId;
                let previewHtml = '';
                
                if (message.preview_data) {
                    const preview = JSON.parse(message.preview_data);
                    previewHtml = `
                        <div class="mt-2 p-2 bg-black bg-opacity-20 rounded">
                            ${preview.image ? `<img src="${preview.image}" alt="Preview" class="w-full rounded mb-2">` : ''}
                            <div class="font-medium">${preview.title}</div>
                            <a href="${preview.url}" class="text-green-400 text-sm truncate block" target="_blank">Visit site</a>
                        </div>
                    `;
                }
                
                const messageText = message.message.replace(/https?:\/\/\S+/g, url => 
                    `<a href="${url}" target="_blank" class="text-green-400">${url}</a>`
                );
                
                const messageClass = isOwn ? 
                    'bg-[#025C4C] ml-auto' : 
                    'bg-[#202C33] mr-auto';
                
                return `
                    <div class="message max-w-[75%] p-3 rounded-lg relative ${messageClass}" 
                         data-id="${message.id}"
                         ontouchstart="handleTouchStart(this)"
                         ontouchend="handleTouchEnd(this)">
                        ${!isOwn ? `
                            <div class="flex items-center gap-2 mb-1">
                                <img src="${message.avatar}" class="w-8 h-8 rounded-full object-cover" alt="Avatar">
                                <span class="font-medium text-sm text-green-400">${message.sender_name}</span>
                            </div>
                        ` : ''}
                        <div class="break-words ${message.is_deleted ? 'opacity-60 italic' : ''}">
                            ${messageText}
                            ${previewHtml}
                        </div>
                        <span class="text-xs text-gray-400 float-right mt-1">${message.created_at}</span>
                    </div>
                `;
            }

            window.handleTouchStart = function(element) {
                touchStartTime = new Date().getTime();
            };

            window.handleTouchEnd = function(element) {
                touchEndTime = new Date().getTime();
                const touchDuration = touchEndTime - touchStartTime;
                
                if (touchDuration > 500 && element.classList.contains('ml-auto')) {
                    handleMessageDelete(element);
                }
            };

            function handleMessageDelete(element) {
                if (confirm('Delete this message?')) {
                    const messageId = element.dataset.id;
                    fetch('', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `delete_message=1&message_id=${messageId}`
                    })
                    .then(() => {
                        const content = element.querySelector('div:not(.flex)');
                        content.textContent = '[Message deleted]';
                        content.classList.add('opacity-60', 'italic');
                    });
                }
            }

            function fetchMessages() {
                fetch(`?class_id=<?= $class_id ?>&fetch_messages=1&last_id=${lastMessageId}`)
                    .then(response => response.json())
                    .then(messages => {
                        if (messages.length > 0) {
                            const wasAtBottom = messagesDiv.scrollHeight - messagesDiv.scrollTop 
                                             <= messagesDiv.clientHeight + 100;
                            
                            messages.forEach(message => {
                                messagesDiv.insertAdjacentHTML('beforeend', formatMessage(message));
                                lastMessageId = Math.max(lastMessageId, message.id);
                            });
                            
                            if (wasAtBottom) {
                                messagesDiv.scrollTop = messagesDiv.scrollHeight;
                            }
                        }
                    });
            }

            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    const message = this.value.trim();
                    if (message) {
                        fetch('', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: `message=${encodeURIComponent(message)}`
                        })
                        .then(() => {
                            this.value = '';
                            fetchMessages();
                        });
                    }
                }
            });

            fetchMessages();
            setInterval(fetchMessages, 3000);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;

            // Handle mobile keyboard
            window.addEventListener('resize', () => {
                setTimeout(() => {
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                }, 100);
            });
        });
    </script>
</body>
</html>