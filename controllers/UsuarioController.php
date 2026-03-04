<?php
/**
 * UsuarioController
 * Controlador para gestionar autenticación y usuarios
 * 
 * @author Reynaldo Acosta Perez
 * @version 1.0
 */

session_start();

require_once '../../config/database.php';
require_once '../../models/Usuario.php';

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
                header("Location: ../../views/auth/login.php");
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
                
                // Redirigir según el rol
                switch ($resultado['rol']) {
                    case 'cliente':
                        header("Location: ../../views/cliente/dashboard.php");
                        break;
                    case 'recepcionista':
                        header("Location: ../../views/recepcionista/dashboard.php");
                        break;
                    case 'administrador':
                        header("Location: ../../views/admin/dashboard.php");
                        break;
                    default:
                        $_SESSION['error'] = "Rol no reconocido";
                        header("Location: ../../views/auth/login.php");
                }
                exit();
            } else {
                // Login fallido
                $_SESSION['error'] = "Usuario o contraseña incorrectos";
                header("Location: ../../views/auth/login.php");
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
        header("Location: ../../views/auth/login.php");
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
            
            // Validaciones
            if (empty($username) || empty($password) || empty($password_confirm)) {
                $_SESSION['error'] = "Todos los campos son obligatorios";
                header("Location: ../../views/auth/registro.php");
                exit();
            }
            
            if ($password !== $password_confirm) {
                $_SESSION['error'] = "Las contraseñas no coinciden";
                header("Location: ../../views/auth/registro.php");
                exit();
            }
            
            if (strlen($password) < 6) {
                $_SESSION['error'] = "La contraseña debe tener al menos 6 caracteres";
                header("Location: ../../views/auth/registro.php");
                exit();
            }
            
            // Verificar si el username ya existe
            $this->usuario->username = $username;
            if ($this->usuario->existeUsername()) {
                $_SESSION['error'] = "El nombre de usuario ya está en uso";
                header("Location: ../../views/auth/registro.php");
                exit();
            }
            
            // Crear usuario
            $this->usuario->username = $username;
            $this->usuario->password = $password;
            $this->usuario->rol = 'cliente';  // Por defecto es cliente
            $this->usuario->activo = 1;
            
            if ($this->usuario->crear()) {
                $_SESSION['success'] = "Registro exitoso. Por favor inicia sesión.";
                header("Location: ../../views/auth/login.php");
                exit();
            } else {
                $_SESSION['error'] = "Error al crear el usuario. Intente nuevamente.";
                header("Location: ../../views/auth/registro.php");
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
            header("Location: ../../views/auth/login.php");
            exit();
    }
} else {
    // Si no hay acción, intentar login por defecto
    $controller = new UsuarioController();
    $controller->login();
}
?>