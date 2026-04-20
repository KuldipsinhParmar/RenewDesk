<?php
// ============================================================
// Cron Trigger Endpoint
// URL: https://yourdomain.com/api/cron_trigger.php?key=YOUR_SECRET_KEY
// ============================================================

// --- Enable full error reporting & logging ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__, 2) . '/cron/cron_debug.log');

header("Content-Type: application/json; charset=UTF-8");

// --- Log helper ---
function cronLog($msg) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = dirname(__DIR__, 2) . '/cron/cron_debug.log';
    file_put_contents($logFile, "[$timestamp] $msg\n", FILE_APPEND);
}

cronLog("========== CRON TRIGGER START ==========");
cronLog("IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'CLI'));
cronLog("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A'));

// --- Secret key to prevent unauthorized access ---
$secretKey = 'RD-CRON-2024-xK9mP3qW7vN1';

// Validate key
if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    cronLog("ERROR: Unauthorized access attempt. Key: " . ($_GET['key'] ?? 'none'));
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Unauthorized. Invalid cron key."]);
    exit;
}

cronLog("Key validated successfully.");

try {
    // Check if cron script exists
    $cronFile = dirname(__DIR__, 2) . '/cron/send_reminders.php';
    cronLog("Cron file path: $cronFile");
    cronLog("File exists: " . (file_exists($cronFile) ? 'YES' : 'NO'));

    if (!file_exists($cronFile)) {
        cronLog("ERROR: Cron script not found at $cronFile");
        throw new Exception("Cron script not found at: $cronFile");
    }

    // Capture output from the cron script
    cronLog("Executing cron script...");
    ob_start();
    require_once $cronFile;
    $output = ob_get_clean();
    cronLog("Cron script output: $output");

    cronLog("========== CRON TRIGGER SUCCESS ==========");

    echo json_encode([
        "status" => "success",
        "message" => "Cron job executed successfully.",
        "output" => $output,
        "executed_at" => date('Y-m-d H:i:s T')
    ]);

} catch (Exception $e) {
    cronLog("ERROR: " . $e->getMessage());
    cronLog("Stack trace: " . $e->getTraceAsString());
    cronLog("========== CRON TRIGGER FAILED ==========");

    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
        "executed_at" => date('Y-m-d H:i:s T')
    ]);
}
?>
