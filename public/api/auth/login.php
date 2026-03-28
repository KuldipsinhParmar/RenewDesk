<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once dirname(__DIR__, 3) . '/api/config/session.php';

$data = json_decode(file_get_contents("php://input"));

if(!isset($data->email) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(array("status" => "error", "message" => "Incomplete data."));
    exit();
}

require_once dirname(__DIR__, 3) . '/api/config/db.php';
$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, name, email, password FROM admin WHERE email = :email LIMIT 0,1";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $data->email);

if($stmt->execute()) {
    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $password_hash = $row['password'];
        
        // Verify Password
        if(password_verify($data->password, $password_hash)) {
            // Setup Session
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
            http_response_code(401);
            echo json_encode(array("status" => "error", "message" => "Login failed. Incorrect password."));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("status" => "error", "message" => "Login failed. User not found."));
    }
} else {
    http_response_code(500);
    echo json_encode(array("status" => "error", "message" => "Database error."));
}
?>
