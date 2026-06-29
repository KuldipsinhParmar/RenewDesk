<?php
// ============================================================
// Cron Job: Send Renewal Reminder Emails
// CLI: php /var/www/html/cron/send_reminders.php
// ============================================================

require_once dirname(__DIR__) . '/api/config/db.php';

$db = (new Database())->getConnection();

// Load all settings from DB
$stmt = $db->query("SELECT `key`, `value` FROM settings");
$settingsMap = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settingsMap[$row['key']] = $row['value'];
}

// Abort if alerts are disabled
if (($settingsMap['alert_enabled'] ?? '1') === '0') {
    echo "Email alerts are disabled in settings. Skipping.\n";
    exit;
}

$adminEmail = $settingsMap['email_cc'] ?: 'kuldipparmar18@gmail.com';
$fromEmail  = $settingsMap['email_from'] ?: 'noreply@renewdesk.local';
$remindDays = array_map('trim', explode(',', $settingsMap['remind_days'] ?? '30,15,7,1'));

// ── Helpers ──────────────────────────────────────────────────
function daysBetween($dateStr) {
    if (!$dateStr) return -1;
    $future = new DateTime($dateStr);
    $now    = new DateTime();
    return (int)($future > $now ? $now->diff($future)->days : -$now->diff($future)->days);
}

function urgencyStyle($daysLeft) {
    if ($daysLeft <= 3)  return ['#dc2626', 'CRITICAL — ' . $daysLeft . ' DAY' . ($daysLeft != 1 ? 'S' : '') . ' LEFT'];
    if ($daysLeft <= 7)  return ['#ea580c', 'URGENT — '   . $daysLeft . ' DAYS LEFT'];
    if ($daysLeft <= 15) return ['#d97706', 'WARNING — '  . $daysLeft . ' DAYS LEFT'];
    return ['#0f9d76', 'NOTICE — ' . $daysLeft . ' DAYS LEFT'];
}

function typeStyle($type) {
    switch (strtolower($type)) {
        case 'domain':      return ['#0f9d76', '🌐'];
        case 'hosting':     return ['#5b69e6', '☁️'];
        case 'maintenance': return ['#d97706', '🔧'];
        case 'backup':      return ['#0b7c5e', '💾'];
        default:            return ['#64748b', '📋'];
    }
}

function buildEmail($type, $project, $asset, $expiryDate, $daysLeft, $price) {
    list($urgBg, $urgLabel) = urgencyStyle($daysLeft);
    list($typeColor, $typeIcon) = typeStyle($type);
    $expFmt    = date('d M Y', strtotime($expiryDate));
    $today     = date('d M Y');
    $typeUp    = htmlspecialchars(strtoupper($type));
    $projectE  = htmlspecialchars($project);
    $assetE    = htmlspecialchars($asset);
    $priceE    = htmlspecialchars($price);
    $daysLabel = $daysLeft . ' day' . ($daysLeft != 1 ? 's' : '');

    return '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f0f0ea;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Arial,sans-serif">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f0f0ea;padding:32px 16px">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">

  <!-- Header -->
  <tr><td style="background:#15181a;padding:24px 32px;border-radius:16px 16px 0 0">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
      <td>
        <table role="presentation" cellpadding="0" cellspacing="0"><tr>
          <td style="background:#0f9d76;width:34px;height:34px;border-radius:9px;text-align:center;vertical-align:middle">
            <span style="color:#fff;font-size:17px;font-weight:800;line-height:1">&#8635;</span>
          </td>
          <td style="padding-left:11px">
            <span style="color:#fff;font-size:18px;font-weight:800;letter-spacing:-0.4px">RenewDesk</span>
          </td>
        </tr></table>
      </td>
      <td align="right" style="vertical-align:middle">
        <span style="color:#5e635f;font-size:12px">' . $today . '</span>
      </td>
    </tr></table>
  </td></tr>

  <!-- Urgency Banner -->
  <tr><td style="background:' . $urgBg . ';padding:13px 32px;text-align:center">
    <span style="color:#fff;font-size:12.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase">' . $urgLabel . '</span>
  </td></tr>

  <!-- Body -->
  <tr><td style="background:#fff;padding:36px 32px">

    <p style="margin:0 0 24px;color:#3d4140;font-size:15px;line-height:1.7">
      Hi Admin,<br>
      A <strong style="color:#15181a">' . $typeUp . '</strong> asset requires attention — please renew before it expires to avoid service disruption.
    </p>

    <!-- Asset Card -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e1d4;border-radius:14px;overflow:hidden;margin-bottom:24px">
      <tr><td colspan="2" style="background:' . $typeColor . ';padding:12px 20px">
        <span style="color:#fff;font-size:13px;font-weight:700">' . $typeIcon . '&nbsp;&nbsp;' . $typeUp . ' RENEWAL ALERT</span>
      </td></tr>
      <tr>
        <td style="padding:14px 20px 6px;color:#979b92;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;width:130px">Project</td>
        <td style="padding:14px 20px 6px;color:#15181a;font-size:15px;font-weight:600">' . $projectE . '</td>
      </tr>
      <tr>
        <td style="padding:6px 20px;color:#979b92;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px">Item</td>
        <td style="padding:6px 20px;color:#15181a;font-size:15px;font-weight:600">' . $assetE . '</td>
      </tr>
      <tr>
        <td style="padding:6px 20px;color:#979b92;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px">Expiry Date</td>
        <td style="padding:6px 20px;color:#dc2626;font-size:15px;font-weight:700">' . $expFmt . '</td>
      </tr>
      <tr>
        <td style="padding:6px 20px;color:#979b92;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px">Days Left</td>
        <td style="padding:6px 20px">
          <span style="display:inline-block;background:' . $urgBg . ';color:#fff;font-size:12px;font-weight:700;padding:4px 14px;border-radius:99px">' . $daysLabel . '</span>
        </td>
      </tr>
      <tr>
        <td style="padding:6px 20px 16px;color:#979b92;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px">Renewal Cost</td>
        <td style="padding:6px 20px 16px;color:#15181a;font-size:15px;font-weight:700;font-family:monospace">' . $priceE . '</td>
      </tr>
    </table>

    <!-- Action note -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#e8f5f0;border:1px solid #b8e4d6;border-radius:12px">
      <tr><td style="padding:16px 20px">
        <p style="margin:0;color:#0b5740;font-size:13px;line-height:1.7">
          <strong>Action required:</strong> Log in to your RenewDesk dashboard to renew this asset or mark it as handled. Ignoring this alert may cause a service interruption for your client.
        </p>
      </td></tr>
    </table>

  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#f5f3ee;border-top:1px solid #e6e1d4;padding:22px 32px;border-radius:0 0 16px 16px;text-align:center">
    <p style="margin:0 0 5px;color:#979b92;font-size:12px">Automated alert from <strong style="color:#5e635f">RenewDesk</strong></p>
    <p style="margin:0;color:#c8cbc3;font-size:11px">Manage reminder settings from the dashboard &rarr; Settings</p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>';
}

function sendEmail($to, $from, $subject, $html) {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: RenewDesk Alerts <$from>\r\n";
    $headers .= "Reply-To: $from\r\n";
    return mail($to, $subject, $html, $headers);
}

function processAsset($type, $db, $adminEmail, $fromEmail, $remindDays, $query) {
    $stmt  = $db->query($query);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $item) {
        $daysLeft = daysBetween($item['date']);
        if (!in_array((string)$daysLeft, $remindDays, true)) continue;
        $html    = buildEmail($type, $item['project'], $item['name'], $item['date'], $daysLeft, $item['price']);
        $subject = "RenewDesk: $type expiry — {$item['name']} ($daysLeft day" . ($daysLeft != 1 ? 's' : '') . " left)";
        sendEmail($adminEmail, $fromEmail, $subject, $html);
        echo "Sent: $type — {$item['name']} ($daysLeft days)\n";
    }
}

// ── Process each asset type ──────────────────────────────────
processAsset('Domain', $db, $adminEmail, $fromEmail, $remindDays,
    "SELECT p.name as project, d.domain_name as name, d.renewal_date as date, CONCAT(d.currency, ' ', d.price) as price
     FROM domains d JOIN projects p ON d.project_id = p.id WHERE d.status = 'active'"
);

processAsset('Hosting', $db, $adminEmail, $fromEmail, $remindDays,
    "SELECT p.name as project, COALESCE(h.plan_name, h.provider, 'Hosting Plan') as name, h.renewal_date as date, CONCAT(h.currency, ' ', h.price) as price
     FROM hosting h JOIN projects p ON h.project_id = p.id WHERE h.status = 'active'"
);

processAsset('Maintenance', $db, $adminEmail, $fromEmail, $remindDays,
    "SELECT p.name as project, 'AMC Contract' as name, m.end_date as date, CONCAT(m.currency, ' ', m.price) as price
     FROM maintenance m JOIN projects p ON m.project_id = p.id WHERE m.status = 'active'"
);

processAsset('Backup', $db, $adminEmail, $fromEmail, $remindDays,
    "SELECT p.name as project, CONCAT('Backup (', b.frequency, ')') as name, b.next_backup as date, '—' as price
     FROM backups b JOIN projects p ON b.project_id = p.id WHERE b.next_backup IS NOT NULL"
);

echo "Reminders completed.\n";
?>
