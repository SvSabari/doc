<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../src/Exception.php';
require __DIR__ . '/../src/PHPMailer.php';
require __DIR__ . '/../src/SMTP.php';

function sendMail($to, $subject, $message, $from = null) {

    $mail = new PHPMailer(true);

    try {
        // SMTP Settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'documents.0109@gmail.com'; // CHANGE THIS
        $mail->Password   = 'szkfqyihsghzmyyr'; // CHANGE THIS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Sender
        $mail->setFrom('documents.0109@gmail.com', 'Document Verification System');
        
        if (!empty($from)) {
            $mail->addReplyTo($from);
        }

        // Recipient
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;

    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}