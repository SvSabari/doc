<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");

$work_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$work = mysqli_query($conn,
    "SELECT * FROM works WHERE id=$work_id AND user_id=$user_id"
);

if (mysqli_num_rows($work) != 1) {
    die("Invalid access");
}

$w = mysqli_fetch_assoc($work);
$msg = "";

if (isset($_POST['submit_work'])) {

    $folder = "../uploads/submissions/";
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $file = time() . "_" . $_FILES['doc']['name'];
    move_uploaded_file($_FILES['doc']['tmp_name'], $folder . $file);

    // Insert submission
    mysqli_query($conn,
        "INSERT INTO submissions (work_id, file_path)
         VALUES ($work_id, '$file')"
    );

    // Update status
    mysqli_query($conn,
        "UPDATE works SET status='submitted' WHERE id=$work_id"
    );

    $msg = "<div class='alert alert-success'>Document submitted successfully</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5" style="max-width:600px;">
    <a href="dashboard.php" class="btn btn-secondary btn-sm mb-3">← Back</a>

    <div class="card shadow p-4">
        <h4><?= $w['title'] ?></h4>
        <p><b>Due:</b> <?= $w['due_datetime'] ?></p>

        <?= $msg ?>

        <?php if ($w['status'] != 'accepted') { ?>
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Upload Document</label>
                <input type="file" name="doc" class="form-control" required>
            </div>

            <button name="submit_work" class="btn btn-primary w-100">
                Submit Document
            </button>
        </form>
        <?php } else { ?>
            <div class="alert alert-success">Document already accepted</div>
        <?php } ?>
    </div>
</div>

</body>
</html>
