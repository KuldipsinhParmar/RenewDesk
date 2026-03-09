<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
session_start();
session_destroy();
http_response_code(200);
echo json_encode(["status" => "success", "message" => "Logged out successfully."]);
?>
