<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, PUT");

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/env.php';

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $db->query("SELECT `key`, `value`, `label` FROM settings");
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $cronKey = renewdesk_env('CRON_KEY', 'RD-CRON-2024-xK9mP3qW7vN1');
        $proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $cronUrl = $proto . '://' . $host . '/api/cron_trigger.php?key=' . urlencode($cronKey);
        echo json_encode(["status" => "success", "data" => $settings, "cron_url" => $cronUrl]);
    } 
    elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents("php://input"), true);
        $allowed = [
            'remind_days', 'email_from', 'email_cc', 'alert_enabled',
            'biz_name', 'biz_website', 'biz_phone', 'biz_email', 'biz_address', 'biz_logo_url',
            'bank_name', 'bank_ifsc', 'bank_account', 'upi_id', 'gpay_number',
            'signatory_name', 'invoice_prefix'
        ];
        foreach ($input as $key => $value) {
            if (!in_array($key, $allowed, true)) continue;
            $stmt = $db->prepare("UPDATE settings SET value=? WHERE `key`=?");
            $stmt->execute([trim((string)$value), $key]);
        }
        echo json_encode(["status" => "success", "message" => "Settings updated."]);
    }
} catch (PDOException $e) {
    error_log("[settings.php] " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => renewdesk_debug() ? $e->getMessage() : "A database error occurred."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} catch (Throwable $e) {
    error_log("[settings.php] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => (function_exists('renewdesk_debug') && renewdesk_debug()) ? ($e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine()) : "An unexpected error occurred."]);
}
?>
