<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Kolkata');

include '../config/db.php';
include '../config/mail.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);

    if (!empty($email)) {

        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {

            $user_id = $user['id'];

            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", time() + 3600);

            $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id=?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expiry) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $token, $expiry);
            $stmt->execute();

            $resetLink = "http://192.168.29.186/doc/auth/reset_password.php?token=" . $token;

            $subject = "Password Reset";
            $body = "
                <h3>Password Reset</h3>
                <p>Click the button below to reset your password:</p>
                <a href='$resetLink' style='padding:10px 15px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>
                    Reset Password
                </a>
                <p>This link expires in 1 hour.</p>
            ";

            sendMail($email, $subject, $body);

            $message = "Reset link sent to your email.";
        } else {
            $message = "Email not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            width: 350px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            text-align: center;
        }

        .card h2 {
            margin-bottom: 20px;
            color: #333;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background: #2a5298;
            color: white;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #1e3c72;
        }

        .message {
            margin-bottom: 15px;
            color: green;
            font-size: 14px;
        }

        .link {
            margin-top: 15px;
            font-size: 13px;
        }

        .link a {
            color: #2a5298;
            text-decoration: none;
        }

        .link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Forgot Password</h2>

    <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Send Reset Link</button>
    </form>

    <div class="link">
        <a href="/DOC/auth/login.php">Back to Login</a>
    </div>
</div>

</body>
</html>