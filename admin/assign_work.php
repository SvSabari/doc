<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
include("../src/FCMHelper.php");
include("../src/EmailHelper.php");
$msg = "";

// Fetch active users
$users = mysqli_query($conn, "SELECT id, name, email FROM users WHERE status='active'");

// System-generated assigned datetime
$currentDateTime = date("Y-m-d\TH:i");

if (isset($_POST['assign_work'])) {

    $user_ids = $_POST['user_ids'] ?? [];
    if (empty($user_ids)) {
        $msg = "<div class='alert alert-warning'>Please select at least one user.</div>";
    } else {
        $title    = mysqli_real_escape_string($conn, $_POST['title']);
        $assigned = $_POST['assigned_datetime'];
        $due      = $_POST['due_datetime'];

        $sample_doc = NULL;

        // Optional sample upload
        if (!empty($_FILES['sample_doc']['name'])) {
            $folder = "../uploads/samples/";
            if (!is_dir($folder)) mkdir($folder, 0777, true);

            $sample_doc = time() . "_" . basename($_FILES['sample_doc']['name']);
            move_uploaded_file($_FILES['sample_doc']['tmp_name'], $folder . $sample_doc);
        }

        $success_count = 0;
        $error_count = 0;

        foreach ($user_ids as $uid) {
            $uid = mysqli_real_escape_string($conn, $uid);
            $sql = "INSERT INTO works 
                    (user_id, title, assigned_datetime, due_datetime, sample_doc, status) 
                    VALUES 
                    ('$uid', '$title', '$assigned', '$due', '$sample_doc', 'pending')";
            
            if (mysqli_query($conn, $sql)) {
                $success_count++;
                
                // Professional FCM Notification
                $notif_title = "New Work Assigned";
                $notif_body = "Admin assigned a document verification task: " . $title;
                FCMHelper::notifyUser($conn, $uid, 'user', $notif_title, $notif_body);

                // Professional Email Notification
                EmailHelper::notifyUser($conn, $uid, 'user', $notif_title, $notif_body);
            } else {
                $error_count++;
            }
        }

        if ($error_count == 0) {
            $msg = "<div class='alert alert-success'>Work assigned successfully to $success_count user(s).</div>";
        } else {
            $msg = "<div class='alert alert-info'>Assigned to $success_count user(s). Errors occurred for $error_count user(s).</div>";
        }
    }
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Work</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .user-selection-box {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 10px;
            background-color: #fff;
        }
        .user-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand">Admin Panel</span>
    <div>
        <a href="dashboard.php" class="btn btn-sm btn-secondary">Dashboard</a>
    </div>
</nav>

<div class="container mt-4" style="max-width:720px;">
    <div class="card shadow p-4">
        <h4>Assign Work</h4>
        <?= $msg ?>

        <form method="post" enctype="multipart/form-data">

            <div class="mb-3">
                <label class="form-label d-flex justify-content-between">
                    Select Users
                    <span>
                        <input type="checkbox" id="selectAll" class="form-check-input">
                        <label for="selectAll" class="form-check-label small">Select All</label>
                    </span>
                </label>
                
                <!-- NEW: Search Bar -->
                <input type="text" id="userSearch" class="form-control mb-2 form-control-sm" placeholder="Search user by name or email...">

                <div class="user-selection-box" id="userList">
                    <?php while ($u = mysqli_fetch_assoc($users)) { ?>
                        <div class="form-check user-item">
                            <input class="form-check-input user-checkbox" type="checkbox" name="user_ids[]" value="<?= $u['id'] ?>" id="user_<?= $u['id'] ?>">
                            <label class="form-check-label w-100" for="user_<?= $u['id'] ?>">
                                <?= $u['name'] ?> (<?= $u['email'] ?>)
                            </label>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="mb-3">
                <label>Work Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Assigned Date & Time</label>
                <input type="datetime-local" class="form-control" value="<?= $currentDateTime ?>" disabled>
                <input type="hidden" name="assigned_datetime" value="<?= $currentDateTime ?>">
            </div>

            <div class="mb-3">
                <label>Due Date & Time</label>
                <input type="datetime-local" name="due_datetime" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Sample Document (Optional)</label>
                <input type="file" name="sample_doc" class="form-control">
            </div>

            <button name="assign_work" class="btn btn-success w-100">
                Assign Work
            </button>
        </form>
    </div>


</div>

<script>
    // Select All functionality
    document.getElementById('selectAll').addEventListener('change', function() {
        // Only select checkboxes that are currently visible
        const visibleCheckboxes = document.querySelectorAll('.user-item:not([style*="display: none"]) .user-checkbox');
        visibleCheckboxes.forEach(cb => cb.checked = this.checked);
    });

    // Search functionality
    document.getElementById('userSearch').addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        const items = document.querySelectorAll('.user-item');
        
        items.forEach(item => {
            const text = item.innerText.toLowerCase();
            if (text.includes(filter)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>

<!-- Firebase Notification Integration -->
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging-compat.js"></script>
<script src="../js/fcm-init.js"></script>

</body>
</html>
