<?php
/**
 * Clase Usuario
 * Modelo para gestionar usuarios del sistema
 * 
 * @author Reynaldo Acosta Perez
 * @version 1.0
 */

class Usuario {
    // Conexión a BD y nombre de tabla
    private $conn;
    private $table_name = "USUARIO";
    
    // Propiedades del objeto
    public $usuario_id;
    public $username;
    public $password;
    public $rol;
    public $fecha_registro;
    public $ultimo_acceso;
    public $activo;
    
    /**
     * Constructor
     * 
     * @param PDO $db Conexión a la base de datos
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Autenticar usuario
     * Verifica las credenciales y retorna los datos del usuario
     * 
     * @return array|false Datos del usuario o false si falla
     */
    public function login() {
        $query = "SELECT usuario_id, username, password, rol, activo 
                  FROM " . $this->table_name . " 
                  WHERE username = :username AND activo = 1 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar la contraseña
            if (password_verify($this->password, $row['password'])) {
                // Actualizar último acceso
                $this->actualizarUltimoAcceso($row['usuario_id']);
                
                return $row;
            }
        }
        
        return false;
    }
    
    /**
     * Crear nuevo usuario
     * 
     * @return bool True si se creó exitosamente
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  (username, password, rol, activo)
                  VALUES (:username, :password, :rol, :activo)";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->rol = htmlspecialchars(strip_tags($this->rol));
        
        // Hash de la contraseña
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        // Bind de parámetros
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":rol", $this->rol);
        $stmt->bindValue(":activo", $this->activo ?? 1);
        
        if ($stmt->execute()) {
            $this->usuario_id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Leer datos de un usuario por ID
     * 
     * @return bool True si encontró el usuario
     */
    public function leerPorId() {
        $query = "SELECT usuario_id, username, rol, fecha_registro, ultimo_acceso, activo
                  FROM " . $this->table_name . "
                  WHERE usuario_id = :usuario_id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->username = $row['username'];
            $this->rol = $row['rol'];
            $this->fecha_registro = $row['fecha_registro'];
            $this->ultimo_acceso = $row['ultimo_acceso'];
            $this->activo = $row['activo'];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Actualizar contraseña del usuario
     * 
     * @param string $password_antigua Contraseña actual
     * @param string $password_nueva Nueva contraseña
     * @return bool True si se actualizó exitosamente
     */
    public function cambiarPassword($password_antigua, $password_nueva) {
        // Primero verificar la contraseña antigua
        $query = "SELECT password FROM " . $this->table_name . " 
                  WHERE usuario_id = :usuario_id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password_antigua, $row['password'])) {
                // Actualizar con la nueva contraseña
                $query = "UPDATE " . $this->table_name . "
                          SET password = :password
                          WHERE usuario_id = :usuario_id";
                
                $stmt = $this->conn->prepare($query);
                
                $password_hash = password_hash($password_nueva, PASSWORD_BCRYPT);
                
                $stmt->bindParam(":password", $password_hash);
                $stmt->bindParam(":usuario_id", $this->usuario_id);
                
                return $stmt->execute();
            }
        }
        
        return false;
    }
    
    /**
     * Actualizar último acceso del usuario
     * 
     * @param int $usuario_id ID del usuario
     * @return bool True si se actualizó
     */
    private function actualizarUltimoAcceso($usuario_id) {
        $query = "UPDATE " . $this->table_name . "
                  SET ultimo_acceso = NOW()
                  WHERE usuario_id = :usuario_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        
        return $stmt->execute();
    }
    
    /**
     * Verificar si un username ya existe
     * 
     * @return bool True si existe
     */
    public function existeUsername() {
        $query = "SELECT usuario_id FROM " . $this->table_name . "
                  WHERE username = :username LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $this->username = htmlspecialchars(strip_tags($this->username));
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Activar o desactivar usuario
     * 
     * @param bool $estado True para activar, False para desactivar
     * @return bool True si se actualizó
     */
    public function cambiarEstado($estado) {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = :activo
                  WHERE usuario_id = :usuario_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":activo", $estado);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        
        return $stmt->execute();
    }
}
?>