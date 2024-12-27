<?php
include '../includes/auth.php';
requireLogin();

if (!isAdmin()) {
    header("Location: ../dashboard.php");
    exit();
}

include '../includes/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-extrabold text-white sm:text-5xl">
                <span class="block">Welcome, Admin</span>
                <span class="block text-indigo-400">Manage Your System</span>
            </h2>
            <p class="mt-4 text-xl text-gray-300">Control and oversee all aspects of the system with ease.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Manage Classes -->
            <div class="bg-white bg-opacity-10 backdrop-filter backdrop-blur-lg rounded-xl overflow-hidden shadow-2xl transform hover:scale-105 transition duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-center w-16 h-16 bg-blue-500 rounded-full mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <h3 class="text-2xl font-semibold text-white mb-2">Classes</h3>
                    <a href="manage_classes.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-full transition duration-300 ease-in-out transform hover:-translate-y-1">Manage Classes</a>
                </div>
            </div>

            <!-- Manage Users -->
            <div class="bg-white bg-opacity-10 backdrop-filter backdrop-blur-lg rounded-xl overflow-hidden shadow-2xl transform hover:scale-105 transition duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-center w-16 h-16 bg-green-500 rounded-full mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-semibold text-white mb-2">Students</h3>
                    <a href="manage_users.php" class="inline-block bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-full transition duration-300 ease-in-out transform hover:-translate-y-1">Manage Users</a>
                </div>
            </div>

            <!-- Approve Join Requests -->
            <div class="bg-white bg-opacity-10 backdrop-filter backdrop-blur-lg rounded-xl overflow-hidden shadow-2xl transform hover:scale-105 transition duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-center w-16 h-16 bg-yellow-500 rounded-full mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-semibold text-white mb-2">Approve Requests</h3>
                    <a href="approve_requests.php" class="inline-block bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-full transition duration-300 ease-in-out transform hover:-translate-y-1">Approve Join Requests</a>
                </div>
            </div>

            <!-- Messaging -->
            <div class="bg-white bg-opacity-10 backdrop-filter backdrop-blur-lg rounded-xl overflow-hidden shadow-2xl transform hover:scale-105 transition duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-center w-16 h-16 bg-purple-500 rounded-full mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 8h10M7 12h5m-5 4h10"></path></svg>
                    </div>
                    <h3 class="text-2xl font-semibold text-white mb-2">Messages</h3>
                    <a href="messages.php" class="inline-block bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded-full transition duration-300 ease-in-out transform hover:-translate-y-1">Manage Messages</a>
                </div>
            </div>

            <!-- Reset Passwords -->
            <div class="bg-white bg-opacity-10 backdrop-filter backdrop-blur-lg rounded-xl overflow-hidden shadow-2xl transform hover:scale-105 transition duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-center w-16 h-16 bg-red-500 rounded-full mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-semibold text-white mb-2">Change Password</h3>
                    <a href="reset_password.php" class="inline-block bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-full transition duration-300 ease-in-out transform hover:-translate-y-1">Reset Passwords</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
