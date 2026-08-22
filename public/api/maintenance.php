<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $db->query("SELECT m.*, p.name as project_name FROM maintenance m JOIN projects p ON m.project_id = p.id ORDER BY m.start_date ASC");
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
    elseif ($method === 'PUT' && ($_GET['action'] ?? '') === 'renew') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");

        $cur = $db->prepare("SELECT * FROM maintenance WHERE id = ?");
        $cur->execute([$id]);
        $row = $cur->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new Exception("Maintenance contract not found");

        $db->prepare("UPDATE maintenance SET status='renewed', client_paid=1 WHERE id=?")->execute([$id]);

        $nextStart = new DateTime($row['start_date']);
        $nextStart->modify('+1 year');
        $nextEnd = new DateTime($row['end_date']);
        $nextEnd->modify('+1 year');
        $db->prepare("INSERT INTO maintenance (project_id, start_date, end_date, price, currency, status, notes) VALUES (?, ?, ?, ?, ?, 'active', ?)")
            ->execute([$row['project_id'], $nextStart->format('Y-m-d'), $nextEnd->format('Y-m-d'), $row['price'], $row['currency'] ?? 'INR', $row['notes']]);

        echo json_encode(["status" => "success", "message" => "Maintenance renewed."]);
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
} catch (PDOException $e) {
    error_log("[maintenance.php] " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => renewdesk_debug() ? $e->getMessage() : "A database error occurred."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} catch (Throwable $e) {
    error_log("[maintenance.php] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => (function_exists('renewdesk_debug') && renewdesk_debug()) ? ($e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine()) : "An unexpected error occurred."]);
}
?>
