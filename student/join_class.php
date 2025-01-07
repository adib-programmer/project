<?php
require_once '../includes/auth.php';
requireLogin();

include '../includes/header.php';
include '../includes/db.php';

$success = '';
$error = '';

// Handle class join request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_code = trim($_POST['class_code']);
    $user_id = $_SESSION['user']['id'];

    if (empty($class_code)) {
        $error = "Class code cannot be empty.";
    } else {
        // Check if the class exists
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE class_code = :class_code");
        $stmt->execute(['class_code' => $class_code]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$class) {
            $error = "Invalid class code.";
        } else {
            $class_id = $class['id'];

            // Check if a request already exists
            $stmt = $pdo->prepare("SELECT status FROM class_requests WHERE user_id = :user_id AND class_id = :class_id");
            $stmt->execute(['user_id' => $user_id, 'class_id' => $class_id]);
            $existingRequest = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingRequest) {
                if ($existingRequest['status'] === 'pending') {
                    $error = "You already have a pending request for this class.";
                } elseif ($existingRequest['status'] === 'approved') {
                    $error = "You have already joined this class.";
                } elseif ($existingRequest['status'] === 'declined') {
                    // Allow resending the request by deleting the declined record and inserting a new pending request
                    $stmt = $pdo->prepare("DELETE FROM class_requests WHERE user_id = :user_id AND class_id = :class_id");
                    $stmt->execute(['user_id' => $user_id, 'class_id' => $class_id]);

                    // Insert a new pending request
                    $stmt = $pdo->prepare("INSERT INTO class_requests (user_id, class_id, status) VALUES (:user_id, :class_id, 'pending')");
                    $stmt->execute(['user_id' => $user_id, 'class_id' => $class_id]);
                    $success = "Your new request to join the class has been sent successfully.";
                }
            } else {
                // Insert a new request
                $stmt = $pdo->prepare("INSERT INTO class_requests (user_id, class_id, status) VALUES (:user_id, :class_id, 'pending')");
                $stmt->execute(['user_id' => $user_id, 'class_id' => $class_id]);
                $success = "Your request to join the class has been sent successfully.";
            }
        }
    }
}

?>

<div class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900 p-6">
    <div class="max-w-3xl mx-auto">
        <h2 class="text-3xl font-bold text-white mb-6 text-center">Join a Class</h2>

        <?php if ($error): ?>
            <div class="bg-red-500 text-white p-4 mb-6 rounded-lg">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif ($success): ?>
            <div class="bg-green-500 text-white p-4 mb-6 rounded-lg">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Join Class Form -->
        <form method="POST" class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <div class="mb-4">
                <label for="class_code" class="block text-gray-400 mb-2">Class Code</label>
                <input type="text" id="class_code" name="class_code" class="w-full p-3 rounded bg-gray-700 text-white" placeholder="Enter class code" required>
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition">
                Send Request
            </button>
        </form>

        <div class="text-center mt-8">
            <a href="dashboard.php" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition">
                Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
