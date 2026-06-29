<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once dirname(__DIR__, 2) . '/api/config/auth.php';
require_once dirname(__DIR__, 2) . '/api/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

try {
    $db   = (new Database())->getConnection();
    $stmt = $db->query("SELECT `key`, `value` FROM settings WHERE `key` IN ('email_from','email_cc')");
    $map  = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $map[$row['key']] = $row['value'];
    }

    $toEmail   = trim($map['email_cc'] ?? '') ?: 'kuldipparmar18@gmail.com';
    $fromEmail = trim($map['email_from'] ?? '') ?: 'noreply@renewdesk.local';
    $today     = date('d M Y');

    $html = '<!DOCTYPE html>
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
            <span style="color:#fff;font-size:17px;font-weight:800">&#8635;</span>
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

  <!-- Success Banner -->
  <tr><td style="background:#0f9d76;padding:13px 32px;text-align:center">
    <span style="color:#fff;font-size:12.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase">&#10003; EMAIL CONFIGURED SUCCESSFULLY</span>
  </td></tr>

  <!-- Body -->
  <tr><td style="background:#fff;padding:36px 32px">

    <p style="margin:0 0 24px;color:#3d4140;font-size:15px;line-height:1.7">
      Hi Admin,<br>
      Your RenewDesk email system is working correctly. This test confirms that your server can send automated renewal reminder emails to the configured address.
    </p>

    <!-- Status Card -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #b8e4d6;border-radius:14px;overflow:hidden;margin-bottom:24px">
      <tr><td colspan="2" style="background:#0f9d76;padding:12px 20px">
        <span style="color:#fff;font-size:13px;font-weight:700">&#128235;&nbsp;&nbsp;DELIVERY STATUS</span>
      </td></tr>
      <tr>
        <td style="padding:14px 20px 6px;color:#979b92;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;width:130px">Recipient</td>
        <td style="padding:14px 20px 6px;color:#15181a;font-size:14px;font-weight:600;font-family:monospace">' . htmlspecialchars($toEmail) . '</td>
      </tr>
      <tr>
        <td style="padding:6px 20px;color:#979b92;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px">From</td>
        <td style="padding:6px 20px;color:#15181a;font-size:14px;font-weight:600;font-family:monospace">' . htmlspecialchars($fromEmail) . '</td>
      </tr>
      <tr>
        <td style="padding:6px 20px;color:#979b92;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px">Method</td>
        <td style="padding:6px 20px;color:#15181a;font-size:14px;font-weight:600">PHP mail()</td>
      </tr>
      <tr>
        <td style="padding:6px 20px 16px;color:#979b92;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px">Status</td>
        <td style="padding:6px 20px 16px">
          <span style="display:inline-block;background:#0f9d76;color:#fff;font-size:11px;font-weight:700;padding:4px 14px;border-radius:99px">DELIVERED</span>
        </td>
      </tr>
    </table>

    <!-- Info note -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f3ee;border:1px solid #e6e1d4;border-radius:12px">
      <tr><td style="padding:16px 20px">
        <p style="margin:0;color:#5e635f;font-size:13px;line-height:1.7">
          <strong>What happens next?</strong> RenewDesk will automatically send expiry alerts for Domains, Hosting, Maintenance contracts, and Backups based on your configured reminder schedule.
        </p>
      </td></tr>
    </table>

  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#f5f3ee;border-top:1px solid #e6e1d4;padding:22px 32px;border-radius:0 0 16px 16px;text-align:center">
    <p style="margin:0 0 5px;color:#979b92;font-size:12px">Test email from <strong style="color:#5e635f">RenewDesk</strong></p>
    <p style="margin:0;color:#c8cbc3;font-size:11px">Sent to verify your server email configuration is working correctly.</p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>';

    $subject = 'RenewDesk — Email Test Successful';
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: RenewDesk <$fromEmail>\r\n";

    $sent = mail($toEmail, $subject, $html, $headers);

    if ($sent) {
        echo json_encode(["status" => "success", "message" => "Test email sent to $toEmail"]);
    } else {
        throw new Exception("Server mail() failed. Check your server mail configuration.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
