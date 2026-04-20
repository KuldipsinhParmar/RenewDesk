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
        if (isset($_GET['project_id'])) {
            // Tasks for a specific project
            $stmt = $db->prepare("SELECT t.*, p.name as project_name FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.project_id = ? ORDER BY t.task_date DESC, t.created_at DESC");
            $stmt->execute([$_GET['project_id']]);
        } else {
            // All tasks across projects
            $stmt = $db->query("SELECT t.*, p.name as project_name FROM tasks t JOIN projects p ON t.project_id = p.id ORDER BY t.task_date DESC, t.created_at DESC");
        }
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "success", "data" => $tasks]);
    }
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        $stmt = $db->prepare("INSERT INTO tasks (project_id, task_date, task_title, hours, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['project_id'], $input['task_date'], $input['task_title'],
            $input['hours'] ?? 0, $input['notes'] ?? null
        ]);
        echo json_encode(["status" => "success", "message" => "Task added.", "id" => $db->lastInsertId()]);
    }
    elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");
        $input = json_decode(file_get_contents("php://input"), true);

        $stmt = $db->prepare("UPDATE tasks SET project_id=?, task_date=?, task_title=?, hours=?, notes=? WHERE id=?");
        $stmt->execute([
            $input['project_id'], $input['task_date'], $input['task_title'],
            $input['hours'] ?? 0, $input['notes'] ?? null, $id
        ]);
        echo json_encode(["status" => "success", "message" => "Task updated."]);
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");
        $stmt = $db->prepare("DELETE FROM tasks WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Task deleted."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
