<?php
require_once '../includes/auth.php';
requireLogin();

if (!isAdmin()) {
    header("Location: ../index.php");
    exit;
}

include '../includes/header.php';
include '../includes/db.php';

$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    header("Location: manage_classes.php");
    exit();
}

// Fetch class details
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = :id");
$stmt->execute(['id' => $class_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    die("Class not found!");
}
?>

<div class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900 py-12 px-6">
    <div class="max-w-7xl mx-auto">
        <h2 class="text-4xl font-bold text-white text-center mb-6"><?= htmlspecialchars($class['name']) ?></h2>
        <p class="text-gray-400 text-center mb-6">Class Code: <span class="text-blue-400 font-bold"><?= htmlspecialchars($class['class_code']) ?></span></p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <a href="view_students.php?class_id=<?= $class['id'] ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-6 rounded-lg transition">
                View Students
            </a>
            <a href="view_dues.php?class_id=<?= $class['id'] ?>" class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg transition">
                Manage Dues
            </a>
            <a href="results.php?class_id=<?= $class['id'] ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg transition">
                Results and Sheets
            </a>
            <a href="messages.php?class_id=<?= $class['id'] ?>" class="bg-pink-500 hover:bg-pink-600 text-white font-bold py-3 px-6 rounded-lg transition">
                Group Messages
            </a>

        </div>

        <div class="text-center mt-8">
            <a href="manage_classes.php" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Manage Classes
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
