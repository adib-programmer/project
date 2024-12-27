<?php
require_once '../includes/auth.php';
requireLogin();
if (!isAdmin()) {
    header('Location: ../student/dashboard.php');
    exit;
}

include '../includes/header.php';

// Fetch users for messaging
$stmt = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'student'");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_id = $_POST['recipient_id'];
    $message = $_POST['message'];

    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (:sender_id, :receiver_id, :message)");
    $stmt->execute([
        'sender_id' => $_SESSION['user']['id'],
        'receiver_id' => $recipient_id,
        'message' => $message,
    ]);

    $success = "Message sent successfully!";
}
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold">Messaging</h1>
    <div class="mt-4">
        <?php if (!empty($success)): ?>
            <p class="text-green-400"><?= $success ?></p>
        <?php endif; ?>
        <form method="POST" class="bg-gray-800 p-4 rounded">
            <label for="recipient_id" class="block">Recipient:</label>
            <select id="recipient_id" name="recipient_id" class="w-full p-2 bg-gray-700 rounded">
                <?php foreach ($students as $student): ?>
                    <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <label for="message" class="block mt-2">Message:</label>
            <textarea id="message" name="message" class="w-full p-2 bg-gray-700 rounded"></textarea>
            
            <button type="submit" class="bg-blue-500 px-4 py-2 mt-2 rounded">Send Message</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
