<?php
require_once '../includes/auth.php';
requireLogin();

if (!isAdmin()) {
    header("Location: ../index.php");
    exit;
}

include '../includes/header.php';
include '../includes/db.php';

$student_id = $_GET['id'] ?? null;

if (!$student_id) {
    header("Location: manage_users.php");
    exit();
}

// Fetch student details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'student'");
$stmt->execute(['id' => $student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student not found!");
}

// Fetch classes the student has joined
$stmt = $pdo->prepare("
    SELECT classes.name AS class_name, classes.class_code 
    FROM class_requests 
    JOIN classes ON class_requests.class_id = classes.id 
    WHERE class_requests.user_id = :user_id AND class_requests.status = 'approved'
");
$stmt->execute(['user_id' => $student_id]);
$joined_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900 py-12 px-6">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-4xl font-bold text-white text-center mb-6">Student Profile</h2>

        <div class="bg-gray-800 p-6 rounded-xl shadow-lg mb-8">
            <div class="flex items-center mb-6">
                <img src="<?= $student['avatar'] ?: 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png' ?>"
                    alt="Profile" class="w-20 h-20 rounded-full mr-4">
                <div>
                    <div class="flex">
                        <h3 class="text-2xl font-bold text-white mr-2"><?= htmlspecialchars($student['first_name']) ?>
                        </h3>
                        <h3 class="text-2xl font-bold text-white"><?= htmlspecialchars($student['last_name'] ?? '') ?>
                        </h3>
                    </div>

                    <p class="text-gray-400">Email: <?= htmlspecialchars($student['username']) ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-lg text-blue-400 font-semibold">Contact Information</h4>
                    <p class="text-gray-300"><strong>Phone:</strong>
                        <?= htmlspecialchars($student['contact_no'] ?: 'Not Provided') ?></p>
                    <p class="text-gray-300"><strong>College ID:</strong>
                        <?= htmlspecialchars($student['collage_id'] ?: 'Not Provided') ?></p>
                </div>
                <div>
                    <h4 class="text-lg text-blue-400 font-semibold">Educational Information</h4>
                    <p class="text-gray-300"><strong>Grade:</strong>
                        <?= htmlspecialchars($student['grade'] ?: 'Not Provided') ?></p>
                    <p class="text-gray-300"><strong>Section:</strong>
                        <?= htmlspecialchars($student['section'] ?: 'Not Provided') ?></p>
                    <p class="text-gray-300"><strong>Shift:</strong>
                        <?= htmlspecialchars($student['shift'] ?: 'Not Provided') ?></p>
                    <p class="text-gray-300"><strong>DRMC Student:</strong> <?= $student['is_drmc'] ? 'Yes' : 'No' ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 p-6 rounded-xl shadow-lg">
            <h3 class="text-2xl font-bold text-white mb-4">Joined Classes</h3>
            <?php if ($joined_classes): ?>
                <ul class="space-y-3">
                    <?php foreach ($joined_classes as $class): ?>
                        <li class="bg-gray-700 p-3 rounded flex justify-between items-center">
                            <p class="text-white"><?= htmlspecialchars($class['class_name']) ?>
                                <span class="text-gray-400 text-sm">(Code: <?= htmlspecialchars($class['class_code']) ?>)</span>
                            </p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-gray-400">This student has not joined any classes yet.</p>
            <?php endif; ?>
        </div>

        <div class="text-center mt-8">
            <a href="manage_users.php"
                class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Manage Users
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>