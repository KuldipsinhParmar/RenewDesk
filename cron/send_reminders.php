<?php
// ============================================================
// Cron Job Script to Email Reminders 
// Run natively via CLI: `php /var/www/html/cron/send_reminders.php`
// ============================================================

require_once dirname(__DIR__) . '/api/config/db.php';

$db = (new Database())->getConnection();

// --- Hardcoded Admin Email ---
$adminEmail = 'kuldipparmar18@gmail.com';

// --- Load remind_days from settings ---
$stmt = $db->query("SELECT `key`, `value` FROM settings WHERE `key` = 'remind_days'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$remindDays = explode(',', $row['value'] ?? '30,15,7,1');

// --- Helper Functions ---
function daysBetween($dateStr) {
    if(!$dateStr) return -1;
    return (new DateTime())->diff(new DateTime($dateStr))->days * ((new DateTime($dateStr)) > (new DateTime()) ? 1 : -1);
}

function getUrgencyColor($daysLeft) {
    if ($daysLeft <= 3) return ['bg' => '#dc2626', 'text' => '#ffffff', 'label' => '🔴 CRITICAL'];
    if ($daysLeft <= 7) return ['bg' => '#ea580c', 'text' => '#ffffff', 'label' => '🟠 URGENT'];
    if ($daysLeft <= 15) return ['bg' => '#d97706', 'text' => '#ffffff', 'label' => '🟡 WARNING'];
    return ['bg' => '#2563eb', 'text' => '#ffffff', 'label' => '🔵 NOTICE'];
}

function getTypeIcon($type) {
    switch(strtolower($type)) {
        case 'domain':      return '🌐';
        case 'hosting':     return '☁️';
        case 'maintenance': return '🔧';
        case 'backup':      return '💾';
        default:            return '📋';
    }
}

function getTypeColor($type) {
    switch(strtolower($type)) {
        case 'domain':      return '#2563eb';
        case 'hosting':     return '#7c3aed';
        case 'maintenance': return '#d97706';
        case 'backup':      return '#059669';
        default:            return '#475569';
    }
}

function buildEmailHTML($type, $projectName, $assetName, $expiryDate, $daysLeft, $price) {
    $urgency = getUrgencyColor($daysLeft);
    $icon = getTypeIcon($type);
    $typeColor = getTypeColor($type);
    $formattedDate = date('d M Y', strtotime($expiryDate));
    $today = date('d M Y');

    return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%;">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding:28px 32px; border-radius:16px 16px 0 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="background-color:#2563eb; width:36px; height:36px; border-radius:10px; text-align:center; vertical-align:middle;">
                                                    <span style="color:#ffffff; font-size:18px; font-weight:bold;">↻</span>
                                                </td>
                                                <td style="padding-left:12px;">
                                                    <span style="color:#ffffff; font-size:20px; font-weight:800; letter-spacing:-0.5px;">RenewDesk</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="color:#94a3b8; font-size:12px;">' . $today . '</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Urgency Banner -->
                    <tr>
                        <td style="background-color:' . $urgency['bg'] . '; padding:16px 32px; text-align:center;">
                            <span style="color:' . $urgency['text'] . '; font-size:14px; font-weight:700; letter-spacing:1px; text-transform:uppercase;">' . $urgency['label'] . ' — EXPIRES IN ' . $daysLeft . ' DAY' . ($daysLeft != 1 ? 'S' : '') . '</span>
                        </td>
                    </tr>

                    <!-- Main Body -->
                    <tr>
                        <td style="background-color:#ffffff; padding:36px 32px;">

                            <!-- Greeting -->
                            <p style="margin:0 0 20px 0; color:#334155; font-size:15px; line-height:1.6;">
                                Hi Admin,<br>
                                A <strong>' . strtolower($type) . '</strong> asset requires your attention. Please review and take action before it expires.
                            </p>

                            <!-- Asset Info Card -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; margin-bottom:24px;">
                                
                                <!-- Type Header -->
                                <tr>
                                    <td colspan="2" style="background-color:' . $typeColor . '; padding:12px 20px;">
                                        <span style="color:#ffffff; font-size:14px; font-weight:700;">' . $icon . '  ' . strtoupper($type) . ' RENEWAL ALERT</span>
                                    </td>
                                </tr>

                                <!-- Project -->
                                <tr>
                                    <td style="padding:14px 20px 6px 20px; color:#64748b; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; width:140px;">Project</td>
                                    <td style="padding:14px 20px 6px 20px; color:#0f172a; font-size:15px; font-weight:600;">' . htmlspecialchars($projectName) . '</td>
                                </tr>

                                <!-- Asset -->
                                <tr>
                                    <td style="padding:6px 20px; color:#64748b; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Item</td>
                                    <td style="padding:6px 20px; color:#0f172a; font-size:15px; font-weight:600;">' . htmlspecialchars($assetName) . '</td>
                                </tr>

                                <!-- Expiry Date -->
                                <tr>
                                    <td style="padding:6px 20px; color:#64748b; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Expiry Date</td>
                                    <td style="padding:6px 20px; color:#dc2626; font-size:15px; font-weight:700;">' . $formattedDate . '</td>
                                </tr>

                                <!-- Days Left -->
                                <tr>
                                    <td style="padding:6px 20px; color:#64748b; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Days Left</td>
                                    <td style="padding:6px 20px;">
                                        <span style="display:inline-block; background-color:' . $urgency['bg'] . '; color:#ffffff; font-size:12px; font-weight:700; padding:4px 12px; border-radius:20px;">' . $daysLeft . ' day' . ($daysLeft != 1 ? 's' : '') . '</span>
                                    </td>
                                </tr>

                                <!-- Price -->
                                <tr>
                                    <td style="padding:6px 20px 14px 20px; color:#64748b; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Cost</td>
                                    <td style="padding:6px 20px 14px 20px; color:#0f172a; font-size:15px; font-weight:600;">' . htmlspecialchars($price) . '</td>
                                </tr>

                            </table>

                            <!-- Action Note -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0; color:#1e40af; font-size:13px; line-height:1.6;">
                                            💡 <strong>Action Required:</strong> Log in to your RenewDesk dashboard to renew this asset or mark it as handled. Ignoring this alert may result in service disruption.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8fafc; border-top:1px solid #e2e8f0; padding:24px 32px; border-radius:0 0 16px 16px; text-align:center;">
                            <p style="margin:0 0 8px 0; color:#94a3b8; font-size:12px;">
                                This is an automated alert from <strong style="color:#64748b;">RenewDesk</strong>
                            </p>
                            <p style="margin:0; color:#cbd5e1; font-size:11px;">
                                You are receiving this because you are the system admin. Manage reminder settings from the dashboard.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

function sendAlertEmail($to, $subject, $htmlBody) {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: RenewDesk <noreply@renewdesk.local>\r\n";
    $headers .= "Reply-To: noreply@renewdesk.local\r\n";

    return mail($to, $subject, $htmlBody, $headers);
}

function processAsset($type, $db, $adminEmail, $remindDaysArr, $query, $projectNameCol = 'project', $assetNameCol = 'name', $dateCol = 'date', $priceCol = 'price') {
    $stmt = $db->query($query);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $daysLeft = daysBetween($item[$dateCol]);
        if (in_array((string)$daysLeft, $remindDaysArr)) {
            
            // Build premium HTML email
            $htmlBody = buildEmailHTML(
                $type,
                $item[$projectNameCol],
                $item[$assetNameCol],
                $item[$dateCol],
                $daysLeft,
                $item[$priceCol]
            );
            
            // Send Mail
            $subject = "🚨 RenewDesk: $type Expiry Alert — {$item[$assetNameCol]} ($daysLeft days left)";
            $success = sendAlertEmail($adminEmail, $subject, $htmlBody);
            
            // Log it
            $logMsg = "Project: {$item[$projectNameCol]} | Item: {$item[$assetNameCol]} ({$type}) | Expires: {$item[$dateCol]} (in $daysLeft days) | Price: {$item[$priceCol]}";
            $logStmt = $db->prepare("INSERT INTO reminder_logs (project_id, type, sent_to, subject, message, success) VALUES (?, ?, ?, ?, ?, ?)");
            $logStmt->execute([$item['project_id'] ?? null, strtolower($type), $adminEmail, $subject, $logMsg, $success ? 1 : 0]);
            
            echo "Sent summary for $type: {$item[$assetNameCol]}\n";
        }
    }
}

// --- 2. Process Domains ---
processAsset('Domain', $db, $adminEmail, $remindDays, "SELECT d.project_id, p.name as project, d.domain_name as name, d.renewal_date as date, d.price FROM domains d JOIN projects p ON d.project_id = p.id WHERE d.status = 'active'");

// --- 3. Process Hosting ---
processAsset('Hosting', $db, $adminEmail, $remindDays, "SELECT h.project_id, p.name as project, h.plan_name as name, h.renewal_date as date, h.price FROM hosting h JOIN projects p ON h.project_id = p.id WHERE h.status = 'active'");

// --- 4. Process AMC Maintenance ---
processAsset('Maintenance', $db, $adminEmail, $remindDays, "SELECT m.project_id, p.name as project, 'AMC Contract' as name, m.end_date as date, m.price FROM maintenance m JOIN projects p ON m.project_id = p.id WHERE m.status = 'active'");

// --- 5. Process Backups ---
processAsset('Backup', $db, $adminEmail, $remindDays, "SELECT b.project_id, p.name as project, CONCAT('Backup (', b.frequency, ')') as name, b.next_backup as date, '—' as price FROM backups b JOIN projects p ON b.project_id = p.id WHERE b.next_backup IS NOT NULL");

echo "Reminders process completed.\n";
?>
