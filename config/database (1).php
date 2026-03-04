<?php
// Configuração de conexão com o banco de dados
class Database {
    private $host = "209.50.227.136";
    private $db_name = "sanveg";
    private $username = "sanveg";
    private $password = "xD5MhfGRknjz8hZQ";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8mb4");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Database: " . $exception->getMessage());
            return null;
        }
        
        return $this->conn;
    }
}
?>