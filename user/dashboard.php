<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");

$user_id = $_SESSION['user_id'];

$works = mysqli_query(
    $conn,
    "SELECT * FROM works WHERE user_id = $user_id ORDER BY created_at DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link id="favicon" rel="icon" type="image/png" href="../favicon_base.png">
    <script src="../js/notification_engine.js"></script>
    <!-- Link JSZip and Docx-Preview -->
    <script src="https://unpkg.com/jszip/dist/jszip.min.js"></script>
    <script src="https://unpkg.com/docx-preview/dist/docx-preview.min.js"></script>

    <style>
        .preview-container {
            width: 100%;
            height: 500px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #dee2e6;
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

<body class="bg-light">

<nav class="navbar navbar-dark bg-primary px-4 shadow-sm">
    <span class="navbar-brand fw-bold mb-0">User Dashboard</span>
    <div class="ms-auto d-flex gap-2">
        <a href="reports.php" class="btn btn-sm btn-light fw-bold"><i class="bi bi-file-earmark-bar-graph me-1"></i> My Reports</a>
        <a href="../auth/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
</nav>

<div class="container mt-4">

    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">My Assigned Works</h5>
        </div>

        <div class="card-body p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Assigned Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>sample doc</th>
                        <th>Action</th>
                        <th>Remarks</th>
                    </tr>
                </thead>

                <tbody>
                <?php
                if (mysqli_num_rows($works) > 0) {
                    $i = 1;
                    while ($w = mysqli_fetch_assoc($works)) {
                ?>
                    <tr>
                        <td><?= $i++ ?></td>

                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark"><?= htmlspecialchars($w['title']) ?></span>
                                <?php if (!empty($w['sample_doc'])): ?>
                                    <a href="javascript:void(0)" 
                                       onclick="openPreview('../uploads/sample_docs/<?= $w['sample_doc'] ?>', '<?= pathinfo($w['sample_doc'], PATHINFO_EXTENSION) ?>')" 
                                       class="text-primary small text-decoration-none mt-1">
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- 12-hour format -->
                        <td><?= date("d-m-Y h:i A", strtotime($w['assigned_datetime'])) ?></td>

                        <td><?= date("d-m-Y h:i A", strtotime($w['due_datetime'])) ?></td>

                        <td>
                            <?php
                            if ($w['status'] == 'pending') {
                                echo "<span class='badge bg-secondary'>Pending</span>";
                            } elseif ($w['status'] == 'submitted') {
                                echo "<span class='badge bg-info'>Submitted</span>";
                            } elseif ($w['status'] == 'accepted') {
                                echo "<span class='badge bg-success'>Accepted</span>";
                            } elseif ($w['status'] == 'rejected') {
                                echo "<span class='badge bg-danger'>Rejected</span>";
                            }
                            ?>
                        </td>
                        
<!-- SAMPLE DOCUMENT COLUMN -->
<td>
    <?php if (!empty($w['sample_doc'])): 
        $samplePath = "../uploads/samples/" . $w['sample_doc'];
        $sampleExt  = pathinfo($w['sample_doc'], PATHINFO_EXTENSION);
    ?>
        <button class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1" 
                onclick="openPreview('<?= $samplePath ?>', '<?= $sampleExt ?>')">
            <i class="bi bi-file-earmark-text"></i> View Sample
        </button>
    <?php else: ?>
        <span class="text-muted small">No Sample</span>
    <?php endif; ?>
</td>

                        <!-- ACTION COLUMN -->
                        <td>
                            <?php if ($w['status'] == 'pending') { ?>
                                <a href="submit.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-primary px-3 rounded-pill fw-bold">
                                    Submit
                                </a>
                            <?php } elseif ($w['status'] == 'rejected') { ?>
                                <a href="submit.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-warning px-3 rounded-pill fw-bold">
                                    Resubmit
                                </a>
                            <?php } else { 
                                // For Submitted, Accepted, Completed - Show View button
                                $sub_check = mysqli_query($conn, "SELECT * FROM submissions WHERE work_id=".$w['id']." ORDER BY submitted_at DESC LIMIT 1");
                                if ($s_action = mysqli_fetch_assoc($sub_check)) {
                                    $actPath = "../uploads/submissions/".$s_action['file_path'];
                                    $actExt = pathinfo($s_action['file_path'], PATHINFO_EXTENSION);
                            ?>
                                    <button class="btn btn-sm btn-success px-3 rounded-pill fw-bold" 
                                            onclick="openPreview('<?= $actPath ?>', '<?= $actExt ?>')">
                                        <i class="bi bi-file-earmark-check me-1"></i> View
                                    </button>
                                <?php } else { ?>
                                    <span class="text-muted">Waiting</span>
                                <?php } ?>
                            <?php } ?>
                        </td>

                        <td>
                            <?php
                            if ($w['status'] == 'rejected' && !empty($w['remarks'])) {
                                echo "<span class='text-danger'>" . htmlspecialchars($w['remarks']) . "</span>";
                            } else {
                                echo "-";
                            }
                            ?>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            No work assigned yet
                        </td>
                    </tr>
                <?php } ?>
                </tbody>

            </table>
        </div>
    </div>

</div>

<!-- Document Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">My Submission Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="preview-container" id="previewContent">
                    <div class="d-flex flex-column align-items-center justify-content-center w-100 py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <span class="mt-2 text-muted">Opening document...</span>
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <a href="#" id="downloadLink" class="btn btn-primary btn-sm" download>
                        <i class="bi bi-download me-1"></i> Download
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Javascript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    const previewContainer = document.getElementById('previewContent');
    const downloadLink = document.getElementById('downloadLink');

    function openPreview(path, ext) {
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
            fetch(path)
                .then(response => response.blob())
                .then(blob => {
                    previewContainer.innerHTML = '';
                    docx.renderAsync(blob, previewContainer)
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
                <p class="mt-3 text-muted">Preview not available for .${ext} files.</p>
            </div>`;
    }

</script>

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
