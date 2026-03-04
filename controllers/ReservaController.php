<?php
require_once __DIR__ . "/../models/Reserva.php";

class ReservaController {
    public function index() {
        $reserva = new Reserva();
        $stmt = $reserva->obtenerTodas();
        $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . "/../views/recepcionista_dashboard.php";
    }
}
?>