<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
include("../src/FCMHelper.php");
include("../src/EmailHelper.php");

$work_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$work = mysqli_query($conn,
    "SELECT * FROM works WHERE id=$work_id AND user_id=$user_id"
);

if (mysqli_num_rows($work) != 1) {
    die("Invalid access");
}

$w = mysqli_fetch_assoc($work);

// Fetch Total Completed Works for this user
$completed_stats = mysqli_query($conn, "SELECT COUNT(*) as count FROM works WHERE user_id=$user_id AND (status='accepted' OR status='completed')");
$comp = mysqli_fetch_assoc($completed_stats);
$completed_count = $comp['count'];

$msg = "";

if (isset($_POST['submit_work'])) {

    $folder = "../uploads/submissions/";
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $file = time() . "_" . $_FILES['doc']['name'];
    move_uploaded_file($_FILES['doc']['tmp_name'], $folder . $file);

    // Insert submission
    mysqli_query($conn,
        "INSERT INTO submissions (work_id, file_path)
         VALUES ($work_id, '$file')"
    );

    // Update status
    mysqli_query($conn,
        "UPDATE works SET status='submitted' WHERE id=$work_id"
    );

    // Add Notification for Admin
    $user_name = $_SESSION['user_name'] ?? 'A user';
    $work_title = $w['title'];
    $msg_text = "$user_name submitted work: $work_title";
    // Assuming admin_id is 1 or we fetch it. Usually there is at least one admin.
    // For simplicity, we notify all admins or a default one. 
    // Let's notify admin with ID 1 (default admin created in setup_db.php).
    mysqli_query($conn, "INSERT INTO notifications (recipient_id, recipient_role, message) VALUES (1, 'admin', '$msg_text')");

    // Professional FCM Notification for Admins
    FCMHelper::notifyAdmins($conn, 'Work Submitted', $msg_text);

    // Professional Email Notification for Admins
    EmailHelper::notifyAdmins($conn, 'Work Submitted', $msg_text);

    $msg = "<div class='alert alert-success shadow-sm rounded-3 mt-3'>
                <i class='bi bi-check-circle-fill me-2'></i> Document submitted successfully
            </div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Document | My Submissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .card { border-radius: 12px; border: none; }
        .stat-badge { background: #e8f5e9; color: #2e7d32; padding: 10px 15px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; }
    </style>
</head>

<body class="bg-light">

<div class="container mt-5" style="max-width:650px;">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
        <div class="stat-badge shadow-sm">
            <i class="bi bi-check-circle-fill me-2"></i> Your Completed Work: <?= $completed_count ?>
        </div>
    </div>

    <div class="card shadow p-4 mb-4">
        <h4 class="fw-bold text-dark"><?= htmlspecialchars($w['title']) ?></h4>
        <p class="text-muted"><i class="bi bi-calendar-event me-1"></i> <b>Due:</b> <?= date("d M Y, h:i A", strtotime($w['due_datetime'])) ?></p>

        <?= $msg ?>

        <?php if ($w['status'] != 'accepted') { ?>
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Upload Document</label>
                <input type="file" name="doc" class="form-control" required>
            </div>

            <button name="submit_work" class="btn btn-primary w-100">
                Submit Document
            </button>
        </form>
        <?php } else { ?>
            <div class="alert alert-success">Document already accepted</div>
        <?php } ?>
    </div>
</div>

<!-- Firebase Notification Integration -->
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging-compat.js"></script>
<script src="../js/fcm-init.js"></script>

</body>
</html>
