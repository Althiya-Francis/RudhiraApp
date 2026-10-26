<?php
// login.php - checks username and password, then opens the dashboard.
session_start();
require 'config/database.php';

$message = '';
$username = '';

// Message shown after a successful registration.
$success = '';
if (isset($_GET['registered'])) {
    $success = 'Account created. Please login.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 1. Look for a user with this username.
    $stmt = $pdo->prepare('SELECT id, name, password_hash FROM volunteers WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();      // one row, or false if not found

    // 2. password_verify() compares the typed password with the stored hash.
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);               // new session id after login (safer)
        $_SESSION['user_id']   = $user['id'];      // remember who is logged in
        $_SESSION['user_name'] = $user['name'];
        header('Location: dashboard.php');
        exit;
    } else {
        // Same message for "no such user" and "wrong password".
        $message = 'Wrong username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - RudhiraApp</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
<div class="auth-box">
    <h1>&#129656; RudhiraApp</h1>
    <p class="subtitle">Blood Donor Management System</p>

    <?php if ($success != '') { ?>
        <div class="alert alert-ok"><?php echo htmlspecialchars($success); ?></div>
    <?php } ?>
    <?php if ($message != '') { ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div>
    <?php } ?>

    <form method="post" action="login.php">
        <label>Username</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit" class="btn">Login</button>
    </form>

    <p class="auth-link">New here? <a href="register.php">Create an account</a></p>
</div>
</body>
</html>
