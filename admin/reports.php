<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");

// 1. Handle Filters
$user_ids = isset($_GET['user_ids']) ? $_GET['user_ids'] : [];
$time_filter = isset($_GET['period']) ? trim($_GET['period']) : 'overall';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_clauses = ["1=1"];

if (!empty($user_ids)) {
    $sanitized_ids = array_map('intval', $user_ids);
    $where_clauses[] = "w.user_id IN (" . implode(',', $sanitized_ids) . ")";
}

if (!empty($status_filter)) {
    $where_clauses[] = "w.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

// Advanced Time Filter Parsing
if ($time_filter != 'overall' && !empty($time_filter)) {
    $is_date_handled = false;

    // Handle Predefined Shortcuts
    if (strtolower($time_filter) == 'year' || strtolower($time_filter) == 'this year') {
        $where_clauses[] = "YEAR(w.created_at) = YEAR(CURRENT_DATE)";
        $is_date_handled = true;
    } elseif (strtolower($time_filter) == 'month' || strtolower($time_filter) == 'this month') {
        $where_clauses[] = "MONTH(w.created_at) = MONTH(CURRENT_DATE) AND YEAR(w.created_at) = YEAR(CURRENT_DATE)";
        $is_date_handled = true;
    } elseif (strtolower($time_filter) == 'week' || strtolower($time_filter) == 'this week') {
        $where_clauses[] = "YEARWEEK(w.created_at, 1) = YEARWEEK(CURRENT_DATE, 1)";
        $is_date_handled = true;
    }

    // Handle Smart Ranges (e.g., "feb 9 to feb 19")
    if (!$is_date_handled && stripos($time_filter, ' to ') !== false) {
        $parts = explode(' to ', strtolower($time_filter));
        if (count($parts) == 2) {
            $start_dt = strtotime(trim($parts[0]));
            $end_dt = strtotime(trim($parts[1]));
            if ($start_dt && $end_dt) {
                $start_str = date('Y-m-d', $start_dt);
                $end_str = date('Y-m-d', $end_dt);
                $where_clauses[] = "DATE(w.created_at) BETWEEN '$start_str' AND '$end_str'";
                $is_date_handled = true;
            }
        }
    }

    // Handle Specific Patterns (e.g., "Feb", "2026", "2024-02-09")
    if (!$is_date_handled) {
        $timestamp = strtotime($time_filter);
        $is_just_number = preg_match('/^\d+$/', $time_filter);
        $is_year = preg_match('/^\d{4}$/', $time_filter);

        if ($timestamp && (!$is_just_number || $is_year)) {
            if ($is_year) {
                $where_clauses[] = "YEAR(w.created_at) = '$time_filter'";
                $is_date_handled = true;
            } elseif (preg_match('/^(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)/i', $time_filter) && !preg_match('/\d/', $time_filter)) {
                $month_num = date('n', $timestamp);
                $where_clauses[] = "MONTH(w.created_at) = '$month_num' AND YEAR(w.created_at) = YEAR(CURRENT_DATE)";
                $is_date_handled = true;
            } else {
                $date_part = date('Y-m-d', $timestamp);
                $where_clauses[] = "DATE(w.created_at) = '$date_part'";
                $is_date_handled = true;
            }
        }
    }
}

$where_sql = implode(" AND ", $where_clauses);

// 2. Fetch Aggregated Stats (using the same where clauses but on the base table)
// Note: $stats cards should ONLY be filtered by User and Time, not Status.
$stats_where = ["1=1"];
if (!empty($user_ids)) {
    $sanitized_ids = array_map('intval', $user_ids);
    $stats_where[] = "user_id IN (" . implode(',', $sanitized_ids) . ")";
}
// Reuse the same time logic for $stats_where
if ($time_filter != 'overall' && !empty($time_filter)) {
    // (A bit redundant but necessary for accurate stats cards)
    // We'll extract the time component from $where_clauses later or just rebuild.
    // For simplicity, let's extract clauses focusing on created_at.
    foreach($where_clauses as $clause) {
        if (stripos($clause, 'created_at') !== false || stripos($clause, 'w.created_at') !== false) {
            $stats_where[] = str_replace('w.', '', $clause);
        }
    }
}
$stats_where_sql = implode(" AND ", $stats_where);

$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM works
    WHERE $stats_where_sql
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// 3. Fetch Detailed Records
$records_query = "
    SELECT w.*, u.name as user_name, u.email as user_email
    FROM works w
    JOIN users u ON w.user_id = u.id
    WHERE $where_sql
    ORDER BY w.created_at DESC
";
$records = mysqli_query($conn, $records_query);

// 4. Helper for generating URLs
function filterUrl($status = '') {
    $params = $_GET;
    if ($status === null || $status === '') unset($params['status']); else $params['status'] = $status;
    return "?" . http_build_query($params);
}

// 5. Fetch Users for Filter
$users_list = mysqli_query($conn, "SELECT id, name, email FROM users ORDER BY name ASC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports & Analytics | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --sidebar-width: 260px;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: #f3f4f6;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #1f2937;
        }

        @media print {
            .no-print { display: none !important; }
            .card { border: 1px solid #ddd !important; box-shadow: none !important; }
            body { background: white !important; }
            .container { max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
        }

        .navbar {
            background: #111827 !important;
            border-bottom: 4px solid var(--primary-color);
        }

        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none;
            display: block;
            border-radius: 1rem;
            border: none;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }
        .stat-card.active {
            outline: 4px solid rgba(79, 70, 229, 0.3);
            transform: scale(1.05);
        }
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            pointer-events: none;
        }

        .filter-section {
            background: white;
            border-radius: 1.25rem;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: var(--card-shadow);
        }

        .user-selection-box {
            max-height: 180px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 10px;
            background-color: #fff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: absolute;
            z-index: 1000;
            width: 100%;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
        }
        .user-selection-box.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .user-selection-wrapper {
            position: relative;
        }
        .user-item {
            padding: 6px 10px;
            border-radius: 0.5rem;
            transition: background 0.2s;
            margin-bottom: 2px;
        }
        .user-item:hover {
            background-color: #f3f4f6;
        }
        
        .form-control, .form-select {
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            padding: 0.625rem 1rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            border-radius: 0.75rem;
            padding: 0.625rem 1.25rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        .table-responsive {
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        .table thead {
            background-color: #f8fafc;
        }
        .table thead th {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4 py-3 no-print">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold d-flex align-items-center">
            <i class="bi bi-graph-up-arrow me-2"></i> Admin Analytics
        </span>
        <div class="d-flex align-items-center">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-4">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
        </div>
    </div>
</nav>

<div class="container mt-5 mb-5">
    
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <nav aria-label="breadcrumb" class="no-print">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Admin</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
            <h1 class="fw-extrabold text-dark mb-0 tracking-tight" style="font-size: 2.25rem;">Analytics & Insights</h1>
            <p class="text-secondary mt-2">Monitor submission trends and user performance across the system.</p>
        </div>
        <button onclick="window.print()" class="btn btn-white border-0 shadow-sm no-print px-4 py-2 rounded-pill font-semibold">
            <i class="bi bi-printer me-2"></i> Export PDF
        </button>
    </div>

    <!-- Filter Bar -->
    <div class="filter-section p-4 mb-5 no-print">
        <form id="filterForm" method="get" class="row g-4">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
            
            <div class="col-lg-7">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="form-label small fw-bold text-uppercase text-muted tracking-wide mb-0">Target Users</label>
                    <div class="form-check form-switch small">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                        <label class="form-check-label text-muted" for="selectAll">Select All</label>
                    </div>
                </div>
                <div class="user-selection-wrapper">
                    <div class="input-group input-group-sm mb-0">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="userSearch" class="form-control border-start-0 ps-0" placeholder="Click to select users..." autocomplete="off">
                    </div>
                    <div class="user-selection-box mt-1" id="userList">
                        <?php while($u = mysqli_fetch_assoc($users_list)): ?>
                            <div class="form-check user-item">
                                <input class="form-check-input user-checkbox" type="checkbox" name="user_ids[]" value="<?= $u['id'] ?>" id="user_<?= $u['id'] ?>" <?= in_array($u['id'], $user_ids) ? 'checked' : '' ?>>
                                <label class="form-check-label w-100 fw-medium" for="user_<?= $u['id'] ?>" style="cursor: pointer;">
                                    <div class="d-flex flex-column">
                                        <span><?= htmlspecialchars($u['name']) ?></span>
                                        <small class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($u['email']) ?></small>
                                    </div>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="h-100 d-flex flex-column">
                    <label class="form-label small fw-bold text-uppercase text-muted tracking-wide d-block mb-3">Time Period Analysis</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 py-3"><i class="bi bi-calendar3 text-primary"></i></span>
                        <input type="text" name="period" id="smartTimeFilter" class="form-control border-start-0 py-3 ps-0 font-medium filter-input" 
                               placeholder="Type 'Feb', '2026', 'Week' or 'Feb 1 to Feb 10'" 
                               value="<?= ($time_filter == 'overall') ? '' : htmlspecialchars($time_filter) ?>">
                    </div>
                    <div class="mt-auto pt-3">
                        <a href="reports.php" class="btn btn-link btn-sm text-decoration-none text-muted p-0">
                            <i class="bi bi-arrow-counterclockwise"></i> Clear All Filters
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Stats Summary -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <a href="<?= filterUrl('') ?>" class="card stat-card border-0 shadow-sm bg-primary text-white p-3 <?= ($status_filter == '') ? 'active' : '' ?>">
                <div class="small text-uppercase opacity-75 fw-bold">Total Work</div>
                <h3 class="mb-0 fw-bold"><?= (int)$stats['total'] ?></h3>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= filterUrl('accepted') ?>" class="card stat-card border-0 shadow-sm bg-success text-white p-3 <?= ($status_filter == 'accepted') ? 'active' : '' ?>">
                <div class="small text-uppercase opacity-75 fw-bold">Accepted</div>
                <h3 class="mb-0 fw-bold"><?= (int)$stats['accepted'] ?></h3>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= filterUrl('rejected') ?>" class="card stat-card border-0 shadow-sm bg-danger text-white p-3 <?= ($status_filter == 'rejected') ? 'active' : '' ?>">
                <div class="small text-uppercase opacity-75 fw-bold">Rejected</div>
                <h3 class="mb-0 fw-bold"><?= (int)$stats['rejected'] ?></h3>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= filterUrl('pending') ?>" class="card stat-card border-0 shadow-sm bg-warning text-dark p-3 <?= ($status_filter == 'pending') ? 'active' : '' ?>">
                <div class="small text-uppercase opacity-75 fw-bold">Pending</div>
                <h3 class="mb-0 fw-bold"><?= (int)$stats['pending'] ?></h3>
            </a>
        </div>
    </div>

    <!-- Detailed Records -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Report Details</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User Name</th>
                            <th>Work Title</th>
                            <th>Created On</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($records) > 0): ?>
                            <?php while($r = mysqli_fetch_assoc($records)): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($r['user_name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($r['user_email']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($r['title']) ?></td>
                                    <td><?= date('d-m-Y', strtotime($r['created_at'])) ?></td>
                                    <td>
                                        <?php
                                        $statusClass = 'bg-secondary';
                                        if($r['status'] == 'accepted') $statusClass = 'bg-success';
                                        if($r['status'] == 'rejected') $statusClass = 'bg-danger';
                                        if($r['status'] == 'pending') $statusClass = 'bg-warning text-dark';
                                        if($r['status'] == 'submitted') $statusClass = 'bg-info text-dark';
                                        ?>
                                        <span class="badge <?= $statusClass ?> text-uppercase" style="font-size:0.7rem;">
                                            <?= $r['status'] ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        <?= !empty($r['remarks']) ? htmlspecialchars($r['remarks']) : '-' ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No records found for the selected filters.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    const filterForm = document.getElementById('filterForm');
    const selectAll = document.getElementById('selectAll');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const userSearchInput = document.getElementById('userSearch');
    const userList = document.getElementById('userList');
    
    // Toggle User List visibility
    userSearchInput.addEventListener('focus', () => {
        userList.classList.add('show');
    });

    // Close user list when clicking outside
    document.addEventListener('click', (e) => {
        if (!userSearchInput.contains(e.target) && !userList.contains(e.target)) {
            userList.classList.remove('show');
        }
    });

    // Initialize Select All state on load
    function updateSelectAllState() {
        const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
        selectAll.checked = (checkedCount > 0 && checkedCount === userCheckboxes.length);
    }
    updateSelectAllState();

    // Auto-submit on filter change
    document.querySelectorAll('.filter-input, .user-checkbox').forEach(input => {
        input.addEventListener('change', () => filterForm.submit());
    });

    // Select All functionality
    selectAll.addEventListener('change', function() {
        userCheckboxes.forEach(cb => {
            if (cb.parentElement.style.display !== 'none') {
                cb.checked = this.checked;
            }
        });
        filterForm.submit();
    });

    // User Search functionality
    userSearchInput.addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        const items = document.querySelectorAll('.user-item');
        items.forEach(item => {
            const text = item.innerText.toLowerCase();
            item.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<!-- Firebase Notification Integration -->
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging-compat.js"></script>
<script src="../js/fcm-init.js"></script>

</body>
</html>
