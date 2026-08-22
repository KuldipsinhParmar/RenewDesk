<?php
// Standalone diagnostics endpoint — deliberately does NOT require auth.php,
// so it still works to diagnose a broken deployment even if login itself
// is failing (e.g. config didn't deploy correctly). Reveals no secrets:
// only booleans/counts, and full error text only when APP_DEBUG=true.
header("Content-Type: application/json; charset=UTF-8");

$report = [
    "php_version" => PHP_VERSION,
    "config_loaded" => false,
    "db_connected" => false,
    "tables" => [],
    "errors" => [],
];

try {
    require_once __DIR__ . '/config/env.php';
    require_once __DIR__ . '/config/db.php';
    $report["config_loaded"] = true;
    $debug = function_exists('renewdesk_debug') && renewdesk_debug();

    try {
        $db = (new Database())->getConnection();
        $report["db_connected"] = true;

        $expectedTables = ['admin', 'projects', 'clients', 'domains', 'hosting', 'maintenance', 'backups', 'tasks', 'settings', 'countries', 'invoices', 'invoice_items', 'login_attempts', 'maintenance_reports'];
        $stmt = $db->query("SHOW TABLES");
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($expectedTables as $t) {
            $report["tables"][$t] = in_array($t, $existing, true);
        }
    } catch (Throwable $e) {
        $report["errors"][] = "DB connection failed" . ($debug ? (": " . $e->getMessage()) : "");
    }
} catch (Throwable $e) {
    $report["errors"][] = "Config failed to load" . ((function_exists('renewdesk_debug') && renewdesk_debug()) ? (": " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine()) : "");
}

$report["status"] = ($report["config_loaded"] && $report["db_connected"] && empty($report["errors"])) ? "ok" : "degraded";
echo json_encode($report, JSON_PRETTY_PRINT);
?>
