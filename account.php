<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';
$user_id = $_SESSION['user_id'];
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_username'])) {
        $new_username = trim($_POST['new_username'] ?? '');
        
        if (empty($new_username)) { 
            $error = "Username cannot be empty.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM " . USERS_TABLE . " WHERE username = ? AND id != ?");
            $stmt->execute([$new_username, $user_id]);
            
            if ($stmt->fetch()) {
                $error = "That username is already taken.";
            } else {
                $stmt = $pdo->prepare("UPDATE " . USERS_TABLE . " SET username = ? WHERE id = ?");
                if ($stmt->execute([$new_username, $user_id])) {
                    $_SESSION['username'] = $new_username;
                    $msg = "Username updated successfully."; 
                }
            }
        }
    }

    if (isset($_POST['change_password'])) {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';

        if (empty($current_pass) || empty($new_pass)) {
            $error = "Both password fields are required.";
        } else {
            $stmt = $pdo->prepare("SELECT password_hash FROM " . USERS_TABLE . " WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_pass, $user['password_hash'])) {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE " . USERS_TABLE . " SET password_hash = ? WHERE id = ?");
                $stmt->execute([$new_hash, $user_id]);
                $msg = "Password changed successfully.";
            } else {
                $error = "Current password is incorrect.";
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
    <title>My Account - DMBuilder</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

    <header class="main-header">
        <div class="logo">
            <a href="index.php" style="text-decoration: none; color: inherit;">
                <h1>DM<span>Builder</span></h1>
            </a>
        </div>
        <nav class="user-nav">
            <div class="dropdown">
                <button class="dropdown-trigger" id="userMenuBtn">
                    <?php echo htmlspecialchars($_SESSION['username']); ?> &#9662;
                </button>
                <div class="dropdown-menu" id="userMenu">
                    <a href="account.php">Profile Settings</a>
                    <a href="logout.php">Log Out</a>
                </div>
            </div>
        </nav>
    </header>

    <div class="auth-container">
        <main class="auth-card">
            <h2>Account Settings</h2>

            <?php if ($msg): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="account.php" method="POST">
                <div class="form-group">
                    <label for="new_username">New Username</label>
                    <input type="text" name="new_username" id="new_username" 
                           value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
                </div>
                <button type="submit" name="update_username" class="auth-btn">Update Username</button>
            </form>

            <div class="account-divider"></div>

            <form action="account.php" method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" name="current_password" id="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>
                <button type="submit" name="change_password" class="auth-btn">Change Password</button>
            </form>

            <div class="account-divider"></div>

            <a href="logout.php" class="btn-logout" onclick="return confirm('Are you sure you want to log out?');">
                Log Out
            </a>
        </main>
    </div>

    <script>
        const menuBtn = document.getElementById('userMenuBtn');
        const menu = document.getElementById('userMenu');
        
        if (menuBtn && menu) {
            menuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                menu.classList.toggle('show');
            });

            document.addEventListener('click', () => {
                menu.classList.remove('show');
            });
        }
    </script>
</body>
</html>