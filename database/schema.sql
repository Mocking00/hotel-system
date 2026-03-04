<?php
/**
 * Clase Database
 * Maneja la conexión a la base de datos MySQL usando PDO
 * 
 * @author Reynaldo Acosta Perez
 * @version 1.0
 */
class Database {
    // Credenciales de la base de datos
    private $host = "localhost";
    private $db_name = "hotel_gestion";
    private $username = "root";
    private $password = "";  // Vacío por defecto en XAMPP
    private $charset = "utf8mb4";
    
    public $conn;

    /**
     * Obtiene la conexión a la base de datos
     * 
     * @return PDO|null Retorna el objeto de conexión o null en caso de error
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            // Crear el DSN (Data Source Name)
            $dsn = "mysql:host=" . $this->host . 
                   ";dbname=" . $this->db_name . 
                   ";charset=" . $this->charset;
            
            // Opciones de PDO
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            // Crear la conexión
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
            error_log("Error DB: " . $exception->getMessage());
        }
        
        return $this->conn;
    }
    
    /**
     * Cierra la conexión a la base de datos
     */
    public function closeConnection() {
        $this->conn = null;
    }
}
?>