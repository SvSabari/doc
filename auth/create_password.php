<?php
include("../config/db.php");

$msg = "";
$showForm = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $q = mysqli_query($conn,
        "SELECT * FROM password_resets
         WHERE token='$token' AND expiry >= NOW()"
    );

    if (mysqli_num_rows($q) == 1) {
        $row = mysqli_fetch_assoc($q);
        $user_id = $row['user_id'];
        $showForm = true;

        if (isset($_POST['set_password'])) {
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

            mysqli_query($conn,
                "UPDATE users SET password='$pass', status='active'
                 WHERE id=$user_id"
            );

            mysqli_query($conn,
                "DELETE FROM password_resets WHERE user_id=$user_id"
            );

            $msg = "<div class='alert alert-success'>Password created. You can login now.</div>";
            $showForm = false;
        }
    } else {
        $msg = "<div class='alert alert-danger'>Invalid or expired link</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="height:100vh;">
    <div class="card shadow p-4" style="width:400px;">
        <h4 class="mb-3">Create Password</h4>

        <?= $msg ?>

        <?php if ($showForm): ?>
        <form method="post">
            <div class="mb-3">
                <label>New Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button name="set_password" class="btn btn-success w-100">Set Password</button>
        </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
