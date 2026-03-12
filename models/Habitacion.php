<?php
class Habitacion {
    private $conn;
    private $table = 'HABITACION';

    public $habitacion_id;
    public $numero;
    public $tipo;
    public $precio_noche;
    public $capacidad;
    public $estado;
    public $piso;
    public $descripcion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table . "
                  (numero, tipo, precio_noche, capacidad, estado, piso, descripcion)
                  VALUES (:numero, :tipo, :precio_noche, :capacidad, :estado, :piso, :descripcion)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':numero',       htmlspecialchars(strip_tags($this->numero)));
        $stmt->bindParam(':tipo',         htmlspecialchars(strip_tags($this->tipo)));
        $stmt->bindParam(':precio_noche', htmlspecialchars(strip_tags($this->precio_noche)));
        $stmt->bindParam(':capacidad',    htmlspecialchars(strip_tags($this->capacidad)));
        $stmt->bindParam(':estado',       htmlspecialchars(strip_tags($this->estado)));
        $stmt->bindParam(':piso',         htmlspecialchars(strip_tags($this->piso)));
        $stmt->bindParam(':descripcion',  htmlspecialchars(strip_tags($this->descripcion)));

        return $stmt->execute();
    }

    public function leerTodas($tipo = '', $estado = '') {
        $query = "SELECT * FROM " . $this->table . " WHERE 1=1";
        if (!empty($tipo))   $query .= " AND tipo = :tipo";
        if (!empty($estado)) $query .= " AND estado = :estado";
        $query .= " ORDER BY piso ASC, numero ASC";

        $stmt = $this->conn->prepare($query);
        if (!empty($tipo))   $stmt->bindParam(':tipo',   $tipo);
        if (!empty($estado)) $stmt->bindParam(':estado', $estado);
        $stmt->execute();
        return $stmt;
    }

    public function leerPorId() {
        $query = "SELECT * FROM " . $this->table . " WHERE habitacion_id = :id LIMIT 1";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->habitacion_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->numero       = $row['numero'];
            $this->tipo         = $row['tipo'];
            $this->precio_noche = $row['precio_noche'];
            $this->capacidad    = $row['capacidad'];
            $this->estado       = $row['estado'];
            $this->piso         = $row['piso'];
            $this->descripcion  = $row['descripcion'];
            return true;
        }
        return false;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table . "
                  SET numero=:numero, tipo=:tipo, precio_noche=:precio_noche,
                      capacidad=:capacidad, estado=:estado, piso=:piso, descripcion=:descripcion
                  WHERE habitacion_id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':numero',       htmlspecialchars(strip_tags($this->numero)));
        $stmt->bindParam(':tipo',         htmlspecialchars(strip_tags($this->tipo)));
        $stmt->bindParam(':precio_noche', htmlspecialchars(strip_tags($this->precio_noche)));
        $stmt->bindParam(':capacidad',    htmlspecialchars(strip_tags($this->capacidad)));
        $stmt->bindParam(':estado',       htmlspecialchars(strip_tags($this->estado)));
        $stmt->bindParam(':piso',         htmlspecialchars(strip_tags($this->piso)));
        $stmt->bindParam(':descripcion',  htmlspecialchars(strip_tags($this->descripcion)));
        $stmt->bindParam(':id',           $this->habitacion_id);
        return $stmt->execute();
    }

    public function eliminar() {
        $query = "DELETE FROM " . $this->table . " WHERE habitacion_id = :id";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->habitacion_id);
        return $stmt->execute();
    }

    public function cambiarEstado() {
        $query = "UPDATE " . $this->table . " SET estado=:estado WHERE habitacion_id=:id";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':id',     $this->habitacion_id);
        return $stmt->execute();
    }

    public function buscarDisponibles($tipo = '', $capacidad_min = 0) {
        $query = "SELECT * FROM " . $this->table . " WHERE estado='disponible'";
        if (!empty($tipo))      $query .= " AND tipo=:tipo";
        if ($capacidad_min > 0) $query .= " AND capacidad>=:cap";
        $query .= " ORDER BY precio_noche ASC";
        $stmt = $this->conn->prepare($query);
        if (!empty($tipo))      $stmt->bindParam(':tipo', $tipo);
        if ($capacidad_min > 0) $stmt->bindParam(':cap',  $capacidad_min);
        $stmt->execute();
        return $stmt;
    }

    public function numeroExiste($excluir_id = null) {
        $query = "SELECT habitacion_id FROM " . $this->table . " WHERE numero=:numero";
        if ($excluir_id) $query .= " AND habitacion_id!=:excluir";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':numero', $this->numero);
        if ($excluir_id) $stmt->bindParam(':excluir', $excluir_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>