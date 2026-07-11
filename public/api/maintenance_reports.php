<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once dirname(__DIR__, 2) . '/api/config/auth.php';
require_once dirname(__DIR__, 2) . '/api/config/db.php';

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $db->prepare("SELECT r.*, p.name as project_name, p.logo_url as project_logo_url FROM maintenance_reports r JOIN projects p ON r.project_id = p.id WHERE r.id = ?");
            $stmt->execute([$id]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$report) throw new Exception("Report not found");
            $report['sections'] = json_decode($report['sections'], true);
            echo json_encode(["status" => "success", "data" => $report]);
        } else {
            $projectId = $_GET['project_id'] ?? null;
            $sql = "SELECT r.id, r.project_id, r.website_url, r.report_date, r.period_start, r.period_end, r.prepared_by, r.reviewed_by, r.overall_health, r.status, r.sections, r.created_at, p.name as project_name
                    FROM maintenance_reports r JOIN projects p ON r.project_id = p.id";
            $params = [];
            if ($projectId) { $sql .= " WHERE r.project_id = ?"; $params[] = $projectId; }
            $sql .= " ORDER BY r.report_date DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($reports as &$r) {
                $sections = json_decode($r['sections'], true) ?: [];
                $r['sections_count'] = count($sections);
                unset($r['sections']);
            }
            echo json_encode(["status" => "success", "data" => $reports]);
        }
    }
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        $stmt = $db->prepare("INSERT INTO maintenance_reports (project_id, website_url, report_date, period_start, period_end, prepared_by, reviewed_by, overall_health, status, sections) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['project_id'],
            $input['website_url'] ?? null,
            $input['report_date'],
            $input['period_start'] ?? null,
            $input['period_end'] ?? null,
            $input['prepared_by'] ?? null,
            $input['reviewed_by'] ?? null,
            $input['overall_health'] ?? 'good',
            $input['status'] ?? 'draft',
            json_encode($input['sections'] ?? [])
        ]);
        echo json_encode(["status" => "success", "message" => "Report created.", "id" => $db->lastInsertId()]);
    }
    elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");
        $input = json_decode(file_get_contents("php://input"), true);
        $stmt = $db->prepare("UPDATE maintenance_reports SET website_url=?, report_date=?, period_start=?, period_end=?, prepared_by=?, reviewed_by=?, overall_health=?, status=?, sections=? WHERE id=?");
        $stmt->execute([
            $input['website_url'] ?? null,
            $input['report_date'],
            $input['period_start'] ?? null,
            $input['period_end'] ?? null,
            $input['prepared_by'] ?? null,
            $input['reviewed_by'] ?? null,
            $input['overall_health'] ?? 'good',
            $input['status'] ?? 'draft',
            json_encode($input['sections'] ?? []),
            $id
        ]);
        echo json_encode(["status" => "success", "message" => "Report updated."]);
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");
        $stmt = $db->prepare("DELETE FROM maintenance_reports WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Report deleted."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
