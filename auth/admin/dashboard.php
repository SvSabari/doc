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
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand">Document Verification System</span>
    <a href="../auth/logout.php" class="btn btn-sm btn-danger">Logout</a>
</nav>

<div class="container mt-4">
    <h4 class="mb-4">Admin Dashboard</h4>

    <div class="row g-4">

        <div class="col-md-4">
            <div class="card shadow text-center p-4">
                <i class="bi bi-person-plus fs-1 text-primary"></i>
                <h5 class="mt-2">Add User</h5>
                <a href="add_user.php" class="btn btn-primary btn-sm mt-2">Go</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow text-center p-4">
                <i class="bi bi-clipboard-check fs-1 text-success"></i>
                <h5 class="mt-2">Assign Work</h5>
                <a href="assign_work.php" class="btn btn-success btn-sm mt-2">Go</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow text-center p-4">
                <i class="bi bi-folder2-open fs-1 text-warning"></i>
                <h5 class="mt-2">View Submissions</h5>
                <a href="view_work.php" class="btn btn-warning btn-sm mt-2">Go</a>
            </div>
        </div>

    </div>
</div>

</body>
</html>
