<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env if not already loaded
if (!defined('MAIL_HOST')) {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            [$key, $val] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($val);
        }
    }
    define('MAIL_HOST',     $_ENV['MAIL_HOST']     ?? 'smtp.gmail.com');
    define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
    define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
    define('MAIL_PORT',     (int)($_ENV['MAIL_PORT'] ?? 587));
    define('MAIL_FROM',     $_ENV['MAIL_FROM']     ?? '');
    define('MAIL_NAME',     $_ENV['MAIL_NAME']     ?? 'SPTA System');
}

function createMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;
    $mail->setFrom(MAIL_FROM, MAIL_NAME);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    return $mail;
}

function emailTemplate(string $title, string $body): string {
    return "<!DOCTYPE html><html><head><meta charset='UTF-8'/>
    <style>
      body{font-family:'Segoe UI',sans-serif;background:#f3f4f6;margin:0;padding:0;}
      .wrap{max-width:560px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);}
      .hdr{background:#0f2342;padding:32px 40px;text-align:center;}
      .hdr h1{color:#fff;font-size:20px;margin:0;}
      .hdr p{color:rgba(255,255,255,0.6);font-size:13px;margin:4px 0 0;}
      .bdy{padding:36px 40px;}
      .bdy p{color:#374151;font-size:15px;line-height:1.7;margin:0 0 16px;}
      .code-box{background:#f8faff;border:2px dashed #0f2342;border-radius:12px;text-align:center;padding:20px;margin:24px 0;}
      .code-box span{font-size:36px;font-weight:800;color:#0f2342;letter-spacing:8px;}
      .btn{display:inline-block;background:#0f2342;color:#fff;padding:14px 32px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px;}
      .ftr{background:#f9fafb;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;}
      .ftr p{color:#9ca3af;font-size:12px;margin:0;}
      td{border:1px solid #e5e7eb;}
    </style></head><body>
    <div class='wrap'>
      <div class='hdr'><h1>SPTA Payment System</h1><p>Pawing Central School</p></div>
      <div class='bdy'><p><strong>$title</strong></p>$body</div>
      <div class='ftr'><p>&copy; " . date('Y') . " Pawing Central School. All rights reserved.</p></div>
    </div></body></html>";
}

function sendVerificationEmail(string $to, string $name, string $code): bool {
    try {
        $mail = createMailer();
        $mail->addAddress($to, $name);
        $mail->Subject = 'Your SPTA System Verification Code';
        $body = "<p>Hi <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Thank you for registering! Use the code below to verify your email address:</p>
            <div class='code-box'><span>$code</span></div>
            <p>This code expires in <strong>24 hours</strong>. Do not share this code with anyone.</p>
            <p>If you did not register, please ignore this email.</p>";
        $mail->Body    = emailTemplate('Email Verification', $body);
        $mail->AltBody = "Hi $name, your verification code is: $code";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail error: " . $e->getMessage());
        return false;
    }
}

function sendPasswordResetEmail(string $to, string $name, string $token): bool {
    try {
        $base = 'http://' . $_SERVER['HTTP_HOST'] . '/spta-system';
        $link = $base . '/reset_password.php?token=' . $token;
        $mail = createMailer();
        $mail->addAddress($to, $name);
        $mail->Subject = 'Reset your SPTA System password';
        $body = "<p>Hi <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>We received a request to reset your password.</p>
            <p style='text-align:center;margin:28px 0;'><a href='$link' class='btn'>Reset Password</a></p>
            <p>This link expires in <strong>1 hour</strong>. If you did not request this, ignore this email.</p>";
        $mail->Body    = emailTemplate('Password Reset Request', $body);
        $mail->AltBody = "Reset your password: $link";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail error: " . $e->getMessage());
        return false;
    }
}

function sendPaymentConfirmationEmail(string $to, string $parentName, array $payment): bool {
    try {
        $mail = createMailer();
        $mail->addAddress($to, $parentName);
        $mail->Subject = '✅ Payment Confirmed — ' . ($payment['category_name'] ?? 'SPTA System');
        $amount   = '₱' . number_format($payment['amount_paid'], 2);
        $student  = htmlspecialchars($payment['student_name']);
        $cat      = htmlspecialchars($payment['category_name']);
        $date     = date('F d, Y', strtotime($payment['payment_date']));
        $receipt  = htmlspecialchars($payment['receipt_no'] ?? '—');
        $method   = ucfirst(str_replace('_', ' ', $payment['payment_method']));
        $status   = ucfirst($payment['status']);
        $statusColor = match($payment['status']) {
            'paid'    => '#16a34a',
            'partial' => '#d97706',
            default   => '#dc2626',
        };
        $statusBg = match($payment['status']) {
            'paid'    => '#dcfce7',
            'partial' => '#fef3c7',
            default   => '#fee2e2',
        };

        // Receipt link if available
        $receiptLink = '';
        if (!empty($payment['receipt_id'])) {
            $base = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/spta-system';
            $url  = $base . '/receipt.php?id=' . (int)$payment['receipt_id'];
            $receiptLink = "<p style='text-align:center;margin:28px 0;'>
                <a href='$url' style='display:inline-block;background:#0f2342;color:#fff;padding:14px 32px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px;'>🖨️ View &amp; Print Receipt</a>
            </p>";
        }

        $body = "<p>Hi <strong>" . htmlspecialchars($parentName) . "</strong>,</p>
            <p>A payment has been successfully recorded for your child. Here are the details:</p>

            <div style='background:#f8faff;border-radius:14px;padding:24px;margin:20px 0;text-align:center;'>
              <div style='font-size:13px;color:#6b7280;margin-bottom:6px;'>Amount Paid</div>
              <div style='font-size:40px;font-weight:800;color:#0f2342;'>$amount</div>
              <div style='display:inline-block;background:$statusBg;color:$statusColor;font-weight:700;font-size:13px;padding:4px 16px;border-radius:100px;margin-top:10px;'>$status</div>
            </div>

            <table style='width:100%;border-collapse:collapse;margin:20px 0;font-size:14px;border-radius:12px;overflow:hidden;'>
              <tr style='background:#f8faff;'><td style='padding:12px 16px;color:#6b7280;width:40%;border-bottom:1px solid #e5e7eb;'>Student</td><td style='padding:12px 16px;font-weight:600;color:#0f2342;border-bottom:1px solid #e5e7eb;'>$student</td></tr>
              <tr><td style='padding:12px 16px;color:#6b7280;border-bottom:1px solid #e5e7eb;'>Payment For</td><td style='padding:12px 16px;font-weight:600;border-bottom:1px solid #e5e7eb;'>$cat</td></tr>
              <tr style='background:#f8faff;'><td style='padding:12px 16px;color:#6b7280;border-bottom:1px solid #e5e7eb;'>Method</td><td style='padding:12px 16px;border-bottom:1px solid #e5e7eb;'>$method</td></tr>
              <tr><td style='padding:12px 16px;color:#6b7280;border-bottom:1px solid #e5e7eb;'>Date</td><td style='padding:12px 16px;border-bottom:1px solid #e5e7eb;'>$date</td></tr>
              <tr style='background:#f8faff;'><td style='padding:12px 16px;color:#6b7280;'>Receipt No.</td><td style='padding:12px 16px;font-weight:800;color:#0f2342;letter-spacing:0.5px;'>$receipt</td></tr>
            </table>

            $receiptLink

            <p style='font-size:13px;color:#6b7280;text-align:center;'>Please keep this email as your payment confirmation. Thank you!</p>";

        $mail->Body    = emailTemplate('Payment Confirmed! 🎉', $body);
        $mail->AltBody = "Payment confirmed for $student. Amount: $amount. Receipt No: $receipt. Date: $date.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail error: " . $e->getMessage());
        return false;
    }
}