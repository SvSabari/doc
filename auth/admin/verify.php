<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");

$id = $_GET['id'];
$id = $_GET['id'];
$msg = "";

// AUTO-FIX: Add 'remarks' column if it doesn't exist
try {
    $check_col = mysqli_query($conn, "SHOW COLUMNS FROM works LIKE 'remarks'");
    if (mysqli_num_rows($check_col) == 0) {
        mysqli_query($conn, "ALTER TABLE works ADD COLUMN remarks TEXT DEFAULT NULL AFTER status");
    }
} catch (Exception $e) {
    // Silently catch error during auto-fix attempt
}

// FINAL CHECK: Ensure column exists to prevent crash
try {
    $check_final = mysqli_query($conn, "SHOW COLUMNS FROM works LIKE 'remarks'");
    if (mysqli_num_rows($check_final) == 0) {
        die('<div class="alert alert-danger m-4"><b>Critical Error:</b> The database table `works` is missing the `remarks` column. Auto-fix failed. Please check database permissions.</div>');
    }
} catch (Exception $e) {
    die('<div class="alert alert-danger m-4"><b>Database Error:</b> ' . $e->getMessage() . '</div>');
}

if (isset($_POST['accept'])) {
    if (mysqli_query($conn, "UPDATE works SET status='accepted' WHERE id=$id")) {
        $msg = "Accepted successfully";
    } else {
        $msg = "Error updating status: " . mysqli_error($conn);
    }
}

if (isset($_POST['reject'])) {
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    if (mysqli_query($conn, "UPDATE works SET status='rejected', remarks='$remarks' WHERE id=$id")) {
        $msg = "Rejected & sent for resubmission";
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

        <form method="post" id="verifyForm">
            <button type="submit" name="accept" class="btn btn-success w-100 mb-2" onclick="return confirm('Are you sure you want to accept this document?');">Accept</button>

            <!-- Trigger Modal -->
            <button type="button" class="btn btn-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>

            <button type="submit" name="resend" class="btn btn-warning w-100" onclick="return confirm('Are you sure you want to extend the deadline by 1 day?');">Resend (+1 day)</button>
        </form>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="rejectModalLabel">Reject Document</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label for="remarks" class="form-label fw-bold">Reason for Rejection</label>
            <textarea name="remarks" id="remarks" class="form-control" rows="4" placeholder="Please type the reason here..." form="verifyForm" required></textarea>
            <div class="form-text">This reason will be sent to the user.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="reject" class="btn btn-danger" form="verifyForm">Confirm Rejection</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
