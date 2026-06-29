<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once dirname(__DIR__, 2) . '/api/config/auth.php';
require_once dirname(__DIR__, 2) . '/api/config/db.php';

$db     = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $db->query("SELECT id, code, name FROM countries ORDER BY name ASC");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($method === 'POST') {
        $in   = json_decode(file_get_contents("php://input"), true);
        $code = strtoupper(trim($in['code'] ?? ''));
        $name = trim($in['name'] ?? '');
        if (strlen($code) !== 2) throw new Exception("Country code must be exactly 2 characters.");
        if ($name === '')        throw new Exception("Country name is required.");
        $stmt = $db->prepare("INSERT INTO countries (code, name) VALUES (?, ?)");
        $stmt->execute([$code, $name]);
        echo json_encode(["status" => "success", "message" => "Country added.", "id" => $db->lastInsertId()]);

    } elseif ($method === 'PUT') {
        $id   = (int)($_GET['id'] ?? 0);
        if (!$id) throw new Exception("ID required.");
        $in   = json_decode(file_get_contents("php://input"), true);
        $code = strtoupper(trim($in['code'] ?? ''));
        $name = trim($in['name'] ?? '');
        if (strlen($code) !== 2) throw new Exception("Country code must be exactly 2 characters.");
        if ($name === '')        throw new Exception("Country name is required.");
        $stmt = $db->prepare("UPDATE countries SET code=?, name=? WHERE id=?");
        $stmt->execute([$code, $name, $id]);
        echo json_encode(["status" => "success", "message" => "Country updated."]);

    } elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) throw new Exception("ID required.");
        $stmt = $db->prepare("DELETE FROM countries WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Country deleted."]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
