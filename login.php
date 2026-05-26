<?php
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'registered') {
    $success = "Registration successful! You can now log in.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both your username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM " . USERS_TABLE . " WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DMBuilder</title>
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
            <h2>Welcome Back</h2>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="auth-btn">Login</button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </main>
    </div>

</body>
</html>