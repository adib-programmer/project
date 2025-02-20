<?php
require_once '../includes/auth.php';
requireLogin();

include '../includes/header.php';

$error = '';
$success = '';
$userId = $_SESSION['user']['id'] ?? null;

if (!$userId) {
    header('Location: ../index.php');
    exit;
}

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=school_management;charset=utf8mb4",
        "root", // Replace with your database username
        "",     // Replace with your database password
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = "User not found.";
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    // Validate and sanitize input
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $contact_no = filter_input(INPUT_POST, 'contact_no', FILTER_SANITIZE_STRING);
    $avatar_link = filter_input(INPUT_POST, 'avatar_link', FILTER_SANITIZE_URL);
    $grade = filter_input(INPUT_POST, 'grade', FILTER_SANITIZE_STRING);
    $section = filter_input(INPUT_POST, 'section', FILTER_SANITIZE_STRING);
    $shift = filter_input(INPUT_POST, 'shift', FILTER_SANITIZE_STRING);
    $collage_id = filter_input(INPUT_POST, 'collage_id', FILTER_SANITIZE_NUMBER_INT);
    $is_drmc = isset($_POST['is_drmc']) ? 1 : 0;

    $errors = [];

    // Validation
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($username)) $errors[] = "Username is required";
    if (empty($contact_no)) $errors[] = "Contact number is required";
    if (!empty($avatar_link) && !filter_var($avatar_link, FILTER_VALIDATE_URL)) $errors[] = "Invalid avatar URL";

    if (empty($errors)) {
        try {
            // Check if username exists for other users
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $userId]);
            if ($stmt->rowCount() > 0) {
                $error = "Username already exists.";
            } else {
                // Update user profile
                $sql = "UPDATE users SET 
                        first_name = ?,
                        last_name = ?,
                        username = ?,
                        contact_no = ?,
                        avatar = ?,
                        grade = ?,
                        section = ?,
                        shift = ?,
                        collage_id = ?,
                        is_drmc = ?
                        WHERE id = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $first_name,
                    $last_name,
                    $username,
                    $contact_no,
                    $avatar_link,
                    $grade,
                    $section,
                    $shift,
                    $collage_id,
                    $is_drmc,
                    $userId
                ]);

                if ($stmt->rowCount() > 0) {
                    $success = "Profile updated successfully!";
                    
                    // Update session data
                    $_SESSION['user'] = array_merge($_SESSION['user'], [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'username' => $username,
                        'contact_no' => $contact_no,
                        'avatar' => $avatar_link,
                        'grade' => $grade,
                        'section' => $section,
                        'shift' => $shift,
                        'collage_id' => $collage_id,
                        'is_drmc' => $is_drmc
                    ]);
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                }
            }
        } catch (PDOException $e) {
            $error = "Failed to update profile: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<div class="min-h-screen bg-gray-900 py-8">
    <div class="container mx-auto px-4 max-w-2xl">
        <div class="bg-gray-800 rounded-lg shadow-xl p-6">
            <div class="flex items-center mb-6">
                <div class="relative">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= htmlspecialchars($user['avatar']) ?>" 
                             alt="Profile" 
                             class="w-16 h-16 rounded-full object-cover border-2 border-purple-500"
                             onerror="this.src='../assets/default-avatar.png'">
                    <?php else: ?>
                        <div class="w-16 h-16 rounded-full bg-gray-700 flex items-center justify-center text-gray-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                <h1 class="text-2xl font-bold text-white ml-4">Edit Profile</h1>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-red-500 text-white px-4 py-3 rounded mb-4">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="bg-green-500 text-white px-4 py-3 rounded mb-4">
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-300">First Name</label>
                        <input type="text" id="first_name" name="first_name" required
                               value="<?= htmlspecialchars($user['first_name'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500">
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-300">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required
                               value="<?= htmlspecialchars($user['last_name'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500">
                    </div>
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-300">Email</label>
                    <input type="text" id="username" name="username" required
                           value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                           class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500">
                </div>

                <div>
                    <label for="contact_no" class="block text-sm font-medium text-gray-300">Phone Number</label>
                    <input type="tel" id="contact_no" name="contact_no" required
                           value="<?= htmlspecialchars($user['contact_no'] ?? '') ?>"
                           class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500">
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label for="grade" class="block text-sm font-medium text-gray-300">Grade</label>
                        <input type="text" id="grade" name="grade"
                               value="<?= htmlspecialchars($user['grade'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500">
                    </div>
                    <div>
                        <label for="section" class="block text-sm font-medium text-gray-300">Section</label>
                        <input type="text" id="section" name="section"
                               value="<?= htmlspecialchars($user['section'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label for="shift" class="block text-sm font-medium text-gray-300">Shift</label>
                        <select id="shift" name="shift"
                                class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500">
                            <option value="">Select Shift</option>
                            <option value="morning" <?= ($user['shift'] ?? '') === 'morning' ? 'selected' : '' ?>>Morning</option>
                            <option value="day" <?= ($user['shift'] ?? '') === 'day' ? 'selected' : '' ?>>day</option>
                            <option value="evening" <?= ($user['shift'] ?? '') === 'evening' ? 'selected' : '' ?>>Evening</option>
                        </select>
                    </div>
                    <div>
                        <label for="collage_id" class="block text-sm font-medium text-gray-300">College ID</label>
                        <input type="text" id="collage_id" name="collage_id"
                               value="<?= htmlspecialchars($user['collage_id'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500">
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="is_drmc" name="is_drmc" 
                           <?= ($user['is_drmc'] ?? false) ? 'checked' : '' ?>
                           class="h-4 w-4 rounded border-gray-600 text-purple-500 focus:ring-purple-500 bg-gray-700">
                    <label for="is_drmc" class="ml-2 block text-sm text-gray-300">
                        DRMC Student
                    </label>
                </div>

                <div class="border-t border-gray-700 pt-6">
                    <label for="avatar_link" class="block text-sm font-medium text-gray-300">Profile Picture URL</label>
                    <input type="url" id="avatar_link" name="avatar_link"
                           value="<?= htmlspecialchars($user['avatar'] ?? '') ?>"
                           placeholder="https://example.com/avatar.jpg"
                           class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500">
                    <p class="mt-2 text-sm text-gray-400">Enter the URL of your profile picture</p>
                </div>

                <div class="flex justify-end pt-6">
                    <button type="submit" 
                            class="bg-purple-500 hover:bg-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 text-white font-semibold py-2 px-4 rounded-md">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>