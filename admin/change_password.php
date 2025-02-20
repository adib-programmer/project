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
$userId = $_SESSION['user']['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    try {
        // Input validation
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All fields are required';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (strlen($new_password) < 8) {
            $error = 'New password must be at least 8 characters long';
        } elseif (!preg_match("/[A-Z]/", $new_password)) {
            $error = 'New password must contain at least one uppercase letter';
        } elseif (!preg_match("/[a-z]/", $new_password)) {
            $error = 'New password must contain at least one lowercase letter';
        } elseif (!preg_match("/[0-9]/", $new_password)) {
            $error = 'New password must contain at least one number';
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'admin'");
                
                if ($stmt->execute([$hashed_password, $userId])) {
                    $success = 'Password updated successfully';
                    // Clear sensitive POST data
                    $_POST = array();
                } else {
                    $error = 'Failed to update password';
                }
            } else {
                $error = 'Current password is incorrect';
            }
        }
    } catch (PDOException $e) {
        $error = 'An error occurred. Please try again later.';
        error_log("Password change error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Change Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'dark-bg': '#121212',
                        'dark-card': '#1E1E1E',
                        'dark-border': '#333333',
                    },
                },
            },
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-dark-bg text-gray-100">
    <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-700 py-12 px-4 sm:px-6 lg:px-8 flex items-center justify-center">
        <div class="max-w-md w-full space-y-8">
            <div class="bg-dark-card rounded-xl shadow-2xl border border-dark-border overflow-hidden">
                <div class="px-6 py-4 border-b border-dark-border bg-gradient-to-r from-blue-600 to-purple-600">
                    <div class="flex items-center justify-between">
                        <h1 class="text-2xl font-bold text-white">Change Admin Password</h1>
                        <a href="dashboard.php" class="text-white hover:text-gray-200 transition duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <?php if ($error): ?>
                        <div class="px-4 py-3 rounded-lg bg-red-900 border-l-4 border-red-500 text-red-100 animate-pulse">
                            <p class="text-sm font-medium"><?= htmlspecialchars($error) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="px-4 py-3 rounded-lg bg-green-900 border-l-4 border-green-500 text-green-100 animate-pulse">
                            <p class="text-sm font-medium"><?= htmlspecialchars($success) ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-300">
                                Current Password
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    class="block w-full rounded-md border border-gray-600 bg-gray-700 px-4 py-2 text-gray-100 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition duration-150"
                                    required
                                >
                                <button 
                                    type="button"
                                    onclick="togglePasswordVisibility('current_password')"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-200 transition duration-150"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-300">
                                New Password
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input
                                    type="password"
                                    id="new_password"
                                    name="new_password"
                                    class="block w-full rounded-md border border-gray-600 bg-gray-700 px-4 py-2 text-gray-100 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition duration-150"
                                    required
                                >
                                <button 
                                    type="button"
                                    onclick="togglePasswordVisibility('new_password')"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-200 transition duration-150"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-2 text-xs text-gray-400">
                                Password must be at least 8 characters and include uppercase, lowercase, and numbers
                            </p>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-300">
                                Confirm Password
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    class="block w-full rounded-md border border-gray-600 bg-gray-700 px-4 py-2 text-gray-100 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition duration-150"
                                    required
                                >
                                <button 
                                    type="button"
                                    onclick="togglePasswordVisibility('confirm_password')"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-200 transition duration-150"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <button
                                type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 transform hover:scale-105"
                            >
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
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

    // Add password strength meter
    const newPasswordInput = document.getElementById('new_password');
    const strengthMeter = document.createElement('div');
    strengthMeter.className = 'mt-2 h-2 w-full bg-gray-600 rounded-full overflow-hidden';
    newPasswordInput.parentNode.insertBefore(strengthMeter, newPasswordInput.nextSibling);

    newPasswordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^A-Za-z0-9]/)) strength++;

        const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500', 'bg-blue-500'];
        const widths = ['20%', '40%', '60%', '80%', '100%'];
        strengthMeter.innerHTML = `<div class="h-full ${colors[strength - 1]} transition-all duration-300" style="width: ${widths[strength - 1]}"></div>`;
    });
    </script>
</body>
</html>

<?php include '../includes/footer.php'; ?>