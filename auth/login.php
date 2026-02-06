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
</head>

<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="height:100vh;">
    <div class="card shadow p-4" style="width:400px;">
        
        <h4 class="text-center mb-3">Document Verification System</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post">

            <!-- USERNAME / EMAIL -->
            <div class="mb-3">
                <label class="form-label">Username / Email</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username or email" required>
            </div>

            <!-- PASSWORD WITH SHOW / HIDE -->
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
                    <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <!-- LOGIN BUTTON -->
            <button type="submit" name="login" class="btn btn-primary w-100">
                Login
            </button>

        </form>
    </div>
</div>

<!-- SHOW / HIDE PASSWORD SCRIPT -->
<script>
function togglePassword() {
    const passwordField = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");

    if (passwordField.type === "password") {
        passwordField.type = "text";
        eyeIcon.classList.remove("bi-eye");
        eyeIcon.classList.add("bi-eye-slash");
    } else {
        passwordField.type = "password";
        eyeIcon.classList.remove("bi-eye-slash");
        eyeIcon.classList.add("bi-eye");
    }
}
</script>

</body>
</html>
