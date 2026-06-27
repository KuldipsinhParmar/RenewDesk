<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once dirname(__DIR__, 2) . '/api/config/auth.php';
require_once dirname(__DIR__, 2) . '/api/config/db.php';

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $stmt = $db->prepare("SELECT p.*, c.name as client_name, c.company as client_company FROM projects p LEFT JOIN clients c ON c.id = p.client_id WHERE p.id = ?");
            $stmt->execute([$id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($project) {
                $stmt = $db->prepare("SELECT * FROM domains WHERE project_id = ?");
                $stmt->execute([$id]);
                $project['domains'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $stmt = $db->prepare("SELECT * FROM hosting WHERE project_id = ?");
                $stmt->execute([$id]);
                $project['hosting'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $stmt = $db->prepare("SELECT * FROM maintenance WHERE project_id = ?");
                $stmt->execute([$id]);
                $project['maintenance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $stmt = $db->prepare("SELECT * FROM backups WHERE project_id = ?");
                $stmt->execute([$id]);
                $project['backups'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $stmt = $db->prepare("SELECT * FROM tasks WHERE project_id = ? ORDER BY task_date DESC, created_at DESC");
                $stmt->execute([$id]);
                $project['tasks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(["status" => "success", "data" => $project]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Project not found"]);
            }
        } else {
            if (isset($_GET['client_id'])) {
                $stmt = $db->prepare("SELECT p.*, c.name as client_name, c.company as client_company FROM projects p LEFT JOIN clients c ON c.id = p.client_id WHERE p.client_id = ? ORDER BY p.created_at DESC");
                $stmt->execute([(int)$_GET['client_id']]);
            } else {
                $stmt = $db->query("SELECT p.*, c.name as client_name, c.company as client_company FROM projects p LEFT JOIN clients c ON c.id = p.client_id ORDER BY p.created_at DESC");
            }
            echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        $name = trim($input['name'] ?? '');
        if ($name === '') throw new Exception("Project name is required.");
        $stmt = $db->prepare("INSERT INTO projects (client_id, name, description, status, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['client_id'] ? (int)$input['client_id'] : null,
            $name,
            trim($input['description'] ?? '') ?: null,
            $input['status'] ?? 'active',
            trim($input['notes'] ?? '') ?: null
        ]);
        echo json_encode(["status" => "success", "message" => "Project created.", "id" => $db->lastInsertId()]);
    } elseif ($method === 'PUT') {
        if (!isset($_GET['id'])) throw new Exception("ID required");
        $id = $_GET['id'];
        $input = json_decode(file_get_contents("php://input"), true);
        $name = trim($input['name'] ?? '');
        if ($name === '') throw new Exception("Project name is required.");
        $stmt = $db->prepare("UPDATE projects SET client_id=?, name=?, description=?, status=?, notes=? WHERE id=?");
        $stmt->execute([
            $input['client_id'] ? (int)$input['client_id'] : null,
            $name,
            trim($input['description'] ?? '') ?: null,
            $input['status'] ?? 'active',
            trim($input['notes'] ?? '') ?: null,
            (int)$id
        ]);
        echo json_encode(["status" => "success", "message" => "Project updated."]);
    } elseif ($method === 'DELETE') {
        if (!isset($_GET['id'])) throw new Exception("ID required");
        $id = $_GET['id'];
        $stmt = $db->prepare("DELETE FROM projects WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Project deleted."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
