<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once dirname(__DIR__) . '/config/session.php';

$data = json_decode(file_get_contents("php://input"));

if(!isset($data->email) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(array("status" => "error", "message" => "Incomplete data."));
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $email = strtolower(trim($data->email));
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $maxAttempts = 5;
    $windowMinutes = 15;

    // Best-effort brute-force throttle. Wrapped so a missing/un-migrated
    // login_attempts table degrades to "no throttle" instead of breaking login.
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        $stmt->execute([$email, $windowMinutes]);
        if ($stmt->fetchColumn() >= $maxAttempts) {
            http_response_code(429);
            echo json_encode(["status" => "error", "message" => "Too many failed attempts. Please try again in a few minutes."]);
            exit();
        }
    } catch (PDOException $e) {
        error_log("[login.php] attempt-check skipped: " . $e->getMessage());
    }

    $query = "SELECT id, name, email, password FROM admin WHERE email = :email LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);

    $authenticated = false;
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($data->password, $row['password'])) {
            $authenticated = true;
        }
    }

    if ($authenticated) {
        try {
            $db->prepare("DELETE FROM login_attempts WHERE email = ?")->execute([$email]);
        } catch (PDOException $e) {
            error_log("[login.php] attempt-clear skipped: " . $e->getMessage());
        }

        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['admin_name'] = $row['name'];
        $_SESSION['logged_in'] = true;

        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "message" => "Login successful.",
            "user" => [
                "name" => $row['name'],
                "email" => $row['email']
            ]
        ));
    } else {
        try {
            $db->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)")->execute([$email, $ip]);
        } catch (PDOException $e) {
            error_log("[login.php] attempt-log skipped: " . $e->getMessage());
        }

        // Same message whether the email doesn't exist or the password is
        // wrong — distinguishing the two lets an attacker enumerate admin emails.
        http_response_code(401);
        echo json_encode(array("status" => "error", "message" => "Invalid email or password."));
    }
} catch (PDOException $e) {
    error_log("[login.php] " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => (function_exists('renewdesk_debug') && renewdesk_debug()) ? $e->getMessage() : "A database error occurred."]);
} catch (Throwable $e) {
    error_log("[login.php] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => (function_exists('renewdesk_debug') && renewdesk_debug()) ? ($e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine()) : "An unexpected error occurred."]);
}
?>
