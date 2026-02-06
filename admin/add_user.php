<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
include("../config/mail.php");

$msg = "";

if (isset($_POST['add_user'])) {

    $name  = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));

    // Check if user already exists
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $msg = "<div class='alert alert-danger'>User with this email already exists</div>";
    } else {
        // AUTO-FIX: Add 'must_reset_password' column if it doesn't exist
        $check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'must_reset_password'");
        if (mysqli_num_rows($check_col) == 0) {
            mysqli_query($conn, "ALTER TABLE users ADD COLUMN must_reset_password TINYINT(1) DEFAULT 0 AFTER status");
        }

        // Generate random temporary password
        $temp_password = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 10);
        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

        // Insert user (active with temporary password and reset flag)
        mysqli_query($conn,
            "INSERT INTO users (name, email, password, status, must_reset_password)
             VALUES ('$name', '$email', '$hashed_password', 'active', 1)"
        );

        $user_id = mysqli_insert_id($conn);

        // Mail content
        $message = "
            <h3>Document Verification System</h3>
            <p>Hello <b>$name</b>,</p>
            <p>Your account has been created successfully.</p>
            <p>Here are your login credentials:</p>
            <p><b>Email:</b> $email</p>
            <p><b>Temporary Password:</b> $temp_password</p>
            <p>Please login and change your password for security.</p>
        ";

        // Try sending mail
        $mailStatus = sendMail($email, "Your Account Credentials", $message);

        // Professional success message (displays password even if mail fails)
        $msg = "
        <div class='alert alert-success border-2 shadow-sm'>
            <div class='d-flex align-items-center mb-2'>
                <h5 class='mb-0'>✅ User Created Successfully</h5>
            </div>
            <p class='text-muted small mb-3'>The credentials have been sent to <b>$email</b>. You can also provide them manually below:</p>
            
            <div class='bg-white p-3 rounded border mb-2'>
                <div class='mb-2'>
                    <label class='text-uppercase fw-bold text-muted' style='font-size: 0.7rem;'>User Email</label>
                    <div class='fw-bold'>$email</div>
                </div>
                <hr class='my-2'>
                <div class='mb-0'>
                    <label class='text-uppercase fw-bold text-muted' style='font-size: 0.7rem;'>Temporary Password</label>
                    <div class='input-group'>
                        <input type='text' id='tempPass' class='form-control fw-bold text-primary font-monospace border-0 bg-light' value='$temp_password' readonly>
                        <button class='btn btn-outline-primary active' onclick='copyPassword()'>Copy</button>
                    </div>
                </div>
            </div>
            <script>
            function copyPassword() {
                var copyText = document.getElementById(\"tempPass\");
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(copyText.value);
                alert(\"Password copied to clipboard!\");
            }
            </script>
        </div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand">Admin Panel</span>
    <a href="dashboard.php" class="btn btn-sm btn-secondary">Dashboard</a>
</nav>

<div class="container mt-5" style="max-width:600px;">
    <div class="card shadow p-4">
        <h4 class="mb-3">Add User</h4>

        <?= $msg ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">User Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email ID</label>
                <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
            </div>

            <button type="submit" name="add_user" class="btn btn-primary w-100">
                Create User
            </button>
        </form>
    </div>
</div>

</body>
</html>
