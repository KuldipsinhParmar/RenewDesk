<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once dirname(__DIR__, 2) . '/api/config/auth.php';
require_once dirname(__DIR__, 2) . '/api/config/db.php';

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $db->query("SELECT m.*, p.name as project_name FROM maintenance m JOIN projects p ON m.project_id = p.id ORDER BY m.end_date ASC");
        $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "success", "data" => $maintenance]);
    }
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        $stmt = $db->prepare("INSERT INTO maintenance (project_id, start_date, end_date, price, client_paid, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['project_id'], $input['start_date'], $input['end_date'], 
            $input['price'] ?? 0, $input['client_paid'] ?? 0, 'active', $input['notes'] ?? null
        ]);
        echo json_encode(["status" => "success", "message" => "Maintenance contract added."]);
    }
    elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");
        $input = json_decode(file_get_contents("php://input"), true);
        
        $stmt = $db->prepare("UPDATE maintenance SET start_date=?, end_date=?, price=?, client_paid=?, status=?, notes=? WHERE id=?");
        $stmt->execute([
            $input['start_date'], $input['end_date'], 
            $input['price'] ?? 0, $input['client_paid'] ?? 0, $input['status'] ?? 'active', $input['notes'] ?? null, $id
        ]);
        echo json_encode(["status" => "success", "message" => "Maintenance updated."]);
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");
        $stmt = $db->prepare("DELETE FROM maintenance WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Maintenance deleted."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
