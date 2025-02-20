<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin();
if ($_SESSION['user']['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$class_id = $_GET['class_id'] ?? null;
if (!$class_id) {
    header("Location: ../dashboard.php");
    exit;
}

// Verify student's enrollment
$stmt = $pdo->prepare("SELECT * FROM class_requests WHERE user_id = ? AND class_id = ? AND status = 'approved'");
$stmt->execute([$_SESSION['user']['id'], $class_id]);
if (!$stmt->fetch()) {
    header("Location: ../dashboard.php");
    exit;
}

// Handle submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_due'])) {
        $due_id = $_POST['due_id'];
        $submission_link = trim($_POST['submission_link']);

        if (empty($submission_link)) {
            $error = "Submission link is required.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO submissions (user_id, class_id, due_id, submission_link)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$_SESSION['user']['id'], $class_id, $due_id, $submission_link]);
                $success = "Assignment submitted successfully!";
            } catch (PDOException $e) {
                $error = "Error submitting assignment.";
            }
        }
    } elseif (isset($_POST['edit_submission'])) {
        $submission_id = $_POST['submission_id'];
        $new_link = trim($_POST['new_link']);

        if (empty($new_link)) {
            $error = "Submission link is required.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE submissions 
                    SET submission_link = ? 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$new_link, $submission_id, $_SESSION['user']['id']]);
                $success = "Submission updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating submission.";
            }
        }
    }
}

// Fetch class details
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$class_id]);
$class = $stmt->fetch();

// Fetch dues with submission status
$stmt = $pdo->prepare("
    SELECT 
        d.*,
        s.id as submission_id,
        s.submission_link,
        s.feedback,
        s.submitted_at,
        CASE 
            WHEN s.id IS NOT NULL THEN 'submitted'
            WHEN d.due_date < NOW() THEN 'missed'
            ELSE 'pending'
        END as status
    FROM dues d
    LEFT JOIN submissions s ON d.id = s.due_id AND s.user_id = ?
    WHERE d.class_id = ?
    ORDER BY d.due_date DESC
");
$stmt->execute([$_SESSION['user']['id'], $class_id]);
$dues = $stmt->fetchAll();

$activeDues = [];
$expiredDues = [];
$currentTime = new DateTime();

foreach ($dues as $due) {
    $dueDate = new DateTime($due['due_date']);
    if ($dueDate > $currentTime) {
        $activeDues[] = $due;
    } else {
        $expiredDues[] = $due;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Dues - <?= htmlspecialchars($class['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #0f172a;
            color: #e2e8f0;
        }

        .due-card {
            background: #1e293b;
            border: 1px solid #334155;
            transition: all 0.3s ease;
        }

        .due-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .modal {
            background-color: rgba(0, 0, 0, 0.8);
        }

        .modal-content {
            background-color: #1e293b;
            border: 1px solid #334155;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-submitted { background-color: #059669; color: white; }
        .status-missed { background-color: #dc2626; color: white; }
        .status-pending { background-color: #d97706; color: white; }

        .time-left {
            color: #ef4444;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-indigo-400">
                <?= htmlspecialchars($class['name']) ?> - Assignments
            </h1>
            <a href="dashboard.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-900 text-red-100 p-4 rounded-lg mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-900 text-green-100 p-4 rounded-lg mb-6">
                <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($activeDues)): ?>
            <h2 class="text-2xl font-bold mb-6 text-green-400">
                <i class="fas fa-clock mr-2"></i>Active Assignments
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <?php foreach ($activeDues as $due): ?>
                    <div class="due-card rounded-lg overflow-hidden">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-xl font-semibold text-indigo-300"><?= htmlspecialchars($due['title']) ?></h3>
                                <span class="status-badge status-<?= $due['status'] ?>">
                                    <?= ucfirst($due['status']) ?>
                                </span>
                            </div>
                            <p class="text-gray-400 mb-4">
                                <?= htmlspecialchars(strlen($due['description']) > 100 ? substr($due['description'], 0, 100) . '...' : $due['description']) ?>
                            </p>
                            <div class="flex justify-between items-center text-sm text-gray-400">
                                <span><i class="far fa-calendar-alt mr-2"></i>Due: <span class="due-date" data-date="<?= $due['due_date'] ?>"></span></span>
                                <span class="time-left" id="time-left-<?= $due['id'] ?>"></span>
                            </div>
                            <?php if ($due['feedback']): ?>
                                <div class="mt-4 p-3 bg-indigo-900 rounded-lg">
                                    <p class="text-sm"><i class="fas fa-comment mr-2"></i>Feedback: <?= htmlspecialchars($due['feedback']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="px-6 pb-6">
                            <button onclick="openSubmissionModal(<?= htmlspecialchars(json_encode($due)) ?>)" 
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg transition-colors">
                                <i class="fas fa-upload mr-2"></i>
                                <?= $due['status'] === 'submitted' ? 'View Submission' : 'Submit Assignment' ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($expiredDues)): ?>
            <h2 class="text-2xl font-bold mb-6 text-red-400">
                <i class="fas fa-history mr-2"></i>Past Assignments
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($expiredDues as $due): ?>
                    <div class="due-card rounded-lg overflow-hidden opacity-75">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-xl font-semibold text-gray-400"><?= htmlspecialchars($due['title']) ?></h3>
                                <span class="status-badge status-<?= $due['status'] ?>">
                                    <?= ucfirst($due['status']) ?>
                                </span>
                            </div>
                            <p class="text-gray-500 mb-4">
                                <?= htmlspecialchars(strlen($due['description']) > 100 ? substr($due['description'], 0, 100) . '...' : $due['description']) ?>
                            </p>
                            <div class="flex justify-between items-center text-sm text-gray-500">
                                <span><i class="far fa-calendar-alt mr-2"></i>Due: <span class="due-date" data-date="<?= $due['due_date'] ?>"></span></span>
                                <span><i class="fas fa-times-circle mr-2"></i>Expired</span>
                            </div>
                            <?php if ($due['feedback']): ?>
                                <div class="mt-4 p-3 bg-indigo-900/50 rounded-lg">
                                    <p class="text-sm"><i class="fas fa-comment mr-2"></i>Feedback: <?= htmlspecialchars($due['feedback']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="px-6 pb-6">
                            <button onclick="openSubmissionModal(<?= htmlspecialchars(json_encode($due)) ?>)" 
                                    class="w-full bg-gray-600 hover:bg-gray-700 text-white py-2 rounded-lg transition-colors">
                                <i class="fas fa-eye mr-2"></i>View Details
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Submission Modal -->
    <div id="submissionModal" class="modal fixed inset-0 hidden z-50 overflow-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-content w-full max-w-4xl rounded-lg shadow-xl p-6">
                <div class="flex justify-between items-start mb-6">
                    <h2 class="text-2xl font-bold text-indigo-300" id="modalTitle"></h2>
                    <button onclick="closeSubmissionModal()" class="text-gray-400 hover:text-white">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="space-y-6">
                    <div id="modalDescription" class="text-gray-300"></div>
                    <div class="bg-slate-800 rounded-lg p-4">
                        <div class="flex justify-between items-center text-sm mb-2">
                            <span><i class="far fa-calendar-alt mr-2"></i>Due Date: <span id="modalDueDate"></span></span>
                            <span class="time-left" id="modalTimeLeft"></span>
                        </div>
                    </div>
                    <div id="modalResourceSection" class="hidden">
                        <h3 class="text-lg font-semibold mb-2">Resource</h3>
                        <div class="bg-slate-800 rounded-lg p-4">
                            <a id="modalResourceLink" href="#" target="_blank" class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-external-link-alt mr-2"></i>View Resource
                            </a>
                            <iframe id="modalResourcePreview" class="w-full h-64 mt-4 rounded border border-gray-600"></iframe>
                        </div>
                    </div>
                    <div id="modalSubmissionSection"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    moment.locale('en');

    function formatDateTime(date) {
        return moment(date).format('MMMM D, YYYY HH:mm:ss');
    }

    function updateTimeLeft(dueDate, elementId) {
        const now = moment();
        const due = moment(dueDate);
        const duration = moment.duration(due.diff(now));
        
        if (duration.asSeconds() > 0) {
            let timeLeftText = '';
            const days = Math.floor(duration.asDays());
            const hours = duration.hours();
            const minutes = duration.minutes();
            const seconds = duration.seconds();
            
            if (days > 0) timeLeftText += days + "d ";
            timeLeftText += hours.toString().padStart(2, '0') + "h ";
            timeLeftText += minutes.toString().padStart(2, '0') + "m ";
            timeLeftText += seconds.toString().padStart(2, '0') + "s";
            
            document.getElementById(elementId).innerHTML = 
                `<i class="fas fa-hourglass-half mr-2"></i>${timeLeftText} remaining`;
        } else {
            document.getElementById(elementId).innerHTML = 
                `<i class="fas fa-clock mr-2"></i>Time expired`;
        }
    }

    // Update all due dates and time left
    document.querySelectorAll('.due-date').forEach(element => {
        const date = element.dataset.date;
        element.textContent = formatDateTime(date);
    });

    function updateAllTimers() {
        const dues = <?= json_encode($dues) ?>;
        dues.forEach(due => {
            updateTimeLeft(due.due_date, `time-left-${due.id}`);
        });
    }

    setInterval(updateAllTimers, 1000);
    updateAllTimers();

    function openSubmissionModal(due) {
        const modal = document.getElementById('submissionModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalDescription = document.getElementById('modalDescription');
        const modalDueDate = document.getElementById('modalDueDate');
        const modalTimeLeft = document.getElementById('modalTimeLeft');
        const modalResourceSection = document.getElementById('modalResourceSection');
        const modalResourceLink = document.getElementById('modalResourceLink');
        const modalResourcePreview = document.getElementById('modalResourcePreview');
        const modalSubmissionSection = document.getElementById('modalSubmissionSection');

        modalTitle.textContent = due.title;
        modalDescription.textContent = due.description || 'No description available';
        modalDueDate.textContent = formatDateTime(due.due_date);

        // Update resource section
        if (due.link) {
            modalResourceSection.classList.remove('hidden');
            modalResourceLink.href = due.link;
            modalResourcePreview.src = due.link;
        } else {
            modalResourceSection.classList.add('hidden');
        }

        // Submission section content
        const dueDate = moment(due.due_date);
        const isExpired = moment().isAfter(dueDate);
        
        let submissionHTML = '';
        if (due.status === 'submitted') {
            submissionHTML = `
                <div class="bg-slate-800 rounded-lg p-4">
                    <h3 class="text-lg font-semibold mb-2">Your Submission</h3>
                    <div class="mb-4">
                        <p class="text-sm text-gray-400">Submitted on: ${formatDateTime(due.submitted_at)}</p>
                        <a href="${due.submission_link}" target="_blank" class="text-blue-400 hover:text-blue-300">
                            <i class="fas fa-external-link-alt mr-2"></i>View Submission
                        </a>
                    </div>
                    ${!isExpired ? `
                        <form method="POST" class="mt-4" onsubmit="return confirm('Are you sure you want to update your submission?');">
                            <input type="hidden" name="submission_id" value="${due.submission_id}">
                            <input type="hidden" name="due_id" value="${due.id}">
                            <div class="mb-3">
                                <input type="url" name="new_link" value="${due.submission_link}" required
                                    class="w-full bg-slate-700 border border-slate-600 rounded px-3 py-2 text-white">
                            </div>
                            <button type="submit" name="edit_submission" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                                <i class="fas fa-edit mr-2"></i>Update Submission
                            </button>
                        </form>
                    ` : ''}
                </div>`;
        } else if (!isExpired) {
            submissionHTML = `
                <form method="POST" class="bg-slate-800 rounded-lg p-4">
                    <input type="hidden" name="due_id" value="${due.id}">
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-2">Submission Link</label>
                        <input type="url" name="submission_link" required
                            class="w-full bg-slate-700 border border-slate-600 rounded px-3 py-2 text-white"
                            placeholder="Enter your submission link">
                    </div>
                    <button type="submit" name="submit_due" 
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                        <i class="fas fa-upload mr-2"></i>Submit Assignment
                    </button>
                </form>`;
        }

        if (due.feedback) {
            submissionHTML = `
                <div class="bg-indigo-900/50 rounded-lg p-4 mb-4">
                    <h3 class="text-lg font-semibold mb-2">Teacher's Feedback</h3>
                    <p>${due.feedback}</p>
                </div>
            ` + submissionHTML;
        }

        modalSubmissionSection.innerHTML = submissionHTML;
        modal.classList.remove('hidden');
        
        // Update time left in modal
        function updateModalTimer() {
            updateTimeLeft(due.due_date, 'modalTimeLeft');
        }
        updateModalTimer();
        window.modalTimerInterval = setInterval(updateModalTimer, 1000);
    }

    function closeSubmissionModal() {
        document.getElementById('submissionModal').classList.add('hidden');
        clearInterval(window.modalTimerInterval);
    }

    // Close modal when clicking outside
    document.getElementById('submissionModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeSubmissionModal();
        }
    });
</script>
</body>
</html>