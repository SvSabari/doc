<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
include("../src/FCMHelper.php");
include("../src/EmailHelper.php");

$id = $_GET['id'];
$msg = "";

// Fetch work data BEFORE potential updates to get user_id and title for notifications
$work_data_query = mysqli_query($conn, "SELECT user_id, title FROM works WHERE id=$id");
$w_pre_update = mysqli_fetch_assoc($work_data_query);
$user_id = $w_pre_update['user_id'];
$title = $w_pre_update['title'];


if (isset($_POST['accept'])) {
    if (mysqli_query($conn, "UPDATE works SET status='accepted' WHERE id=$id")) {
        $msg = "Accepted successfully";
        // Add Notification for User
        mysqli_query($conn, "INSERT INTO notifications (recipient_id, recipient_role, message) VALUES ($user_id, 'user', 'Your work \"$title\" has been accepted!')");
        
        // Professional FCM Notification
        FCMHelper::notifyUser($conn, $user_id, 'user', 'Work Accepted', "Your work \"$title\" has been accepted!");

        // Professional Email Notification
        EmailHelper::notifyUser($conn, $user_id, 'user', 'Work Accepted', "Your work \"$title\" has been accepted!");
    } else {
        $msg = "Error updating status: " . mysqli_error($conn);
    }
}

if (isset($_POST['reject'])) {
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    if (mysqli_query($conn, "UPDATE works SET status='rejected', remarks='$remarks' WHERE id=$id")) {
        $msg = "Rejected & sent for resubmission";
        // Add Notification for User
        mysqli_query($conn, "INSERT INTO notifications (recipient_id, recipient_role, message) VALUES ($user_id, 'user', 'Your work \"$title\" was rejected. Reason: $remarks')");
        
        // Professional FCM Notification
        FCMHelper::notifyUser($conn, $user_id, 'user', 'Work Rejected', "Your work \"$title\" was rejected. Reason: $remarks");

        // Professional Email Notification
        EmailHelper::notifyUser($conn, $user_id, 'user', 'Work Rejected', "Your work \"$title\" was rejected. Reason: $remarks");
    } else {
        $msg = "Error updating status: " . mysqli_error($conn);
    }
}

if (isset($_POST['resend'])) {
    if (mysqli_query($conn, "UPDATE works SET due_datetime = DATE_ADD(due_datetime, INTERVAL 1 DAY) WHERE id=$id")) {
        $msg = "Due date extended by 1 day";
    } else {
        $msg = "Error updating due date: " . mysqli_error($conn);
    }
}

// Fetch work data AFTER potential updates so the UI shows current state
$work = mysqli_query($conn, "SELECT * FROM works WHERE id=$id");
$w = mysqli_fetch_assoc($work);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Document | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .card { border: none; border-radius: 12px; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); }
        .status-badge { padding: 0.5em 1em; font-size: 0.9rem; font-weight: 600; border-radius: 50rem; }
        .btn-action { padding: 0.8rem; font-weight: 500; border-radius: 10px; transition: transform 0.2s; }
        .btn-action:hover { transform: translateY(-2px); }
    </style>
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4 no-print shadow-sm">
    <span class="navbar-brand">Admin Panel - Verify Submission</span>
    <div>
        <a href="view_work.php" class="btn btn-sm btn-outline-light me-2"><i class="bi bi-arrow-left"></i> Back</a>
        <a href="dashboard.php" class="btn btn-sm btn-secondary">Dashboard</a>
    </div>
</nav>

<div class="container mt-5" style="max-width:600px;">

    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($w['title']) ?></h4>
            <?php
                $statusColor = 'bg-secondary';
                if ($w['status'] == 'accepted') $statusColor = 'bg-success';
                if ($w['status'] == 'rejected') $statusColor = 'bg-danger';
                if ($w['status'] == 'pending') $statusColor = 'bg-warning text-dark';
                if ($w['status'] == 'submitted') $statusColor = 'bg-info text-dark';
                if ($w['status'] == 'completed') $statusColor = 'bg-success';
            ?>
            <span class="badge <?= $statusColor ?> status-badge">
                <?= ucfirst($w['status']) ?>
            </span>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4">
                <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                <div><?= $msg ?></div>
            </div>
        <?php endif; ?>

        <?php if ($w['status'] == 'rejected' && $w['remarks']): ?>
            <div class="alert alert-light border mb-4">
                <small class="text-muted d-block mb-1 font-monospace">PREVIOUS REJECTION REASON:</small>
                <div class="text-danger fw-medium"><?= htmlspecialchars($w['remarks']) ?></div>
            </div>
        <?php endif; ?>

        <div class="d-grid gap-3">
            <!-- Accept Button -->
            <button type="button" class="btn btn-success btn-action shadow-sm" 
                    data-bs-toggle="modal" data-bs-target="#acceptModal">
                <i class="bi bi-check2-circle me-1"></i> Accept Document
            </button>

            <!-- Reject Button -->
            <button type="button" class="btn btn-danger btn-action shadow-sm" 
                    data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="bi bi-x-circle me-1"></i> Reject & Revise
            </button>

            <!-- Resend Button -->
            <button type="button" class="btn btn-warning btn-action shadow-sm" 
                    data-bs-toggle="modal" data-bs-target="#resendModal">
                <i class="bi bi-calendar-plus me-1"></i> Extend Deadline (+1 day)
            </button>
        </div>
    </div>
</div>

<!-- Accept Modal -->
<div class="modal fade" id="acceptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold"><i class="bi bi-check2-circle me-2"></i> Confirm Approval</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="display-4 text-success mb-3"><i class="bi bi-shield-check"></i></div>
                    <p class="mb-0">Are you sure you want to <b>Accept</b> this document?<br>The user will be notified of the completion.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="accept" class="btn btn-success px-4">Yes, Approve Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title font-weight-bold"><i class="bi bi-x-circle me-2"></i> Reject Submission</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="remarks" class="form-label fw-bold">Reason for Rejection</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="4" placeholder="Briefly explain what needs to be fixed..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="reject" class="btn btn-danger">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Resend/Extend Modal -->
<div class="modal fade" id="resendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold"><i class="bi bi-calendar-plus me-2"></i> Extend Deadline</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="display-4 text-warning mb-3"><i class="bi bi-clock-history"></i></div>
                    <p class="mb-0">This will extend the due date by <b>exactly 24 hours</b>.<br>Would you like to proceed?</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="resend" class="btn btn-warning px-4 text-dark fw-bold">Increase Deadline</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
