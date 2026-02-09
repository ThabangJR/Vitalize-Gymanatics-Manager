<?php

class Database {
    private $conn;
    public function __construct() {
        $servername = "localhost:3306";
        $username = "root";
        $password = "Thabang@23768";
        $dbname = "vitalize_gym";
        $this->conn = new mysqli($servername, $username, $password, $dbname);
        if ($this->conn->connect_error) {
            throw new RuntimeException("Connection failed: " . $this->conn->connect_error);
        }
    }
    public function getConnection() {
        return $this->conn;
    }
}
?>