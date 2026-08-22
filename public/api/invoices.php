<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

function nextInvoiceNumber($db) {
    $stmt = $db->prepare("SELECT `value` FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute(['invoice_prefix']);
    $prefix = $stmt->fetchColumn();
    if ($prefix === false || $prefix === '') $prefix = 'INV-';

    $stmt->execute(['invoice_next_number']);
    $next = (int)($stmt->fetchColumn() ?: 1);

    $db->prepare("UPDATE settings SET `value` = ? WHERE `key` = 'invoice_next_number'")->execute([(string)($next + 1)]);

    return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
}

try {
    if ($method === 'GET' && isset($_GET['unpaid_for'])) {
        $projectId = $_GET['unpaid_for'];
        $items = [];

        $stmt = $db->prepare("SELECT id, domain_name, renewal_date, price FROM domains WHERE project_id = ? AND client_paid = 0");
        $stmt->execute([$projectId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $items[] = ['source_type' => 'domain', 'source_id' => $r['id'], 'description' => "Domain renewal — {$r['domain_name']}", 'qty' => 1, 'price' => $r['price']];
        }

        $stmt = $db->prepare("SELECT id, plan_name, provider, renewal_date, price FROM hosting WHERE project_id = ? AND client_paid = 0");
        $stmt->execute([$projectId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $label = $r['plan_name'] ?: ($r['provider'] ?: 'Hosting Plan');
            $items[] = ['source_type' => 'hosting', 'source_id' => $r['id'], 'description' => "Hosting renewal — {$label}", 'qty' => 1, 'price' => $r['price']];
        }

        $stmt = $db->prepare("SELECT id, start_date, end_date, price FROM maintenance WHERE project_id = ? AND client_paid = 0");
        $stmt->execute([$projectId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $items[] = ['source_type' => 'maintenance', 'source_id' => $r['id'], 'description' => "AMC Contract ({$r['start_date']} to {$r['end_date']})", 'qty' => 1, 'price' => $r['price']];
        }

        echo json_encode(["status" => "success", "data" => $items]);
    }
    elseif ($method === 'GET' && isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT i.*, c.name as client_name, c.company as client_company, c.email as client_email, p.name as project_name
                              FROM invoices i JOIN clients c ON i.client_id = c.id LEFT JOIN projects p ON i.project_id = p.id WHERE i.id = ?");
        $stmt->execute([$_GET['id']]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$invoice) throw new Exception("Invoice not found");

        $stmt = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$_GET['id']]);
        $invoice['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "data" => $invoice]);
    }
    elseif ($method === 'GET') {
        $where = [];
        $params = [];
        if (!empty($_GET['client_id']))  { $where[] = 'i.client_id = ?';  $params[] = $_GET['client_id']; }
        if (!empty($_GET['project_id'])) { $where[] = 'i.project_id = ?'; $params[] = $_GET['project_id']; }
        if (!empty($_GET['status']))     { $where[] = 'i.status = ?';     $params[] = $_GET['status']; }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmt = $db->prepare("SELECT i.*, c.name as client_name, p.name as project_name
                              FROM invoices i JOIN clients c ON i.client_id = c.id LEFT JOIN projects p ON i.project_id = p.id
                              $whereSql ORDER BY i.invoice_date DESC, i.id DESC");
        $stmt->execute($params);
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        if (empty($input['client_id'])) throw new Exception("Client is required");
        if (empty($input['items']) || !is_array($input['items'])) throw new Exception("At least one line item is required");

        $subtotal = 0;
        foreach ($input['items'] as $it) {
            $subtotal += (float)($it['qty'] ?? 1) * (float)($it['price'] ?? 0);
        }
        $discount = (float)($input['discount'] ?? 0);
        $grandTotal = max(0, $subtotal - $discount);

        $db->beginTransaction();
        try {
            $invoiceNumber = nextInvoiceNumber($db);
            $stmt = $db->prepare("INSERT INTO invoices (invoice_number, client_id, project_id, invoice_date, due_date, subtotal, discount, grand_total, currency, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $invoiceNumber, $input['client_id'], $input['project_id'] ?? null,
                $input['invoice_date'] ?? date('Y-m-d'), $input['due_date'] ?? null,
                $subtotal, $discount, $grandTotal, $input['currency'] ?? 'INR',
                $input['status'] ?? 'draft', $input['notes'] ?? null
            ]);
            $invoiceId = $db->lastInsertId();

            $itemStmt = $db->prepare("INSERT INTO invoice_items (invoice_id, description, qty, price, total, source_type, source_id, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach (array_values($input['items']) as $idx => $it) {
                $qty = (float)($it['qty'] ?? 1);
                $price = (float)($it['price'] ?? 0);
                $itemStmt->execute([
                    $invoiceId, $it['description'] ?? 'Item', $qty, $price, $qty * $price,
                    $it['source_type'] ?? 'manual', $it['source_id'] ?? null, $idx
                ]);
            }

            $db->commit();
            echo json_encode(["status" => "success", "message" => "Invoice created.", "id" => $invoiceId, "invoice_number" => $invoiceNumber]);
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");
        $input = json_decode(file_get_contents("php://input"), true);

        // Status-only update (Mark Sent / Paid / Cancelled)
        if (isset($input['status']) && count($input) === 1) {
            $db->prepare("UPDATE invoices SET status = ? WHERE id = ?")->execute([$input['status'], $id]);

            if ($input['status'] === 'paid') {
                $stmt = $db->prepare("SELECT source_type, source_id FROM invoice_items WHERE invoice_id = ? AND source_id IS NOT NULL");
                $stmt->execute([$id]);
                $bySource = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $bySource[$r['source_type']][] = $r['source_id'];
                }
                $tableMap = ['domain' => 'domains', 'hosting' => 'hosting', 'maintenance' => 'maintenance'];
                foreach ($bySource as $type => $ids) {
                    if (!isset($tableMap[$type]) || empty($ids)) continue;
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $db->prepare("UPDATE {$tableMap[$type]} SET client_paid = 1 WHERE id IN ($placeholders)")->execute($ids);
                }
            }

            echo json_encode(["status" => "success", "message" => "Invoice status updated."]);
        } else {
            $subtotal = 0;
            foreach ($input['items'] ?? [] as $it) {
                $subtotal += (float)($it['qty'] ?? 1) * (float)($it['price'] ?? 0);
            }
            $discount = (float)($input['discount'] ?? 0);
            $grandTotal = max(0, $subtotal - $discount);

            $db->beginTransaction();
            try {
                $db->prepare("UPDATE invoices SET client_id=?, project_id=?, invoice_date=?, due_date=?, subtotal=?, discount=?, grand_total=?, notes=? WHERE id=?")
                    ->execute([
                        $input['client_id'], $input['project_id'] ?? null, $input['invoice_date'], $input['due_date'] ?? null,
                        $subtotal, $discount, $grandTotal, $input['notes'] ?? null, $id
                    ]);

                if (isset($input['items'])) {
                    $db->prepare("DELETE FROM invoice_items WHERE invoice_id = ?")->execute([$id]);
                    $itemStmt = $db->prepare("INSERT INTO invoice_items (invoice_id, description, qty, price, total, source_type, source_id, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach (array_values($input['items']) as $idx => $it) {
                        $qty = (float)($it['qty'] ?? 1);
                        $price = (float)($it['price'] ?? 0);
                        $itemStmt->execute([
                            $id, $it['description'] ?? 'Item', $qty, $price, $qty * $price,
                            $it['source_type'] ?? 'manual', $it['source_id'] ?? null, $idx
                        ]);
                    }
                }

                $db->commit();
                echo json_encode(["status" => "success", "message" => "Invoice updated."]);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
        }
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");
        $db->prepare("DELETE FROM invoices WHERE id=?")->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Invoice deleted."]);
    }
} catch (PDOException $e) {
    error_log("[invoices.php] " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => renewdesk_debug() ? $e->getMessage() : "A database error occurred."]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
