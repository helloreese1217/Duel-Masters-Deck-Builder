<?php
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both a username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM " . USERS_TABLE . " WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = "That username is already taken.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO " . USERS_TABLE . " (username, password_hash) VALUES (?, ?)");
            if ($stmt->execute([$username, $password_hash])) {
                header("Location: login.php?msg=registered");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - DMBuilder</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

    <header class="main-header">
        <div class="logo">
            <h1>DM<span>Builder</span></h1>
        </div>
    </header>

    <div class="auth-container">
        <main class="auth-card">
            <h2>Create Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required autocomplete="new-password">
                </div>
                <button type="submit" class="auth-btn">Register</button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </main>
    </div>

</body>
</html>