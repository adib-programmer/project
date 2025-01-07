<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100">
    <nav class="p-4 bg-gray-800">
        <div class="container mx-auto flex justify-between">
            <a href="dashboard.php" class="text-lg font-bold">School Management</a>
            <ul class="flex space-x-4">
                <?php
                // Determine the base project URL dynamically
                $baseURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
                $projectDir = trim(dirname($_SERVER['PHP_SELF'], 2), "/"); // Go two levels up to the project root
                $baseURL .= $projectDir ? "/$projectDir" : '';

                if (isLoggedIn()): ?>
                    <li><a href="<?= $baseURL ?>/logout.php" class="hover:text-red-400">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?= $baseURL ?>/index.php" class="hover:text-blue-400">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</body>
</html>
