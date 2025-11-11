<?php
session_start();

// Hardcoded credentials (demo)
$adminUser = 'admin';
$adminPass = 'admin123'; // In production, use password_hash!

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === $adminUser && $password === $adminPass) {
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = $username;
        header('Location: admin.php');
        exit;
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Login</title>
<link rel="stylesheet" href="login.css">
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <h1>Admin Login</h1>
    </div>
    <div class="login-body">
        <?php if($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post">
            <label>Username:</label>
            <input type="text" name="username" placeholder="Enter username" required>

            <label>Password:</label>
            <input type="password" name="password" placeholder="Enter password" required>

            <button type="submit">Login</button>
        </form>
        <a href="index.php" class="menu-btn">Back to Menu</a>
    </div>
</div>
</body>
</html>
