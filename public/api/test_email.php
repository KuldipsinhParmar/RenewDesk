<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once dirname(__DIR__, 2) . '/api/config/auth.php';
require_once dirname(__DIR__, 2) . '/api/config/db.php';
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = (new Database())->getConnection();

try {
    $stmt = $db->query("SELECT `key`, `value` FROM settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    if (empty($settings['smtp_host'])) {
        throw new Exception("SMTP Host is not configured in Settings.");
    }
    if (empty($settings['admin_notify_email'])) {
        throw new Exception("Admin Notify Email is not configured. Where should we send the test?");
    }

    $mail = new PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host       = $settings['smtp_host'];
    $mail->SMTPAuth   = !empty($settings['smtp_user']);
    $mail->Username   = $settings['smtp_user'];
    $mail->Password   = $settings['smtp_pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $settings['smtp_port'];
    
    $mail->setFrom($settings['smtp_from_email'] ?? 'noreply@renewdesk.local', $settings['smtp_from_name'] ?? 'RenewDesk');
    $mail->addAddress($settings['admin_notify_email']);
    
    $mail->isHTML(true);
    $mail->Subject = '🟢 RenewDesk SMTP Test Successful';
    $mail->Body    = '<h3>SMTP Configured Correctly!</h3><p>Your RenewDesk application is now able to send automated expiry reminders to this email address.</p>';
    
    if ($mail->send()) {
        echo json_encode(["status" => "success", "message" => "Test email sent successfully to " . $settings['admin_notify_email']]);
    } else {
        throw new Exception("Mailer Error.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
