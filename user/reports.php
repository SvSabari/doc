<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");

$user_id = $_SESSION['user_id'];

// 1. Handle Filters
$time_filter = isset($_GET['period']) ? $_GET['period'] : 'overall';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where_clauses = ["user_id = $user_id"];

if (!empty($status_filter)) {
    $where_clauses[] = "status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

if ($time_filter == 'year') {
    $where_clauses[] = "YEAR(created_at) = YEAR(CURRENT_DATE)";
} elseif ($time_filter == 'month') {
    $where_clauses[] = "MONTH(created_at) = MONTH(CURRENT_DATE) AND YEAR(created_at) = YEAR(CURRENT_DATE)";
} elseif ($time_filter == 'week') {
    $where_clauses[] = "YEARWEEK(created_at, 1) = YEARWEEK(CURRENT_DATE, 1)";
}

$where_sql = implode(" AND ", $where_clauses);

// 2. Fetch User Stats (Always based on Period ONLY, to keep card totals accurate)
$base_where = ["user_id = $user_id"];
if ($time_filter == 'year') $base_where[] = "YEAR(created_at) = YEAR(CURRENT_DATE)";
elseif ($time_filter == 'month') $base_where[] = "MONTH(created_at) = MONTH(CURRENT_DATE) AND YEAR(created_at) = YEAR(CURRENT_DATE)";
elseif ($time_filter == 'week') $base_where[] = "YEARWEEK(created_at, 1) = YEARWEEK(CURRENT_DATE, 1)";
$base_where_sql = implode(" AND ", $base_where);

$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM works
    WHERE $base_where_sql
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// 3. Fetch History (Based on ALL filters including Status)
$history_query = "
    SELECT * FROM works
    WHERE $where_sql
    ORDER BY created_at DESC
";
$history = mysqli_query($conn, $history_query);

// 4. Helper for generating URLs
function filterUrl($status = '', $period = null) {
    $params = $_GET;
    if ($status === null) unset($params['status']); else $params['status'] = $status;
    if ($period !== null) $params['period'] = $period;
    return "?" . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Reports | Document Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; }
        .stat-card { border-radius: 12px; border: none; transition: all 0.3s ease; cursor: pointer; text-decoration: none; display: block; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .stat-card.active { outline: 3px solid rgba(0,0,0,0.1); transform: scale(1.02); }
        .filter-select { border-radius: 8px; border: 1px solid #ddd; padding: 10px; font-weight: 500; }
        .table { border-radius: 12px; overflow: hidden; }
        .badge { padding: 6px 12px; border-radius: 6px; font-weight: 600; text-transform: uppercase; font-size: 0.7rem; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary px-4 no-print shadow-sm">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold"><i class="bi bi-box-arrow-in-right me-2"></i> My Submission Report</span>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-light btn-sm fw-bold"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
            <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">My Performance Report</h2>
            <p class="text-muted small">Overview of your assigned work and submission status.</p>
        </div>
        <button onclick="window.print()" class="btn btn-outline-dark no-print">
            <i class="bi bi-printer me-1"></i> Print Summary
        </button>
    </div>

    <!-- Filter Bar -->
    <div class="card shadow-sm p-3 mb-4 no-print border-0" style="border-radius: 12px;">
        <form method="get" class="row g-3 align-items-end">
            <!-- Maintain status if active -->
            <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
            <div class="col-md-9">
                <label class="form-label small fw-bold text-muted">Filter by Time Period</label>
                <select name="period" class="form-select filter-select shadow-sm" onchange="this.form.submit()">
                    <option value="overall" <?= ($time_filter == 'overall') ? 'selected' : '' ?>>Overall History</option>
                    <option value="year" <?= ($time_filter == 'year') ? 'selected' : '' ?>>This Year's Activity</option>
                    <option value="month" <?= ($time_filter == 'month') ? 'selected' : '' ?>>Recent Monthly Progress</option>
                    <option value="week" <?= ($time_filter == 'week') ? 'selected' : '' ?>>Weekly Snapshot</option>
                </select>
            </div>
            <div class="col-md-3 text-end">
                <?php if($time_filter != 'overall' || !empty($status_filter)): ?>
                    <a href="reports.php" class="btn btn-link btn-sm text-decoration-none text-muted">
                        <i class="bi bi-x-circle"></i> Clear All Filters
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Stats Summary -->
    <div class="row g-4 mb-4">
        <div class="col-md-12 col-lg-3">
            <a href="<?= filterUrl('') ?>" class="card stat-card shadow-sm bg-dark text-white p-3 <?= ($status_filter == '') ? 'active' : '' ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="small text-uppercase opacity-75 fw-bold">Total Assigned</div>
                        <h2 class="mb-0 fw-bold"><?= $stats['total'] ?></h2>
                    </div>
                    <i class="bi bi-briefcase fs-3 opacity-25"></i>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-2">
            <a href="<?= filterUrl('submitted') ?>" class="card stat-card shadow-sm bg-white p-3 border-start border-primary border-4 <?= ($status_filter == 'submitted') ? 'active' : '' ?>">
                <div class="small text-uppercase text-muted fw-bold">Submitted</div>
                <h2 class="mb-0 fw-bold text-primary"><?= $stats['submitted'] ?></h2>
            </a>
        </div>
        <div class="col-md-6 col-lg-2">
            <a href="<?= filterUrl('accepted') ?>" class="card stat-card shadow-sm bg-white p-3 border-start border-success border-4 <?= ($status_filter == 'accepted') ? 'active' : '' ?>">
                <div class="small text-uppercase text-muted fw-bold">Accepted</div>
                <h2 class="mb-0 fw-bold text-success"><?= $stats['accepted'] ?></h2>
            </a>
        </div>
        <div class="col-md-6 col-lg-2">
            <a href="<?= filterUrl('rejected') ?>" class="card stat-card shadow-sm bg-white p-3 border-start border-danger border-4 <?= ($status_filter == 'rejected') ? 'active' : '' ?>">
                <div class="small text-uppercase text-muted fw-bold">Rejected</div>
                <h2 class="mb-0 fw-bold text-danger"><?= $stats['rejected'] ?></h2>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="<?= filterUrl('pending') ?>" class="card stat-card shadow-sm bg-white p-3 border-start border-warning border-4 <?= ($status_filter == 'pending') ? 'active' : '' ?>">
                <div class="small text-uppercase text-muted fw-bold">Pending Submission</div>
                <h2 class="mb-0 fw-bold text-warning"><?= $stats['pending'] ?></h2>
            </a>
        </div>
    </div>

    <!-- History Table -->
    <div class="card shadow-sm border-0" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i> Detailed Submission History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Work Title</th>
                            <th>Assigned Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action / Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($history) > 0): ?>
                            <?php while($h = mysqli_fetch_assoc($history)): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($h['title']) ?></td>
                                    <td><?= date('d M Y', strtotime($h['assigned_datetime'])) ?></td>
                                    <td><?= date('d M Y', strtotime($h['due_datetime'])) ?></td>
                                    <td>
                                        <?php
                                        $badge = 'bg-secondary';
                                        if($h['status'] == 'accepted') $badge = 'bg-success';
                                        if($h['status'] == 'rejected') $badge = 'bg-danger';
                                        if($h['status'] == 'pending') $badge = 'bg-warning text-dark';
                                        if($h['status'] == 'submitted') $badge = 'bg-info text-dark';
                                        if($h['status'] == 'completed') $badge = 'bg-success';
                                        ?>
                                        <span class="badge <?= $badge ?>">
                                            <?= $h['status'] ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <?php if($h['status'] == 'rejected'): ?>
                                            <span class="text-danger small fw-bold">Reason: <?= htmlspecialchars($h['remarks']) ?></span>
                                        <?php elseif($h['status'] == 'pending'): ?>
                                            <a href="submit.php?id=<?= $h['id'] ?>" class="btn btn-primary btn-sm rounded-pill px-3">Submit Now</a>
                                        <?php else: ?>
                                            <span class="text-muted small italic">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" style="width: 80px; opacity: 0.3;" alt="No Data">
                                    <p class="text-muted mt-3">No work records found for this period.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Firebase Notification Integration -->
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging-compat.js"></script>
<script src="../js/fcm-init.js"></script>

</body>
</html>
