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

        // No hardcoded fallbacks here on purpose — those used to default to
        // the local Docker dev credentials, which would silently point a
        // production deploy at a host/user that doesn't exist there instead
        // of failing clearly. A missing .env should fail loudly (below),
        // never fall back to another environment's credentials.
        $this->host = renewdesk_env('DB_HOST', '') ?? '';
        $this->port = renewdesk_env('DB_PORT', '3306') ?? '3306';
        $this->db_name = renewdesk_env('DB_NAME', '') ?? '';
        $this->username = renewdesk_env('DB_USER', '') ?? '';
        $this->password = renewdesk_env('DB_PASS', '') ?? '';
    }

    public function getConnection()
    {
        $this->conn = null;

        $missing = [];
        if ($this->host === '') $missing[] = 'DB_HOST';
        if ($this->db_name === '') $missing[] = 'DB_NAME';
        if ($this->username === '') $missing[] = 'DB_USER';
        if (!empty($missing)) {
            $msg = renewdesk_debug()
                ? 'Missing required .env value(s): ' . implode(', ', $missing)
                : 'Server is not configured correctly.';
            error_log('[db.php] Missing required .env value(s): ' . implode(', ', $missing));
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $msg]);
            exit;
        }

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
