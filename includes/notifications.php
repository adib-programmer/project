<?php
function sendNotification($userId, $message) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)");
    $stmt->execute(['user_id' => $userId, 'message' => $message]);
}
?>
