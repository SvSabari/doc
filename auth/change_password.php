<?php
session_start();
include("../config/db.php");

// Only allow access if user is logged in via temp session (from login.php)
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['temp_user_id'];
$error = "";
$success = "";

if (isset($_POST['change_password'])) {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (strlen($new_pass) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "Passwords do not match";
    } else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        
        // Update password and clear reset flag
        $stmt = $conn->prepare("UPDATE users SET password = ?, must_reset_password = 0 WHERE id = ?");
        $stmt->bind_param("si", $hashed_pass, $user_id);
        
        if ($stmt->execute()) {
            // Transfer to full session
            $_SESSION['user_id'] = $user_id;
            unset($_SESSION['temp_user_id']);
            
            $success = "Password updated successfully! Redirecting...";
            header("refresh:2;url=../user/dashboard.php");
        } else {
            $error = "Error updating password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Set New Password | Document Verification System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="height:100vh;">
    <div class="card shadow p-4" style="width:450px;">
        
        <div class="text-center mb-4">
            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                <i class="bi bi-shield-lock-fill fs-3"></i>
            </div>
            <h4>Secure Your Account</h4>
            <p class="text-muted small">This is your first login. Please set a new permanent password to continue.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success text-center"><?= $success ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label fw-bold">New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
                <div class="form-text">Min 6 characters.</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" required>
            </div>

            <button type="submit" name="change_password" class="btn btn-primary w-100 py-2">
                Save & Continue to Dashboard
            </button>
        </form>
    </div>
</div>

</body>
</html>
