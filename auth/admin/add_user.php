<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
include("../config/mail.php");

$msg = "";
$userCreated = false;

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

        $userCreated = true;

        // Simplified success display focused on the link
        $msg = "
        <div class='text-center mb-4'>
            <div class='display-6 text-success mb-2'><i class='bi bi-check-circle-fill'></i></div>
            <h4 class='mb-1'>Account Created</h4>
            <p class='text-muted small'>Copy and share the temporary code (link) below:</p>
            
            <div class='input-group mb-3 shadow-sm'>
                <input type='text' class='form-control border-primary bg-white' value='$link' id='inviteLink' readonly>
                <button class='btn btn-primary' type='button' onclick='copyLink()'>
                    <i class='bi bi-copy'></i> Copy
                </button>
            </div>
            
            <div class='d-grid gap-2'>
                <a href='add_user.php' class='btn btn-outline-secondary'>
                    <i class='bi bi-person-plus'></i> Create Another User
                </a>
            </div>
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

        <?php if (!$userCreated): ?>
        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">User Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter full name" required autocomplete="new-password">
            </div>

            <div class="mb-3">
                <label class="form-label">Email ID</label>
                <input type="email" name="email" class="form-control" placeholder="Enter email address" required autocomplete="new-password">
            </div>

            <button type="submit" name="add_user" class="btn btn-primary w-100">
                Create User
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function copyLink() {
    var copyText = document.getElementById("inviteLink");
    if(copyText) {
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        alert("Link copied to clipboard!");
    }
}
</script>

</body>
</html>
