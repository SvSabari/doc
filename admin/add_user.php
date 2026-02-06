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

    $name  = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if user already exists
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {

        $msg = "<div class='alert alert-danger'>User with this email already exists</div>";

    } else {

        // Insert user (inactive until password set)
        mysqli_query($conn,
            "INSERT INTO users (name, email, status)
             VALUES ('$name','$email','inactive')"
        );

        $user_id = mysqli_insert_id($conn);

        // Generate password creation token
        $token  = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 day"));

        mysqli_query($conn,
            "INSERT INTO password_resets (user_id, token, expiry)
             VALUES ($user_id, '$token', '$expiry')"
        );

        // Password creation link
        $link = "http://localhost/doc_verification/auth/create_password.php?token=$token";

        // Mail content
        $message = "
            <h3>Document Verification System</h3>
            <p>Hello <b>$name</b>,</p>
            <p>Your account has been created.</p>
            <p>Click the link below to create your password:</p>
            <a href='$link'>$link</a>
            <p><b>Note:</b> Link valid for 24 hours.</p>
        ";

        // Try sending mail
        $mailStatus = sendMail($email, "Create Your Password", $message);

        if ($mailStatus) {
            $msg = "<div class='alert alert-success'>User added and email sent successfully</div>";
        } else {
            // Fallback if mail server not working
            $msg = "
            <div class='alert alert-warning'>
                <b>User added successfully.</b><br>
                Mail server not configured.<br><br>
                <b>Use this link to create password:</b><br>
                <a href='$link' target='_blank'>$link</a>
            </div>";
        }
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
