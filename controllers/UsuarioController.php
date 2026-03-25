<?php
/**
 * UsuarioController
 * Controlador para gestionar autenticación y usuarios
 * 
 * @author Reynaldo Acosta Perez
 * @version 1.0
 */

session_start();

// Usar rutas absolutas desde el directorio del archivo
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

class UsuarioController {
    private $db;
    private $usuario;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->usuario = new Usuario($this->db);
    }
    
    /**
     * Procesar login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtener datos del formulario
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            // Validar que no estén vacíos
            if (empty($username) || empty($password)) {
                $_SESSION['error'] = "Por favor complete todos los campos";
                header("Location: /hotel-system/views/auth/login.php");
                exit();
            }
            
            // Intentar autenticar
            $this->usuario->username = $username;
            $this->usuario->password = $password;
            
            $resultado = $this->usuario->login();
            
            if ($resultado) {
                // Login exitoso - crear sesión
                $_SESSION['usuario_id'] = $resultado['usuario_id'];
                $_SESSION['username'] = $resultado['username'];
                $_SESSION['rol'] = $resultado['rol'];
                
                // Redirigir según el rol usando RUTAS ABSOLUTAS
                switch ($resultado['rol']) {
                    case 'cliente':
                        header("Location: /hotel-system/views/cliente/dashboard.php");
                        break;
                    case 'recepcionista':
                        header("Location: /hotel-system/views/recepcionista/dashboard.php");
                        break;
                    case 'administrador':
                        header("Location: /hotel-system/views/admin/dashboard.php");
                        break;
                    default:
                        $_SESSION['error'] = "Rol no reconocido";
                        header("Location: /hotel-system/views/auth/login.php");
                }
                exit();
            } else {
                // Login fallido
                $_SESSION['error'] = "Usuario o contraseña incorrectos";
                header("Location: /hotel-system/views/auth/login.php");
                exit();
            }
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        header("Location: /hotel-system/views/auth/login.php");
        exit();
    }
    
    /**
     * Registrar nuevo usuario (cliente)
     */
    public function registrar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtener datos del formulario
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';
            $nombre = trim($_POST['nombre'] ?? '');
            $apellido = trim($_POST['apellido'] ?? '');
            $cedula = trim($_POST['cedula'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
            
            // Validaciones
            if (empty($username) || empty($password) || empty($password_confirm) || empty($nombre) || empty($apellido) || empty($cedula) || empty($telefono) || empty($email)) {
                $_SESSION['error'] = "Todos los campos son obligatorios";
                header("Location: /hotel-system/views/auth/registro.php");
                exit();
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = "El email no es válido";
                header("Location: /hotel-system/views/auth/registro.php");
                exit();
            }
            
            if ($password !== $password_confirm) {
                $_SESSION['error'] = "Las contraseñas no coinciden";
                header("Location: /hotel-system/views/auth/registro.php");
                exit();
            }
            
            if (strlen($password) < 6) {
                $_SESSION['error'] = "La contraseña debe tener al menos 6 caracteres";
                header("Location: /hotel-system/views/auth/registro.php");
                exit();
            }
            
            // Verificar si el username ya existe
            $this->usuario->username = $username;
            if ($this->usuario->existeUsername()) {
                $_SESSION['error'] = "El nombre de usuario ya está en uso";
                header("Location: /hotel-system/views/auth/registro.php");
                exit();
            }
            
            try {
                $this->db->beginTransaction();

                // Crear usuario
                $this->usuario->username = $username;
                $this->usuario->password = $password;
                $this->usuario->rol = 'cliente';
                $this->usuario->activo = 1;

                if (!$this->usuario->crear()) {
                    throw new Exception("Error al crear el usuario.");
                }

                // Validar cédula y email únicos en CLIENTE
                $stmt = $this->db->prepare("SELECT cliente_id FROM CLIENTE WHERE cedula = :cedula OR email = :email LIMIT 1");
                $stmt->bindParam(':cedula', $cedula);
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    throw new Exception("Ya existe un cliente registrado con esa cédula o email.");
                }

                // Crear perfil cliente vinculado
                $stmt = $this->db->prepare(
                    "INSERT INTO CLIENTE (usuario_id, nombre, apellido, cedula, telefono, email, direccion, fecha_nacimiento)
                     VALUES (:usuario_id, :nombre, :apellido, :cedula, :telefono, :email, :direccion, :fecha_nacimiento)"
                );

                $usuario_id = $this->usuario->usuario_id;
                $fecha_nac = !empty($fecha_nacimiento) ? $fecha_nacimiento : null;
                $dir = !empty($direccion) ? $direccion : null;

                $stmt->bindParam(':usuario_id', $usuario_id);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':apellido', $apellido);
                $stmt->bindParam(':cedula', $cedula);
                $stmt->bindParam(':telefono', $telefono);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':direccion', $dir);
                $stmt->bindParam(':fecha_nacimiento', $fecha_nac);

                if (!$stmt->execute()) {
                    throw new Exception("Error al crear el perfil de cliente.");
                }

                $this->db->commit();

                $_SESSION['success'] = "Cuenta creada correctamente. Ya puedes iniciar sesión y hacer tu pre-reserva.";
                header("Location: /hotel-system/views/auth/login.php");
                exit();

            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                $_SESSION['error'] = $e->getMessage();
                header("Location: /hotel-system/views/auth/registro.php");
                exit();
            }
        }
    }
}

// Procesar la acción solicitada
if (isset($_GET['action'])) {
    $controller = new UsuarioController();
    
    switch ($_GET['action']) {
        case 'login':
            $controller->login();
            break;
        case 'logout':
            $controller->logout();
            break;
        case 'registrar':
            $controller->registrar();
            break;
        default:
            header("Location: /hotel-system/views/auth/login.php");
            exit();
    }
} else {
    // Si no hay acción, intentar login por defecto
    $controller = new UsuarioController();
    $controller->login();
}
?>