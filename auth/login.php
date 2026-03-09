<?php
session_start();

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: ../admin/dashboard.php");
    exit;
}
if (isset($_SESSION['user_id'])) {
    header("Location: ../user/dashboard.php");
    exit;
}

include("../config/db.php");

$error = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Escape inputs
    $username_escaped = mysqli_real_escape_string($conn, $username);

    // ADMIN LOGIN
    $admin_q = mysqli_query($conn, "SELECT * FROM admins WHERE username='$username_escaped'");
    if (mysqli_num_rows($admin_q) == 1) {
        $admin = mysqli_fetch_assoc($admin_q);
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['username'];
            header("Location: ../admin/dashboard.php");
            exit;
        }
    }

    // USER LOGIN (Allow login with Email OR Name)
    $user_q = mysqli_query($conn, "SELECT * FROM users WHERE (email='$username_escaped' OR name='$username_escaped')");
    
    if (mysqli_num_rows($user_q) == 1) {
        $user = mysqli_fetch_assoc($user_q);
        
        if ($user['status'] !== 'active') {
            $error = "Account is inactive. Please contact admin.";
        } elseif (password_verify($password, $user['password'])) {
            
            // AUTO-FIX: Ensure 'must_reset_password' column exists
            $check_reset_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'must_reset_password'");
            if (mysqli_num_rows($check_reset_col) == 0) {
                mysqli_query($conn, "ALTER TABLE users ADD COLUMN must_reset_password TINYINT(1) DEFAULT 0 AFTER status");
                // Refresh user data
                $user_q = mysqli_query($conn, "SELECT * FROM users WHERE id=" . $user['id']);
                $user = mysqli_fetch_assoc($user_q);
            }

            // Check if password reset is forced
            if (isset($user['must_reset_password']) && $user['must_reset_password'] == 1) {
                $_SESSION['temp_user_id'] = $user['id'];
                header("Location: ../auth/change_password.php");
                exit;
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: ../user/dashboard.php");
            exit;
        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "No user found with that email/name.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login | Document Verification System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <link id="favicon" rel="icon" type="image/png" href="../favicon_base.png">
</head>

<body class="bg-light">

<style>
body {
    margin: 0;
    padding: 0;
    height: 100vh;
    font-family: 'Segoe UI', sans-serif;
}

.login-card {
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.title-glow {
    color: #0d6efd;
    font-weight: 600;
}

.btn-primary {
    border-radius: 8px;
}

.form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

a:hover {
    text-decoration: underline;
}

</style>

<div class="container d-flex justify-content-center align-items-center" style="height:100vh;">
    <div class="card login-card p-4" style="width:400px;">
        
        <div class="text-center mb-4">
            <i class="bi bi-shield-lock" style="font-size:42px; color:#0d6efd;"></i>
            <h4 class="mt-2 title-glow">Document Verification</h4>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post">

            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Username / Email</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username or email" required>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
                    <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <button type="submit" name="login" class="btn btn-primary w-100">
                Login
            </button>

            <p class="text-center mt-4 mb-0">
                <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
            </p>

        </form>
    </div>
</div>

<script>
function togglePassword() {
    const passInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    if (passInput.type === 'password') {
        passInput.type = 'text';
        eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        passInput.type = 'password';
        eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>

<!-- Firebase Notification Integration -->
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging-compat.js"></script>
<script src="../js/fcm-init.js"></script>

</body>
</html>

</body>
</html>
