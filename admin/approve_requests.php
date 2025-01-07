<?php
require_once '../includes/auth.php';
requireLogin();

if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

include '../includes/header.php';
include '../includes/db.php';

$error = '';
$success = '';

// Handle request actions (approve or decline)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE class_requests SET status = 'approved' WHERE id = :id");
        $stmt->execute(['id' => $request_id]);
        $success = "Request approved successfully.";
    } elseif ($action === 'decline') {
        $stmt = $pdo->prepare("UPDATE class_requests SET status = 'declined' WHERE id = :id");
        $stmt->execute(['id' => $request_id]);
        $success = "Request declined successfully.";
    }
}

// Fetch pending class requests
$stmt = $pdo->query("
    SELECT class_requests.id AS request_id, 
           users.first_name, users.last_name, users.grade, users.avatar, 
           classes.name AS class_name, classes.class_code 
    FROM class_requests
    JOIN users ON class_requests.user_id = users.id
    JOIN classes ON class_requests.class_id = classes.id
    WHERE class_requests.status = 'pending'
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900 py-12 px-6">
    <div class="max-w-7xl mx-auto">
        <h2 class="text-4xl font-bold text-white text-center mb-6">Approve Class Join Requests</h2>

        <?php if ($error): ?>
            <p class="bg-red-500 text-white text-center p-3 rounded mb-4"><?= $error ?></p>
        <?php elseif ($success): ?>
            <p class="bg-green-500 text-white text-center p-3 rounded mb-4"><?= $success ?></p>
        <?php endif; ?>

        <div class="bg-gray-800 p-6 rounded-xl shadow-lg">
            <h3 class="text-2xl font-semibold text-white mb-4">Pending Requests</h3>
            <?php if ($requests): ?>
                <ul class="space-y-4">
                    <?php foreach ($requests as $request): ?>
                        <li class="bg-gray-700 p-4 rounded flex justify-between items-center">
                            <div class="flex items-center">
                                <img src="<?= $request['avatar'] ?: 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png' ?>" 
                                     alt="Profile" class="w-12 h-12 rounded-full mr-4">
                                <div>
                                    <p class="text-white font-bold"><?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) ?></p>
                                    <p class="text-gray-400">Grade: <?= htmlspecialchars($request['grade']) ?></p>
                                    <p class="text-gray-400">Requesting Class: <?= htmlspecialchars($request['class_name']) ?> 
                                        <span class="text-blue-400">(Code: <?= htmlspecialchars($request['class_code']) ?>)</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <a href="view_student.php?id=<?= $request['request_id'] ?>" 
                                   class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition">Inspect</a>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                    <button type="submit" name="action" value="approve" 
                                            class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition">Approve</button>
                                </form>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                    <button type="submit" name="action" value="decline" 
                                            class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition">Decline</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-gray-400 text-center">No pending requests at the moment.</p>
            <?php endif; ?>
        </div>

        <div class="text-center mt-8">
            <a href="dashboard.php" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
