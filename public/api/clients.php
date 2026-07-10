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
            $id = (int)$_GET['id'];
            $stmt = $db->prepare("SELECT c.*, co.name as country_name, co.code as country_code, COUNT(p.id) as project_count FROM clients c LEFT JOIN countries co ON co.id = c.country_id LEFT JOIN projects p ON p.client_id = c.id WHERE c.id = ? GROUP BY c.id");
            $stmt->execute([$id]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$client) { http_response_code(404); echo json_encode(["status"=>"error","message"=>"Client not found"]); exit; }

            $stmt2 = $db->prepare("SELECT id, name, description, status FROM projects WHERE client_id = ? ORDER BY created_at DESC");
            $stmt2->execute([$id]);
            $client['projects'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["status"=>"success","data"=>$client]);
        } else {
            $stmt = $db->query("SELECT c.*, co.name as country_name, co.code as country_code, COUNT(p.id) as project_count FROM clients c LEFT JOIN countries co ON co.id = c.country_id LEFT JOIN projects p ON p.client_id = c.id GROUP BY c.id ORDER BY c.created_at DESC");
            echo json_encode(["status"=>"success","data"=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
    } elseif ($method === 'POST') {
        $in = json_decode(file_get_contents("php://input"), true);
        $stmt = $db->prepare("INSERT INTO clients (name, company, email, phone, country_id, notes) VALUES (?,?,?,?,?,?)");
        $cname = trim($in['name'] ?? '');
        if ($cname === '') throw new Exception('Client name is required.');
        $countryId = !empty($in['country_id']) ? (int)$in['country_id'] : null;
        $stmt->execute([$cname, trim($in['company']??'')?: null, trim($in['email']??'')?: null, trim($in['phone']??'')?: null, $countryId, trim($in['notes']??'')?: null]);
        echo json_encode(["status"=>"success","message"=>"Client created.","id"=>$db->lastInsertId()]);
    } elseif ($method === 'PUT') {
        if (!isset($_GET['id'])) throw new Exception("ID required");
        $id = (int)$_GET['id'];
        $in = json_decode(file_get_contents("php://input"), true);
        $stmt = $db->prepare("UPDATE clients SET name=?, company=?, email=?, phone=?, country_id=?, notes=? WHERE id=?");
        $cname = trim($in['name'] ?? '');
        if ($cname === '') throw new Exception('Client name is required.');
        $countryId = !empty($in['country_id']) ? (int)$in['country_id'] : null;
        $stmt->execute([$cname, trim($in['company']??'')?: null, trim($in['email']??'')?: null, trim($in['phone']??'')?: null, $countryId, trim($in['notes']??'')?: null, $id]);
        echo json_encode(["status"=>"success","message"=>"Client updated."]);
    } elseif ($method === 'DELETE') {
        if (!isset($_GET['id'])) throw new Exception("ID required");
        $id = (int)$_GET['id'];
        $db->prepare("UPDATE projects SET client_id = NULL WHERE client_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM clients WHERE id = ?")->execute([$id]);
        echo json_encode(["status"=>"success","message"=>"Client deleted."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
}
?>
