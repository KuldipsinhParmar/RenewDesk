<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT, DELETE");

require_once dirname(__DIR__, 2) . '/api/config/auth.php';
require_once dirname(__DIR__, 2) . '/api/config/db.php';

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        $stmt = $db->prepare("INSERT INTO hosting (project_id, provider, plan_name, renewal_date, price, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['project_id'], $input['provider'], $input['plan_name'], $input['renewal_date'], 
            $input['price'] ?? 0, 'active', $input['notes'] ?? null
        ]);
        echo json_encode(["status" => "success", "message" => "Hosting added."]);
    }
    elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");
        $input = json_decode(file_get_contents("php://input"), true);
        
        $stmt = $db->prepare("UPDATE hosting SET provider=?, plan_name=?, renewal_date=?, price=?, status=?, notes=? WHERE id=?");
        $stmt->execute([
            $input['provider'], $input['plan_name'], $input['renewal_date'], 
            $input['price'] ?? 0, $input['status'] ?? 'active', $input['notes'] ?? null, $id
        ]);
        echo json_encode(["status" => "success", "message" => "Hosting updated."]);
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");
        $stmt = $db->prepare("DELETE FROM hosting WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Hosting deleted."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
