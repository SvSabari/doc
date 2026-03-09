<?php
session_start();
include("../config/db.php");
include("../src/FCMHelper.php");

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['admin_id'];
$role = isset($_SESSION['user_id']) ? 'user' : 'admin';

$title = "Test Notification";
$body = "This is a test notification from your Document Verification System!";

$result = FCMHelper::notifyUser($conn, $user_id, $role, $title, $body);

if ($result) {
    echo json_encode(["status" => "success", "message" => "Notification sent", "debug" => $result]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to send notification. Check if you have registered a token and added your Server Key in FCMHelper.php"]);
}
?>
