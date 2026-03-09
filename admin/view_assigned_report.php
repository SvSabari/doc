<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");

// Handle Search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$date_filter = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';

$where_clauses = ["1=1"];

if (!empty($search)) {
    $where_clauses[] = "(w.title LIKE '%$search%' OR u.name LIKE '%$search%')";
}

if (!empty($date_filter)) {
    $where_clauses[] = "DATE(w.assigned_datetime) = '$date_filter'";
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch assigned works
$assigned_works = mysqli_query($conn, "
    SELECT w.*, u.name as user_name, u.email as user_email 
    FROM works w 
    JOIN users u ON w.user_id = u.id 
    WHERE $where_sql
    ORDER BY w.assigned_datetime DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assignment Report | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand">Assignment Report</span>
    <div>
        <a href="assign_work.php" class="btn btn-sm btn-outline-light me-2">Back</a>
        <a href="dashboard.php" class="btn btn-sm btn-secondary">Dashboard</a>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">Work Assignment Report</h2>
        <button onclick="window.print()" class="btn btn-outline-dark no-print">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">Recently Assigned Work Details</h5>
                </div>
                <div class="col-md-7 no-print">
                    <form method="get" class="d-flex align-items-center">
                        <input type="text" name="search" class="form-control form-control-sm me-2" 
                               placeholder="Search title or user..." 
                               value="<?= htmlspecialchars($search) ?>" style="flex: 2;">
                        <input type="date" name="date" class="form-control form-control-sm me-2" 
                               value="<?= htmlspecialchars($date_filter) ?>" style="flex: 1;">
                        <button type="submit" class="btn btn-sm btn-primary me-1 text-nowrap">Search</button>
                        <?php if (!empty($search) || !empty($date_filter)): ?>
                            <a href="view_assigned_report.php" class="btn btn-sm btn-outline-secondary text-nowrap">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Work Title</th>
                            <th>Assigned Date</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($assigned_works) > 0): ?>
                            <?php while($w = mysqli_fetch_assoc($assigned_works)): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($w['title']) ?></td>
                                    <td><?= date('d M Y, h:i A', strtotime($w['assigned_datetime'])) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($w['user_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($w['user_email']) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = 'bg-secondary';
                                        if($w['status'] == 'accepted') $statusClass = 'bg-success';
                                        if($w['status'] == 'rejected') $statusClass = 'bg-danger';
                                        if($w['status'] == 'pending') $statusClass = 'bg-warning text-dark';
                                        if($w['status'] == 'submitted') $statusClass = 'bg-info text-dark';
                                        ?>
                                        <span class="badge <?= $statusClass ?> text-uppercase" style="font-size:0.7rem;">
                                            <?= $w['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">No assignments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        .navbar { display: none !important; }
        body { background: white !important; }
    }
</style>

<!-- Firebase Notification Integration -->
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging-compat.js"></script>
<script src="../js/fcm-init.js"></script>

</body>
</html>
