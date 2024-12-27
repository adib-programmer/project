<?php
require_once '../includes/auth.php';
requireLogin();

include '../includes/header.php';

$error = '';
$success = '';

// Handle leave class request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_class'])) {
    $class_id = $_POST['class_id'];

    $stmt = $pdo->prepare("DELETE FROM class_requests WHERE user_id = :user_id AND class_id = :class_id");
    $stmt->execute([
        'user_id' => $_SESSION['user']['id'],
        'class_id' => $class_id
    ]);

    $success = "You have successfully left the class.";
}

// Fetch joined classes
$stmt = $pdo->prepare("SELECT classes.id, classes.name FROM class_requests 
                       JOIN classes ON class_requests.class_id = classes.id 
                       WHERE class_requests.user_id = :user_id AND class_requests.status = 'approved'");
$stmt->execute(['user_id' => $_SESSION['user']['id']]);
$joinedClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold">Your Classes</h1>
    <div class="mt-4">
        <?php if (!empty($success)): ?>
            <p class="text-green-400"><?= $success ?></p>
        <?php endif; ?>
        <h2 class="text-lg">Joined Classes</h2>
        <ul>
            <?php foreach ($joinedClasses as $class): ?>
                <li class="bg-gray-700 p-2 rounded mt-2 flex justify-between">
                    <span><?= htmlspecialchars($class['name']) ?></span>
                    <form method="POST" class="flex space-x-2">
                        <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                        <button type="submit" name="leave_class" class="bg-red-500 px-4 py-2 rounded">Leave Class</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
