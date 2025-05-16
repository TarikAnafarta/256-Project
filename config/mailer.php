<?php
// config/mailer.php

require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail(string $to, string $code): bool {
    $mail = new PHPMailer(true);
    try {
        // ---- Direct Gmail SMTP ----
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ayalqasimli@gmail.com';    // your Gmail address
        $mail->Password   = 'Ayal2006?';    // 16‑char App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // ---- Headers & Body ----
        $mail->setFrom('your.account@gmail.com', 'Sustainability Shop');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'Your Verification Code';
        $mail->Body    = "<p>Your code is: <strong>{$code}</strong></p>";

        return $mail->send();
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}
