
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $user_id = $_POST['user_id'];
    $new_password = password_hash('user123', PASSWORD_BCRYPT); // Default reset password

    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :user_id");
    $stmt->execute([
        'password' => $new_password,
        'user_id' => $user_id
    ]);

    $success = "Password reset successfully! New password is: user123";
}