<?php

/**
 * Mail helper using PHPMailer (from local PHPMailer directory)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendWelcomeEmail(string $toEmail, string $firstName, string $tempPassword, string $role, string $guardianName = ''): bool
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $displayName = ($role === 'student' && $guardianName !== '') ? $guardianName : $firstName;
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $displayName);

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to ' . APP_NAME;

        $updatedrole = $role;
        if ($role === 'student') {
            $updatedrole = 'Student (Guardian: ' . $guardianName . ')';
        }
        $roleName = ucfirst(str_replace('_', ' ', $updatedrole));
        $loginUrl = APP_URL . '/login.php';

        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f8fafb;font-family:'Overpass',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:40px auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    <tr><td style="background:#3753a4;padding:30px 40px;text-align:center;">
        <h1 style="color:#ffffff;margin:0;font-size:22px;">புதிய பீல் தமிழ் ஆசிரியர் சங்கம் – கனடா</h1>
        <p style="color:#c7d2fe;margin:6px 0 0;font-size:14px;">New Peel Tamil Teachers Association – Canada</p>
    </td></tr>
    <tr><td style="padding:36px 40px;">
        <h2 style="color:#3753a4;margin:0 0 16px;">Welcome, {$displayName}!</h2>
        <p style="color:#374151;font-size:15px;line-height:1.7;">
            Your account has been created as <strong>{$roleName}</strong>. Please use the credentials below to log in and set your new password.
        </p>
        <table style="width:100%;background:#f0f4ff;border-radius:8px;padding:20px;margin:20px 0;" cellpadding="8">
            <tr><td style="color:#6b7280;font-size:13px;">Email</td><td style="color:#111827;font-weight:600;">{$toEmail}</td></tr>
            <tr><td style="color:#6b7280;font-size:13px;">Temporary Password</td><td style="color:#111827;font-weight:600;letter-spacing:1px;">{$tempPassword}</td></tr>
        </table>
        <p style="text-align:center;margin:28px 0 0;">
            <a href="{$loginUrl}" style="display:inline-block;background:#3753a4;color:#ffffff;text-decoration:none;padding:12px 36px;border-radius:8px;font-weight:600;font-size:15px;">Login Now</a>
        </p>
        <p style="color:#9ca3af;font-size:12px;margin-top:28px;text-align:center;">If you did not expect this email, please ignore it.</p>
    </td></tr>
    <tr><td style="background:#f8fafb;padding:16px 40px;text-align:center;">
        <p style="color:#9ca3af;font-size:12px;margin:0;">&copy; New Peel Tamil Teachers Association – Canada</p>
    </td></tr>
</table>
</body></html>
HTML;

        $mail->AltBody = "Welcome {$displayName}! Your account ({$roleName}) has been created.\nEmail: {$toEmail}\nTemporary Password: {$tempPassword}\nLogin: {$loginUrl}";

        $mail->send();
        return true;
    } catch (Exception $e) {

        error_log('Mail Error: ' . $mail->ErrorInfo);
        return false;
    }
}

function sendPasswordResetEmail(string $toEmail, string $displayName, string $resetUrl): bool
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $displayName);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset – ' . APP_NAME;

        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f8fafb;font-family:'Overpass',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:40px auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    <tr><td style="background:#3753a4;padding:30px 40px;text-align:center;">
        <h1 style="color:#ffffff;margin:0;font-size:22px;">புதிய பீல் தமிழ் ஆசிரியர் சங்கம் – கனடா</h1>
        <p style="color:#c7d2fe;margin:6px 0 0;font-size:14px;">New Peel Tamil Teachers Association – Canada</p>
    </td></tr>
    <tr><td style="padding:36px 40px;">
        <h2 style="color:#3753a4;margin:0 0 16px;">Password Reset</h2>
        <p style="color:#374151;font-size:15px;line-height:1.7;">
            Hi <strong>{$displayName}</strong>, we received a request to reset your password. Click the button below to set a new password.
        </p>
        <p style="text-align:center;margin:28px 0;">
            <a href="{$resetUrl}" style="display:inline-block;background:#3753a4;color:#ffffff;text-decoration:none;padding:12px 36px;border-radius:8px;font-weight:600;font-size:15px;">Reset Password</a>
        </p>
        <p style="color:#6b7280;font-size:13px;line-height:1.6;">
            This link will expire in <strong>1 hour</strong>. If you did not request this, please ignore this email — your password will remain unchanged.
        </p>
        <p style="color:#9ca3af;font-size:12px;margin-top:20px;word-break:break-all;">
            If the button doesn't work, copy and paste this link into your browser:<br>{$resetUrl}
        </p>
    </td></tr>
    <tr><td style="background:#f8fafb;padding:16px 40px;text-align:center;">
        <p style="color:#9ca3af;font-size:12px;margin:0;">&copy; New Peel Tamil Teachers Association – Canada</p>
    </td></tr>
</table>
</body></html>
HTML;

        $mail->AltBody = "Hi {$displayName}, click the link below to reset your password:\n{$resetUrl}\n\nThis link expires in 1 hour.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo 'Mailer Error: ' . $mail->ErrorInfo;
        error_log('Mail Error: ' . $mail->ErrorInfo);
        return false;
    }
}
