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

// Handle creating a new student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_student'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $grade = $_POST['grade'];
    $is_drmc = isset($_POST['is_drmc']) ? 1 : 0;
    $section = $_POST['section'] ?? null;
    $shift = $_POST['shift'] ?? null;
    $collage_id = $_POST['collage_id'] ?? null;

    if (empty($full_name) || empty($email) || empty($phone) || empty($grade)) {
        $error = "Full name, email, phone number, and grade are required.";
    } else {
        // Check for duplicate username
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->execute(['username' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "A user with this email already exists.";
        } else {
            // Default password
            $password = password_hash('user123', PASSWORD_BCRYPT);

            // Insert student into the database
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, role, first_name, contact_no, grade, section, shift, is_drmc, collage_id, avatar) 
                VALUES (:username, :password, 'student', :full_name, :phone, :grade, :section, :shift, :is_drmc, :collage_id, NULL)
            ");
            $stmt->execute([
                'username' => $email,
                'password' => $password,
                'full_name' => $full_name,
                'phone' => $phone,
                'grade' => $grade,
                'section' => $section,
                'shift' => $shift,
                'is_drmc' => $is_drmc,
                'collage_id' => $collage_id
            ]);

            $success = "Student '$full_name' created successfully!";
        }
    }
}

// Handle deleting a student
if (isset($_GET['delete_student_id'])) {
    $student_id = $_GET['delete_student_id'];
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Only remove the student from classes by deleting class_requests
        $stmt = $pdo->prepare("DELETE FROM class_requests WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $student_id]);
        
        // Delete the student from users table
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'student'");
        $stmt->execute(['id' => $student_id]);
        
        // Commit transaction
        $pdo->commit();
        
        $success = "Student deleted successfully! Their messages and submissions have been preserved.";
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error = "Error deleting student: " . $e->getMessage();
    }
}

// Fetch students grouped by their class status
$stmt = $pdo->query("
    SELECT users.*, classes.name AS class_name, classes.id AS class_id
    FROM users 
    LEFT JOIN class_requests ON users.id = class_requests.user_id AND class_requests.status = 'approved'
    LEFT JOIN classes ON class_requests.class_id = classes.id
    WHERE users.role = 'student'
    ORDER BY class_name ASC, users.first_name ASC
");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group students by class
$studentsByClass = [];
foreach ($students as $student) {
    $className = $student['class_name'] ?: 'Not Joined Any Class';
    if (!isset($studentsByClass[$className])) {
        $studentsByClass[$className] = [];
    }
    $studentsByClass[$className][] = $student;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900 min-h-screen py-12 px-4 sm:px-6">
    <div class="max-w-7xl mx-auto">
        <h2 class="text-4xl font-bold text-white text-center mb-8">Manage Users</h2>

        <?php if ($error): ?>
            <div class="bg-red-500 text-white text-center p-4 rounded-lg mb-6 shadow-lg animate-pulse">
                <p class="flex items-center justify-center">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?= $error ?>
                </p>
            </div>
        <?php elseif ($success): ?>
            <div class="bg-green-500 text-white text-center p-4 rounded-lg mb-6 shadow-lg animate-pulse">
                <p class="flex items-center justify-center">
                    <i class="fas fa-check-circle mr-2"></i> <?= $success ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Create Student Form -->
        <div class="bg-gray-800 p-6 rounded-xl shadow-lg mb-8 transform hover:scale-[1.01] transition-transform duration-300">
            <h3 class="text-2xl font-semibold text-white mb-6 flex items-center">
                <i class="fas fa-user-plus text-blue-400 mr-3"></i>Create New Student
            </h3>
            <form method="POST" class="space-y-4">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="full_name" class="block text-gray-400 mb-2 font-medium">Full Name<span class="text-red-500">*</span></label>
                        <input type="text" id="full_name" name="full_name" 
                               class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all" 
                               required>
                    </div>
                    <div>
                        <label for="email" class="block text-gray-400 mb-2 font-medium">Email<span class="text-red-500">*</span></label>
                        <input type="email" id="email" name="email" 
                               class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all" 
                               required>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="phone" class="block text-gray-400 mb-2 font-medium">Phone Number<span class="text-red-500">*</span></label>
                        <input type="text" id="phone" name="phone" 
                               class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all" 
                               required>
                    </div>
                    <div>
                        <label for="grade" class="block text-gray-400 mb-2 font-medium">Grade<span class="text-red-500">*</span></label>
                        <input type="text" id="grade" name="grade" 
                               class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all" 
                               required>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-3 gap-6">
                    <div>
                        <label for="collage_id" class="block text-gray-400 mb-2 font-medium">College ID</label>
                        <input type="text" id="collage_id" name="collage_id" 
                               class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all">
                    </div>
                    <div>
                        <label for="section" class="block text-gray-400 mb-2 font-medium">Section</label>
                        <input type="text" id="section" name="section" 
                               class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all">
                    </div>
                    <div>
                        <label for="shift" class="block text-gray-400 mb-2 font-medium">Shift</label>
                        <input type="text" id="shift" name="shift" 
                               class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label for="is_drmc" class="inline-flex items-center">
                        <input type="checkbox" id="is_drmc" name="is_drmc" 
                               class="rounded bg-gray-700 border-gray-600 text-blue-500 focus:ring-blue-500 focus:ring-2">
                        <span class="ml-2 text-gray-400">DRMC Student</span>
                    </label>
                </div>
                
                <div class="flex flex-wrap justify-between mt-6 gap-4">
                    <a href="dashboard.php" 
                       class="bg-gray-700 hover:bg-gray-600 text-white font-medium py-3 px-6 rounded-lg transition flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                    <button type="submit" name="create_student" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition transform hover:-translate-y-1 flex items-center">
                        <i class="fas fa-user-plus mr-2"></i>Create Student
                    </button>
                </div>
            </form>
        </div>

        <!-- Students List -->
        <div class="bg-gray-800 p-6 rounded-xl shadow-lg">
            <h3 class="text-2xl font-semibold text-white mb-6 flex items-center">
                <i class="fas fa-users text-blue-400 mr-3"></i>Students List
            </h3>
            
            <?php if (empty($students)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-400 text-lg">No students found. Create your first student above!</p>
                </div>
            <?php else: ?>
                <div class="space-y-8">
                    <?php foreach ($studentsByClass as $className => $classStudents): ?>
                        <div class="mb-6">
                            <h4 class="text-xl font-bold text-blue-400 mb-4 flex items-center">
                                <?php if ($className === 'Not Joined Any Class'): ?>
                                    <i class="fas fa-user-slash text-gray-500 mr-2"></i>
                                <?php else: ?>
                                    <i class="fas fa-users-class text-green-500 mr-2"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($className) ?>
                                <span class="text-gray-500 text-sm font-normal ml-2">(<?= count($classStudents) ?> students)</span>
                            </h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($classStudents as $student): ?>
                                    <div class="bg-gray-700 rounded-lg overflow-hidden shadow-md transform hover:scale-[1.02] transition-transform duration-300">
                                        <div class="p-4 flex items-start space-x-4">
                                            <img src="<?= $student['avatar'] ?: '/images/default-avatar.png' ?>" 
                                                 alt="Profile" 
                                                 class="w-16 h-16 rounded-full object-cover border-2 border-gray-600"
                                                 onerror="this.src='https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png'">
                                            
                                            <div class="flex-1 min-w-0">
                                                <h5 class="text-white font-bold truncate"><?= htmlspecialchars($student['first_name']) ?></h5>
                                                <p class="text-gray-400 text-sm flex items-center">
                                                    <i class="fas fa-graduation-cap mr-1"></i> Grade: <?= htmlspecialchars($student['grade']) ?>
                                                </p>
                                                <?php if ($student['collage_id']): ?>
                                                <p class="text-gray-400 text-sm flex items-center">
                                                    <i class="fas fa-id-card mr-1"></i> ID: <?= htmlspecialchars($student['collage_id']) ?>
                                                </p>
                                                <?php endif; ?>
                                                <?php if ($student['is_drmc']): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 mt-2 rounded text-xs font-medium bg-blue-900 text-blue-300">
                                                    DRMC Student
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gray-800 p-3 flex justify-between items-center">
                                            <a href="view_student.php?id=<?= $student['id'] ?>" 
                                               class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-2 px-4 rounded-lg transition flex items-center">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </a>
                                            <a href="?delete_student_id=<?= $student['id'] ?>" 
                                               onclick="return confirm('Are you sure you want to delete this student? This will remove them from all classes but preserve their messages and submissions.');" 
                                               class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-2 px-4 rounded-lg transition flex items-center">
                                                <i class="fas fa-trash-alt mr-1"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Hide success messages after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const successMsg = document.querySelector('.bg-green-500');
            if (successMsg) {
                successMsg.classList.add('opacity-0', 'transition-opacity', 'duration-1000');
                setTimeout(() => successMsg.style.display = 'none', 1000);
            }
        }, 5000);
    });
    </script>

<?php include '../includes/footer.php'; ?>
</body>
</html>