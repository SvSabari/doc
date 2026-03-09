<?php
class FCMHelper {
    private $serverKey = "YOUR_FIREBASE_SERVER_KEY";
    private $api_url = "https://fcm.googleapis.com/fcm/send";

    public function sendNotification($tokens, $title, $body, $data = []) {
        if (empty($tokens)) return false;

        $headers = [
            'Authorization: key=' . $this->serverKey,
            'Content-Type: application/json'
        ];

        $payload = [
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'icon' => 'favicon_base.png'
            ],
            'data' => $data
        ];

        if (is_array($tokens)) {
            $payload['registration_ids'] = $tokens;
        } else {
            $payload['to'] = $tokens;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public static function notifyUser($conn, $user_id, $role, $title, $body) {
        $helper = new self();
        $q = @mysqli_query($conn, "SELECT token FROM user_tokens WHERE user_id='$user_id' AND user_role='$role'");
        if (!$q) return ["status" => "error", "message" => "Database error or 'user_tokens' table missing."];

        $tokens = [];
        while ($row = mysqli_fetch_assoc($q)) {
            $tokens[] = $row['token'];
        }

        if (empty($tokens)) return ["status" => "error", "message" => "No registered device tokens found for this user."];
        return $helper->sendNotification($tokens, $title, $body);
    }

    public static function notifyAdmins($conn, $title, $body) {
        $helper = new self();
        $q = @mysqli_query($conn, "SELECT token FROM user_tokens WHERE user_role='admin'");
        if (!$q) return ["status" => "error", "message" => "Database error or 'user_tokens' table missing."];

        $tokens = [];
        while ($row = mysqli_fetch_assoc($q)) {
            $tokens[] = $row['token'];
        }

        if (empty($tokens)) return ["status" => "error", "message" => "No registered device tokens found for admins."];
        return $helper->sendNotification($tokens, $title, $body);
    }
}
?>
