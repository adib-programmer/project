<?php
require_once '../includes/auth.php';
requireLogin();

include '../includes/header.php';

$error = '';
$success = '';

// Handle query submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query_due'])) {
    $due_id = $_POST['due_id'];
    $message = $_POST['message'];

    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, message, class_id) 
                               SELECT :user_id, :message, dues.class_id FROM dues WHERE dues.id = :due_id");
        $stmt->execute([
            'user_id' => $_SESSION['user']['id'],
            'message' => $message,
            'due_id' => $due_id
        ]);

        $success = "Query sent to the admin successfully.";
    } else {
        $error = "Query message cannot be empty.";
    }
}

// Fetch dues
$stmt = $pdo->prepare("SELECT dues.id, dues.title, classes.name AS class_name FROM dues
                       JOIN classes ON dues.class_id = classes.id
                       JOIN class_requests ON class_requests.class_id = classes.id
                       WHERE class_requests.user_id = :user_id AND class_requests.status = 'approved'");
$stmt->execute(['user_id' => $_SESSION['user']['id']]);
$dues = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold">Pending Dues</h1>
    <div class="mt-4">
        <?php if (!empty($success)): ?>
            <p class="text-green-400"><?= $success ?></p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <p class="text-red-400"><?= $error ?></p>
        <?php endif; ?>
        <ul>
            <?php foreach ($dues as $due): ?>
                <li class="bg-gray-700 p-2 rounded mt-2">
                    <p><strong>Class:</strong> <?= htmlspecialchars($due['class_name']) ?></p>
                    <p><strong>Title:</strong> <?= htmlspecialchars($due['title']) ?></p>
                    <form method="POST" class="mt-2">
                        <input type="hidden" name="due_id" value="<?= $due['id'] ?>">
                        <textarea name="message" class="w-full p-2 bg-gray-700 rounded" placeholder="Write your query"></textarea>
                        <button type="submit" name="query_due" class="bg-blue-500 px-4 py-2 mt-2 rounded">Send Query</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
