<?php
require_once '../includes/auth.php';
requireLogin();

include '../includes/header.php';

$error = '';
$success = '';

// Fetch classmates
$stmt = $pdo->prepare("SELECT users.id, users.first_name, users.last_name FROM users 
                       JOIN class_requests ON users.id = class_requests.user_id 
                       WHERE class_requests.class_id IN (
                           SELECT class_requests.class_id FROM class_requests 
                           WHERE class_requests.user_id = :user_id AND class_requests.status = 'approved'
                       ) AND users.id != :user_id");
$stmt->execute(['user_id' => $_SESSION['user']['id']]);
$classmates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch messages
$stmt = $pdo->prepare("SELECT messages.message, messages.created_at, 
                       sender.first_name AS sender_name, receiver.first_name AS receiver_name 
                       FROM messages
                       JOIN users AS sender ON messages.sender_id = sender.id
                       LEFT JOIN users AS receiver ON messages.receiver_id = receiver.id
                       WHERE messages.sender_id = :user_id OR messages.receiver_id = :user_id
                       ORDER BY messages.created_at DESC");
$stmt->execute(['user_id' => $_SESSION['user']['id']]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = $_POST['receiver_id'];
    $message = $_POST['message'];

    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (:sender_id, :receiver_id, :message)");
        $stmt->execute([
            'sender_id' => $_SESSION['user']['id'],
            'receiver_id' => $receiver_id,
            'message' => $message
        ]);
        $success = "Message sent successfully!";
    } else {
        $error = "Message cannot be empty.";
    }
}
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold">Messages</h1>
    <div class="mt-4">
        <?php if (!empty($error)): ?>
            <p class="text-red-400"><?= $error ?></p>
        <?php elseif (!empty($success)): ?>
            <p class="text-green-400"><?= $success ?></p>
        <?php endif; ?>
        <form method="POST" class="bg-gray-800 p-4 rounded">
            <div class="mb-4">
                <label for="receiver_id" class="block">Recipient:</label>
                <select id="receiver_id" name="receiver_id" class="w-full p-2 bg-gray-700 rounded">
                    <?php foreach ($classmates as $classmate): ?>
                        <option value="<?= $classmate['id'] ?>"><?= htmlspecialchars($classmate['first_name'] . ' ' . $classmate['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="message" class="block">Message:</label>
                <textarea id="message" name="message" class="w-full p-2 bg-gray-700 rounded"></textarea>
            </div>
            <button type="submit" class="bg-blue-500 px-4 py-2 rounded">Send Message</button>
        </form>
    </div>
    <div class="mt-4">
        <h2 class="text-lg">Message History</h2>
        <ul>
            <?php foreach ($messages as $msg): ?>
                <li class="bg-gray-700 p-2 rounded mt-2">
                    <p><strong>From:</strong> <?= htmlspecialchars($msg['sender_name']) ?> <strong>To:</strong> <?= htmlspecialchars($msg['receiver_name'] ?: 'Group') ?></p>
                    <p><?= htmlspecialchars($msg['message']) ?></p>
                    <p class="text-gray-400 text-sm"><?= htmlspecialchars($msg['created_at']) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
