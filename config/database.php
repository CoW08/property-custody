<?php
require_once __DIR__ . '/config.php';

class Database {
    private $host;
    private $db_name;
    private $db_port;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
        $this->db_name = defined('DB_NAME') ? DB_NAME : 'systems';
        $this->db_port = defined('DB_PORT') ? DB_PORT : '3306';
        $this->username = defined('DB_USER') ? DB_USER : 'root';
        $this->password = defined('DB_PASS') ? DB_PASS : 'root';
    }

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