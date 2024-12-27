<?php
require_once '../includes/auth.php';
requireLogin();
if (!isAdmin()) {
    header('Location: ../student/dashboard.php');
    exit;
}

include '../includes/header.php';

// Fetch all submissions
$stmt = $pdo->query("SELECT submissions.id, submissions.submission_link, submissions.feedback, users.first_name, users.last_name, classes.name AS class_name
                     FROM submissions
                     JOIN users ON submissions.user_id = users.id
                     JOIN classes ON submissions.class_id = classes.id");
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold">Manage Submissions</h1>
    <div class="mt-4">
        <h2 class="text-lg">All Submissions</h2>
        <ul>
            <?php foreach ($submissions as $submission): ?>
                <li class="bg-gray-700 p-2 rounded mt-2">
                    <p><strong>Student:</strong> <?= htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']) ?></p>
                    <p><strong>Class:</strong> <?= htmlspecialchars($submission['class_name']) ?></p>
                    <p><strong>Link:</strong> <a href="<?= htmlspecialchars($submission['submission_link']) ?>" target="_blank" class="text-blue-400">View Submission</a></p>
                    <form method="POST" action="add_feedback.php" class="mt-2">
                        <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                        <textarea name="feedback" class="w-full p-2 bg-gray-700 rounded" placeholder="Provide feedback"><?= htmlspecialchars($submission['feedback']) ?></textarea>
                        <button type="submit" class="bg-green-500 px-4 py-2 mt-2 rounded">Save Feedback</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
