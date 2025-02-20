<?php
require_once '../includes/auth.php';
requireLogin();

include '../includes/header.php';
include '../includes/db.php';

$error = '';
$success = '';
$userId = $_SESSION['user']['id'] ?? null;

if (!$userId) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else {
        try {
            // Verify current password
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');

                if ($stmt->execute([$hashed_password, $userId])) {
                    $success = 'Password updated successfully';
                } else {
                    $error = 'Failed to update password';
                }
            } else {
                $error = 'Current password is incorrect';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            100: '#1E293B',
                            200: '#334155',
                            300: '#475569',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-dark-100 text-gray-300 p-6">
    <div class="max-w-3xl mx-auto">
        <h2 class="text-3xl font-bold text-white mb-6 text-center">Change Password</h2>

        <?php if ($error): ?>
            <div class="bg-red-900 text-red-200 p-4 mb-6 rounded-lg">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-900 text-green-200 p-4 mb-6 rounded-lg">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-dark-200 p-6 rounded-lg shadow-lg">
            <div class="space-y-6">
                <div>
                    <label for="current_password" class="block text-gray-300 mb-2">
                        Current Password
                    </label>
                    <div class="relative">
                        <input type="password" id="current_password" name="current_password"
                            class="w-full p-3 rounded bg-dark-300 text-white pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                        <button type="button" onclick="togglePasswordVisibility('current_password')"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="new_password" class="block text-gray-300 mb-2">
                        New Password
                    </label>
                    <div class="relative">
                        <input type="password" id="new_password" name="new_password"
                            class="w-full p-3 rounded bg-dark-300 text-white pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                        <button type="button" onclick="togglePasswordVisibility('new_password')"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                        <p class="dark-30">Password must be at least 8 characters long</p>
                    </div>
                </div>

                <div>
                    <label for="confirm_password" class="block text-gray-300 mb-2">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password"
                            class="w-full p-3 rounded bg-dark-300 text-white pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                        <button type="button" onclick="togglePasswordVisibility('confirm_password')"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                    Update Password
                </button>
            </div>
        </form>

        <div class="text-center mt-8">
            <a href="dashboard.php"
                class="bg-dark-300 hover:bg-dark-200 text-white font-bold py-2 px-4 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                button.querySelector('svg').style.opacity = '0.5';
            } else {
                input.type = 'password';
                button.querySelector('svg').style.opacity = '1';
            }
        }
    </script>
</body>

</html>

<?php include '../includes/footer.php'; ?>