<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Dynamically determine the project root URL
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$projectDir = trim(dirname($_SERVER['PHP_SELF'], 1), "/"); // Go one level up from current script
$redirectUrl = $projectDir ? "{$baseUrl}/{$projectDir}/index.php" : "{$baseUrl}/index.php";

// Redirect to the login page at the project root
header("Location: {$redirectUrl}");
exit;
