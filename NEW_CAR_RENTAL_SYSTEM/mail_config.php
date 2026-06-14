<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/vendor/autoload.php";

define("MAIL_USERNAME", "carrentalmmu@gmail.com");
define("MAIL_PASSWORD", "vhvatxmnwmxxxxme");
define("MAIL_FROM_NAME", "KH Car Rental");

function createMailer() {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
    $mail->isHTML(true);
    $mail->CharSet = "UTF-8";

    return $mail;
}

function sendOtpEmail($toEmail, $toName, $otpCode) {
    try {
        $mail = createMailer();

        $safeName = htmlspecialchars($toName, ENT_QUOTES, "UTF-8");
        $safeOtp = htmlspecialchars($otpCode, ENT_QUOTES, "UTF-8");

        $mail->addAddress($toEmail, $toName);
        $mail->Subject = "KH Car Rental Email Verification OTP";

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 24px; border: 1px solid #dce5f2; border-radius: 18px;'>
                <h2 style='color:#1266f1;'>KH Car Rental Email Verification</h2>

                <p>Hello <strong>{$safeName}</strong>,</p>

                <p>Thank you for registering with KH Car Rental.</p>

                <p>Your verification OTP code is:</p>

                <h1 style='letter-spacing: 8px; color:#0d1728;'>{$safeOtp}</h1>

                <p>This OTP will expire in <strong>10 minutes</strong>.</p>

                <p>If you did not create this account, please ignore this email.</p>

                <br>

                <p>Regards,<br>KH Car Rental Team</p>
            </div>
        ";

        $mail->AltBody = "Your KH Car Rental email verification OTP is: {$otpCode}. This OTP will expire in 10 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function sendRegisterOtp($toEmail, $toName, $otpCode) {
    return sendOtpEmail($toEmail, $toName, $otpCode);
}

function sendResetLinkEmail($toEmail, $toName, $resetLink) {
    try {
        $mail = createMailer();

        $safeName = htmlspecialchars($toName, ENT_QUOTES, "UTF-8");
        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, "UTF-8");

        $mail->addAddress($toEmail, $toName);
        $mail->Subject = "KH Car Rental Password Reset Link";

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 24px; border: 1px solid #dce5f2; border-radius: 18px;'>
                <h2 style='color:#1266f1;'>KH Car Rental Password Reset</h2>

                <p>Hello <strong>{$safeName}</strong>,</p>

                <p>We received a request to reset your KH Car Rental account password.</p>

                <p>Please click the button below to reset your password:</p>

                <p style='margin: 28px 0;'>
                    <a href='{$safeLink}'
                       style='background:#1266f1; color:#ffffff; padding:14px 22px; text-decoration:none; border-radius:12px; font-weight:bold; display:inline-block;'>
                       Reset Password
                    </a>
                </p>

                <p>If the button does not work, copy and paste this link into your browser:</p>

                <p style='word-break: break-all; color:#1266f1;'>{$safeLink}</p>

                <p>This link will expire in <strong>15 minutes</strong>.</p>

                <p>If you did not request this password reset, please ignore this email.</p>

                <br>

                <p>Regards,<br>KH Car Rental Team</p>
            </div>
        ";

        $mail->AltBody = "Reset your KH Car Rental password using this link: {$resetLink}. This link will expire in 15 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}