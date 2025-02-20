<?php
require_once '../includes/auth.php';
requireLogin();
if ($_SESSION['user']['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
} 

include '../includes/header.php';
include '../includes/db.php';

$class_id = $_GET['class_id'] ?? null;
if (!$class_id) {
    header("Location: ../index.php");
    exit;
}

try {
    // Get class details
    $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    $class_name = $class['name'] ?? 'Class';

    // Prepare base query
    $query = "SELECT r.*, 
              DATE_FORMAT(r.published_at, '%M %d, %Y') as formatted_date,
              DATE_FORMAT(r.published_at, '%h:%i %p') as formatted_time
              FROM results r 
              WHERE r.class_id = :class_id";
    
    $params = ['class_id' => $class_id];

    // Apply filters
    if (isset($_GET['type']) && in_array($_GET['type'], ['result', 'sheet'])) {
        $query .= " AND r.type = :type";
        $params['type'] = $_GET['type'];
    }

    if (isset($_GET['date'])) {
        $query .= " AND DATE(r.published_at) = :date";
        $params['date'] = $_GET['date'];
    }

    $query .= " ORDER BY r.published_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error_message = "An error occurred while fetching the data. Please try again later.";
    $results = [];
}
?>

<div class="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 p-4 md:p-6 lg:p-8">
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Filter Sidebar -->
        <div class="lg:w-64">
            <div class="backdrop-blur-lg bg-white/10 rounded-xl p-6 shadow-xl sticky top-6">
                <h2 class="text-xl font-bold text-white mb-6">Filters</h2>
                <form action="" method="GET" class="space-y-6">
                    <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($class_id); ?>">
                    

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-200">Date</label>
                        <input type="date" name="date" value="<?php echo $_GET['date'] ?? ''; ?>" 
                               class="w-full bg-white/10 border border-white/20 text-white rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>

                    <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Apply Filters
                    </button>

                    <?php if (isset($_GET['type']) || isset($_GET['date'])): ?>
                        <a href="?class_id=<?php echo htmlspecialchars($class_id); ?>" 
                           class="block w-full text-center text-gray-300 hover:text-white text-sm py-2">
                            Clear Filters
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1">
            <!-- Header Section -->
            <div class="backdrop-blur-lg bg-white/10 rounded-xl p-6 mb-6 shadow-xl">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2"><?php echo htmlspecialchars($class_name); ?></h1>
                        <p class="text-gray-300">Results & Sheets</p>
                    </div>
                    <a href="dashboard.php" 
                       class="text-gray-300 hover:text-white transition-colors duration-300 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        Back to Dashboard
                    </a>
                </div>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="backdrop-blur-lg bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6">
                    <p class="text-red-300"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if (empty($results)): ?>
                <div class="backdrop-blur-lg bg-white/10 rounded-xl p-8 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-300 text-lg mb-2">No items found</p>
                    <p class="text-gray-400">Try adjusting your filters or check back later</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($results as $result): ?>
                        <div class="backdrop-blur-lg bg-white/10 rounded-xl p-6 transition-all duration-300 hover:transform hover:scale-[1.02] hover:bg-white/20">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $result['type'] === 'result' ? 'bg-blue-500/20 text-blue-300' : 'bg-green-500/20 text-green-300'; ?>">
                                        <?php echo ucfirst($result['type']); ?>
                                    </span>
                                    <h3 class="text-xl font-bold text-white mt-2"><?php echo htmlspecialchars($result['title']); ?></h3>
                                </div>
                                <a href="<?php echo htmlspecialchars($result['link']); ?>" target="_blank"
                                   class="p-2 rounded-full bg-white/10 hover:bg-white/20 transition-colors group">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300 group-hover:text-white" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                    </svg>
                                </a>
                            </div>

                            <?php if (!empty($result['description'])): ?>
                                <p class="text-gray-300 mb-4"><?php echo htmlspecialchars($result['description']); ?></p>
                            <?php endif; ?>

                            <div class="flex items-center text-sm text-gray-400 gap-4">
                                <div class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <?php echo $result['formatted_date']; ?>
                                </div>
                                <div class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <?php echo $result['formatted_time']; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>