<?php
class Database {
    private $host = '127.0.0.1';
    private $db_name = 'systems';
    private $db_port = "3306"; 
    private $username = 'root';
    private $password = 'root';
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";port=" . $this->db_port . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            http_response_code(500);
            echo json_encode(array(
                "error" => "Database connection failed",
                "message" => $exception->getMessage()
            ));
            exit();
        }

        return $this->conn;
    }
}
?>