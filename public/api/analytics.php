<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

$db = (new Database())->getConnection();
$type = $_GET['type'] ?? '';
$year = (int)($_GET['year'] ?? date('Y'));

try {
    if ($type === 'revenue') {
        // Per-client revenue for the selected year: contract value vs paid vs outstanding,
        // broken down by asset type. Mirrors dashboard.php's "any status counts toward the
        // year" rule (a record already renewed still belongs to its original calendar year).
        $stmt = $db->prepare("
            SELECT c.id as client_id, c.name as client_name,
                COALESCE(d.total,0) as domain_total, COALESCE(d.paid,0) as domain_paid,
                COALESCE(h.total,0) as hosting_total, COALESCE(h.paid,0) as hosting_paid,
                COALESCE(m.total,0) as amc_total, COALESCE(m.paid,0) as amc_paid
            FROM clients c
            LEFT JOIN (
                SELECT p.client_id, SUM(d.price) as total, SUM(CASE WHEN d.client_paid=1 THEN d.price ELSE 0 END) as paid
                FROM domains d JOIN projects p ON p.id = d.project_id
                WHERE YEAR(d.renewal_date) = ? GROUP BY p.client_id
            ) d ON d.client_id = c.id
            LEFT JOIN (
                SELECT p.client_id, SUM(h.price) as total, SUM(CASE WHEN h.client_paid=1 THEN h.price ELSE 0 END) as paid
                FROM hosting h JOIN projects p ON p.id = h.project_id
                WHERE YEAR(h.renewal_date) = ? GROUP BY p.client_id
            ) h ON h.client_id = c.id
            LEFT JOIN (
                SELECT p.client_id, SUM(m.price) as total, SUM(CASE WHEN m.client_paid=1 THEN m.price ELSE 0 END) as paid
                FROM maintenance m JOIN projects p ON p.id = m.project_id
                WHERE YEAR(m.start_date) = ? GROUP BY p.client_id
            ) m ON m.client_id = c.id
            HAVING (domain_total + hosting_total + amc_total) > 0
            ORDER BY (domain_total + hosting_total + amc_total) DESC
        ");
        $stmt->execute([$year, $year, $year]);
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC), "year" => $year]);
    }
    elseif ($type === 'renewal_history') {
        $stmt = $db->query("
            (SELECT 'Domain' as type, d.domain_name as name, p.name as project, c.name as client_name, d.renewal_date as date, d.price, d.client_paid
             FROM domains d JOIN projects p ON p.id = d.project_id LEFT JOIN clients c ON c.id = p.client_id WHERE d.status = 'renewed')
            UNION ALL
            (SELECT 'Hosting', COALESCE(h.plan_name, h.provider, 'Hosting'), p.name, c.name, h.renewal_date, h.price, h.client_paid
             FROM hosting h JOIN projects p ON p.id = h.project_id LEFT JOIN clients c ON c.id = p.client_id WHERE h.status = 'renewed')
            UNION ALL
            (SELECT 'Maintenance', 'AMC Contract', p.name, c.name, m.end_date, m.price, m.client_paid
             FROM maintenance m JOIN projects p ON p.id = m.project_id LEFT JOIN clients c ON c.id = p.client_id WHERE m.status = 'renewed')
            ORDER BY date DESC
            LIMIT 500
        ");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    elseif ($type === 'client_cost') {
        // Lifetime billed value per client across all years/records, plus outstanding balance.
        $stmt = $db->query("
            SELECT c.id as client_id, c.name as client_name, c.company,
                COALESCE(d.total,0) + COALESCE(h.total,0) + COALESCE(m.total,0) as lifetime_total,
                COALESCE(d.total,0) - COALESCE(d.paid,0) + COALESCE(h.total,0) - COALESCE(h.paid,0) + COALESCE(m.total,0) - COALESCE(m.paid,0) as outstanding,
                COALESCE(proj.project_count, 0) as project_count
            FROM clients c
            LEFT JOIN (SELECT p.client_id, SUM(d.price) as total, SUM(CASE WHEN d.client_paid=1 THEN d.price ELSE 0 END) as paid FROM domains d JOIN projects p ON p.id = d.project_id GROUP BY p.client_id) d ON d.client_id = c.id
            LEFT JOIN (SELECT p.client_id, SUM(h.price) as total, SUM(CASE WHEN h.client_paid=1 THEN h.price ELSE 0 END) as paid FROM hosting h JOIN projects p ON p.id = h.project_id GROUP BY p.client_id) h ON h.client_id = c.id
            LEFT JOIN (SELECT p.client_id, SUM(m.price) as total, SUM(CASE WHEN m.client_paid=1 THEN m.price ELSE 0 END) as paid FROM maintenance m JOIN projects p ON p.id = m.project_id GROUP BY p.client_id) m ON m.client_id = c.id
            LEFT JOIN (SELECT client_id, COUNT(*) as project_count FROM projects GROUP BY client_id) proj ON proj.client_id = c.id
            HAVING lifetime_total > 0
            ORDER BY lifetime_total DESC
        ");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    elseif ($type === 'overdue') {
        $stmt = $db->query("
            (SELECT 'Domain' as type, d.domain_name as name, p.name as project, d.renewal_date as date, d.price, DATEDIFF(CURDATE(), d.renewal_date) as days_overdue
             FROM domains d JOIN projects p ON p.id = d.project_id WHERE d.status = 'active' AND d.client_paid = 0 AND d.renewal_date < CURDATE())
            UNION ALL
            (SELECT 'Hosting', COALESCE(h.plan_name, h.provider, 'Hosting'), p.name, h.renewal_date, h.price, DATEDIFF(CURDATE(), h.renewal_date)
             FROM hosting h JOIN projects p ON p.id = h.project_id WHERE h.status = 'active' AND h.client_paid = 0 AND h.renewal_date < CURDATE())
            UNION ALL
            (SELECT 'Maintenance', 'AMC Contract', p.name, m.end_date, m.price, DATEDIFF(CURDATE(), m.end_date)
             FROM maintenance m JOIN projects p ON p.id = m.project_id WHERE m.status = 'active' AND m.client_paid = 0 AND m.end_date < CURDATE())
            UNION ALL
            (SELECT 'Backup', CONCAT('Backup (', b.frequency, ')'), p.name, b.next_backup, 0, DATEDIFF(CURDATE(), b.next_backup)
             FROM backups b JOIN projects p ON p.id = b.project_id WHERE b.is_done = 0 AND b.next_backup < CURDATE())
            ORDER BY days_overdue DESC
        ");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    elseif ($type === 'task_time') {
        $stmt = $db->prepare("
            SELECT p.id as project_id, p.name as project_name, c.name as client_name,
                COUNT(t.id) as task_count, SUM(t.hours) as total_hours,
                MIN(t.task_date) as first_task, MAX(t.task_date) as last_task
            FROM tasks t
            JOIN projects p ON p.id = t.project_id
            LEFT JOIN clients c ON c.id = p.client_id
            WHERE YEAR(t.task_date) = ?
            GROUP BY p.id
            ORDER BY total_hours DESC
        ");
        $stmt->execute([$year]);
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC), "year" => $year]);
    }
    elseif ($type === 'backup_compliance') {
        $stmt = $db->query("
            SELECT b.id, p.name as project, b.frequency, b.last_backup, b.next_backup, b.is_done,
                CASE
                    WHEN b.next_backup IS NULL THEN 'unscheduled'
                    WHEN b.next_backup < CURDATE() AND b.is_done = 0 THEN 'overdue'
                    WHEN b.is_done = 1 THEN 'done'
                    ELSE 'on_schedule'
                END as compliance
            FROM backups b JOIN projects p ON p.id = b.project_id
            ORDER BY (b.next_backup < CURDATE() AND b.is_done = 0) DESC, b.next_backup ASC
        ");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Unknown report type."]);
    }
} catch (PDOException $e) {
    error_log("[analytics.php] " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => renewdesk_debug() ? $e->getMessage() : "A database error occurred."]);
} catch (Throwable $e) {
    // Catches fatal PHP errors (TypeError, etc.) that PDOException misses,
    // so the client always gets JSON instead of a blank 500.
    error_log("[analytics.php] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => (function_exists('renewdesk_debug') && renewdesk_debug()) ? ($e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine()) : "An unexpected error occurred."]);
}
?>
