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
    $temp_pass = trim($_POST['temp_password']);
    $new_pass = trim($_POST['new_password']);
    $confirm_pass = trim($_POST['confirm_password']);

    // Fetch current password hash
    $user_q = mysqli_query($conn, "SELECT password FROM users WHERE id = $user_id");
    $user_data = mysqli_fetch_assoc($user_q);

    if (!password_verify($temp_pass, $user_data['password'])) {
        $error = "Incorrect temporary password";
    } elseif (strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters long";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "New passwords do not match";
    } else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        
        // Update password and clear reset flag
        $stmt = $conn->prepare("UPDATE users SET password = ?, must_reset_password = 0 WHERE id = ?");
        $stmt->bind_param("si", $hashed_pass, $user_id);
        
        if ($stmt->execute()) {
            // Clear temporary session
            unset($_SESSION['temp_user_id']);
            
            $success = "Password updated successfully! Please login with your new password.";
            header("refresh:3;url=login.php");
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
                <label class="form-label fw-bold">Temporary Password</label>
                <input type="password" name="temp_password" class="form-control" placeholder="Enter temporary password" required>
                <div class="form-text">The password you just used to login.</div>
            </div>

            <hr class="my-4">

            <div class="mb-3">
                <label class="form-label fw-bold">New Permanent Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
                <div class="form-text">Minimum 6 characters for better security.</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" required>
            </div>

            <button type="submit" name="change_password" class="btn btn-primary w-100 py-2 fw-bold">
                Update Password & Go to Login
            </button>
        </form>
    </div>
</div>

</body>
</html>
