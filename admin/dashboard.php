<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link id="favicon" rel="icon" type="image/png" href="../favicon_base.png">

<style>
body {
    margin: 0;
    padding: 0;
    min-height: 100vh;
    background-color: #f8f9fa;
    font-family: 'Segoe UI', sans-serif;
}

.navbar {
    background-color: #343a40;
    z-index: 10;
}

.dashboard-title {
    font-weight: 600;
    color: #333;
}

.card-dashboard {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    transition: all 0.2s ease;
    height: 100%;
}

.card-dashboard:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 24px;
    color: white;
}

.btn-go {
    border-radius: 20px;
}
</style>
</head>

<body>

<nav class="navbar navbar-dark px-4 py-2 shadow-sm">
    <span class="navbar-brand fw-bold">Admin Panel</span>
    <a href="../auth/logout.php" class="btn btn-sm btn-outline-light">
        <i class="bi bi-box-arrow-right me-1"></i> Logout
    </a>
</nav>

<div class="container py-5">

    <div class="text-center mb-5">
        <h3 class="dashboard-title">Admin Dashboard</h3>
    </div>

    <div class="row g-4 mb-4">

        <div class="col-md-4">
            <div class="card card-dashboard text-center p-4 shadow-sm">
                <div class="icon-circle bg-primary">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h5>Add User</h5>
                <a href="add_user.php" class="btn btn-primary btn-sm btn-go mt-2">Go</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-dashboard text-center p-4 shadow-sm">
                <div class="icon-circle bg-success">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <h5>Assign Work</h5>
                <a href="assign_work.php" class="btn btn-success btn-sm btn-go mt-2">Go</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-dashboard text-center p-4 shadow-sm">
                <div class="icon-circle bg-warning">
                    <i class="bi bi-folder2-open"></i>
                </div>
                <h5>View Submissions</h5>
                <a href="view_work.php" class="btn btn-warning btn-sm btn-go mt-2">Go</a>
            </div>
        </div>

    </div>

    <div class="row g-4 justify-content-center">

        <div class="col-md-4">
            <div class="card card-dashboard text-center p-4 shadow-sm">
                <div class="icon-circle bg-info">
                    <i class="bi bi-bar-chart-line"></i>
                </div>
                <h5>Reports & Analytics</h5>
                <a href="reports.php" class="btn btn-info btn-sm btn-go mt-2 text-white">Go</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-dashboard text-center p-4 shadow-sm">
                <div class="icon-circle bg-dark">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h5>Analytics & Tracking</h5>
                <a href="analytics.php" class="btn btn-dark btn-sm btn-go mt-2">Go</a>
            </div>
        </div>

    </div>

</div>

<div class="text-center mt-3 no-print">
    <small class="text-muted">
        <i class="bi bi-bell-fill me-1"></i> Push Status: <span id="fcm-status-text">Initializing...</span>
    </small>
    <div class="mt-2">
        <button onclick="testNotification()" class="btn btn-sm btn-outline-dark rounded-pill">
            <i class="bi bi-megaphone me-1"></i> Send Test Notification
        </button>
    </div>
</div>

<script>
function testNotification() {
    fetch('../api/test_fcm.php')
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') alert('Test notification sent!');
        else alert('Error: ' + data.message);
    });
}
</script>

<!-- Firebase Notification Integration -->
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging-compat.js"></script>
<script src="../js/fcm-init.js"></script>

</body>
</html>

</body>
</html>