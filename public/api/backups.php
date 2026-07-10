<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once dirname(__DIR__, 2) . '/api/config/auth.php';
require_once dirname(__DIR__, 2) . '/api/config/db.php';

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $db->query("SELECT b.*, p.name as project_name FROM backups b JOIN projects p ON b.project_id = p.id ORDER BY b.next_backup ASC");
        $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "success", "data" => $backups]);
    }
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        $stmt = $db->prepare("INSERT INTO backups (project_id, frequency, last_backup, next_backup, storage_location, is_done, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['project_id'], $input['frequency'], $input['last_backup'] ?? null, $input['next_backup'] ?? null,
            $input['storage_location'] ?? null, $input['is_done'] ?? 0, $input['notes'] ?? null
        ]);
        echo json_encode(["status" => "success", "message" => "Backup schedule added."]);
    }
    elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");
        $input = json_decode(file_get_contents("php://input"), true);
        
        $stmt = $db->prepare("UPDATE backups SET frequency=?, last_backup=?, next_backup=?, storage_location=?, is_done=?, notes=? WHERE id=?");
        $stmt->execute([
            $input['frequency'], $input['last_backup'] ?? null, $input['next_backup'] ?? null,
            $input['storage_location'] ?? null, $input['is_done'] ?? 0, $input['notes'] ?? null, $id
        ]);
        echo json_encode(["status" => "success", "message" => "Backup schedule updated."]);
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");
        $stmt = $db->prepare("DELETE FROM backups WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Backup deleted."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
