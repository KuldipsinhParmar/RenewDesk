<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // Load env via simple parser or hardcode fallback if .env not loaded perfectly
        // For simplicity in plain PHP without a library, we'll parse .env manually
        $env = parse_ini_file(__DIR__ . '/../../.env');
        
        $this->host = $env['DB_HOST'] ?? 'renewdesk_db';
        $this->db_name = $env['DB_NAME'] ?? 'renewdesk_db';
        $this->username = $env['DB_USER'] ?? 'renewdesk_user';
        $this->password = $env['DB_PASS'] ?? 'renewdesk_pass';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo json_encode(['status' => 'error', 'message' => "Connection error: " . $exception->getMessage()]);
            exit;
        }
        return $this->conn;
    }
}
?>
