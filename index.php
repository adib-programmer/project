<?php
require_once 'includes/auth.php';

$error = '';
$selectedRole = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $selectedRole = $_POST['role'];

    if (authenticateUser($username, $password)) {
        $baseUrl = "http://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['PHP_SELF']);
        
        // Ensure the role matches what's in the database
        $redirectUrl = "{$baseUrl}/{$selectedRole}/dashboard.php";
        header("Location: {$redirectUrl}");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
        body {
            font-family: "Hind Siliguri", sans-serif;
            background-image: url('https://i.ytimg.com/vi/B0_0J9Qfg5k/maxresdefault.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
    </style>
</head>
<body class="text-white min-h-screen flex flex-col">
    <?php include 'includes/navbar.php'; ?>
    <div class="container mx-auto mt-10 flex-grow flex flex-col items-center justify-center px-4">
        <div class="bg-black bg-opacity-70 p-8 rounded-lg shadow-2xl backdrop-filter backdrop-blur-lg">
            <h1 class="text-yellow-400 text-4xl md:text-5xl lg:text-6xl font-bold text-center mb-8 animate-pulse">
                ছাত্র ম্যানেজেন্ট সিস্টেমে স্বাগতম
            </h1>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-500 bg-opacity-50 text-white p-3 rounded mb-4 text-center">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($_GET['role'])): ?>
                <!-- Role Selection Buttons -->
                <div class="flex flex-col sm:flex-row justify-center mt-8 space-y-4 sm:space-y-0 sm:space-x-4">
                    <a href="?role=admin" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-full transition duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg text-center">
                        Admin Login
                    </a>
                    <a href="?role=student" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-full transition duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg text-center">
                        Student Login
                    </a>
                </div>
            <?php else: ?>
                <!-- Login Form -->
                <form method="POST" class="max-w-md mx-auto w-full">
                    <input type="hidden" name="role" value="<?= htmlspecialchars($_GET['role']) ?>">
                    
                    <div class="mb-4">
                        <label for="username" class="block text-sm mb-2">Username</label>
                        <input type="text" id="username" name="username" 
                               class="w-full p-2 bg-gray-700 rounded border border-gray-600 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                               required>
                    </div>
                    
                    <div class="mb-6">
                        <label for="password" class="block text-sm mb-2">Password</label>
                        <input type="password" id="password" name="password" 
                               class="w-full p-2 bg-gray-700 rounded border border-gray-600 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                               required>
                    </div>

                    <div class="flex flex-col space-y-4">
                        <button type="submit" 
                                class="<?= $_GET['role'] === 'admin' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700' ?> 
                                       text-white font-bold py-3 px-6 rounded-full transition duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
                            <?= ucfirst(htmlspecialchars($_GET['role'])) ?> Login
                        </button>
                        <a href="index.php" 
                           class="text-gray-300 text-center hover:text-white transition duration-300">
                            Back to Role Selection
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <footer class="bg-black bg-opacity-70 text-white text-center p-6 mt-10 shadow-inner">
        <p class="text-sm">&copy; 2024 Student Management System</p>
        <p class="text-xl text-yellow-500 mt-2">
            <a href="https://github.com/adib-programmar/" class="hover:text-yellow-400 transition duration-300">
                <i class="fab fa-github mr-1"></i>
            </a>
        </p>
    </footer>
    <script src="https://kit.fontawesome.com/21ad1a0bda.js" crossorigin="anonymous"></script>
</body>
</html>