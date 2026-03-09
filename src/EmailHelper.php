<?php
include_once(__DIR__ . "/../config/mail.php");

class EmailHelper {
    
    public static function notifyUser($conn, $user_id, $role, $subject, $body) {
        $email = null;
        if ($role === 'admin') {
            $res = mysqli_query($conn, "SELECT email FROM admins WHERE id='$user_id'");
            if ($row = mysqli_fetch_assoc($res)) $email = $row['email'];
        } else {
            $res = mysqli_query($conn, "SELECT email FROM users WHERE id='$user_id'");
            if ($row = mysqli_fetch_assoc($res)) $email = $row['email'];
        }

        if ($email) {
            return sendMail($email, $subject, $body);
        }
        return false;
    }

    public static function notifyAdmins($conn, $subject, $body) {
        $res = mysqli_query($conn, "SELECT email FROM admins");
        $success = true;
        while ($row = mysqli_fetch_assoc($res)) {
            if (!sendMail($row['email'], $subject, $body)) {
                $success = false;
            }
        }
        return $success;
    }
}
?>
