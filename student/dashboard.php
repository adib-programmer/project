<?php
require_once '../includes/auth.php';
requireLogin();

include '../includes/header.php';

// Fetch classes the student has joined
$stmt = $pdo->prepare("SELECT classes.name, classes.class_code FROM class_requests 
                       JOIN classes ON class_requests.class_id = classes.id 
                       WHERE class_requests.user_id = :user_id AND class_requests.status = 'approved'");
$stmt->execute(['user_id' => $_SESSION['user']['id']]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pending requests
$stmt = $pdo->prepare("SELECT classes.name, classes.class_code FROM class_requests 
                       JOIN classes ON class_requests.class_id = classes.id 
                       WHERE class_requests.user_id = :user_id AND class_requests.status = 'pending'");
$stmt->execute(['user_id' => $_SESSION['user']['id']]);
$pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold">Student Dashboard</h1>
    <div class="mt-4">
        <h2 class="text-lg">Joined Classes</h2>
        <ul>
            <?php foreach ($classes as $class): ?>
                <li class="bg-gray-700 p-2 rounded mt-2"><?= htmlspecialchars($class['name']) ?> (Code: <?= htmlspecialchars($class['class_code']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="mt-4">
        <h2 class="text-lg">Pending Requests</h2>
        <ul>
            <?php foreach ($pendingRequests as $request): ?>
                <li class="bg-gray-700 p-2 rounded mt-2"><?= htmlspecialchars($request['name']) ?> (Code: <?= htmlspecialchars($request['class_code']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
