<?php
// register.php - create a new account (username + password).
session_start();
require 'config/database.php';

$message = '';      // error text shown on the page
$name = '';
$username = '';

// This code runs only when the form was submitted (method = POST).
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 1. Read the values typed in the form. trim() removes extra spaces.
    $name     = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    // 2. Check the values one by one.
    if ($name == '' || $username == '' || $password == '') {
        $message = 'Please fill in all fields.';
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
        $message = 'Username must be 3-30 letters, numbers or underscores.';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
    } elseif ($password != $confirm) {
        $message = 'The two passwords do not match.';
    } else {
        // 3. Is the username already taken? (prepared statement: ? is filled in safely)
        $stmt = $pdo->prepare('SELECT id FROM volunteers WHERE username = ?');
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $message = 'That username is already taken.';
        } else {
            // 4. Save the user. password_hash() turns the password into a
            //    scrambled hash, so the real password is never stored.
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO volunteers (username, password_hash, name) VALUES (?, ?, ?)');
            $stmt->execute([$username, $hash, $name]);

            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - RudhiraApp</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
<div class="auth-box">
    <h1>&#129656; RudhiraApp</h1>
    <p class="subtitle">Create an account</p>

    <?php if ($message != '') { ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div>
    <?php } ?>

    <form method="post" action="register.php">
        <label>Full name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>

        <label>Username</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>

        <label>Password (at least 6 characters)</label>
        <input type="password" name="password" required>

        <label>Confirm password</label>
        <input type="password" name="confirm" required>

        <button type="submit" class="btn">Register</button>
    </form>

    <p class="auth-link">Already have an account? <a href="login.php">Login</a></p>
</div>
</body>
</html>
