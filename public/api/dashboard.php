<?php
header("Content-Type: application/json; charset=UTF-8");

require_once dirname(__DIR__, 2) . '/api/config/auth.php';
require_once dirname(__DIR__, 2) . '/api/config/db.php';

$db = (new Database())->getConnection();

try {
    $data = [];
    
    // Counts
    $stmt = $db->query("SELECT COUNT(*) as c FROM projects WHERE status='active'");
    $data['total_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

    // AMC contracts don't reliably use status='active' for "current" — a contract
    // already marked renewed (paid ahead) still covers today until its end_date.
    // So "current" means the contract period actually contains today, regardless of status.
    $stmt = $db->query("SELECT SUM(price) as rev FROM maintenance WHERE CURDATE() BETWEEN start_date AND end_date");
    $data['amc_contract_value'] = $stmt->fetch(PDO::FETCH_ASSOC)['rev'] ?: 0;
    $stmt = $db->query("SELECT SUM(price) as rev FROM maintenance WHERE CURDATE() BETWEEN start_date AND end_date AND client_paid=1");
    $data['amc_paid'] = $stmt->fetch(PDO::FETCH_ASSOC)['rev'] ?: 0;

    // Don't filter by status='active' here — a record already marked 'renewed'
    // (paid ahead of its actual renewal_date) still belongs to that date's calendar
    // year. Each domain/hosting chain has at most one record per year, so filtering
    // by year alone (any status) correctly captures "this year's" contract value.
    // "My Own" is the user's personal project, not client business — excluded here
    // since it's already broken out separately in my_own_cost.
    $stmt = $db->query("SELECT SUM(h.price) as rev FROM hosting h JOIN projects p ON p.id = h.project_id WHERE p.name != 'My Own' AND YEAR(h.renewal_date) = YEAR(CURDATE())");
    $data['hosting_contract_value'] = $stmt->fetch(PDO::FETCH_ASSOC)['rev'] ?: 0;
    $stmt = $db->query("SELECT SUM(h.price) as rev FROM hosting h JOIN projects p ON p.id = h.project_id WHERE p.name != 'My Own' AND h.client_paid=1 AND YEAR(h.renewal_date) = YEAR(CURDATE())");
    $data['hosting_paid'] = $stmt->fetch(PDO::FETCH_ASSOC)['rev'] ?: 0;

    $stmt = $db->query("SELECT SUM(d.price) as rev FROM domains d JOIN projects p ON p.id = d.project_id WHERE p.name != 'My Own' AND YEAR(d.renewal_date) = YEAR(CURDATE())");
    $data['domain_contract_value'] = $stmt->fetch(PDO::FETCH_ASSOC)['rev'] ?: 0;
    $stmt = $db->query("SELECT SUM(d.price) as rev FROM domains d JOIN projects p ON p.id = d.project_id WHERE p.name != 'My Own' AND d.client_paid=1 AND YEAR(d.renewal_date) = YEAR(CURDATE())");
    $data['domain_paid'] = $stmt->fetch(PDO::FETCH_ASSOC)['rev'] ?: 0;

    $stmt = $db->query("SELECT COUNT(*) as c FROM backups");
    $data['total_backups'] = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM clients");
    $data['total_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

    // "My Own" — the user's personal/internal project (no client attached).
    // Surface its own cost separately from client contract totals.
    $stmt = $db->prepare("SELECT id FROM projects WHERE name = 'My Own' LIMIT 1");
    $stmt->execute();
    $myOwnId = $stmt->fetchColumn();
    $data['my_own_cost'] = 0;
    $data['my_own_domains'] = 0;
    $data['my_own_hosting'] = 0;
    if ($myOwnId) {
        // Current year only, same rule as the client contract totals above.
        $stmt = $db->prepare("SELECT COALESCE(SUM(price),0) FROM domains WHERE project_id = ? AND YEAR(renewal_date) = YEAR(CURDATE())");
        $stmt->execute([$myOwnId]);
        $data['my_own_domains'] = $stmt->fetchColumn() ?: 0;

        $stmt = $db->prepare("SELECT COALESCE(SUM(price),0) FROM hosting WHERE project_id = ? AND YEAR(renewal_date) = YEAR(CURDATE())");
        $stmt->execute([$myOwnId]);
        $data['my_own_hosting'] = $stmt->fetchColumn() ?: 0;

        $stmt = $db->prepare("SELECT COALESCE(SUM(price),0) FROM maintenance WHERE project_id = ? AND CURDATE() BETWEEN start_date AND end_date");
        $stmt->execute([$myOwnId]);
        $myOwnAmc = $stmt->fetchColumn() ?: 0;

        $data['my_own_cost'] = $data['my_own_domains'] + $data['my_own_hosting'] + $myOwnAmc;
    }

    // Not-yet-done items due within the next 30 days — includes already-overdue
    // ones (no lower bound), unlike the old BETWEEN CURDATE()..+30 which hid those.
    $upcoming = [];

    // Domains
    $stmt = $db->query("SELECT 'Domain' as type, d.id, d.domain_name as name, d.renewal_date as date, p.name as project, DATEDIFF(d.renewal_date, CURDATE()) as days_left
                        FROM domains d JOIN projects p ON d.project_id = p.id
                        WHERE d.status = 'active' AND d.renewal_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY d.renewal_date ASC");
    $upcoming = array_merge($upcoming, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Hosting
    $stmt = $db->query("SELECT 'Hosting' as type, h.id, h.plan_name as name, h.renewal_date as date, p.name as project, DATEDIFF(h.renewal_date, CURDATE()) as days_left
                        FROM hosting h JOIN projects p ON h.project_id = p.id
                        WHERE h.status = 'active' AND h.renewal_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY h.renewal_date ASC");
    $upcoming = array_merge($upcoming, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Maintenance
    $stmt = $db->query("SELECT 'Maintenance' as type, m.id, 'AMC Contract' as name, m.end_date as date, p.name as project, DATEDIFF(m.end_date, CURDATE()) as days_left
                        FROM maintenance m JOIN projects p ON m.project_id = p.id
                        WHERE m.status = 'active' AND m.end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY m.end_date ASC");
    $upcoming = array_merge($upcoming, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Backups
    $stmt = $db->query("SELECT 'Backup' as type, b.id, b.project_id, CONCAT('Backup (', b.frequency, ')') as name, b.frequency, b.next_backup as date, b.last_backup as extra, b.storage_location, b.is_done, p.name as project, DATEDIFF(b.next_backup, CURDATE()) as days_left
                        FROM backups b JOIN projects p ON b.project_id = p.id
                        WHERE b.is_done = 0 AND b.next_backup <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY b.next_backup ASC");
    $upcoming = array_merge($upcoming, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Sort by days left
    usort($upcoming, function($a, $b) { return $a['days_left'] <=> $b['days_left']; });

    $data['upcoming'] = $upcoming;

    // Recently expired (domains + hosting + maintenance)
    $expired = [];
    $stmt = $db->query("SELECT 'Domain' as type, d.domain_name as name, p.name as project, d.renewal_date as date FROM domains d JOIN projects p ON d.project_id = p.id WHERE d.status = 'active' AND d.renewal_date < CURDATE() ORDER BY d.renewal_date DESC LIMIT 20");
    $expired = array_merge($expired, $stmt->fetchAll(PDO::FETCH_ASSOC));

    $stmt = $db->query("SELECT 'Hosting' as type, h.plan_name as name, p.name as project, h.renewal_date as date FROM hosting h JOIN projects p ON h.project_id = p.id WHERE h.status = 'active' AND h.renewal_date < CURDATE() ORDER BY h.renewal_date DESC LIMIT 20");
    $expired = array_merge($expired, $stmt->fetchAll(PDO::FETCH_ASSOC));

    $stmt = $db->query("SELECT 'Maintenance' as type, 'AMC Contract' as name, p.name as project, m.end_date as date FROM maintenance m JOIN projects p ON m.project_id = p.id WHERE m.status = 'active' AND m.end_date < CURDATE() ORDER BY m.end_date DESC LIMIT 20");
    $expired = array_merge($expired, $stmt->fetchAll(PDO::FETCH_ASSOC));

    usort($expired, function($a, $b) { return strcmp($b['date'], $a['date']); });
    $data['expired'] = array_slice($expired, 0, 30);

    echo json_encode(["status" => "success", "data" => $data]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
