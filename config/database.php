<?php
/**
 * Clase Database
 * Maneja la conexión a la base de datos MySQL usando PDO
 * 
 * Soporta variables de entorno para facilitar migración entre entornos.
 * En producción (InfinityFree), sobrescribe estos valores.
 * 
 * @author Reynaldo Acosta Perez
 * @version 1.0
 */
class Database {
    // Credenciales de la base de datos
    // Lee desde variables de entorno o usa valores por defecto
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $charset = "utf8mb4";
    
    public function __construct() {
        // Leer desde variables de entorno o desde $_SERVER (SetEnv).
        // Si InfinityFree no aplica SetEnv, usar fallback de produccion.
        $envHost = getenv('DB_HOST') ?: ($_SERVER['DB_HOST'] ?? '');
        $envPort = getenv('DB_PORT') ?: ($_SERVER['DB_PORT'] ?? '');
        $envName = getenv('DB_NAME') ?: ($_SERVER['DB_NAME'] ?? '');
        $envUser = getenv('DB_USER') ?: ($_SERVER['DB_USER'] ?? '');
        $envPass = getenv('DB_PASS') ?: ($_SERVER['DB_PASS'] ?? '');

        if (!empty($envHost) && !empty($envName) && !empty($envUser)) {
            $this->host = $envHost;
            $this->port = !empty($envPort) ? $envPort : '3306';
            $this->db_name = $envName;
            $this->username = $envUser;
            $this->password = $envPass;
            return;
        }

        // Fallback automatico para InfinityFree (cuando SetEnv no funciona).
        $isLocal = isset($_SERVER['HTTP_HOST'])
            ? in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1'], true)
            : true;

        if (!$isLocal) {
            $this->host = 'sql111.infinityfree.com';
            $this->port = '3306';
            $this->db_name = 'if0_41615483_hotel_gestion';
            $this->username = 'if0_41615483';
            $this->password = 'ZA6FhjuOWP';
            return;
        }

        // Valores por defecto para entorno local (XAMPP)
        $this->host = '127.0.0.1';
        $this->port = '3306';
        $this->db_name = 'hotel_gestion';
        $this->username = 'root';
        $this->password = '';
    }
    
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
                   ";port=" . $this->port .
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