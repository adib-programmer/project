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

// Fetch students grouped by their class status
$stmt = $pdo->query("
    SELECT users.*, classes.name AS class_name 
    FROM users 
    LEFT JOIN class_requests ON users.id = class_requests.user_id AND class_requests.status = 'approved'
    LEFT JOIN classes ON class_requests.class_id = classes.id
    WHERE users.role = 'student'
    ORDER BY class_name ASC, users.first_name ASC
");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900 py-12 px-6">
    <div class="max-w-7xl mx-auto">
        <h2 class="text-4xl font-bold text-white text-center mb-6">Manage Users</h2>

        <?php if ($error): ?>
            <p class="bg-red-500 text-white text-center p-3 rounded mb-4"><?= $error ?></p>
        <?php elseif ($success): ?>
            <p class="bg-green-500 text-white text-center p-3 rounded mb-4"><?= $success ?></p>
        <?php endif; ?>

        <!-- Create Student Form -->
        <div class="bg-gray-800 p-6 rounded-xl shadow-lg mb-8">
            <h3 class="text-2xl font-semibold text-white mb-4">Create New Student</h3>
            <form method="POST">
                <div class="mb-4">
                    <label for="full_name" class="block text-gray-400">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="w-full p-3 bg-gray-700 text-white rounded" required>
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-400">Email</label>
                    <input type="email" id="email" name="email" class="w-full p-3 bg-gray-700 text-white rounded" required>
                </div>
                <div class="mb-4">
                    <label for="phone" class="block text-gray-400">Phone Number</label>
                    <input type="text" id="phone" name="phone" class="w-full p-3 bg-gray-700 text-white rounded" required>
                </div>
                <div class="mb-4">
                    <label for="grade" class="block text-gray-400">Grade</label>
                    <input type="text" id="grade" name="grade" class="w-full p-3 bg-gray-700 text-white rounded" required>
                </div>
                <div class="mb-4">
                    <label for="collage_id" class="block text-gray-400">College ID</label>
                    <input type="text" id="collage_id" name="collage_id" class="w-full p-3 bg-gray-700 text-white rounded">
                </div>
                <div class="mb-4">
                    <label for="is_drmc" class="inline-flex items-center">
                        <input type="checkbox" id="is_drmc" name="is_drmc" class="form-checkbox bg-gray-700 text-blue-500">
                        <span class="ml-2 text-gray-400">DRMC Student</span>
                    </label>
                </div>
                <div class="mb-4">
                    <label for="shift" class="block text-gray-400">Shift (Optional)</label>
                    <input type="text" id="shift" name="shift" class="w-full p-3 bg-gray-700 text-white rounded">
                </div>
                <div class="mb-4">
                    <label for="section" class="block text-gray-400">Section (Optional)</label>
                    <input type="text" id="section" name="section" class="w-full p-3 bg-gray-700 text-white rounded">
                </div>
                <button type="submit" name="create_student" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition">Create Student</button>
            </form>
        </div>

        <!-- Students List -->
        <div class="bg-gray-800 p-6 rounded-xl shadow-lg">
            <h3 class="text-2xl font-semibold text-white mb-4">Students List</h3>
            <?php 
            $currentClass = null;
            foreach ($students as $student): 
                if ($student['class_name'] !== $currentClass): 
                    if ($currentClass !== null): ?>
                        </ul>
                    <?php endif; ?>
                    <h4 class="text-xl font-bold text-blue-400 mb-2"><?= htmlspecialchars($student['class_name'] ?: 'Not Joined Any Class') ?></h4>
                    <ul class="mb-6">
                <?php 
                    $currentClass = $student['class_name']; 
                endif; 
                ?>
                <li class="bg-gray-700 p-3 rounded flex justify-between items-center mb-2">
                    <div class="flex items-center">
                        <img src="<?= $student['avatar'] ?: 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png' ?>" alt="Profile" class="w-12 h-12 rounded-full mr-4">
                        <div>
                            <p class="text-white font-bold"><?= htmlspecialchars($student['first_name']) ?></p>
                            <p class="text-gray-400 text-sm">Grade: <?= htmlspecialchars($student['grade']) ?></p>
                            <p class="text-gray-400 text-sm">College ID: <?= htmlspecialchars($student['collage_id'] ?: 'Not Provided') ?></p>
                        </div>
                    </div>
                    <div>
                        <a href="view_student.php?id=<?= $student['id'] ?>" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition">View</a>
                        <a href="?delete_student_id=<?= $student['id'] ?>" onclick="return confirm('Are you sure you want to delete this student?');" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition">Delete</a>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
