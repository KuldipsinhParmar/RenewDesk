<?php

require_once __DIR__ . '/env.php';

class Database
{
    private string $host;
    private string $port;
    private string $db_name;
    private string $username;
    private string $password;
    public $conn;

    public function __construct()
    {
        renewdesk_bootstrap_env();

        $this->host = renewdesk_env('DB_HOST', 'renewdesk_db');
        $this->port = renewdesk_env('DB_PORT', '3306');
        $this->db_name = renewdesk_env('DB_NAME', 'renewdesk_db');
        $this->username = renewdesk_env('DB_USER', 'renewdesk_user');
        $this->password = renewdesk_env('DB_PASS', 'renewdesk_pass');
    }

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec('set names utf8');
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            $msg = renewdesk_debug()
                ? $exception->getMessage()
                : 'Database connection failed.';
            echo json_encode(['status' => 'error', 'message' => 'Connection error: ' . $msg]);
            exit;
        }

        return $this->conn;
    }
}
