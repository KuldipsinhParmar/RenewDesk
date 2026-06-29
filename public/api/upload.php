<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once dirname(__DIR__, 2) . '/api/config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

try {
    $file = $_FILES['logo'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No file uploaded or upload error (code: " . ($file['error'] ?? 'none') . ")");
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes, true)) {
        throw new Exception("Invalid file type. Allowed: JPG, PNG, GIF, WebP, SVG.");
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception("File too large. Maximum size is 2 MB.");
    }

    $uploadDir = dirname(__DIR__) . '/assets/uploads/logos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'logo_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new Exception("Failed to save uploaded file.");
    }

    echo json_encode(["status" => "success", "url" => "assets/uploads/logos/" . $filename]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
