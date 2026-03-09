<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");

// Search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clauses = ["1=1"];

if (!empty($search)) {
    $s_val = mysqli_real_escape_string($conn, $search);
    $is_date_search = false;

    // 1. Check for Date Ranges (e.g., "feb 9 to feb 19")
    if (stripos($search, ' to ') !== false) {
        $parts = explode(' to ', strtolower($search));
        if (count($parts) == 2) {
            $start_dt = strtotime(trim($parts[0]));
            $end_dt = strtotime(trim($parts[1]));
            if ($start_dt && $end_dt) {
                $start_str = date('Y-m-d', $start_dt);
                $end_str = date('Y-m-d', $end_dt);
                $where_clauses[] = "DATE(w.created_at) BETWEEN '$start_str' AND '$end_str'";
                $is_date_search = true;
            }
        }
    }

    // 2. Check for Specific Date patterns (e.g., "Feb 9", "9 Feb", "2024-02-09")
    if (!$is_date_search) {
        // Try parsing the entire search string as a date
        $timestamp = strtotime($search);
        
        // Refine detection: if it's just a number, it's likely a title/ID, not a date (unless it's a year)
        $is_just_number = preg_match('/^\d+$/', $search);
        $is_year = preg_match('/^\d{4}$/', $search);

        if ($timestamp && (!$is_just_number || $is_year)) {
            $date_part = date('Y-m-d', $timestamp);
            
            // Check if user provided only a year
            if ($is_year) {
                $where_clauses[] = "YEAR(w.created_at) = '$search'";
                $is_date_search = true;
            }
            // Check if user provided only a month name
            elseif (preg_match('/^(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)/i', $search) && !preg_match('/\d/', $search)) {
                $month_num = date('n', $timestamp);
                $where_clauses[] = "MONTH(w.created_at) = '$month_num' AND YEAR(w.created_at) = YEAR(CURRENT_DATE)";
                $is_date_search = true;
            }
            // Otherwise, treat as a specific day
            else {
                // If it's a month + day (like "Feb 9"), we should match that day in any year or current year?
                // Usually "Feb 9" implies current year if year is omitted.
                $where_clauses[] = "DATE(w.created_at) = '$date_part'";
                $is_date_search = true;
            }
        }
    }

    // 3. Fallback or Additional Text Search
    if (!$is_date_search) {
        // Handle shortcuts like "this week", "this month"
        if (strtolower($search) == 'week' || strtolower($search) == 'this week') {
            $where_clauses[] = "YEARWEEK(w.created_at, 1) = YEARWEEK(CURRENT_DATE, 1)";
        }
        elseif (strtolower($search) == 'month' || strtolower($search) == 'this month') {
            $where_clauses[] = "MONTH(w.created_at) = MONTH(CURRENT_DATE) AND YEAR(w.created_at) = YEAR(CURRENT_DATE)";
        }
        else {
            // Default text search for name or title
            $where_clauses[] = "(u.name LIKE '%$s_val%' OR w.title LIKE '%$s_val%')";
        }
    }
}

$where_sql = implode(" AND ", $where_clauses);

$works = mysqli_query($conn,
    "SELECT w.*, u.name, u.email
     FROM works w
     JOIN users u ON w.user_id = u.id
     WHERE $where_sql
     ORDER BY w.created_at DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Submissions | Admin</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Link JSZip and Docx-Preview -->
    <script src="https://unpkg.com/jszip/dist/jszip.min.js"></script>
    <script src="https://unpkg.com/docx-preview/dist/docx-preview.min.js"></script>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
            border-radius: 12px;
        }
        .table th {
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            background-color: #fff;
            border-bottom: 2px solid #edf2f9;
        }
        .table td {
            vertical-align: middle;
            color: #495057;
        }
        .user-info h6 {
            margin: 0;
            font-weight: 600;
            color: #212529;
        }
        .user-info small {
            color: #95aac9;
        }
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            border-radius: 50rem;
        }
        .btn-action {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
        }
        /* Modal Preview */
        .preview-container {
            width: 100%;
            height: 500px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            overflow: hidden;
        }
        .preview-container iframe, .preview-container img, .preview-container .docx-wrapper {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border: none;
            overflow-y: auto;
        }
        .preview-container .docx-wrapper {
            background: #fff;
            padding: 20px !important;
        }
    </style>
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 py-3">
    <div class="container-fluid">
        <span class="navbar-brand d-flex align-items-center gap-2">
            <i class="bi bi-shield-check fs-4"></i>
            <span>DocVerify <small class="text-secondary ms-1">Admin</small></span>
        </span>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">
    
    <!-- Page Header -->
    <div class="row align-items-center mb-4">
        <div class="col-lg-4">
            <h2 class="fw-bold text-dark mb-1">Document Submissions</h2>
            <p class="text-secondary mb-0">Review and verify user uploaded documents.</p>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body bg-white rounded p-3">
                    <form method="GET" class="row g-3">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" name="search" id="smart_search" class="form-control border-start-0" 
                                       placeholder="Search by name, title, date (YYYY-MM-DD), month, or 'week'..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 fw-medium">
                                Search
                            </button>
                            <a href="view_work.php" class="btn btn-outline-secondary w-100 fw-medium">Clear</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Card -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="submissionsTable">
                    <thead>
                        <tr>
                            <th class="ps-4">User</th>
                            <th>Document Title</th>
                            <th>Submission Date</th>
                            <th>Status</th>
                            <th>Evidence</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    if (mysqli_num_rows($works) > 0) {
                        while ($w = mysqli_fetch_assoc($works)) { 

                            // Fetch latest submission
                            $sub = mysqli_query($conn,
                                "SELECT * FROM submissions WHERE work_id=".$w['id']." ORDER BY submitted_at DESC LIMIT 1"
                            );
                            $s = mysqli_fetch_assoc($sub);
                            
                            // Status Badge Logic
                            $statusClass = 'bg-secondary';
                            if ($w['status'] == 'completed') $statusClass = 'bg-success';
                            if ($w['status'] == 'accepted') $statusClass = 'bg-success';
                            if ($w['status'] == 'rejected') $statusClass = 'bg-danger';
                            if ($w['status'] == 'pending') $statusClass = 'bg-warning text-dark';
                            if ($w['status'] == 'submitted') $statusClass = 'bg-info text-dark';
                            
                            // File Info
                            $filePath = '';if ($s && !empty($s['file_path'])) {
                                $filePath = "../uploads/submissions/" . trim($s['file_path']);
                            }
                            $fileExt = $s ? pathinfo($s['file_path'], PATHINFO_EXTENSION) : '';
                    ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center text-primary fw-bold me-3" style="width: 40px; height: 40px;">
                                        <?= strtoupper(substr($w['name'], 0, 1)) ?>
                                    </div>
                                    <div class="user-info">
                                        <h6><?= htmlspecialchars($w['name']) ?></h6>
                                        <small><?= htmlspecialchars($w['email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-medium text-dark"><?= htmlspecialchars($w['title']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span><?= date('M d, Y', strtotime($w['created_at'])) ?></span>
                                    <small class="text-muted"><?= date('h:i A', strtotime($w['created_at'])) ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill <?= $statusClass ?> status-badge">
                                    <?= ucfirst($w['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($s): ?>
                                    <button class="btn btn-sm btn-primary"
                                     onclick="openPreview('<?= $filePath ?>')">
                                     Preview
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted fst-italic"><small>Not submitted</small></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="verify.php?id=<?= $w['id'] ?>" class="btn btn-primary btn-action">
                                    Verify
                                </a>
                            </td>
                        </tr>
                    <?php 
                        } 
                    } else {
                    ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No submissions found.</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Document Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Document Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="preview-container" id="previewContent">
                    <!-- Content injected by JS -->
                    <div class="d-flex flex-column align-items-center justify-content-center w-100 py-5" id="loader">
                        <div class="spinner-border text-primary" role="status"></div>
                        <span class="mt-2 text-muted">Opening document...</span>
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <a href="#" id="downloadLink" class="btn btn-outline-primary btn-sm" download>
                        <i class="bi bi-download me-1"></i> Download File
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Javascript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>

    // Preview Modal Logic
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    const previewContainer = document.getElementById('previewContent');
    const downloadLink = document.getElementById('downloadLink');
    const smartSearch = document.getElementById('smart_search');
    const submissionsTable = document.getElementById('submissionsTable');

    // Real-time Table Filtering
    smartSearch.addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        const rows = submissionsTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        for (let row of rows) {
            if (row.cells.length <= 1) continue;

            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        }
    });

    function openPreview(path) {

    previewModal.show();
    downloadLink.href = path;

    previewContainer.innerHTML = `
        <div class="text-center p-5 w-100">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted">Loading preview...</p>
        </div>`;

    // Automatically detect extension
    const extension = path.split('.').pop().toLowerCase();

    const images = ['jpg','jpeg','png','gif','webp'];

    // IMAGE
    if (images.includes(extension)) {
        previewContainer.innerHTML =
            `<img src="${path}" style="max-width:100%; max-height:100%;">`;
    }

    // PDF
    else if (extension === 'pdf') {
        previewContainer.innerHTML =
            `<iframe src="${path}" width="100%" height="100%" style="border:none;"></iframe>`;
    }

    // DOCX
    else if (extension === 'docx') {

        fetch(path)
            .then(res => res.blob())
            .then(blob => {
                previewContainer.innerHTML = '';
                docx.renderAsync(blob, previewContainer);
            })
            .catch(() => {
                previewContainer.innerHTML = `
                    <div class="text-center p-5">
                        <p class="text-danger">Unable to preview Word file.</p>
                    </div>`;
            });
    }

    // EXCEL (cannot preview locally)
    else if (extension === 'xlsx' || extension === 'xls') {

        previewContainer.innerHTML = `
            <div class="text-center p-5">
                <i class="bi bi-file-earmark-excel display-4 text-success"></i>
                <p class="mt-3">Excel preview not supported on localhost.</p>
                <p class="small text-muted">Please download the file.</p>
            </div>`;
    }

    else {
        previewContainer.innerHTML = `
            <div class="text-center p-5">
                <p>No preview available for this file type.</p>
            </div>`;
    }
}

</script>

<!-- Firebase Notification Integration -->
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging-compat.js"></script>
<script src="../js/fcm-init.js"></script>

</body>
</html>
