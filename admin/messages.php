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
$hasAccess = $stmt->fetch() || isAdmin();

if (!$hasAccess) {
    header('Location: dashboard.php');
    exit;
}

// Handle message deletion
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

// Handle new message submission
if (isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, class_id, message) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user']['id'], $class_id, $message]);
        
        // Handle URL previews
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

// Fetch messages
if (isset($_GET['fetch_messages'])) {
    $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    $stmt = $pdo->prepare("
        SELECT m.*, u.first_name, u.last_name, u.avatar, u.role
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.class_id = ? AND m.id > ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$class_id, $lastId]);
    $messages = $stmt->fetchAll();
    
    foreach ($messages as &$message) {
        $message['avatar'] = $message['avatar'] ?? 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png';
        $message['created_at'] = date('g:i A', strtotime($message['created_at']));
        // Fix for NULL lastname
        $message['display_name'] = trim($message['first_name'] . ' ' . ($message['last_name'] ?? ''));
    }
    
    exit(json_encode($messages));
}

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($class['name']) ?> - Messages</title>
    <style>
        :root {
            --primary-bg: #111111;
            --secondary-bg: #1a1a1a;
            --header-bg: #1e1e1e;
            --border-color: #2d2d2d;
            --text-primary: #e4e6eb;
            --text-secondary: #b0b3b8;
            --message-own: #0084ff;
            --message-other: #2d2d2d;
            --input-bg: #3a3b3c;
            --link-color: #4599ff;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-primary);
            margin: 0;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        .chat-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: var(--secondary-bg);
        }

        .header {
            background-color: var(--header-bg);
            padding: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header h1 {
            margin: 0;
            font-size: 1.25rem;
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            background-color: var(--primary-bg);
            scroll-behavior: smooth;
        }

        .message-group {
            margin-bottom: 20px;
        }

        .message {
            padding: 12px 16px;
            margin: 4px 0;
            max-width: 80%;
            border-radius: 18px;
            position: relative;
            transition: opacity 0.3s ease;
        }

        .message.own {
            margin-left: auto;
            background-color: var(--message-own);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.other {
            margin-right: auto;
            background-color: var(--message-other);
            border-bottom-left-radius: 4px;
        }

        .message.deleted {
            opacity: 0.6;
            font-style: italic;
        }

        .message-header {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            gap: 8px;
        }

        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .username {
            font-weight: 500;
            color: var(--text-primary);
            text-decoration: none;
        }

        .timestamp {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 4px;
            display: inline-block;
        }

        .input-container {
            padding: 16px;
            background-color: var(--header-bg);
            border-top: 1px solid var(--border-color);
            position: sticky;
            bottom: 0;
        }

        .message-input {
            width: 100%;
            padding: 12px 20px;
            border-radius: 24px;
            border: 1px solid var(--border-color);
            background-color: var(--input-bg);
            color: var(--text-primary);
            outline: none;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .message-input:focus {
            border-color: var(--message-own);
        }

        .link-preview {
            margin-top: 12px;
            padding: 12px;
            background-color: rgba(255,255,255,0.1);
            border-radius: 8px;
            transition: transform 0.2s ease;
        }

        .link-preview:hover {
            transform: translateY(-2px);
        }

        .link-preview img {
            max-width: 100%;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .link-preview-title {
            font-weight: 500;
            margin-bottom: 4px;
        }

        .link-preview-url {
            color: var(--link-color);
            font-size: 0.875rem;
            text-decoration: none;
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .back-button {
            padding: 8px;
            border-radius: 50%;
            transition: background-color 0.2s ease;
        }

        .back-button:hover {
            background-color: var(--message-other);
        }

        @media (max-width: 768px) {
            .message {
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="header">
            <a href="dashboard.php" class="back-button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M19 12H5M12 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <div>
                <h1><?= htmlspecialchars($class['name']) ?></h1>
            </div>
        </div>

        <div id="messages" class="messages-container"></div>

        <div class="input-container">
            <input type="text" id="messageInput" class="message-input" 
                   placeholder="Type a message..." autocomplete="off">
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let lastMessageId = 0;
            const messagesDiv = document.getElementById('messages');
            const messageInput = document.getElementById('messageInput');
            const currentUserId = <?= $_SESSION['user']['id'] ?>;
            let isScrolledToBottom = true;
            
            function formatMessage(message) {
                const isOwn = message.sender_id == currentUserId;
                const displayName = message.display_name;
                const isDeleted = message.is_deleted === 1;
                
                const userLink = message.role === 'student' 
                    ? `<a href="view_student.php?id=${message.sender_id}" class="username">
                         ${displayName}
                       </a>`
                    : `<span class="username">${displayName}</span>`;
                    
                let previewHtml = '';
                if (message.preview_data) {
                    const preview = JSON.parse(message.preview_data);
                    previewHtml = `
                        <div class="link-preview">
                            ${preview.image ? `<img src="${preview.image}" alt="Preview" loading="lazy">` : ''}
                            <div class="link-preview-title">${preview.title}</div>
                            <a href="${preview.url}" class="link-preview-url" target="_blank" rel="noopener noreferrer">
                                ${preview.url}
                            </a>
                        </div>
                    `;
                }
                
                return `
                    <div class="message ${isOwn ? 'own' : 'other'} ${isDeleted ? 'deleted' : ''}" 
                         data-id="${message.id}" 
                         ${isOwn && !isDeleted ? 'ondblclick="handleMessageDelete(this)"' : ''}>
                        ${!isOwn ? `
                            <div class="message-header">
                                <img src="${message.avatar}" class="avatar" alt="Avatar" loading="lazy">
                                ${userLink}
                            </div>
                        ` : ''}
                        <div class="message-content">
                            <p>${message.message}</p>
                            ${previewHtml}
                            <span class="timestamp">${message.created_at}</span>
                        </div>
                    </div>
                `;
            }

            function scrollToBottom() {
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            }

            messagesDiv.addEventListener('scroll', function() {
                isScrolledToBottom = Math.abs(
                    (messagesDiv.scrollHeight - messagesDiv.scrollTop) - messagesDiv.clientHeight
                ) < 1;
            });

            function fetchMessages() {
                fetch(`?class_id=<?= $class_id ?>&fetch_messages=1&last_id=${lastMessageId}`)
                    .then(response => response.json())
                    .then(messages => {
                        if (messages.length > 0) {
                            messages.forEach(message => {
                                messagesDiv.insertAdjacentHTML('beforeend', formatMessage(message));
                                lastMessageId = Math.max(lastMessageId, message.id);
                            });
                            
                            if (isScrolledToBottom) {
                                scrollToBottom();
                            }
                        }
                    });
            }

            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    const message = this.value.trim();
                    if (message) {
                        this.disabled = true;
                        fetch('', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: `message=${encodeURIComponent(message)}`
                        })
                        .then(() => {
                            this.value = '';
                            this.disabled = false;
                            this.focus();
                            fetchMessages();
                            isScrolledToBottom = true;
                        });
                    }
                }
            });

            window.handleMessageDelete = function(element) {
                if (confirm('Delete this message?')) {
                    const messageId = element.dataset.id;
                    element.style.opacity = '0.5';
                    fetch('', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `delete_message=1&message_id=${messageId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            element.querySelector('p').textContent = '[Message deleted]';
                            element.classList.add('deleted');
                        }
                    });
                }
            };

            // Initial load
            fetchMessages();
            setInterval(fetchMessages, 3000);
            scrollToBottom();
            
            // Focus input on load
            messageInput.focus();
        });
    </script>
</body>
</html>