<?php
class Reserva {
    private $conn;
    private $table = 'RESERVA';

    public $reserva_id;
    public $cliente_id;
    public $habitacion_id;
    public $fecha_entrada;
    public $fecha_salida;
    public $numero_personas;
    public $precio_total;
    public $estado;
    public $notas_especiales;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function leerTodas($filtros = [], $solo_cliente_id = null) {
        $query = "SELECT r.reserva_id, r.codigo_confirmacion, r.fecha_entrada, r.fecha_salida,
                         r.numero_personas, r.precio_total, r.estado, r.fecha_reserva,
                         r.fecha_checkin, r.fecha_checkout, r.notas_especiales,
                         c.cliente_id,
                         CONCAT(c.nombre, ' ', c.apellido) AS nombre_cliente,
                         c.email, c.telefono, c.cedula,
                         h.habitacion_id,
                         h.numero AS numero_habitacion,
                         h.tipo   AS tipo_habitacion,
                         DATEDIFF(r.fecha_salida, r.fecha_entrada) AS noches
                  FROM " . $this->table . " r
                  JOIN CLIENTE c    ON r.cliente_id = c.cliente_id
                  JOIN HABITACION h ON r.habitacion_id = h.habitacion_id
                  WHERE 1=1";

        if (!empty($filtros['estado'])) {
            $query .= " AND r.estado = :estado";
        }
        if (!empty($filtros['fecha_desde'])) {
            $query .= " AND DATE(r.fecha_entrada) >= :fecha_desde";
        }
        if (!empty($filtros['fecha_hasta'])) {
            $query .= " AND DATE(r.fecha_salida) <= :fecha_hasta";
        }
        if (!empty($filtros['buscar'])) {
            $query .= " AND (
                r.codigo_confirmacion LIKE :buscar
                OR CONCAT(c.nombre, ' ', c.apellido) LIKE :buscar2
                OR c.cedula LIKE :buscar3
                OR h.numero LIKE :buscar4
            )";
        }
        if (!empty($solo_cliente_id)) {
            $query .= " AND r.cliente_id = :cliente_id";
        }

        $query .= " ORDER BY r.fecha_reserva DESC, r.reserva_id DESC";

        $stmt = $this->conn->prepare($query);

        if (!empty($filtros['estado'])) {
            $stmt->bindParam(':estado', $filtros['estado']);
        }
        if (!empty($filtros['fecha_desde'])) {
            $stmt->bindParam(':fecha_desde', $filtros['fecha_desde']);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $stmt->bindParam(':fecha_hasta', $filtros['fecha_hasta']);
        }
        if (!empty($filtros['buscar'])) {
            $like = '%' . $filtros['buscar'] . '%';
            $stmt->bindParam(':buscar', $like);
            $stmt->bindParam(':buscar2', $like);
            $stmt->bindParam(':buscar3', $like);
            $stmt->bindParam(':buscar4', $like);
        }
        if (!empty($solo_cliente_id)) {
            $stmt->bindParam(':cliente_id', $solo_cliente_id);
        }

        $stmt->execute();
        return $stmt;
    }

    public function leerPorId($id, $solo_cliente_id = null) {
        $query = "SELECT r.reserva_id, r.codigo_confirmacion, r.fecha_entrada, r.fecha_salida,
                         r.numero_personas, r.precio_total, r.estado, r.fecha_reserva,
                         r.fecha_checkin, r.fecha_checkout, r.notas_especiales,
                         c.cliente_id, c.nombre, c.apellido, c.email, c.telefono, c.cedula,
                         h.habitacion_id, h.numero AS numero_habitacion, h.tipo AS tipo_habitacion,
                         h.capacidad, h.precio_noche,
                         DATEDIFF(r.fecha_salida, r.fecha_entrada) AS noches
                  FROM " . $this->table . " r
                  JOIN CLIENTE c    ON r.cliente_id = c.cliente_id
                  JOIN HABITACION h ON r.habitacion_id = h.habitacion_id
                  WHERE r.reserva_id = :id";

        if (!empty($solo_cliente_id)) {
            $query .= " AND r.cliente_id = :cliente_id";
        }

        $query .= " LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        if (!empty($solo_cliente_id)) {
            $stmt->bindParam(':cliente_id', $solo_cliente_id);
        }
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table . "
                    (cliente_id, habitacion_id, fecha_entrada, fecha_salida,
                     numero_personas, precio_total, estado, notas_especiales)
                  VALUES
                    (:cliente_id, :habitacion_id, :fecha_entrada, :fecha_salida,
                     :numero_personas, :precio_total, :estado, :notas_especiales)";

        $stmt = $this->conn->prepare($query);

        $estado = !empty($this->estado) ? $this->estado : 'pendiente';
        $notas  = !empty($this->notas_especiales) ? $this->notas_especiales : null;

        $stmt->bindParam(':cliente_id', $this->cliente_id);
        $stmt->bindParam(':habitacion_id', $this->habitacion_id);
        $stmt->bindParam(':fecha_entrada', $this->fecha_entrada);
        $stmt->bindParam(':fecha_salida', $this->fecha_salida);
        $stmt->bindParam(':numero_personas', $this->numero_personas);
        $stmt->bindParam(':precio_total', $this->precio_total);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':notas_especiales', $notas);

        if ($stmt->execute()) {
            $this->reserva_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function obtenerHabitacionesDisponibles($fecha_entrada, $fecha_salida, $personas = 1, $tipo = '') {
        $query = "SELECT h.habitacion_id, h.numero, h.tipo, h.precio_noche, h.capacidad, h.piso, h.estado
                  FROM HABITACION h
                  WHERE h.estado = 'disponible'
                    AND h.capacidad >= :personas";

        if (!empty($tipo)) {
            $query .= " AND h.tipo = :tipo";
        }

        $query .= "
                    AND h.habitacion_id NOT IN (
                        SELECT r.habitacion_id
                        FROM RESERVA r
                        WHERE r.estado IN ('pendiente', 'confirmada')
                          AND NOT (
                                r.fecha_salida <= :fecha_entrada
                                OR r.fecha_entrada >= :fecha_salida
                          )
                    )
                  ORDER BY h.precio_noche ASC, h.numero ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':personas', $personas, PDO::PARAM_INT);
        if (!empty($tipo)) {
            $stmt->bindParam(':tipo', $tipo);
        }
        $stmt->bindParam(':fecha_entrada', $fecha_entrada);
        $stmt->bindParam(':fecha_salida', $fecha_salida);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function calcularPrecioTotal($habitacion_id, $fecha_entrada, $fecha_salida) {
        $stmt = $this->conn->prepare("SELECT precio_noche FROM HABITACION WHERE habitacion_id = :id LIMIT 1");
        $stmt->bindParam(':id', $habitacion_id);
        $stmt->execute();
        $precio_noche = $stmt->fetchColumn();

        if (!$precio_noche) {
            return false;
        }

        $entrada = new DateTime($fecha_entrada);
        $salida  = new DateTime($fecha_salida);
        $noches  = (int) $entrada->diff($salida)->days;

        if ($noches <= 0) {
            return false;
        }

        return $precio_noche * $noches;
    }

    public function cancelar($id) {
        $query = "UPDATE " . $this->table . "
                  SET estado = 'cancelada'
                  WHERE reserva_id = :id
                    AND estado IN ('pendiente', 'confirmada')
                    AND fecha_checkin IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function registrarCheckin($id) {
        $query = "UPDATE " . $this->table . "
                  SET fecha_checkin = NOW(), estado = 'confirmada'
                  WHERE reserva_id = :id
                    AND estado IN ('pendiente', 'confirmada')
                    AND fecha_checkin IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function registrarCheckout($id) {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare(
                "SELECT habitacion_id
                 FROM " . $this->table . "
                 WHERE reserva_id = :id
                   AND estado IN ('pendiente', 'confirmada')
                   AND fecha_checkin IS NOT NULL
                   AND fecha_checkout IS NULL
                 LIMIT 1"
            );
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $habitacion_id = $stmt->fetchColumn();

            if (!$habitacion_id) {
                $this->conn->rollBack();
                return false;
            }

            $stmt = $this->conn->prepare(
                "UPDATE " . $this->table . "
                 SET fecha_checkout = NOW(),
                     estado = 'completada'
                 WHERE reserva_id = :id
                   AND fecha_checkout IS NULL"
            );
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() <= 0) {
                $this->conn->rollBack();
                return false;
            }

            $stmt_hab = $this->conn->prepare(
                "UPDATE HABITACION
                 SET estado = 'disponible'
                 WHERE habitacion_id = :habitacion_id"
            );
            $stmt_hab->bindParam(':habitacion_id', $habitacion_id);
            $stmt_hab->execute();

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    public function obtenerClienteIdPorUsuario($usuario_id) {
        $stmt = $this->conn->prepare("SELECT cliente_id FROM CLIENTE WHERE usuario_id = :usuario_id LIMIT 1");
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function obtenerClientesSelect() {
        $query = "SELECT cliente_id, nombre, apellido, cedula, email, telefono
                  FROM CLIENTE
                  ORDER BY apellido ASC, nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerReporteReservas($filtros = []) {
        $query = "SELECT reserva_id, codigo_confirmacion, fecha_entrada, fecha_salida,
                         precio_total, estado, nombre_cliente, email, telefono,
                         numero_habitacion, tipo_habitacion, noches
                  FROM v_reservas_completas
                  WHERE 1=1";

        if (!empty($filtros['estado'])) {
            $query .= " AND estado = :estado";
        }
        if (!empty($filtros['fecha_desde'])) {
            $query .= " AND DATE(fecha_entrada) >= :fecha_desde";
        }
        if (!empty($filtros['fecha_hasta'])) {
            $query .= " AND DATE(fecha_salida) <= :fecha_hasta";
        }

        $query .= " ORDER BY fecha_entrada DESC, reserva_id DESC";

        $stmt = $this->conn->prepare($query);

        if (!empty($filtros['estado'])) {
            $stmt->bindParam(':estado', $filtros['estado']);
        }
        if (!empty($filtros['fecha_desde'])) {
            $stmt->bindParam(':fecha_desde', $filtros['fecha_desde']);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $stmt->bindParam(':fecha_hasta', $filtros['fecha_hasta']);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerKpisReporte($filtros = []) {
        $query = "SELECT
                    COUNT(*) AS total_reservas,
                    SUM(CASE WHEN estado IN ('confirmada', 'completada') THEN precio_total ELSE 0 END) AS ingresos,
                    SUM(numero_personas) AS total_huespedes,
                    AVG(DATEDIFF(fecha_salida, fecha_entrada)) AS promedio_noches
                  FROM RESERVA
                  WHERE 1=1";

        if (!empty($filtros['estado'])) {
            $query .= " AND estado = :estado";
        }
        if (!empty($filtros['fecha_desde'])) {
            $query .= " AND DATE(fecha_entrada) >= :fecha_desde";
        }
        if (!empty($filtros['fecha_hasta'])) {
            $query .= " AND DATE(fecha_salida) <= :fecha_hasta";
        }

        $stmt = $this->conn->prepare($query);

        if (!empty($filtros['estado'])) {
            $stmt->bindParam(':estado', $filtros['estado']);
        }
        if (!empty($filtros['fecha_desde'])) {
            $stmt->bindParam(':fecha_desde', $filtros['fecha_desde']);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $stmt->bindParam(':fecha_hasta', $filtros['fecha_hasta']);
        }

        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerResumenPorEstado($filtros = []) {
        $query = "SELECT estado, COUNT(*) AS total
                  FROM RESERVA
                  WHERE 1=1";

        if (!empty($filtros['fecha_desde'])) {
            $query .= " AND DATE(fecha_entrada) >= :fecha_desde";
        }
        if (!empty($filtros['fecha_hasta'])) {
            $query .= " AND DATE(fecha_salida) <= :fecha_hasta";
        }

        $query .= " GROUP BY estado ORDER BY total DESC";

        $stmt = $this->conn->prepare($query);

        if (!empty($filtros['fecha_desde'])) {
            $stmt->bindParam(':fecha_desde', $filtros['fecha_desde']);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $stmt->bindParam(':fecha_hasta', $filtros['fecha_hasta']);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerIngresosMensuales($limite = 6) {
        $limite = max(1, (int) $limite);
        $query = "SELECT DATE_FORMAT(fecha_entrada, '%Y-%m') AS periodo,
                         SUM(precio_total) AS total
                  FROM RESERVA
                  WHERE estado IN ('confirmada', 'completada')
                  GROUP BY DATE_FORMAT(fecha_entrada, '%Y-%m')
                  ORDER BY periodo DESC
                  LIMIT " . $limite;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_reverse($datos);
    }
}
?>