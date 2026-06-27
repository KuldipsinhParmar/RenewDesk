<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, PUT");

require_once dirname(__DIR__, 2) . '/api/config/auth.php';
require_once dirname(__DIR__, 2) . '/api/config/db.php';

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $db->query("SELECT `key`, `value`, `label` FROM settings");
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "success", "data" => $settings]);
    } 
    elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents("php://input"), true);
        $allowed = ['remind_days', 'email_from', 'email_cc', 'alert_enabled'];
        foreach ($input as $key => $value) {
            if (!in_array($key, $allowed, true)) continue;
            $stmt = $db->prepare("UPDATE settings SET value=? WHERE `key`=?");
            $stmt->execute([trim((string)$value), $key]);
        }
        echo json_encode(["status" => "success", "message" => "Settings updated."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
