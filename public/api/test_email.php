<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once dirname(__DIR__, 2) . '/api/config/auth.php';

// Hardcoded admin email
$adminEmail = 'kuldipparmar18@gmail.com';
$today = date('d M Y');

try {
    $htmlBody = '
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

                    <!-- Success Banner -->
                    <tr>
                        <td style="background-color:#059669; padding:16px 32px; text-align:center;">
                            <span style="color:#ffffff; font-size:14px; font-weight:700; letter-spacing:1px; text-transform:uppercase;">✅ EMAIL CONFIGURED SUCCESSFULLY</span>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="background-color:#ffffff; padding:36px 32px;">

                            <p style="margin:0 0 20px 0; color:#334155; font-size:15px; line-height:1.6;">
                                Hi Admin,<br><br>
                                Great news! Your RenewDesk email system is working correctly. This test confirms that your server can send automated reminder emails.
                            </p>

                            <!-- Status Card -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding-bottom:12px; border-bottom:1px solid #dcfce7;">
                                                    <span style="color:#166534; font-size:14px; font-weight:700;">📬 Delivery Status</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top:12px;">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="color:#64748b; font-size:12px; font-weight:600; text-transform:uppercase; padding-bottom:4px;">Recipient</td>
                                                            <td style="color:#0f172a; font-size:14px; font-weight:600; padding-bottom:4px; text-align:right;">' . $adminEmail . '</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color:#64748b; font-size:12px; font-weight:600; text-transform:uppercase; padding-bottom:4px;">Method</td>
                                                            <td style="color:#0f172a; font-size:14px; font-weight:600; padding-bottom:4px; text-align:right;">PHP mail()</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color:#64748b; font-size:12px; font-weight:600; text-transform:uppercase;">Status</td>
                                                            <td style="text-align:right;">
                                                                <span style="display:inline-block; background-color:#059669; color:#ffffff; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px;">DELIVERED</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Info Card -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eff6ff; border:1px solid #bfdbfe; border-radius:10px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0; color:#1e40af; font-size:13px; line-height:1.6;">
                                            💡 <strong>What happens next?</strong> RenewDesk will automatically send expiry alerts for Domains, Hosting, Maintenance & Backups based on your configured reminder thresholds.
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
                                This is a test email from <strong style="color:#64748b;">RenewDesk</strong>
                            </p>
                            <p style="margin:0; color:#cbd5e1; font-size:11px;">
                                Sent to verify your server email configuration is working correctly.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

    $subject = '🟢 RenewDesk — Email Test Successful';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: RenewDesk <noreply@renewdesk.local>\r\n";

    $sent = mail($adminEmail, $subject, $htmlBody, $headers);

    if ($sent) {
        echo json_encode(["status" => "success", "message" => "Test email sent successfully to " . $adminEmail]);
    } else {
        throw new Exception("Server mail() function failed. Check server mail config.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
