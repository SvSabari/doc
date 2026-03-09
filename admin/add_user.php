<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
include("../config/mail.php");

$msg = "";
$userCreated = false;

if (isset($_POST['add_user'])) {

    $name  = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));

    // Get admin email for sending
    $admin_id = $_SESSION['admin_id'];
    // AUTO-FIX: Ensure 'email' column exists in admins table
    $check_admin_email_col = mysqli_query($conn, "SHOW COLUMNS FROM admins LIKE 'email'");
    if (mysqli_num_rows($check_admin_email_col) == 0) {
        mysqli_query($conn, "ALTER TABLE admins ADD COLUMN email VARCHAR(100) AFTER username");
    }

    $admin_query = mysqli_query($conn, "SELECT email FROM admins WHERE id='$admin_id' LIMIT 1");
    if ($admin_query && mysqli_num_rows($admin_query) > 0) {
        $admin_email = mysqli_fetch_assoc($admin_query)['email'];
    }

    // Check if user already exists
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $msg = "<div class='alert alert-danger'>User with this email already exists</div>";
    } else {
        // AUTO-FIX: Ensure 'email' column exists (it should, but safety first)
        $check_email_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'email'");
        if (mysqli_num_rows($check_email_col) == 0) {
            mysqli_query($conn, "ALTER TABLE users ADD COLUMN email VARCHAR(100) NOT NULL UNIQUE AFTER name");
        }

        // AUTO-FIX: Add 'must_reset_password' column if it doesn't exist
        $check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'must_reset_password'");
        if (mysqli_num_rows($check_col) == 0) {
            mysqli_query($conn, "ALTER TABLE users ADD COLUMN must_reset_password TINYINT(1) DEFAULT 0 AFTER status");
        }

        // Generate random temporary password
        $temp_password = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 10);
        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

        // Insert user (active with temporary password and reset flag)
        mysqli_query($conn,
            "INSERT INTO users (name, email, password, status, must_reset_password)
             VALUES ('$name', '$email', '$hashed_password', 'active', 1)"
        );

        $user_id = mysqli_insert_id($conn);

        // Mail content
        $message = "
            <h3>Document Verification System</h3>
            <p>Hello <b>$name</b>,</p>
            <p>Your account has been created successfully.</p>
            <p>Here are your login credentials:</p>
            <p><b>Email:</b> $email</p>
            <p><b>Temporary Password:</b> $temp_password</p>
            <p>Please login and change your password for security.</p>
        ";

        // Try sending mail. use sendMail helper, passing admin email as sender when available
        if (!empty($admin_email)) {
            $mailStatus = sendMail($email, "Your Account Credentials", $message, $admin_email);
        } else {
            $mailStatus = sendMail($email, "Your Account Credentials", $message);
        }

        $userCreated = true;

        $mailFeedback = $mailStatus 
            ? "<div class='badge bg-success mb-2 p-2 w-100'>Sent to $email successfully</div>" 
            : "<div class='badge bg-warning text-dark mb-2 p-2 w-100'>Note: Manual sharing required (Email failed)</div>";

        // Simplified success message focusing strictly on the temporary password
        $msg = "
        <div class='alert alert-success border-2 shadow-sm'>
            <div class='d-flex align-items-center mb-2'>
                <h5 class='mb-0'>✅ User Created Successfully</h5>
            </div>
            $mailFeedback
            <div class='alert alert-info py-2 small mb-3'>
                <i class='bi bi-info-circle me-1'></i> <b>Tip:</b> If the email doesn't arrive in minutes, please ask the user to check their <b>Spam/Junk</b> folder.
            </div>
            <p class='text-muted small mb-3'>A temporary password has been emailed to the user. You can also copy it here:</p>
            
            <div class='bg-white p-3 rounded border mb-3'>
                <div class='mb-0'>
                    <label class='text-uppercase fw-bold text-muted' style='font-size: 0.7rem;'>Temporary Password</label>
                    <div class='input-group'>
                        <input type='text' id='tempPass' class='form-control fw-bold text-primary font-monospace border-0 bg-light' value='$temp_password' readonly>
                        <button class='btn btn-outline-primary active' onclick='copyPassword()'>Copy</button>
                    </div>
                </div>
            </div>

            <div class='d-grid'>
                <a href='add_user.php' class='btn btn-success'>Create Another User</a>
            </div>

            <script>
            function copyPassword() {
                var copyText = document.getElementById(\"tempPass\");
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(copyText.value);
                alert(\"Password copied to clipboard!\");
            }
            </script>
        </div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4 py-3">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold d-flex align-items-center">
            <i class="bi bi-person-plus-fill me-2"></i> User Management
        </span>
        <div class="d-flex align-items-center">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-4">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
        </div>
    </div>
</nav>

<div class="container mt-5" style="max-width:550px;">
    <div class="card shadow-sm border-0 p-4" style="border-radius: 1.25rem;">
        <h3 class="fw-bold text-dark mb-1">Create New Account</h3>
        <p class="text-secondary small mb-4">Credentials will be emailed to the provided address.</p>

        <?= $msg ?>

        <?php if (!$userCreated): ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label small fw-bold text-uppercase text-muted tracking-wide">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" name="name" class="form-control border-start-0 ps-0" placeholder="e.g. John Doe" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-uppercase text-muted tracking-wide">Mail ID (Email)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="e.g. john@example.com" required>
                </div>
                <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i> Temporary passwords are sent instantly.</div>
            </div>

            <button type="submit" name="add_user" class="btn btn-primary w-100 py-3 fw-bold rounded-pill">
                <i class="bi bi-person-plus me-1"></i> Confirm & Send Password
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Quick Stats Section -->
    <?php
    $view = isset($_GET['view']) ? $_GET['view'] : '';
    
    $today_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
    $week_q  = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
    $month_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");

    $today_users = mysqli_fetch_assoc($today_q)['count'];
    $week_users  = mysqli_fetch_assoc($week_q)['count'];
    $month_users = mysqli_fetch_assoc($month_q)['count'];

    // Fetch User List if a view is selected
    $user_list_data = null;
    if ($view) {
        $where_view = "1=1";
        if ($view == 'day') $where_view = "DATE(created_at) = CURDATE()";
        elseif ($view == 'week') $where_view = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
        elseif ($view == 'month') $where_view = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        
        $user_list_data = mysqli_query($conn, "SELECT name, email, created_at FROM users WHERE $where_view ORDER BY created_at DESC");
    }
    ?>
    <style>
        .stat-card { transition: all 0.3s ease; text-decoration: none; display: block; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 10px rgba(0,0,0,0.1) !important; }
        .stat-card.active { border: 2px solid #0d6efd !important; background-color: #f8f9ff !important; }
    </style>

    <div class="row g-3 mt-4 no-print">
        <div class="col-4">
            <a href="?view=day" class="card stat-card shadow-sm border-0 bg-white text-center p-3 <?= ($view == 'day') ? 'active' : '' ?>" style="border-radius: 12px;">
                <div class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem;">Added Today</div>
                <h4 class="mb-0 fw-bold text-primary"><?= $today_users ?></h4>
            </a>
        </div>
        <div class="col-4">
            <a href="?view=week" class="card stat-card shadow-sm border-0 bg-white text-center p-3 <?= ($view == 'week') ? 'active' : '' ?>" style="border-radius: 12px;">
                <div class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem;">This Week</div>
                <h4 class="mb-0 fw-bold text-success"><?= $week_users ?></h4>
            </a>
        </div>
        <div class="col-4">
            <a href="?view=month" class="card stat-card shadow-sm border-0 bg-white text-center p-3 <?= ($view == 'month') ? 'active' : '' ?>" style="border-radius: 12px;">
                <div class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem;">This Month</div>
                <h4 class="mb-0 fw-bold text-dark"><?= $month_users ?></h4>
            </a>
        </div>
    </div>

    <!-- User List Display -->
    <?php if ($user_list_data): ?>
    <div class="card shadow-sm border-0 mt-4" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-people me-2"></i> 
                Users added <?= ($view == 'day') ? 'Today' : (($view == 'week') ? 'this Week' : 'this Month') ?>
            </h6>
            <a href="add_user.php" class="btn btn-link btn-sm text-decoration-none text-muted">Clear List</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Name</th>
                            <th>Email</th>
                            <th class="pe-3 text-end">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($user_list_data) > 0): ?>
                            <?php while($u = mysqli_fetch_assoc($user_list_data)): ?>
                                <tr>
                                    <td class="ps-3 fw-bold"><?= htmlspecialchars($u['name']) ?></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td class="pe-3 text-end text-muted small"><?= date('d M, H:i', strtotime($u['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-3 text-muted">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<!-- Firebase Notification Integration -->
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging-compat.js"></script>
<script src="../js/fcm-init.js"></script>

</body>
</html>
