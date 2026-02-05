<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'prop_custodian_db';
    private $db_port = "3306"; 
<<<<<<< HEAD
    private $username = 'root';
    private $password = 'root';

=======
    private $username = 'prop_custodian_db';
    private $password = '123';
>>>>>>> fa6a104dc6be42e918703977ce565121b21da755
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