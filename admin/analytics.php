<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");

// 1. Handle Search Query
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = "";
if ($search != "") {
    $where_clause = " WHERE (u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR w.title LIKE '%$search%') ";
}

// 2. Fetch Data for Bar Chart (Status vs Timing)
$chart_data = [
    'pending' => ['regular' => 0, 'irregular' => 0],
    'submitted' => ['regular' => 0, 'irregular' => 0],
    'accepted' => ['regular' => 0, 'irregular' => 0],
    'rejected' => ['regular' => 0, 'irregular' => 0]
];

$sql = "SELECT 
            w.status,
            s.submitted_at,
            w.due_datetime
        FROM works w
        LEFT JOIN submissions s ON w.id = s.work_id
        JOIN users u ON w.user_id = u.id
        $where_clause";

$res = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($res)) {
    $status = $row['status'];
    if (!isset($chart_data[$status])) continue;

    $due = strtotime($row['due_datetime']);
    $submitted = $row['submitted_at'] ? strtotime($row['submitted_at']) : null;
    $now = time();

    if ($submitted) {
        if ($submitted <= $due) {
            $chart_data[$status]['regular']++;
        } else {
            $chart_data[$status]['irregular']++;
        }
    } else {
        // Not submitted yet
        if ($now <= $due) {
            $chart_data[$status]['regular']++;
        } else {
            $chart_data[$status]['irregular']++;
        }
    }
}

// 2. Fetch Data for Student Appraisal (REMOVED)
// Appraisal query removed as per user request to focus only on graphical representation.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics & Appraisal | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        }
        .text-gradient {
            background: linear-gradient(45deg, #4f46e5, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .star-rating { color: #fbbf24; font-size: 1.1rem; }
        .progress { height: 8px; border-radius: 4px; }
        .nav-link.active { font-weight: 700; color: #4f46e5 !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-3">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold d-flex align-items-center">
            <i class="bi bi-graph-up-arrow me-2 text-primary"></i> 
            <span class="text-dark">Analytics <span class="text-muted fw-normal">Pro</span></span>
        </span>
        <div class="ms-auto">
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4 me-2">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm rounded-pill px-4">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row g-4">
        <!-- Chart Section (Full Width) -->
        <div class="col-12">
            <div class="glass-card p-5">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">Performance Overview</h4>
                        <p class="text-muted small mb-0">
                            <?= $search ? "Filtering results for: <b class='text-primary'>$search</b>" : "Status-wise Regular vs Irregular breakdown" ?>
                        </p>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="search-box">
                        <form method="GET" action="" class="input-group">
                            <input type="text" name="search" class="form-control border-end-0 rounded-start-pill px-4 shadow-none" placeholder="Search name, email, or title..." value="<?= htmlspecialchars($search) ?>" style="width: 300px;">
                            <button class="btn btn-white border-start-0 rounded-end-pill px-4 text-primary shadow-none border" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if($search): ?>
                                <a href="analytics.php" class="btn btn-link link-secondary text-decoration-none d-flex align-items-center mb-0">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div style="height: 450px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('statusChart').getContext('2d');
    
    // Data from PHP
    const chartData = <?= json_encode($chart_data) ?>;
    
    const labels = ['Pending', 'Submitted', 'Accepted', 'Rejected'];
    const regularData = [
        chartData.pending.regular,
        chartData.submitted.regular,
        chartData.accepted.regular,
        chartData.rejected.regular
    ];
    const irregularData = [
        chartData.pending.irregular,
        chartData.submitted.irregular,
        chartData.accepted.irregular,
        chartData.rejected.irregular
    ];

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Regular (On-time)',
                    data: regularData,
                    backgroundColor: '#4f46e5',
                    borderRadius: 8,
                    barThickness: 30
                },
                {
                    label: 'Irregular (Late/Overdue)',
                    data: irregularData,
                    backgroundColor: '#ef4444',
                    borderRadius: 8,
                    barThickness: 30
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { size: 12, weight: '600' }
                    }
                },
                tooltip: {
                    padding: 15,
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#1f2937',
                    bodyColor: '#4b5563',
                    borderColor: '#e5e7eb',
                    borderWidth: 1,
                    usePointStyle: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { display: true, drawBorder: false, color: '#f3f4f6' },
                    ticks: { font: { size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 12, weight: '600' } }
                }
            }
        }
    });
</script>

<!-- Firebase Notification Integration -->
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging-compat.js"></script>
<script src="../js/fcm-init.js"></script>

</body>
</html>
