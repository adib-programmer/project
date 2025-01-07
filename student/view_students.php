<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Get class_id from URL
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    header('Location: dashboard.php');
    exit;
}

// Fetch class details
$stmt = $pdo->prepare("SELECT name FROM classes WHERE id = :class_id");
$stmt->execute(['class_id' => $class_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    header('Location: dashboard.php');
    exit;
}

// Fetch students enrolled in the class
$stmt = $pdo->prepare("
    SELECT 
        u.first_name,
        u.last_name,
        u.collage_id,
        u.section,
        u.shift
    FROM class_requests cr
    JOIN users u ON cr.user_id = u.id
    WHERE cr.class_id = :class_id 
    AND cr.status = 'approved'
    ORDER BY u.first_name, u.last_name
");
$stmt->execute(['class_id' => $class_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($class['name']) ?> - Students</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <div class="container mx-auto p-6">
        <!-- Header Section -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold mb-2">
                    <?= htmlspecialchars($class['name']) ?>
                </h1>
                <p class="text-gray-400">Total Students: <?= count($students) ?></p>
            </div>
            <a href="dashboard.php" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>

        <!-- Students Table -->
        <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Name</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">College ID</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Section</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Shift</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): ?>
                                <tr class="hover:bg-gray-700 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium">
                                            <?= htmlspecialchars($student['first_name']) ?>
                                            <?= $student['last_name'] ? ' ' . htmlspecialchars($student['last_name']) : '' ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm">
                                            <?= htmlspecialchars($student['collage_id'] ?? 'Not Set') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm">
                                            <?= htmlspecialchars($student['section'] ?? 'Not Set') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm">
                                            <?= htmlspecialchars($student['shift'] ?? 'Not Set') ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                    <i class="fas fa-users text-4xl mb-4 block"></i>
                                    <p>No students enrolled in this class yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>