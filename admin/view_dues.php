<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin();
if (!isAdmin()) {
    header("Location: ../unauthorized.php");
    exit;
}

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $due_date = $_POST['due_date'] ?? '';

    if (empty($title) || empty($description) || empty($due_date)) {
        $errorMessage = 'Title, description, and due date are required.';
    } else {
        try {
            if (isset($_POST['due_id']) && !empty($_POST['due_id'])) {
                $stmt = $pdo->prepare("
                    UPDATE dues 
                    SET title = :title, description = :description, link = :link, due_date = :due_date
                    WHERE id = :id
                ");
                $stmt->execute([
                    'title' => $title,
                    'description' => $description,
                    'link' => $link,
                    'due_date' => $due_date,
                    'id' => $_POST['due_id']
                ]);
                $successMessage = 'Due updated successfully!';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO dues (title, description, link, due_date)
                    VALUES (:title, :description, :link, :due_date)
                ");
                $stmt->execute([
                    'title' => $title,
                    'description' => $description,
                    'link' => $link,
                    'due_date' => $due_date
                ]);
                $successMessage = 'New due created successfully!';
            }
        } catch (PDOException $e) {
            $errorMessage = 'Database error occurred.';
        }
    }
}

if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM dues WHERE id = :id");
        $stmt->execute(['id' => $_GET['delete_id']]);
        header("Location: view_dues.php?success=1");
        exit;
    } catch (PDOException $e) {
        $errorMessage = 'Error deleting due.';
    }
}

$stmt = $pdo->prepare("SELECT * FROM dues ORDER BY due_date ASC");
$stmt->execute();
$dues = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>View Dues</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.34/moment-timezone-with-data.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #e2e8f0;
        }

        .shadcn-input {
            background-color: #1e293b;
            border: 1px solid #334155;
            color: #e2e8f0;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .shadcn-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .due-card {
            background-color: #1e293b;
            transition: all 0.3s ease;
            border: 1px solid #334155;
        }

        .due-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .time-left {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>

<body>
    <div class="container mx-auto p-4 md:p-6">
        <h1 class="text-3xl font-bold text-center mb-8 text-indigo-400">Manage Dues</h1>

        <?php if ($errorMessage): ?>
            <div class="bg-red-900 text-red-100 p-3 rounded mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($successMessage || isset($_GET['success'])): ?>
            <div class="bg-green-900 text-green-100 p-3 rounded mb-4">
                <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($successMessage ?? 'Operation completed successfully!') ?>
            </div>
        <?php endif; ?>

        <div class="bg-slate-800 p-4 rounded-lg shadow-lg mb-8">
            <h2 class="text-xl font-bold mb-4 text-indigo-300">
                <i class="fas fa-plus-circle mr-2"></i>Create Due
            </h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="due_id" id="due_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Title</label>
                        <input type="text" name="title" id="title" class="shadcn-input w-full" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Due Date</label>
                        <input type="datetime-local" name="due_date" id="due_date" class="shadcn-input w-full" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Description</label>
                    <textarea name="description" id="description" class="shadcn-input w-full h-24" required></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Link</label>
                    <input type="url" name="link" id="link" class="shadcn-input w-full">
                </div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-save mr-2"></i>Submit
                </button>
            </form>
        </div>

        <?php if (!empty($activeDues)): ?>
            <h2 class="text-2xl font-bold mb-4 text-green-400">
                <i class="fas fa-clock mr-2"></i>Active Dues
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <?php foreach ($activeDues as $due): ?>
                    <div class="due-card rounded-lg p-4 cursor-pointer" onclick="toggleDueDetails(<?= $due['id'] ?>)">
                        <h3 class="text-lg font-semibold text-indigo-300"><?= htmlspecialchars($due['title']) ?></h3>
                        <p class="text-sm text-gray-400 mt-2">
                            <?= htmlspecialchars(strlen($due['description']) > 100 ? substr($due['description'], 0, 100) . '...' : $due['description']) ?>
                        </p>
                        <div class="mt-3">
                            <p class="text-sm"><i class="far fa-calendar-alt mr-2"></i><span class="due-date" data-date="<?= $due['due_date'] ?>"></span></p>
                            <p class="time-left font-semibold mt-1" id="time-left-<?= $due['id'] ?>"></p>
                        </div>
                        <div id="due-details-<?= $due['id'] ?>" class="hidden mt-4">
                            <?php if ($due['link']): ?>
                                <div class="mb-4">
                                    <a href="<?= htmlspecialchars($due['link']) ?>" target="_blank" class="text-blue-400 hover:text-blue-300">
                                        <i class="fas fa-external-link-alt mr-2"></i>View Resource
                                    </a>
                                    <iframe src="<?= htmlspecialchars($due['link']) ?>" class="w-full h-64 mt-2 rounded border border-gray-600"></iframe>
                                </div>
                            <?php endif; ?>
                            <div class="flex space-x-2">
                                <button onclick="editDue(<?= htmlspecialchars(json_encode($due)) ?>)" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </button>
                                <a href="?delete_id=<?= $due['id'] ?>" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded" onclick="return confirm('Are you sure you want to delete this due?')">
                                    <i class="fas fa-trash-alt mr-1"></i>Delete
                                </a>
                                <button class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded">
                                    <i class="fas fa-clipboard-list mr-1"></i>View Submissions
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($expiredDues)): ?>
            <h2 class="text-2xl font-bold mb-4 text-red-400">
                <i class="fas fa-history mr-2"></i>Expired Dues
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($expiredDues as $due): ?>
                    <div class="due-card rounded-lg p-4 opacity-75 cursor-pointer" onclick="toggleDueDetails(<?= $due['id'] ?>)">
                        <h3 class="text-lg font-semibold text-gray-400"><?= htmlspecialchars($due['title']) ?></h3>
                        <p class="text-sm text-gray-500 mt-2">
                            <?= htmlspecialchars(strlen($due['description']) > 100 ? substr($due['description'], 0, 100) . '...' : $due['description']) ?>
                        </p>
                        <div class="mt-3">
                            <p class="text-sm"><i class="far fa-calendar-alt mr-2"></i><span class="due-date" data-date="<?= $due['due_date'] ?>"></span></p>
                            <p class="text-red-400 font-semibold mt-1"><i class="fas fa-times-circle mr-2"></i>Expired</p>
                        </div>
                        <div id="due-details-<?= $due['id'] ?>" class="hidden mt-4">
                            <?php if ($due['link']): ?>
                                <div class="mb-4">
                                    <a href="<?= htmlspecialchars($due['link']) ?>" target="_blank" class="text-blue-400 hover:text-blue-300">
                                        <i class="fas fa-external-link-alt mr-2"></i>View Resource
                                    </a>
                                    <iframe src="<?= htmlspecialchars($due['link']) ?>" class="w-full h-64 mt-2 rounded border border-gray-600"></iframe>
                                </div>
                            <?php endif; ?>
                            <div class="flex space-x-2">
                                <button onclick="editDue(<?= htmlspecialchars(json_encode($due)) ?>)" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </button>
                                <a href="?delete_id=<?= $due['id'] ?>" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded" onclick="return confirm('Are you sure you want to delete this due?')">
                                    <i class="fas fa-trash-alt mr-1"></i>Delete
                                </a>
                                <button class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded">
                                    <i class="fas fa-clipboard-list mr-1"></i>View Submissions
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    moment.tz.setDefault("Asia/Dhaka");

    function editDue(due) {
        document.getElementById('due_id').value = due.id;
        document.getElementById('title').value = due.title;
        document.getElementById('description').value = due.description;
        document.getElementById('link').value = due.link || '';
        document.getElementById('due_date').value = due.due_date.replace(' ', 'T');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function toggleDueDetails(dueId) {
        const detailsDiv = document.getElementById(`due-details-${dueId}`);
        detailsDiv.classList.toggle('hidden');
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
                `<i class="fas fa-hourglass-half mr-2"></i>${timeLeftText}`;
        } else {
            document.getElementById(elementId).innerHTML = 
                `<i class="fas fa-times-circle mr-2"></i>Expired`;
        }
    }

    // Initialize all due dates and timers
    document.addEventListener('DOMContentLoaded', function() {
        // Format all due dates
        document.querySelectorAll('.due-date').forEach(element => {
            const date = moment(element.dataset.date);
            element.textContent = date.format('MMMM D, YYYY HH:mm:ss');
        });

        // Set up time-left counters for active dues
        const activeDues = document.querySelectorAll('[id^="time-left-"]');
        activeDues.forEach(element => {
            const dueId = element.id.split('-')[2];
            const dueDate = element.closest('.due-card').querySelector('.due-date').dataset.date;
            
            updateTimeLeft(dueDate, element.id);
            setInterval(() => updateTimeLeft(dueDate, element.id), 1000);
        });
    });
</script>
</body>
</html>