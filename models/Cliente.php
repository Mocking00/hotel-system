<?php
/**
 * Clase Cliente
 * Modelo para gestionar clientes del hotel
 *
 * @author Reynaldo Acosta Perez
 * @version 1.0
 */
class Cliente {
    private $conn;
    private $table = 'CLIENTE';

    public $cliente_id;
    public $usuario_id;
    public $nombre;
    public $apellido;
    public $cedula;
    public $telefono;
    public $email;
    public $direccion;
    public $fecha_nacimiento;
    public $fecha_registro;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Valida fecha (YYYY-MM-DD), no futura y con edad minima.
    private function fechaNacimientoValida($fechaNacimiento, $edadMinima = 18) {
        if (empty($fechaNacimiento)) {
            return true; // Se mantiene opcional para el perfil cliente.
        }

        $fecha = DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
        if (!$fecha || $fecha->format('Y-m-d') !== $fechaNacimiento) {
            return false;
        }

        $hoy = new DateTime('today');
        if ($fecha > $hoy) {
            return false;
        }

        return $fecha->diff($hoy)->y >= $edadMinima;
    }

    // ── LISTAR TODOS ─────────────────────────────────────────────────────
    public function leerTodos($buscar = '') {
        $query = "SELECT c.cliente_id, c.nombre, c.apellido, c.cedula,
                         c.telefono, c.email, c.fecha_registro,
                         u.username,
                         (SELECT COUNT(*) FROM RESERVA r WHERE r.cliente_id = c.cliente_id) AS total_reservas
                  FROM " . $this->table . " c
                  LEFT JOIN USUARIO u ON c.usuario_id = u.usuario_id
                  WHERE 1=1";

        if (!empty($buscar)) {
            $query .= " AND (c.nombre LIKE :buscar
                          OR c.apellido LIKE :buscar2
                          OR c.cedula   LIKE :buscar3
                          OR c.email    LIKE :buscar4)";
        }

        $query .= " ORDER BY c.fecha_registro DESC";

        $stmt = $this->conn->prepare($query);

        if (!empty($buscar)) {
            $like = '%' . $buscar . '%';
            $stmt->bindParam(':buscar',  $like);
            $stmt->bindParam(':buscar2', $like);
            $stmt->bindParam(':buscar3', $like);
            $stmt->bindParam(':buscar4', $like);
        }

        $stmt->execute();
        return $stmt;
    }

    // ── LEER POR ID ──────────────────────────────────────────────────────
    public function leerPorId() {
        $query = "SELECT c.*, u.username, u.activo AS usuario_activo
                  FROM " . $this->table . " c
                  LEFT JOIN USUARIO u ON c.usuario_id = u.usuario_id
                  WHERE c.cliente_id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->cliente_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            foreach ($row as $key => $val) {
                if (property_exists($this, $key)) $this->$key = $val;
            }
            return $row; // devuelve el array completo (incluye username)
        }
        return false;
    }

    // ── CREAR ────────────────────────────────────────────────────────────
    public function crear() {
        if (!$this->fechaNacimientoValida($this->fecha_nacimiento, 18)) {
            return false;
        }

        $query = "INSERT INTO " . $this->table . "
                  (usuario_id, nombre, apellido, cedula, telefono,
                   email, direccion, fecha_nacimiento)
                  VALUES
                  (:usuario_id, :nombre, :apellido, :cedula, :telefono,
                   :email, :direccion, :fecha_nacimiento)";

        $stmt = $this->conn->prepare($query);

        $usuario_id = !empty($this->usuario_id) ? $this->usuario_id : null;

        $stmt->bindParam(':usuario_id',       $usuario_id);
        $stmt->bindParam(':nombre',           $this->nombre);
        $stmt->bindParam(':apellido',         $this->apellido);
        $stmt->bindParam(':cedula',           $this->cedula);
        $stmt->bindParam(':telefono',         $this->telefono);
        $stmt->bindParam(':email',            $this->email);
        $stmt->bindParam(':direccion',        $this->direccion);
        $stmt->bindParam(':fecha_nacimiento', $this->fecha_nacimiento);

        if ($stmt->execute()) {
            $this->cliente_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // ── ACTUALIZAR ───────────────────────────────────────────────────────
    public function actualizar() {
        if (!$this->fechaNacimientoValida($this->fecha_nacimiento, 18)) {
            return false;
        }

        $query = "UPDATE " . $this->table . "
                  SET nombre           = :nombre,
                      apellido         = :apellido,
                      cedula           = :cedula,
                      telefono         = :telefono,
                      email            = :email,
                      direccion        = :direccion,
                      fecha_nacimiento = :fecha_nacimiento
                  WHERE cliente_id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre',           $this->nombre);
        $stmt->bindParam(':apellido',         $this->apellido);
        $stmt->bindParam(':cedula',           $this->cedula);
        $stmt->bindParam(':telefono',         $this->telefono);
        $stmt->bindParam(':email',            $this->email);
        $stmt->bindParam(':direccion',        $this->direccion);
        $stmt->bindParam(':fecha_nacimiento', $this->fecha_nacimiento);
        $stmt->bindParam(':id',               $this->cliente_id);

        return $stmt->execute();
    }

    // ── ELIMINAR ─────────────────────────────────────────────────────────
    public function eliminar() {
        // Verificar que no tenga reservas activas
        $check = $this->conn->prepare(
            "SELECT COUNT(*) FROM RESERVA
             WHERE cliente_id = :id
               AND estado IN ('pendiente','confirmada')"
        );
        $check->bindParam(':id', $this->cliente_id);
        $check->execute();
        if ($check->fetchColumn() > 0) return false;

        $query = "DELETE FROM " . $this->table . " WHERE cliente_id = :id";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->cliente_id);
        return $stmt->execute();
    }

    // ── RESERVAS DEL CLIENTE ─────────────────────────────────────────────
    public function leerReservas() {
        $query = "SELECT r.reserva_id, r.codigo_confirmacion,
                         r.fecha_entrada, r.fecha_salida,
                         r.precio_total, r.estado, r.fecha_reserva,
                         h.numero AS numero_habitacion,
                         h.tipo   AS tipo_habitacion,
                         DATEDIFF(r.fecha_salida, r.fecha_entrada) AS noches
                  FROM RESERVA r
                  JOIN HABITACION h ON r.habitacion_id = h.habitacion_id
                  WHERE r.cliente_id = :id
                  ORDER BY r.fecha_reserva DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->cliente_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── VERIFICAR CÉDULA DUPLICADA ───────────────────────────────────────
    public function cedulaExiste($excluir_id = null) {
        $query = "SELECT cliente_id FROM " . $this->table . " WHERE cedula = :cedula";
        if ($excluir_id) $query .= " AND cliente_id != :excluir";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cedula', $this->cedula);
        if ($excluir_id) $stmt->bindParam(':excluir', $excluir_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // ── VERIFICAR EMAIL DUPLICADO ────────────────────────────────────────
    public function emailExiste($excluir_id = null) {
        $query = "SELECT cliente_id FROM " . $this->table . " WHERE email = :email";
        if ($excluir_id) $query .= " AND cliente_id != :excluir";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        if ($excluir_id) $stmt->bindParam(':excluir', $excluir_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // ── BUSCAR (para autocomplete en reservas) ───────────────────────────
    public function buscar($termino) {
        $like  = '%' . $termino . '%';
        $query = "SELECT cliente_id,
                         CONCAT(nombre, ' ', apellido) AS nombre_completo,
                         cedula, email, telefono
                  FROM " . $this->table . "
                  WHERE nombre  LIKE :t
                     OR apellido LIKE :t2
                     OR cedula   LIKE :t3
                  ORDER BY apellido
                  LIMIT 10";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':t',  $like);
        $stmt->bindParam(':t2', $like);
        $stmt->bindParam(':t3', $like);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── LEER TODOS PARA SELECT ───────────────────────────────────────────
    // Usado por ReservaController al crear reservas
    public function leerTodosSelect() {
        $query = "SELECT cliente_id, nombre, apellido, cedula, email, telefono
                  FROM " . $this->table . "
                  ORDER BY apellido ASC, nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>