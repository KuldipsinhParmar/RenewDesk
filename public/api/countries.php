<?php
header("Content-Type: application/json; charset=UTF-8");

require_once dirname(__DIR__, 2) . '/api/config/auth.php';
require_once dirname(__DIR__, 2) . '/api/config/db.php';

$db = (new Database())->getConnection();
$stmt = $db->query("SELECT id, code, name FROM countries ORDER BY name ASC");
echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
?>
