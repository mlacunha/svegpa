<?php
// Configuração de conexão com o banco de dados
// Em produção: defina DB_HOST, DB_NAME, DB_USER, DB_PASS no ambiente para sobrescrever
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host     = getenv('DB_HOST')     ?: '209.50.227.136';
        $this->db_name  = getenv('DB_NAME')     ?: 'sanveg';
        $this->username = getenv('DB_USER')      ?: 'sanveg';
        $this->password = getenv('DB_PASS')     ?: 'xD5MhfGRknjz8hZQ';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $exception) {
            error_log("Database SVEG: " . $exception->getMessage());
            return null;
        }

        return $this->conn;
    }
}