<?php
require_once '../includes/auth.php';
requireLogin();

if (!isAdmin()) {
    header("Location: ../index.php");
    exit;
}

include '../includes/header.php';
include '../includes/db.php';

$error = '';
$success = '';

// Handle adding a new class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $class_name = $_POST['class_name'];
    $description = $_POST['description'];

    if (empty($class_name)) {
        $error = "Class name cannot be empty.";
    } else {
        // Generate unique class code
        $class_code = strtoupper(bin2hex(random_bytes(4)));

        // Insert the class into the database
        $stmt = $pdo->prepare("INSERT INTO classes (name, class_code, description) VALUES (:name, :class_code, :description)");
        $stmt->execute([
            'name' => $class_name,
            'class_code' => $class_code,
            'description' => $description
        ]);

        $success = "Class '$class_name' created successfully with join code: $class_code";
    }
}

// Handle deleting a class
if (isset($_GET['delete_class_id'])) {
    $class_id = $_GET['delete_class_id'];

    // Delete the class and all associated data
    $pdo->prepare("DELETE FROM classes WHERE id = :id")->execute(['id' => $class_id]);
    $success = "Class deleted successfully!";
}

// Fetch all classes
$classes = $pdo->query("SELECT * FROM classes ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900 py-12 px-6">
    <div class="max-w-7xl mx-auto">
        <h2 class="text-4xl font-bold text-white text-center mb-6">Manage Classes</h2>

        <?php if ($error): ?>
            <p class="bg-red-500 text-white text-center p-3 rounded mb-4"><?= $error ?></p>
        <?php elseif ($success): ?>
            <p class="bg-green-500 text-white text-center p-3 rounded mb-4"><?= $success ?></p>
        <?php endif; ?>

        <!-- Add Class Form -->
        <div class="bg-gray-800 p-6 rounded-xl shadow-lg mb-8">
            <h3 class="text-2xl font-semibold text-white mb-4">Add New Class</h3>
            <form method="POST">
                <div class="mb-4">
                    <label for="class_name" class="block text-gray-400">Class Name</label>
                    <input type="text" id="class_name" name="class_name" class="w-full p-3 bg-gray-700 text-white rounded" required>
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-gray-400">Description</label>
                    <textarea id="description" name="description" rows="3" class="w-full p-3 bg-gray-700 text-white rounded"></textarea>
                </div>
                <button type="submit" name="add_class" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition">Create Class</button>
            </form>
        </div>

        <!-- Classes List -->
        <div class="bg-gray-800 p-6 rounded-xl shadow-lg">
            <h3 class="text-2xl font-semibold text-white mb-4">Existing Classes</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($classes as $class): ?>
                    <div class="bg-gray-700 p-6 rounded-lg shadow-md">
                        <h4 class="text-lg font-bold text-blue-300 mb-2"><?= htmlspecialchars($class['name']) ?></h4>
                        <p class="text-gray-400 mb-2"><strong>Join Code:</strong> <?= htmlspecialchars($class['class_code']) ?></p>
                        <div class="flex justify-between items-center">
                            <a href="view_class.php?class_id=<?= $class['id'] ?>" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition">View Class</a>
                            <a href="?delete_class_id=<?= $class['id'] ?>" onclick="return confirm('Are you sure you want to delete this class?');" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="text-center mt-8">
            <a href="dashboard.php" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
