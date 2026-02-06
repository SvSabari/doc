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
    <title>View Submissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand">View Submissions</span>
    <a href="dashboard.php" class="btn btn-sm btn-secondary">Dashboard</a>
</nav>

<div class="container mt-4">

<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>User</th>
            <th>Work</th>
            <th>Due</th>
            <th>Document</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
    <?php while ($w = mysqli_fetch_assoc($works)) {

        $sub = mysqli_query($conn,
            "SELECT * FROM submissions WHERE work_id=".$w['id']." ORDER BY submitted_at DESC LIMIT 1"
        );
        $s = mysqli_fetch_assoc($sub);
    ?>
    <tr>
        <td><?= $w['name'] ?><br><small><?= $w['email'] ?></small></td>
        <td><?= $w['title'] ?></td>
        <td><?= $w['due_datetime'] ?></td>
        <td>
            <?php if ($s) { ?>
                <a href="../uploads/submissions/<?= $s['file_path'] ?>" target="_blank">View</a>
            <?php } else { echo "Not submitted"; } ?>
        </td>
        <td><?= ucfirst($w['status']) ?></td>
        <td>
            <a href="verify.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-primary">Verify</a>
        </td>
    </tr>
    <?php } ?>
    </tbody>
</table>

</div>
</body>
</html>
