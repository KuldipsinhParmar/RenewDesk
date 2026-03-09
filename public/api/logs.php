<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once dirname(__DIR__, 2) . '/api/config/auth.php';
require_once dirname(__DIR__, 2) . '/api/config/db.php';

$db = (new Database())->getConnection();

try {
    $stmt = $db->query("
        SELECT r.id, r.type, r.sent_to, r.subject, r.success, r.sent_at, p.name as project_name 
        FROM reminder_logs r 
        LEFT JOIN projects p ON r.project_id = p.id 
        ORDER BY r.sent_at DESC 
        LIMIT 100
    ");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $logs]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
