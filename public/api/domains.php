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
        $stmt = $db->query("SELECT d.*, p.name as project_name FROM domains d JOIN projects p ON d.project_id = p.id ORDER BY d.renewal_date ASC");
        $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "success", "data" => $domains]);
    }
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        $stmt = $db->prepare("INSERT INTO domains (project_id, domain_name, registrar, renewal_date, price, auto_renew, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['project_id'], $input['domain_name'], $input['registrar'], $input['renewal_date'], 
            $input['price'] ?? 0, $input['auto_renew'] ?? 0, 'active', $input['notes'] ?? null
        ]);
        echo json_encode(["status" => "success", "message" => "Domain added."]);
    }
    elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");
        $input = json_decode(file_get_contents("php://input"), true);
        
        $stmt = $db->prepare("UPDATE domains SET domain_name=?, registrar=?, renewal_date=?, price=?, auto_renew=?, status=?, notes=? WHERE id=?");
        $stmt->execute([
            $input['domain_name'], $input['registrar'], $input['renewal_date'], 
            $input['price'] ?? 0, $input['auto_renew'] ?? 0, $input['status'] ?? 'active', $input['notes'] ?? null, $id
        ]);
        echo json_encode(["status" => "success", "message" => "Domain updated."]);
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");
        $stmt = $db->prepare("DELETE FROM domains WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Domain deleted."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
