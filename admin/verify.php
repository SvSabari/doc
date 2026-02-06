<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");

$id = $_GET['id'];
$msg = "";

$work = mysqli_query($conn, "SELECT * FROM works WHERE id=$id");
$w = mysqli_fetch_assoc($work);

if (isset($_POST['accept'])) {
    mysqli_query($conn, "UPDATE works SET status='accepted' WHERE id=$id");
    $msg = "Accepted successfully";
}

if (isset($_POST['reject'])) {
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    mysqli_query($conn,
        "UPDATE works SET status='rejected', remarks='$remarks' WHERE id=$id"
    );
    $msg = "Rejected & sent for resubmission";
}

if (isset($_POST['resend'])) {
    mysqli_query($conn,
        "UPDATE works SET due_datetime = DATE_ADD(due_datetime, INTERVAL 1 DAY)
         WHERE id=$id"
    );
    $msg = "Due date extended by 1 day";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4" style="max-width:600px;">
    <a href="view_work.php" class="btn btn-secondary btn-sm mb-3">← Back</a>

    <div class="card shadow p-4">
        <h4><?= $w['title'] ?></h4>
        <p><b>Status:</b> <?= ucfirst($w['status']) ?></p>

        <?php if ($msg): ?>
            <div class="alert alert-info"><?= $msg ?></div>
        <?php endif; ?>

        <form method="post">
            <button name="accept" class="btn btn-success w-100 mb-2">Accept</button>

            <textarea name="remarks" class="form-control mb-2" placeholder="Reason for rejection"></textarea>
            <button name="reject" class="btn btn-danger w-100 mb-2">Reject</button>

            <button name="resend" class="btn btn-warning w-100">Resend (+1 day)</button>
        </form>
    </div>
</div>

</body>
</html>
