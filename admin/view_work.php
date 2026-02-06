<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");

$works = mysqli_query($conn,
    "SELECT w.*, u.name, u.email
     FROM works w
     JOIN users u ON w.user_id = u.id
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Document Submissions</h2>
            <p class="text-secondary mb-0">Review and verify user uploaded documents.</p>
        </div>
        <div>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-secondary"></i>
                </span>
                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Search users or titles...">
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
                            if ($w['status'] == 'pending') $statusClass = 'bg-warning text-dark';
                            
                            // File Info
                            $filePath = $s ? "../uploads/submissions/".$s['file_path'] : '';
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
                                    <button class="btn btn-light btn-sm border d-flex align-items-center gap-2" 
                                            onclick="openPreview('<?= $filePath ?>', '<?= $fileExt ?>')">
                                        <i class="bi bi-eye text-primary"></i> Preview
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
<script>
    // Search Filter
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const table = document.getElementById('submissionsTable');
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) { // Start at 1 to skip header
            const row = rows[i];
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(searchText) ? '' : 'none';
        }
    });

    // Preview Modal Logic
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    const previewContainer = document.getElementById('previewContent');
    const downloadLink = document.getElementById('downloadLink');

    function openPreview(path, ext) {
        // Clear previous content and show loader
        previewContainer.innerHTML = `
            <div class="d-flex flex-column align-items-center justify-content-center w-100 py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <span class="mt-2 text-muted">Opening document...</span>
            </div>`;
        
        const validImages = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        const extension = ext.toLowerCase();
        
        downloadLink.href = path;
        previewModal.show();

        if (validImages.includes(extension)) {
            previewContainer.innerHTML = `<img src="${path}" alt="Document Preview">`;
        } else if (extension === 'pdf') {
            previewContainer.innerHTML = `<iframe src="${path}"></iframe>`;
        } else if (extension === 'docx') {
            // Fetch and render docx
            fetch(path)
                .then(response => response.blob())
                .then(blob => {
                    previewContainer.innerHTML = ''; // Clear loader
                    docx.renderAsync(blob, previewContainer)
                        .then(() => console.log("docx: finished"))
                        .catch(err => {
                            console.error(err);
                            showError(extension);
                        });
                })
                .catch(err => {
                    console.error(err);
                    showError(extension);
                });
        } else {
            showError(extension);
        }
    }

    function showError(ext) {
        previewContainer.innerHTML = `
            <div class="text-center p-5 w-100">
                <i class="bi bi-file-earmark-text display-1 text-secondary"></i>
                <p class="mt-3 text-muted">Preview not available or failed for .${ext} files.</p>
            </div>`;
    }
</script>

</body>
</html>
