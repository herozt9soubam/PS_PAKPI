<?php
// admin/login.php
session_start();
require_once __DIR__ . '/../config.php';

$error = '';

// Already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password';
    } else {

        try {
            $conn = getConnection();

            // Fetch admin
            $stmt = $conn->prepare("
                SELECT id, username, password, full_name, status
                FROM admins
                WHERE username = :username
                LIMIT 1
            ");

            $stmt->execute([':username' => $username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if user exists
            if (!$admin) {
                $error = 'Invalid username or password';
            }
            // Verify password (hashed password from DB)
            elseif (!password_verify($password, $admin['password'])) {
                $error = 'Invalid username or password';
            }
            // Check account status
            elseif ($admin['status'] !== 'active') {
                $error = 'Your account is not active. Please contact administrator.';
            }
            else {

                // Update last login
                $update = $conn->prepare(
                    "UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = :id"
                );
                $update->execute([':id' => $admin['id']]);

                // Create session
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_fullname'] = $admin['full_name'];

                header('Location: dashboard.php');
                exit();
            }

        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — P.S. Pakpi Enterprises</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/enterprise-theme.css?v=2026-admin-v2">
    <link rel="stylesheet" href="../assets/admin-glass.css?v=2026-liquid-glass">
</head>

<body class="admin-login-page">

<div class="login-wrapper">
    <div class="login-card glass-panel">
        <div class="login-header">
            <div class="login-brand">
                <div class="admin-logo">
                    <span class="logo-mark">PSP</span>
                </div>
                <div class="login-brand-text">
                    <h2>P.S. Pakpi Enterprises</h2>
                    <p class="admin-badge-sub">Enterprise Management Console</p>
                </div>
            </div>
            <div class="login-title-wrap">
                <h1>Administrative Access</h1>
                <p>Sign in with your verified administrator credentials</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="login-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="username"><i class="fa-solid fa-user"></i> Username</label>
                <div class="input-icon-wrap">
                    <input type="text"
                           id="username"
                           name="username"
                           placeholder="Enter admin username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           required
                           autocomplete="username"
                           autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password"><i class="fa-solid fa-lock"></i> Password</label>
                <div class="input-icon-wrap">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Enter admin password"
                           required
                           autocomplete="current-password">
                </div>
            </div>

            <button type="submit" name="login" class="login-btn btn-primary">
                <span>Secure Login</span>
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
            </button>
        </form>

        <div class="login-footer">
            <a href="../index.php" class="back-to-site-link">
                <i class="fa-solid fa-arrow-left"></i> Return to Public Website
            </a>
            <p class="login-copy">© <?php echo date('Y'); ?> P.S. Pakpi Enterprises • All Rights Reserved</p>
        </div>
    </div>
</div>

</body>
</html>
