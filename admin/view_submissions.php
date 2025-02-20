<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit;
}

$errorMessage = '';
$successMessage = '';
$due_id = $_GET['due_id'] ?? null;

if (!$due_id) {
    header("Location: ../dashboard.php");
    exit;
}

// CSV Export Handler
if (isset($_GET['export'])) {
    // Fetch all necessary data
    $stmtSubmissions = $pdo->prepare("
        SELECT 
            u.first_name,
            u.last_name,
            u.collage_id,
            u.grade,
            u.contact_no,
            s.submission_link,
            s.submitted_at,
            s.feedback
        FROM users u
        LEFT JOIN submissions s ON u.id = s.user_id AND s.due_id = :due_id
        JOIN class_requests cr ON u.id = cr.user_id
        WHERE cr.class_id = (SELECT class_id FROM dues WHERE id = :due_id2)
        AND cr.status = 'approved'
        AND u.role = 'student'
        ORDER BY u.first_name ASC
    ");
    $stmtSubmissions->execute([
        'due_id' => $due_id,
        'due_id2' => $due_id
    ]);
    $allData = $stmtSubmissions->fetchAll(PDO::FETCH_ASSOC);

    // Get due info
    $stmtDue = $pdo->prepare("SELECT d.*, c.name as class_name FROM dues d 
                             JOIN classes c ON d.class_id = c.id 
                             WHERE d.id = :due_id");
    $stmtDue->execute(['due_id' => $due_id]);
    $dueInfo = $stmtDue->fetch(PDO::FETCH_ASSOC);

    // Create CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="submissions_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // Write due information
    fputcsv($output, ['Due Information']);
    fputcsv($output, ['Title', $dueInfo['title']]);
    fputcsv($output, ['Class', $dueInfo['class_name']]);
    fputcsv($output, ['Due Date', $dueInfo['due_date']]);
    fputcsv($output, []); // Empty line

    // Write headers
    fputcsv($output, [
        'Name',
        'College ID',
        'Grade',
        'Contact',
        'Status',
        'Submission Link',
        'Submission Time',
        'Feedback'
    ]);

    // Write data
    $domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];

    foreach ($allData as $row) {
        $submissionLink = $row['submission_link']
            ? $domain . $row['submission_link']
            : 'Not Submitted';

        fputcsv($output, [
            $row['first_name'] . ' ' . ($row['last_name'] ?? ''),
            $row['collage_id'] ?? 'N/A',
            $row['grade'] ?? 'N/A',
            $row['contact_no'] ?? 'N/A',
            $row['submission_link'] ? 'Submitted' : 'Not Submitted',
            $submissionLink,
            $row['submitted_at'] ?? 'N/A',
            $row['feedback'] ?? ''
        ]);
    }

    fclose($output);
    exit;
}

// Fetch due details with class info
$stmtDue = $pdo->prepare("
    SELECT d.*, c.name as class_name, c.id as class_id, c.class_code 
    FROM dues d 
    JOIN classes c ON d.class_id = c.id 
    WHERE d.id = :due_id
");
$stmtDue->execute(['due_id' => $due_id]);
$due = $stmtDue->fetch(PDO::FETCH_ASSOC);

if (!$due) {
    header("Location: ../dashboard.php");
    exit;
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['feedback']) && isset($_POST['submission_id'])) {
        try {
            $stmt = $pdo->prepare("UPDATE submissions SET feedback = :feedback WHERE id = :submission_id");
            $stmt->execute([
                'feedback' => $_POST['feedback'],
                'submission_id' => $_POST['submission_id']
            ]);
            $successMessage = 'Feedback updated successfully!';
        } catch (PDOException $e) {
            $errorMessage = 'Error updating feedback.';
        }
    }
}

// Handle submission deletion
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM submissions WHERE id = :id AND due_id = :due_id");
        $stmt->execute([
            'id' => $_GET['delete_id'],
            'due_id' => $due_id
        ]);
        header("Location: view_submissions.php?due_id=" . $due_id . "&success=1");
        exit;
    } catch (PDOException $e) {
        $errorMessage = 'Error deleting submission.';
    }
}

// Fetch all enrolled students
$stmtStudents = $pdo->prepare("
    SELECT DISTINCT u.* 
    FROM users u 
    JOIN class_requests cr ON u.id = cr.user_id 
    WHERE cr.class_id = :class_id 
    AND cr.status = 'approved' 
    AND u.role = 'student'
    ORDER BY u.first_name ASC
");
$stmtStudents->execute(['class_id' => $due['class_id']]);
$allStudents = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

// Fetch submitted students with submission details
$stmtSubmissions = $pdo->prepare("
    SELECT 
        s.*,
        u.first_name,
        u.last_name,
        u.avatar,
        u.grade,
        u.collage_id,
        u.contact_no,
        u.shift,
        u.is_drmc
    FROM submissions s
    JOIN users u ON s.user_id = u.id
    WHERE s.due_id = :due_id
    ORDER BY s.submitted_at DESC
");
$stmtSubmissions->execute(['due_id' => $due_id]);
$submissions = $stmtSubmissions->fetchAll(PDO::FETCH_ASSOC);

$submittedStudentIds = array_column($submissions, 'user_id');
$notSubmittedStudents = array_filter($allStudents, function ($student) use ($submittedStudentIds) {
    return !in_array($student['id'], $submittedStudentIds);
});

// Check if due is expired
$currentTime = new DateTime();
$dueTime = new DateTime($due['due_date']);
$isExpired = $currentTime > $dueTime;

// Calculate statistics
$totalStudents = count($allStudents);
$submittedCount = count($submissions);
$notSubmittedCount = $totalStudents - $submittedCount;
$submissionRate = $totalStudents > 0 ? round(($submittedCount / $totalStudents) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions - <?= htmlspecialchars($due['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.34/moment-timezone-with-data.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #e2e8f0;
        }

        .modal {
            transition: opacity 0.3s ease-in-out;
        }

        .modal-content {
            transform: scale(0.95);
            transition: transform 0.3s ease-in-out;
        }

        .modal.active .modal-content {
            transform: scale(1);
        }

        .student-card {
            background-color: #1e293b;
            transition: all 0.3s ease;
            border: 1px solid #334155;
        }

        .student-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-ring circle {
            transition: stroke-dashoffset 0.5s ease-in-out;
        }
    </style>
</head>

<body>
    <div class="container mx-auto p-4 md:p-6">
        <!-- Header Section -->
        <div class="bg-slate-800 rounded-lg p-6 mb-8">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-indigo-400 mb-2">
                        <?= htmlspecialchars($due['title']) ?>
                    </h1>
                    <div class="space-y-1">
                        <p class="text-gray-400">
                            <i class="fas fa-school mr-2"></i>Class: <?= htmlspecialchars($due['class_name']) ?>
                            (<?= htmlspecialchars($due['class_code']) ?>)
                        </p>
                        <p class="text-gray-400">
                            <i class="fas fa-calendar mr-2"></i>Due: <span id="due-date"
                                data-date="<?= $due['due_date'] ?>"></span>
                        </p>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <a href="?export=true&due_id=<?= $due_id ?>"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-download mr-2"></i>Export CSV
                    </a>
                    <a href="view_dues.php?class_id=<?= $due['class_id'] ?>"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dues
                    </a>
                </div>
            </div>

            <!-- Progress Ring -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6">
                    <div class="relative w-24 h-24">
                        <svg class="progress-ring" width="96" height="96">
                            <circle cx="48" cy="48" r="44" stroke="#1e293b" stroke-width="8" fill="transparent" />
                            <circle id="progress-circle" cx="48" cy="48" r="44" stroke="#6366f1" stroke-width="8"
                                fill="transparent" stroke-dasharray="276.46"
                                stroke-dashoffset="<?= 276.46 * (1 - ($submissionRate / 100)) ?>" />
                        </svg>
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center">
                            <span class="text-2xl font-bold"><?= $submissionRate ?>%</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-400">Submitted</p>
                            <p class="text-2xl font-bold text-green-400"><?= $submittedCount ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-400">Pending</p>
                            <p class="text-2xl font-bold text-red-400"><?= $notSubmittedCount ?></p>
                        </div>
                    </div>
                </div>
                <div>
                    <span class="<?= $isExpired ? 'bg-red-600' : 'bg-green-600' ?> text-white px-4 py-2 rounded-full">
                        <i class="<?= $isExpired ? 'fas fa-times-circle' : 'fas fa-clock' ?> mr-2"></i>
                        <?= $isExpired ? 'Ended' : 'Running' ?>
                    </span>
                    <?php if (!$isExpired): ?>
                        <p class="mt-2 text-center" id="time-left"></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($errorMessage): ?>
            <div class="bg-red-900 text-red-100 p-3 rounded mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($successMessage || isset($_GET['success'])): ?>
            <div class="bg-green-900 text-green-100 p-3 rounded mb-4">
                <i class="fas fa-check-circle mr-2"></i>
                <?= htmlspecialchars($successMessage ?? 'Operation completed successfully!') ?>
            </div>
        <?php endif; ?>

        <!-- Resource Link -->
        <?php if ($due['link']): ?>
            <div class="bg-slate-800 p-6 rounded-lg mb-8">
                <h3 class="text-lg font-semibold text-indigo-400 mb-4">
                    <i class="fas fa-link mr-2"></i>Assignment Resource
                </h3>
                <div class="flex items-center justify-between">
                    <a href="<?= htmlspecialchars($due['link']) ?>" target="_blank"
                        class="text-blue-400 hover:text-blue-300 flex items-center justify-between bg-slate-700 p-4 rounded-lg w-full">
                        <div class="flex items-center">
                            <i class="fas fa-link mr-3 text-xl"></i>
                            <div>
                                <h4 class="font-medium">Assignment Resource</h4>
                                <p class="text-sm text-gray-400">Click to open assignment details</p>
                            </div>
                        </div>
                        <i class="fas fa-external-link-alt text-lg"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?> </a>

        <!-- Submitted Students Section -->
        <?php if (!empty($submissions)): ?>
            <h2 class="text-2xl font-bold mb-6 text-green-400">
                <i class="fas fa-check-circle mr-2"></i>Submitted Students (<?= count($submissions) ?>)
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <?php foreach ($submissions as $submission): ?>
                    <div class="student-card rounded-lg p-4">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="w-12 h-12 rounded-full bg-indigo-600 flex items-center justify-center">
                                <?php if ($submission['avatar']): ?>
                                    <img src="<?= htmlspecialchars($submission['avatar']) ?>" alt="Avatar"
                                        class="w-12 h-12 rounded-full object-cover">
                                <?php else: ?>
                                    <span class="text-xl font-bold">
                                        <?= strtoupper(substr($submission['first_name'], 0, 1)) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg">
                                    <?= htmlspecialchars($submission['first_name'] . ' ' . ($submission['last_name'] ?? '')) ?>
                                </h3>
                                <p class="text-gray-400 text-sm">ID: <?= htmlspecialchars($submission['collage_id'] ?? 'N/A') ?>
                                </p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <p><i class="fas fa-graduation-cap mr-2"></i>Grade:
                                <?= htmlspecialchars($submission['grade'] ?? 'N/A') ?></p>
                            <p><i class="fas fa-phone mr-2"></i>Contact:
                                <?= htmlspecialchars($submission['contact_no'] ?? 'N/A') ?></p>
                            <p><i class="fas fa-clock mr-2"></i>Submitted:
                                <span class="submission-time" data-time="<?= $submission['submitted_at'] ?>"></span>
                            </p>
                        </div>
                        <div class="mt-4 space-y-3">
                            <a href="<?= htmlspecialchars($submission['submission_link']) ?>" target="_blank"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg block text-center">
                                <i class="fas fa-external-link-alt mr-2"></i>View Submission
                            </a>
                            <button
                                onclick="toggleFeedbackModal(<?= $submission['id'] ?>, <?= htmlspecialchars(json_encode($submission['feedback'])) ?>)"
                                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg w-full">
                                <i class="fas fa-comment-alt mr-2"></i>Add Feedback
                            </button>
                            <a href="?delete_id=<?= $submission['id'] ?>&due_id=<?= $due_id ?>"
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg block text-center"
                                onclick="return confirm('Are you sure you want to delete this submission?')">
                                <i class="fas fa-trash-alt mr-2"></i>Delete Submission
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Non-Submitted Students Section -->
        <?php if (!empty($notSubmittedStudents)): ?>
            <h2 class="text-2xl font-bold mb-6 text-red-400">
                <i class="fas fa-times-circle mr-2"></i>Not Submitted (<?= count($notSubmittedStudents) ?>)
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($notSubmittedStudents as $student): ?>
                    <div class="student-card rounded-lg p-4 opacity-75">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="w-12 h-12 rounded-full bg-gray-600 flex items-center justify-center">
                                <?php if ($student['avatar']): ?>
                                    <img src="<?= htmlspecialchars($student['avatar']) ?>" alt="Avatar"
                                        class="w-12 h-12 rounded-full object-cover">
                                <?php else: ?>
                                    <span class="text-xl font-bold">
                                        <?= strtoupper(substr($student['first_name'], 0, 1)) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg">
                                    <?= htmlspecialchars($student['first_name'] . ' ' . ($student['last_name'] ?? '')) ?>
                                </h3>
                                <p class="text-gray-400 text-sm">ID: <?= htmlspecialchars($student['collage_id'] ?? 'N/A') ?>
                                </p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <p><i class="fas fa-graduation-cap mr-2"></i>Grade:
                                <?= htmlspecialchars($student['grade'] ?? 'N/A') ?></p>
                            <p><i class="fas fa-phone mr-2"></i>Contact:
                                <?= htmlspecialchars($student['contact_no'] ?? 'N/A') ?></p>
                            <?php if ($student['shift']): ?>
                                <p><i class="fas fa-clock mr-2"></i>Shift: <?= htmlspecialchars($student['shift']) ?></p>
                            <?php endif; ?>
                            <?php if ($student['is_drmc']): ?>
                                <p><i class="fas fa-building mr-2"></i>DRMC Student</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    </div>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
        <div class="modal-content bg-slate-800 p-6 rounded-lg w-full max-w-lg mx-4">
            <h3 class="text-xl font-bold mb-4">Add Feedback</h3>
            <form method="POST" id="feedbackForm">
                <input type="hidden" name="submission_id" id="submission_id">
                <textarea name="feedback" id="feedback" rows="4"
                    class="w-full bg-slate-700 border border-slate-600 rounded-lg p-3 text-white mb-4"
                    placeholder="Enter your feedback here..."></textarea>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="toggleFeedbackModal()"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg">
                        Save Feedback
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        moment.tz.setDefault("Asia/Dhaka");

        // Update submission times
        document.querySelectorAll('.submission-time').forEach(element => {
            const time = moment(element.dataset.time);
            element.textContent = time.fromNow();
        });

        // Update due date
        const dueElement = document.getElementById('due-date');
        if (dueElement) {
            const dueDate = moment(dueElement.dataset.date);
            dueElement.textContent = dueDate.format('MMMM D, YYYY [at] HH:mm:ss');
        }

        // Time left counter for active dues
        const timeLeftElement = document.getElementById('time-left');
        if (timeLeftElement) {
            function updateTimeLeft() {
                const now = moment();
                const due = moment(dueElement.dataset.date);
                const duration = moment.duration(due.diff(now));

                if (duration.asSeconds() > 0) {
                    let timeLeft = '';
                    const days = Math.floor(duration.asDays());
                    const hours = duration.hours();
                    const minutes = duration.minutes();
                    const seconds = duration.seconds();

                    if (days > 0) timeLeft += `${days}d `;
                    timeLeft += `${String(hours).padStart(2, '0')}h `;
                    timeLeft += `${String(minutes).padStart(2, '0')}m `;
                    timeLeft += `${String(seconds).padStart(2, '0')}s`;

                    timeLeftElement.innerHTML = `<i class="fas fa-hourglass-half mr-2"></i>${timeLeft} remaining`;
                }
            }

            updateTimeLeft();
            setInterval(updateTimeLeft, 1000);
        }

        // Feedback modal functionality
        function toggleFeedbackModal(submissionId = null, existingFeedback = null) {
            const modal = document.getElementById('feedbackModal');
            const feedbackForm = document.getElementById('feedback');
            const submissionIdInput = document.getElementById('submission_id');

            if (submissionId) {
                submissionIdInput.value = submissionId;
                feedbackForm.value = existingFeedback || '';
                modal.classList.remove('hidden');
            } else {
                modal.classList.add('hidden');
            }
        }

        // Close modal when clicking outside
        document.getElementById('feedbackModal').addEventListener('click', (e) => {
            if (e.target.id === 'feedbackModal') {
                toggleFeedbackModal();
            }
        });
    </script>
</body>

</html>