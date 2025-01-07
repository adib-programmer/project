<?php
session_start();
require_once 'db.php';

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Dynamically determine the base project URL
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        $projectDir = trim(dirname($_SERVER['PHP_SELF'], 2), "/"); // Go two levels up to the project root
        $loginUrl = $projectDir ? "{$baseUrl}/{$projectDir}/index.php" : "{$baseUrl}/index.php";

        // Redirect to login page if not logged in
        header("Location: $loginUrl");
        exit;
    }
}




function isAdmin() {
    return isLoggedIn() && $_SESSION['user']['role'] === 'admin';
}

// Authenticate user
function authenticateUser($username, $password) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables for authenticated user
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'avatar' => $user['avatar']
        ];
        return true;
    }
    return false;
}

// Logout user
function logout() {
    session_destroy();
    header('Location: /index.php');
    exit;
}
?>
