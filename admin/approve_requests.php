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
    if (isset($_POST['request_id']) && isset($_POST['action'])) {
        try {
            $request_id = filter_var($_POST['request_id'], FILTER_SANITIZE_NUMBER_INT);
            $action = $_POST['action'];

            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE class_requests SET status = 'approved' WHERE id = :id");
                if ($stmt->execute(['id' => $request_id])) {
                    $success = "Request approved successfully.";
                } else {
                    $error = "Failed to approve request.";
                }
            } elseif ($action === 'decline') {
                $stmt = $pdo->prepare("UPDATE class_requests SET status = 'declined' WHERE id = :id");
                if ($stmt->execute(['id' => $request_id])) {
                    $success = "Request declined successfully.";
                } else {
                    $error = "Failed to decline request.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error occurred.";
            error_log("Database Error: " . $e->getMessage());
        }
    } else {
        $error = "Invalid request parameters.";
    }
}

try {
    // Fetch pending class requests with user_id
    $stmt = $pdo->prepare("
        SELECT 
            cr.id AS request_id,
            u.id AS user_id,
            u.first_name,
            u.last_name,
            u.grade,
            u.section,
            u.avatar,
            c.name AS class_name,
            c.class_code,
            cr.requested_at
        FROM class_requests cr
        JOIN users u ON cr.user_id = u.id
        JOIN classes c ON cr.class_id = c.id
        WHERE cr.status = 'pending'
        ORDER BY cr.requested_at DESC
    ");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to fetch requests.";
    error_log("Database Error: " . $e->getMessage());
    $requests = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Class Join Requests - School Management System</title>
</head>
<body>
    <div class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900 py-12 px-6">
        <div class="max-w-7xl mx-auto">
            <h2 class="text-4xl font-bold text-white text-center mb-6">Approve Class Join Requests</h2>

            <?php if ($error): ?>
                <div class="bg-red-500 text-white text-center p-3 rounded mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-500 text-white text-center p-3 rounded mb-4">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <div class="bg-gray-800 p-6 rounded-xl shadow-lg">
                <h3 class="text-2xl font-semibold text-white mb-4">Pending Requests</h3>
                
                <?php if ($requests): ?>
                    <div class="space-y-4">
                        <?php foreach ($requests as $request): ?>
                            <div class="bg-gray-700 p-4 rounded flex justify-between items-center">
                                <div class="flex items-center">
                                    <img src="<?= htmlspecialchars($request['avatar'] ?: '../assets/images/default-avatar.png') ?>" 
                                         alt="Profile" 
                                         class="w-12 h-12 rounded-full mr-4">
                                    <div>
                                        <p class="text-white font-bold">
                                            <?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) ?>
                                        </p>
                                        <p class="text-gray-400">
                                            Grade: <?= htmlspecialchars($request['grade']) ?> | 
                                            Section: <?= htmlspecialchars($request['section']) ?>
                                        </p>
                                        <p class="text-gray-400">
                                            Requesting Class: <?= htmlspecialchars($request['class_name']) ?> 
                                            <span class="text-blue-400">
                                                (Code: <?= htmlspecialchars($request['class_code']) ?>)
                                            </span>
                                        </p>
                                        <p class="text-gray-400 text-sm">
                                            Requested: <?= htmlspecialchars(date('F j, Y g:i A', strtotime($request['requested_at']))) ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="view_student.php?id=<?= htmlspecialchars($request['user_id']) ?>" 
                                       class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition">
                                        Inspect
                                    </a>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="request_id" value="<?= htmlspecialchars($request['request_id']) ?>">
                                        <button type="submit" 
                                                name="action" 
                                                value="approve" 
                                                class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition">
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="request_id" value="<?= htmlspecialchars($request['request_id']) ?>">
                                        <button type="submit" 
                                                name="action" 
                                                value="decline" 
                                                class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition">
                                            Decline
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-400 text-center">No pending requests at the moment.</p>
                <?php endif; ?>
            </div>

            <div class="text-center mt-8">
                <a href="dashboard.php" 
                   class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div> 
    </div> 

<?php include '../includes/footer.php'; ?> 
</body> 
</html> 