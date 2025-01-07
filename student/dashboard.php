<?php
require_once '../includes/auth.php';
requireLogin();

include '../includes/header.php';
include '../includes/db.php';

// Fetch classes the student has joined
$stmt = $pdo->prepare("
    SELECT classes.id, classes.name, classes.description 
    FROM class_requests 
    JOIN classes ON class_requests.class_id = classes.id 
    WHERE class_requests.user_id = :user_id AND class_requests.status = 'approved'
");
$stmt->execute(['user_id' => $_SESSION['user']['id']]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pending requests
// Update the pending requests SQL query to include description
$stmt = $pdo->prepare("
    SELECT classes.name, classes.class_code, classes.description 
    FROM class_requests 
    JOIN classes ON class_requests.class_id = classes.id 
    WHERE class_requests.user_id = :user_id AND class_requests.status = 'pending'
");
$stmt->execute(['user_id' => $_SESSION['user']['id']]);
$pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 p-4 md:p-6 lg:p-8">
    <!-- Top Section with Glassmorphism -->
    <div class="backdrop-blur-lg bg-white/10 rounded-2xl p-6 mb-8 shadow-xl">
        <div class="flex justify-between items-center">
            <div class="text-white">
                <h1
                    class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
                    Student Dashboard
                </h1>
                <p class="text-gray-300 mt-2">Welcome back,
                    <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Student') ?>
                </p>
            </div>
            <div class="relative">
                <a href="profile.php">
                    <button class="text-white hover:text-purple-400 transition-colors duration-300">
                        <svg style="color: white" xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                            fill="currentColor" class="bi bi-person-fill" viewBox="0 0 16 16">
                            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"
                                fill="white"></path>
                        </svg>
                    </button>
                </a>

                <button class="text-white hover:text-purple-400 transition-colors duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        class="w-8 h-8">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14V11a6 6 0 10-12 0v3c0 .386-.146.735-.405 1.005L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                        </path>
                    </svg>
                </button>




                <span class="absolute -top-1 -right-1 h-4 w-4 bg-pink-500 rounded-full animate-pulse"></span>
            </div>
        </div>
    </div>

    <!-- Action Buttons with Hover Effects -->
    <div class="flex flex-wrap justify-center gap-4 mb-10">
        <a href="join_class.php"
            class="group relative overflow-hidden bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-lg">
            <span class="relative z-10 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Join Class
            </span>
        </a>
        <a href="messages.php"
            class="group bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-lg">
            <span class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                Personal Messages
            </span>
        </a>
        <a href="change_password.php"
            class="group bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-lg">
            <span class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Change Password
            </span>
        </a>
    </div>

    <!-- Joined Classes Section with Card Hover Effects -->
    <div class="mb-10">
        <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            Joined Classes
        </h2>
        <?php if (count($classes) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($classes as $class): ?>
                    <div
                        class="group backdrop-blur-md bg-white/10 rounded-xl p-6 shadow-lg transition-all duration-300 hover:transform hover:scale-105 hover:bg-white/20">
                        <h3 class="text-xl font-bold text-blue-300 mb-2 group-hover:text-blue-200">
                            <?= htmlspecialchars($class['name']) ?>
                        </h3>
                        <p class="text-gray-300 mb-4">
                            <?= htmlspecialchars($class['description'] ?: 'No description available.') ?>
                        </p>
                        <div class="grid grid-cols-2 gap-2">
                            <a href="submit_due.php?class_id=<?= $class['id'] ?>"
                                class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300 text-center text-sm">
                                Submit Due
                            </a>
                            <a href="group_messages.php?class_id=<?= $class['id'] ?>"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300 text-center text-sm">
                                Group Chat
                            </a>
                            <a href="view_results.php?class_id=<?= $class['id'] ?>"
                                class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300 text-center text-sm">
                                Results
                            </a>
                            <a href="view_students.php?class_id=<?= $class['id'] ?>"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300 text-center text-sm">
                                Students
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-10">
                <div class="text-gray-400 mb-4">You haven't joined any classes yet.</div>
                <a href="join_class.php"
                    class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded-lg transition-colors duration-300">
                    Find Classes to Join
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pending Requests Section with Modern List Design -->
    <div class="backdrop-blur-md bg-white/5 rounded-xl p-6">
        <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Pending Requests
        </h2>
        <?php if (count($pendingRequests) > 0): ?>
            <div class="space-y-4">
                <?php foreach ($pendingRequests as $request): ?>
                    <div class="bg-white/10 p-4 rounded-lg backdrop-blur-sm hover:bg-white/20 transition-colors duration-300">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-white font-bold"><?= htmlspecialchars($request['name']) ?></p>
                                <p class="text-gray-300 text-sm">
                                    <?= htmlspecialchars($request['description'] ?: 'No description available.') ?></p>
                            </div>
                            <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-sm">Pending</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-6">
                <p class="text-gray-400">No pending requests at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>