<?php
require_once '../includes/auth.php';
requireLogin();
if (!isAdmin()) {
    header('Location: ../student/dashboard.php');
    exit;
}

include '../includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_name = $_POST['class_name'];

    if (empty($class_name)) {
        $error = "Class name cannot be empty.";
    } else {
        // Generate a unique class code
        $class_code = strtoupper(bin2hex(random_bytes(4))); // Generates an 8-character unique code

        // Check if the class code already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE class_code = :class_code");
        $stmt->execute(['class_code' => $class_code]);

        if ($stmt->fetchColumn() > 0) {
            $error = "Failed to generate a unique class code. Please try again.";
        } else {
            // Insert the class into the database
            $stmt = $pdo->prepare("INSERT INTO classes (name, class_code) VALUES (:name, :class_code)");
            $stmt->execute([
                'name' => $class_name,
                'class_code' => $class_code
            ]);
            $success = "Class successfully created with code: $class_code";
        }
    }
}
?>