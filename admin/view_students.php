<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Require user login and ensure they are an admin
requireLogin();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit;
}

// Get class_id from URL
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    header('Location: manage_classes.php');
    exit;
}

// Fetch class details
$stmt = $pdo->prepare("SELECT name FROM classes WHERE id = :class_id");
$stmt->execute(['class_id' => $class_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    header('Location: manage_classes.php');
    exit;
}

// Fetch students enrolled in the class
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.grade,
        u.section
    FROM class_requests cr
    JOIN users u ON cr.user_id = u.id
    WHERE cr.class_id = :class_id 
    AND cr.status = 'approved'
    ORDER BY u.last_name, u.first_name
");
$stmt->execute(['class_id' => $class_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle delete student request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student_id'])) {
    $student_id = $_POST['delete_student_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Remove the student from the class_requests table
        $stmt = $pdo->prepare("DELETE FROM class_requests WHERE user_id = :user_id AND class_id = :class_id");
        $stmt->execute(['user_id' => $student_id, 'class_id' => $class_id]);
        
        $pdo->commit();
        header("Location: view_students.php?class_id={$class_id}&success=1");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "An error occurred while removing the student.";
    }
}

$success = isset($_GET['success']) ? "Student removed successfully." : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($class['name']) ?> - Students</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .student-name {
            position: relative;
            display: inline-block;
        }
        .student-name:after {
            content: '';
            position: absolute;
            width: 100%;
            transform: scaleX(0);
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: #3b82f6;
            transform-origin: bottom right;
            transition: transform 0.25s ease-out;
        }
        .student-name:hover:after {
            transform: scaleX(1);
            transform-origin: bottom left;
        }
    </style>
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
            <a href="manage_classes.php" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Manage Classes
            </a>
        </div>

        <?php if (isset($success)): ?>
            <div class="bg-green-600 text-white p-4 rounded-lg mb-6 flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-600 text-white p-4 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Students Table -->
        <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Student Name</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Grade</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Section</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $student): ?>
                            <tr class="hover:bg-gray-750 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="view_student.php?id=<?= htmlspecialchars($student['id']) ?>" 
                                       class="student-name text-blue-400 hover:text-blue-300 transition-colors duration-200">
                                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <?= htmlspecialchars($student['grade'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <?= htmlspecialchars($student['section'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this student from the class?');">
                                        <input type="hidden" name="delete_student_id" value="<?= htmlspecialchars($student['id']) ?>">
                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg transition duration-200 inline-flex items-center">
                                            <i class="fas fa-user-minus mr-2"></i> Remove
                                        </button>
                                    </form>
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
</body>
</html>