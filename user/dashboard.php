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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-primary px-4">
    <span class="navbar-brand">User Dashboard</span>
    <a href="../auth/logout.php" class="btn btn-sm btn-light">Logout</a>
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

                        <td><?= htmlspecialchars($w['title']) ?></td>

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

                        <!-- SUBMIT BUTTON LOGIC -->
                        <td>
                            <?php if ($w['status'] == 'pending') { ?>
                                <a href="submit.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-primary">
                                    Submit
                                </a>
                            <?php } elseif ($w['status'] == 'rejected') { ?>
                                <a href="submit.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-warning">
                                    Resubmit
                                </a>
                            <?php } elseif ($w['status'] == 'submitted') { ?>
                                <span class="text-muted">Waiting</span>
                            <?php } else { ?>
                                <span class="text-success">Completed</span>
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

</body>
</html>
