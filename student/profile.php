<?php
require_once '../includes/auth.php';
requireLogin();

include '../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $contact_no = $_POST['contact_no'];

    // Update avatar if uploaded
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        $avatarName = $_SESSION['user']['id'] . "_" . basename($_FILES['avatar']['name']);
        $avatarPath = "../assets/avatars/" . $avatarName;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath)) {
            $stmt = $pdo->prepare("UPDATE users SET avatar = :avatar WHERE id = :user_id");
            $stmt->execute(['avatar' => $avatarName, 'user_id' => $_SESSION['user']['id']]);
            $_SESSION['user']['avatar'] = $avatarName;
        } else {
            $error = "Failed to upload avatar.";
        }
    }

    // Update profile information
    $stmt = $pdo->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, contact_no = :contact_no WHERE id = :user_id");
    $stmt->execute([
        'first_name' => $first_name,
        'last_name' => $last_name,
        'contact_no' => $contact_no,
        'user_id' => $_SESSION['user']['id']
    ]);

    $_SESSION['user']['first_name'] = $first_name;
    $_SESSION['user']['last_name'] = $last_name;
    $_SESSION['user']['contact_no'] = $contact_no;

    $success = "Profile updated successfully!";
}
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold">Manage Profile</h1>
    <div class="mt-4">
        <?php if (!empty($error)): ?>
            <p class="text-red-400"><?= $error ?></p>
        <?php elseif (!empty($success)): ?>
            <p class="text-green-400"><?= $success ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="bg-gray-800 p-4 rounded">
            <div class="mb-4">
                <label for="first_name" class="block">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($_SESSION['user']['first_name']) ?>" class="w-full p-2 bg-gray-700 rounded">
            </div>
            <div class="mb-4">
                <label for="last_name" class="block">Last Name:</label>
                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($_SESSION['user']['last_name']) ?>" class="w-full p-2 bg-gray-700 rounded">
            </div>
            <div class="mb-4">
                <label for="contact_no" class="block">Contact No:</label>
                <input type="text" id="contact_no" name="contact_no" value="<?= htmlspecialchars($_SESSION['user']['contact_no']) ?>" class="w-full p-2 bg-gray-700 rounded">
            </div>
            <div class="mb-4">
                <label for="avatar" class="block">Avatar:</label>
                <input type="file" id="avatar" name="avatar" class="w-full p-2 bg-gray-700 rounded">
                <?php if (!empty($_SESSION['user']['avatar'])): ?>
                    <img src="../assets/avatars/<?= htmlspecialchars($_SESSION['user']['avatar']) ?>" alt="Avatar" class="mt-2 w-16 h-16 rounded-full">
                <?php endif; ?>
            </div>
            <button type="submit" class="bg-blue-500 px-4 py-2 rounded">Update Profile</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
