<?php
require_once '../includes/auth.php';
requireLogin();
if (!isAdmin()) {
    header('Location: ../student/dashboard.php');
    exit;
}

include '../includes/header.php';

// Fetch all results
$stmt = $pdo->query("SELECT results.id, results.title, results.link, classes.name AS class_name 
                     FROM results
                     JOIN classes ON results.class_id = classes.id");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'];
    $title = $_POST['title'];
    $link = $_POST['link'];

    $stmt = $pdo->prepare("INSERT INTO results (class_id, title, link) VALUES (:class_id, :title, :link)");
    $stmt->execute([
        'class_id' => $class_id,
        'title' => $title,
        'link' => $link,
    ]);

    $success = "Result added successfully!";
}
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold">Manage Results</h1>
    <div class="mt-4">
        <?php if (!empty($success)): ?>
            <p class="text-green-400"><?= $success ?></p>
        <?php endif; ?>
        <form method="POST" class="bg-gray-800 p-4 rounded">
            <label for="class_id" class="block">Class:</label>
            <select id="class_id" name="class_id" class="w-full p-2 bg-gray-700 rounded">
                <?php
                $stmt = $pdo->query("SELECT id, name FROM classes");
                $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($classes as $class): ?>
                    <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <label for="title" class="block mt-2">Title:</label>
            <input type="text" id="title" name="title" class="w-full p-2 bg-gray-700 rounded">
            
            <label for="link" class="block mt-2">Link:</label>
            <input type="url" id="link" name="link" class="w-full p-2 bg-gray-700 rounded">
            
            <button type="submit" class="bg-blue-500 px-4 py-2 mt-2 rounded">Add Result</button>
        </form>
    </div>
    <div class="mt-4">
        <h2 class="text-lg">Existing Results</h2>
        <ul>
            <?php foreach ($results as $result): ?>
                <li class="bg-gray-700 p-2 rounded mt-2">
                    <p><strong>Class:</strong> <?= htmlspecialchars($result['class_name']) ?></p>
                    <p><strong>Title:</strong> <?= htmlspecialchars($result['title']) ?></p>
                    <p><strong>Link:</strong> <a href="<?= htmlspecialchars($result['link']) ?>" target="_blank" class="text-blue-400">View Result</a></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
