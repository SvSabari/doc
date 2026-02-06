<?php
function sendMail($to, $subject, $message) {
    // Try sending mail
    if (@mail($to, $subject, $message)) {
        return true;
    } else {
        return false;
    }
}
?>
