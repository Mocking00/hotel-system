<?php
require_once __DIR__ . "/../config/Database.php";

class Reserva {
    private $conn;
    private $table = "RESERVA";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function obtenerTodas() {
        $query = "SELECT * FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>