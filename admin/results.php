<?php
require_once '../includes/auth.php';
requireLogin();
if (!isAdmin()) {
    header('Location: ../student/dashboard.php');
    exit;
}

include '../includes/header.php';
require_once '../includes/UrlMeta.php';

// Get class_id from URL
$class_id = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
if (!$class_id) {
    header('Location: ../admin/dashboard.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM results WHERE id = :id AND class_id = :class_id");
    $stmt->execute(['id' => $delete_id, 'class_id' => $class_id]);
    header("Location: results.php?class_id=" . $class_id);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $link = trim($_POST['link']);
    $type = $_POST['type'];

    if (empty($title) || empty($link)) {
        $error = "Title and link are required fields.";
    } else {
        if (isset($_POST['action']) && $_POST['action'] === 'edit') {
            $stmt = $pdo->prepare("UPDATE results SET title = :title, description = :description, link = :link, type = :type WHERE id = :id AND class_id = :class_id");
            $stmt->execute([
                'id' => $_POST['result_id'],
                'class_id' => $class_id,
                'title' => $title,
                'description' => $description,
                'link' => $link,
                'type' => $type
            ]);
            $success = "Result updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO results (class_id, title, description, link, type) VALUES (:class_id, :title, :description, :link, :type)");
            $stmt->execute([
                'class_id' => $class_id,
                'title' => $title,
                'description' => $description,
                'link' => $link,
                'type' => $type
            ]);
            $success = "Result added successfully!";
        }
    }
}

// Fetch results
$stmt = $pdo->prepare("SELECT * FROM results WHERE class_id = :class_id ORDER BY published_at DESC");
$stmt->execute(['class_id' => $class_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get class name
$stmt = $pdo->prepare("SELECT name FROM classes WHERE id = :id");
$stmt->execute(['id' => $class_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class=" mx-auto p-6 bg-gradient-to-br from-green-600 to-emerald-800 text-white rounded-lg">
    <h1 class="text-3xl font-bold mb-6">Manage Results - <?= htmlspecialchars($class['name']) ?></h1>

    <?php if (isset($error)): ?>
        <div class="bg-red-600 text-white p-4 rounded-lg mb-4 shadow-lg">⚠️ <?= $error ?></div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="bg-green-600 text-white p-4 rounded-lg mb-4 shadow-lg">✅ <?= $success ?></div>
    <?php endif; ?>

    <!-- Add/Edit Form -->
    <div class="bg-white/10 p-8 rounded-lg shadow-lg mb-12">
        <h2 class="text-xl font-semibold mb-4">Add or Edit Result</h2>
        <form method="POST" class="space-y-6" id="resultForm">
            <input type="hidden" name="action" value="add" id="formAction">
            <input type="hidden" name="result_id" id="resultId">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Title <span class="text-red-400">*</span>:</label>
                    <input type="text" name="title" id="titleInput" required
                        class="w-full p-3 bg-gray-800 text-white rounded border border-gray-600 focus:border-green-400 focus:ring focus:ring-green-300" 
                        placeholder="Enter title" title="Result title">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Type <span class="text-red-400">*</span>:</label>
                    <select name="type" id="typeInput" required
                        class="w-full p-3 bg-gray-800 text-white rounded border border-gray-600 focus:border-green-400 focus:ring focus:ring-green-300">
                        <option value="result">Result</option>
                        <option value="sheet">Sheet</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Description:</label>
                <textarea name="description" id="descriptionInput" rows="4"
                    class="w-full p-3 bg-gray-800 text-white rounded border border-gray-600 focus:border-green-400 focus:ring focus:ring-green-300" 
                    placeholder="Optional description" title="Provide a brief description"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Link (including https://) <span class="text-red-400">*</span>:</label>
                <input type="url" name="link" id="linkInput" required
                    class="w-full p-3 bg-gray-800 text-white rounded border border-gray-600 focus:border-green-400 focus:ring focus:ring-green-300" 
                    placeholder="https://example.com" title="Enter a valid URL">
            </div>

            <div class="flex justify-end gap-4">
                <button type="submit" title="Publish the result"
                    class="px-6 py-3 bg-green-500 text-white rounded-lg shadow-md hover:bg-green-600 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                    Publish
                </button>
                <button type="button" onclick="resetForm()" title="Reset form"
                    class="px-6 py-3 bg-gray-500 text-white rounded-lg shadow-md hover:bg-gray-600 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-gray-400">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Results List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($results as $result): ?>
            <?php
            try {
                $urlMeta = new UrlMeta($result['link']);
                $metaData = json_decode($urlMeta->getWebsiteData());
            } catch (Exception $e) {
                $metaData = (object) [
                    'title' => 'Link Preview',
                    'description' => 'Unable to load preview',
                    'image' => '',
                    'url' => $result['link']
                ];
            }
            ?>
            <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden transform transition hover:scale-105">
                <?php if (!empty($metaData->image)): ?>
                    <div class="h-40 bg-gray-700 overflow-hidden">
                        <img src="<?= htmlspecialchars($metaData->image) ?>" alt="Preview" class="w-full h-full object-cover"
                            onerror="this.style.display='none'">
                    </div>
                <?php endif; ?>

                <div class="p-6">
                    <div class="flex justify-between items-center mb-2">
                        <span
                            class="px-3 py-1 text-sm rounded-full <?= $result['type'] === 'result' ? 'bg-green-500' : 'bg-blue-500' ?>">
                            <?= ucfirst(htmlspecialchars($result['type'])) ?>
                        </span>
                        <span class="text-sm text-gray-400">
                            <?= date('M d, Y H:i', strtotime($result['published_at'])) ?>
                        </span>
                    </div>

                    <h3 class="text-xl font-bold mb-2"> <?= htmlspecialchars($result['title']) ?> </h3>

                    <?php if (!empty($result['description'])): ?>
                        <p class="text-gray-300 mb-4"> <?= htmlspecialchars($result['description']) ?> </p>
                    <?php endif; ?>

                    <div class="flex justify-between items-center">
                        <a href="<?= htmlspecialchars($result['link']) ?>" target="_blank" title="View the link"
                            class="text-blue-400 hover:text-blue-300">View Link</a>

                        <div class="flex gap-2">
                            <button onclick="editResult(<?= htmlspecialchars(json_encode($result)) ?>)" title="Edit this result"
                                class="px-3 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                                Edit
                            </button>
                            <a href="?class_id=<?= $class_id ?>&delete=<?= $result['id'] ?>" title="Delete this result"
                                onclick="return confirm('Are you sure you want to delete this result?')"
                                class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                Delete
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    // Function to handle editing a result
    function editResult(result) {
        // Set form to edit mode
        document.getElementById('formAction').value = 'edit';
        document.getElementById('resultId').value = result.id;

        // Fill form fields
        document.getElementById('titleInput').value = result.title;
        document.getElementById('descriptionInput').value = result.description || '';
        document.getElementById('linkInput').value = result.link;
        document.getElementById('typeInput').value = result.type;

        // Scroll to form
        document.getElementById('resultForm').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    // Function to reset the form to add mode
    function resetForm() {
        // Reset form action to add
        document.getElementById('formAction').value = 'add';
        document.getElementById('resultId').value = '';

        // Clear all form fields
        document.getElementById('resultForm').reset();
    }

    // Handle image load errors
    document.addEventListener('DOMContentLoaded', function () {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            img.addEventListener('error', function () {
                this.parentElement.style.display = 'none';
            });
        });
    });

    // Optional: Preview link metadata when entering URL
    let linkPreviewTimeout;
    document.getElementById('linkInput').addEventListener('input', function () {
        clearTimeout(linkPreviewTimeout);
        linkPreviewTimeout = setTimeout(() => {
            const url = this.value;
            if (url && url.startsWith('http')) {
                // You could add AJAX call here to preview the link metadata
                // But that would require additional backend endpoint
            }
        }, 500);
    });
</script>

<?php include '../includes/footer.php'; ?>