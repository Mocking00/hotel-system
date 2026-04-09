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
require_once __DIR__ . '/../utils/Validator.php';

class UsuarioController {
    private $db;
    private $usuario;

    private function esMayorDeEdadSeguro($fechaNacimiento, $edadMinima = 18) {
        if (class_exists('Validator') && method_exists('Validator', 'esMayorDeEdad')) {
            return Validator::esMayorDeEdad($fechaNacimiento, $edadMinima);
        }

        if (empty($fechaNacimiento)) {
            return false;
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
                header("Location: ../views/auth/login.php");
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
                        header("Location: ../views/cliente/dashboard.php");
                        break;
                    case 'recepcionista':
                        header("Location: ../views/recepcionista/dashboard.php");
                        break;
                    case 'administrador':
                        header("Location: ../views/admin/dashboard.php");
                        break;
                    default:
                        $_SESSION['error'] = "Rol no reconocido";
                        header("Location: ../views/auth/login.php");
                }
                exit();
            } else {
                // Login fallido
                $_SESSION['error'] = "Usuario o contraseña incorrectos";
                header("Location: ../views/auth/login.php");
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
        header("Location: ../views/auth/login.php");
        exit();
    }
    
    /**
     * Registrar nuevo usuario (cliente)
     */
    public function registrar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
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
                if (empty($username) || empty($password) || empty($password_confirm) || empty($nombre) || empty($apellido) || empty($cedula) || empty($telefono) || empty($email) || empty($fecha_nacimiento)) {
                    $_SESSION['error'] = "Todos los campos son obligatorios";
                    header("Location: ../views/auth/registro.php");
                    exit();
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['error'] = "El email no es válido";
                    header("Location: ../views/auth/registro.php");
                    exit();
                }

                if (!Validator::nombreValido($nombre)) {
                    $_SESSION['error'] = "El nombre solo puede contener letras y espacios";
                    header("Location: ../views/auth/registro.php");
                    exit();
                }

                if (!Validator::nombreValido($apellido)) {
                    $_SESSION['error'] = "El apellido solo puede contener letras y espacios";
                    header("Location: ../views/auth/registro.php");
                    exit();
                }

                if (!Validator::cedulaValida($cedula)) {
                    $_SESSION['error'] = "La cédula solo puede contener números y guiones";
                    header("Location: ../views/auth/registro.php");
                    exit();
                }

                if (!Validator::telefonoValido($telefono)) {
                    $_SESSION['error'] = "El teléfono solo puede contener números y caracteres de formato";
                    header("Location: ../views/auth/registro.php");
                    exit();
                }

                if (!$this->esMayorDeEdadSeguro($fecha_nacimiento, 18)) {
                    $_SESSION['error'] = "Debes ser mayor de edad (18+ años) para crear una cuenta";
                    header("Location: ../views/auth/registro.php");
                    exit();
                }

                if ($password !== $password_confirm) {
                    $_SESSION['error'] = "Las contraseñas no coinciden";
                    header("Location: ../views/auth/registro.php");
                    exit();
                }

                if (strlen($password) < 6) {
                    $_SESSION['error'] = "La contraseña debe tener al menos 6 caracteres";
                    header("Location: ../views/auth/registro.php");
                    exit();
                }

                // Verificar si el username ya existe
                $this->usuario->username = $username;
                if ($this->usuario->existeUsername()) {
                    $_SESSION['error'] = "El nombre de usuario ya está en uso";
                    header("Location: ../views/auth/registro.php");
                    exit();
                }

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
                header("Location: ../views/auth/login.php");
                exit();

            } catch (Throwable $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                $_SESSION['error'] = "No se pudo completar el registro. " . $e->getMessage();
                header("Location: ../views/auth/registro.php");
                exit();
            }
        }
    }

    /**
     * Permite a un cliente autenticado cambiar su contraseña.
     */
    public function cambiarPasswordCliente() {
        if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'cliente') {
            header("Location: ../views/auth/login.php");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password_actual = $_POST['password_actual'] ?? '';
            $password_nueva = $_POST['password_nueva'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';

            if (empty($password_actual) || empty($password_nueva) || empty($password_confirm)) {
                $_SESSION['error'] = "Todos los campos son obligatorios";
                header("Location: ../views/cliente/cambiar_password.php");
                exit();
            }

            if (strlen($password_nueva) < 6) {
                $_SESSION['error'] = "La nueva contraseña debe tener al menos 6 caracteres";
                header("Location: ../views/cliente/cambiar_password.php");
                exit();
            }

            if ($password_nueva !== $password_confirm) {
                $_SESSION['error'] = "La confirmación no coincide con la nueva contraseña";
                header("Location: ../views/cliente/cambiar_password.php");
                exit();
            }

            $this->usuario->usuario_id = $_SESSION['usuario_id'];
            if (!$this->usuario->cambiarPassword($password_actual, $password_nueva)) {
                $_SESSION['error'] = "La contraseña actual no es correcta";
                header("Location: ../views/cliente/cambiar_password.php");
                exit();
            }

            $_SESSION['success'] = "Contraseña actualizada correctamente";
            header("Location: ../views/cliente/cambiar_password.php");
            exit();
        }

        header("Location: ../views/cliente/cambiar_password.php");
        exit();
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
        case 'cambiar_password':
            $controller->cambiarPasswordCliente();
            break;
        default:
            header("Location: ../views/auth/login.php");
            exit();
    }
} else {
    // Si no hay acción, intentar login por defecto
    $controller = new UsuarioController();
    $controller->login();
}
?>
