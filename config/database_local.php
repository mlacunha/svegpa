<?php
/**
 * Conexão com o banco local (sveg) para sincronização.
 * Usado por sync_web_local.php
 */
class DatabaseLocal {
    private $host = '127.0.0.1';
    private $port = 3306;
    private $db_name = 'sveg';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function __construct() {
        $this->host     = getenv('DB_LOCAL_HOST') ?: '127.0.0.1';
        $this->port     = (int)(getenv('DB_LOCAL_PORT') ?: 3306);
        $this->db_name  = getenv('DB_LOCAL_NAME') ?: 'sveg';
        $this->username = getenv('DB_LOCAL_USER') ?: 'root';
        $this->password = getenv('DB_LOCAL_PASS') ?: '';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            error_log("Database Local SVEG: " . $e->getMessage());
            return null;
        }
        return $this->conn;
    }
}
