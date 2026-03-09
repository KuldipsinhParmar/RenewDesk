<?php
// ============================================================
// Cron Job Script to Email Reminders 
// Run natively via CLI: `php /var/www/html/cron/send_reminders.php`
// ============================================================

require_once dirname(__DIR__) . '/api/config/db.php';
// Composer Autoload for PHPMailer
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = (new Database())->getConnection();

// --- 1. Load Global Settings ---
$stmt = $db->query("SELECT `key`, `value` FROM settings");
$settingsArray = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$adminEmail = $settingsArray['admin_notify_email'] ?? '';
$remindDays = explode(',', $settingsArray['remind_days'] ?? '30,15,7,1');

if (empty($adminEmail)) {
    die("No admin notify email configured. Exiting.\n");
}

// --- Helper Functions ---
function daysBetween($dateStr) {
    if(!$dateStr) return -1;
    return (new DateTime())->diff(new DateTime($dateStr))->days * ((new DateTime($dateStr)) > (new DateTime()) ? 1 : -1);
}

function sendAlertEmail($subject, $body, $settings) {
    $mail = new PHPMailer(true);
    try {
        if (!empty($settings['smtp_host'])) {
            $mail->isSMTP();
            $mail->Host       = $settings['smtp_host'];
            $mail->SMTPAuth   = !empty($settings['smtp_user']);
            $mail->Username   = $settings['smtp_user'];
            $mail->Password   = $settings['smtp_pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $settings['smtp_port'];
        }
        
        $mail->setFrom($settings['smtp_from_email'] ?? 'noreply@renewdesk.local', $settings['smtp_from_name'] ?? 'RenewDesk');
        $mail->addAddress($settings['admin_notify_email']);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function processAsset($type, $db, $settings, $remindDaysArr, $query, $projectNameCol = 'project', $assetNameCol = 'name', $dateCol = 'date', $priceCol = 'price') {
    $stmt = $db->query($query);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $daysLeft = daysBetween($item[$dateCol]);
        if (in_array((string)$daysLeft, $remindDaysArr)) {
            $msg = "Project: {$item[$projectNameCol]}\nItem: {$item[$assetNameCol]} ({$type})\nExpires on: {$item[$dateCol]} (in $daysLeft days)\nPrice: {$item[$priceCol]}";
            
            // Send Mail
            $subject = "🚨 RenewDesk URGENT: $type Expiry Alert ($daysLeft days)";
            $success = sendAlertEmail($subject, nl2br($msg), $settings);
            
            // Log it
            $logStmt = $db->prepare("INSERT INTO reminder_logs (project_id, type, sent_to, subject, message, success) VALUES (?, ?, ?, ?, ?, ?)");
            $logStmt->execute([$item['project_id'] ?? null, strtolower($type), $settings['admin_notify_email'], $subject, $msg, $success ? 1 : 0]);
            
            echo "Sent summary for $type: {$item[$assetNameCol]}\n";
        }
    }
}

// --- 2. Process Domains ---
processAsset('Domain', $db, $settingsArray, $remindDays, "SELECT d.project_id, p.name as project, d.domain_name as name, d.renewal_date as date, d.price FROM domains d JOIN projects p ON d.project_id = p.id WHERE d.status = 'active'");

// --- 3. Process Hosting ---
processAsset('Hosting', $db, $settingsArray, $remindDays, "SELECT h.project_id, p.name as project, h.plan_name as name, h.renewal_date as date, h.price FROM hosting h JOIN projects p ON h.project_id = p.id WHERE h.status = 'active'");

// --- 4. Process AMC Maintenance ---
processAsset('Maintenance', $db, $settingsArray, $remindDays, "SELECT m.project_id, p.name as project, 'AMC Contract' as name, m.end_date as date, m.price FROM maintenance m JOIN projects p ON m.project_id = p.id WHERE m.status = 'active'");

echo "Reminders process completed.\n";
?>
