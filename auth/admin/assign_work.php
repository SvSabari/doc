<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
$msg = "";

// Fetch active users
$users = mysqli_query($conn, "SELECT id, name, email FROM users WHERE status='active'");

// System-generated assigned datetime
$currentDateTime = date("Y-m-d\TH:i");

if (isset($_POST['assign_work'])) {

    $user_id  = $_POST['user_id'];
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

    $sql = "INSERT INTO works 
            (user_id, title, assigned_datetime, due_datetime, sample_doc, status) 
            VALUES 
            ('$user_id', '$title', '$assigned', '$due', '$sample_doc', 'pending')";

    if (mysqli_query($conn, $sql)) {
        $msg = "<div class='alert alert-success'>Work assigned successfully</div>";
    } else {
        $msg = "<div class='alert alert-danger'>DB Error: " . mysqli_error($conn) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Work</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand">Admin Panel</span>
    <a href="dashboard.php" class="btn btn-sm btn-secondary">Dashboard</a>
</nav>

<div class="container mt-4" style="max-width:720px;">
    <div class="card shadow p-4">
        <h4>Assign Work</h4>
        <?= $msg ?>

        <form method="post" enctype="multipart/form-data">

            <div class="mb-3">
                <label>User</label>
                <select name="user_id" class="form-select" required>
                    <option value="">Select User</option>
                    <?php while ($u = mysqli_fetch_assoc($users)) { ?>
                        <option value="<?= $u['id'] ?>">
                            <?= $u['name'] ?> (<?= $u['email'] ?>)
                        </option>
                    <?php } ?>
                </select>
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

</body>
</html>
