<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

if (!isset($_GET['token'])) {
    die("Invalid request.");
}

$token = trim($_GET['token']);

$stmt = $conn->prepare("SELECT * FROM password_resets WHERE token=?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die("Invalid token.");
}

if (strtotime($row['expiry']) < time()) {
    die("Token expired.");
}

$user_id = $row['user_id'];
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param("si", $new_password, $user_id);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $success = true;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            width: 380px;
            padding: 45px 35px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
            color: #fff;
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 13px;
            opacity: 0.8;
            margin-bottom: 25px;
        }

        .success-message {
            background: rgba(0, 255, 150, 0.15);
            border: 1px solid rgba(0,255,150,0.4);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        input {
            width: 100%;
            padding: 14px 45px 14px 14px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }

        input:focus {
            box-shadow: 0 0 0 2px rgba(255,255,255,0.6);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            width: 20px;
            height: 20px;
            fill: #555;
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #ffffff;
            color: #333;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .back-link {
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
        }

        .back-link a {
            color: #fff;
            text-decoration: none;
            opacity: 0.85;
        }

        .back-link a:hover {
            opacity: 1;
            text-decoration: underline;
        }

        @media(max-width: 420px) {
            .card {
                width: 90%;
            }
        }
    </style>
</head>

<body>

<div class="card">
    <h2>Reset Password</h2>
    <div class="subtitle">Enter your new secure password below</div>

    <?php if($success): ?>
        <div class="success-message">
            ✅ Password successfully changed!
        </div>
        <script>
            setTimeout(function(){
                window.location.href = "/DOC/auth/login.php";
            }, 2000);
        </script>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <input type="password" name="password" id="password" placeholder="New Password" required>

            <svg class="toggle-password" onclick="togglePassword()" viewBox="0 0 24 24">
                <path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 
                12a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 
                3 0 1 0 0 6 3 3 0 0 0 0-6z"/>
            </svg>
        </div>

        <button type="submit">Update Password</button>
    </form>

    <div class="back-link">
        <a href="/DOC/auth/login.php">← Back to Login</a>
    </div>
</div>

<script>
function togglePassword() {
    const field = document.getElementById("password");
    field.type = field.type === "password" ? "text" : "password";
}
</script>

</body>
</html>