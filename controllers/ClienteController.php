<?php
session_start();

// ── Protección: solo administrador ───────────────────────────────────────
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /hotel-system/views/auth/login.php");
    exit();
}
if ($_SESSION['rol'] !== 'administrador') {
    header("Location: /hotel-system/views/auth/login.php");
    exit();
}

// ── Dependencias ─────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Cliente.php';

$database = new Database();
$db       = $database->getConnection();
$cliente  = new Cliente($db);

// ── Router ────────────────────────────────────────────────────────────────
$accion = $_GET['accion'] ?? 'listar';

switch ($accion) {
    case 'crear':   accion_crear($db, $cliente);   break;
    case 'editar':  accion_editar($db, $cliente);  break;
    case 'detalle': accion_detalle($cliente);       break;
    case 'eliminar':accion_eliminar($cliente);      break;
    case 'api_buscar': api_buscar($cliente);        break;
    default: accion_listar($cliente);               break;
}

// ════════════════════════════════════════════════════════════════════════
// LISTAR
// ════════════════════════════════════════════════════════════════════════
function accion_listar($cliente) {
    $buscar   = trim($_GET['buscar'] ?? '');
    $resultado = $cliente->leerTodos($buscar);
    $clientes  = $resultado->fetchAll(PDO::FETCH_ASSOC);

    $mensaje  = $_SESSION['mensaje']  ?? '';
    $tipo_msg = $_SESSION['tipo_msg'] ?? '';
    unset($_SESSION['mensaje'], $_SESSION['tipo_msg']);

    include __DIR__ . '/../views/clientes/listar.php';
}

// ════════════════════════════════════════════════════════════════════════
// CREAR
// ════════════════════════════════════════════════════════════════════════
function accion_crear($db, $cliente) {
    $errores = [];
    $datos   = [
        'nombre'          => '',
        'apellido'        => '',
        'cedula'          => '',
        'telefono'        => '',
        'email'           => '',
        'direccion'       => '',
        'fecha_nacimiento'=> '',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Leer POST
        foreach (array_keys($datos) as $campo) {
            $datos[$campo] = trim($_POST[$campo] ?? '');
        }

        // Validaciones
        if (empty($datos['nombre']))   $errores[] = 'El nombre es obligatorio.';
        if (empty($datos['apellido'])) $errores[] = 'El apellido es obligatorio.';
        if (empty($datos['cedula']))   $errores[] = 'La cédula es obligatoria.';
        if (empty($datos['telefono'])) $errores[] = 'El teléfono es obligatorio.';
        if (empty($datos['email']))    $errores[] = 'El email es obligatorio.';
        if (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El formato del email no es válido.';
        }

        if (empty($errores)) {
            $cliente->cedula = $datos['cedula'];
            if ($cliente->cedulaExiste()) $errores[] = 'Ya existe un cliente con esa cédula.';
        }
        if (empty($errores)) {
            $cliente->email = $datos['email'];
            if ($cliente->emailExiste()) $errores[] = 'Ya existe un cliente con ese email.';
        }

        if (empty($errores)) {
            $cliente->nombre           = $datos['nombre'];
            $cliente->apellido         = $datos['apellido'];
            $cliente->cedula           = $datos['cedula'];
            $cliente->telefono         = $datos['telefono'];
            $cliente->email            = $datos['email'];
            $cliente->direccion        = $datos['direccion'];
            $cliente->fecha_nacimiento = !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null;
            $cliente->usuario_id       = null;

            if ($cliente->crear()) {
                $_SESSION['mensaje']  = '✅ Cliente registrado correctamente.';
                $_SESSION['tipo_msg'] = 'success';
                header('Location: /hotel-system/controllers/ClienteController.php');
                exit;
            } else {
                $errores[] = 'Error al guardar el cliente. Intenta de nuevo.';
            }
        }
    }

    include __DIR__ . '/../views/clientes/crear.php';
}

// ════════════════════════════════════════════════════════════════════════
// EDITAR
// ════════════════════════════════════════════════════════════════════════
function accion_editar($db, $cliente) {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        header('Location: /hotel-system/controllers/ClienteController.php');
        exit;
    }

    $errores = [];

    // Cargar datos actuales
    $cliente->cliente_id = $id;
    $datos = $cliente->leerPorId();
    if (!$datos) {
        header('Location: /hotel-system/controllers/ClienteController.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $campos = ['nombre','apellido','cedula','telefono','email','direccion','fecha_nacimiento'];
        foreach ($campos as $campo) {
            $datos[$campo] = trim($_POST[$campo] ?? '');
        }

        // Validaciones
        if (empty($datos['nombre']))   $errores[] = 'El nombre es obligatorio.';
        if (empty($datos['apellido'])) $errores[] = 'El apellido es obligatorio.';
        if (empty($datos['cedula']))   $errores[] = 'La cédula es obligatoria.';
        if (empty($datos['telefono'])) $errores[] = 'El teléfono es obligatorio.';
        if (empty($datos['email']))    $errores[] = 'El email es obligatorio.';
        if (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El formato del email no es válido.';
        }

        if (empty($errores)) {
            $cliente->cedula = $datos['cedula'];
            if ($cliente->cedulaExiste($id)) $errores[] = 'Ya existe otro cliente con esa cédula.';
        }
        if (empty($errores)) {
            $cliente->email = $datos['email'];
            if ($cliente->emailExiste($id)) $errores[] = 'Ya existe otro cliente con ese email.';
        }

        if (empty($errores)) {
            $cliente->cliente_id       = $id;
            $cliente->nombre           = $datos['nombre'];
            $cliente->apellido         = $datos['apellido'];
            $cliente->cedula           = $datos['cedula'];
            $cliente->telefono         = $datos['telefono'];
            $cliente->email            = $datos['email'];
            $cliente->direccion        = $datos['direccion'];
            $cliente->fecha_nacimiento = !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null;

            if ($cliente->actualizar()) {
                $_SESSION['mensaje']  = '✅ Cliente actualizado correctamente.';
                $_SESSION['tipo_msg'] = 'success';
                header("Location: /hotel-system/controllers/ClienteController.php?accion=detalle&id=$id");
                exit;
            } else {
                $errores[] = 'Error al actualizar. Intenta de nuevo.';
            }
        }
    }

    include __DIR__ . '/../views/clientes/editar.php';
}

// ════════════════════════════════════════════════════════════════════════
// DETALLE
// ════════════════════════════════════════════════════════════════════════
function accion_detalle($cliente) {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        header('Location: /hotel-system/controllers/ClienteController.php');
        exit;
    }

    $cliente->cliente_id = $id;
    $datos = $cliente->leerPorId();
    if (!$datos) {
        header('Location: /hotel-system/controllers/ClienteController.php');
        exit;
    }

    $reservas = $cliente->leerReservas();

    $mensaje  = $_SESSION['mensaje']  ?? '';
    $tipo_msg = $_SESSION['tipo_msg'] ?? '';
    unset($_SESSION['mensaje'], $_SESSION['tipo_msg']);

    include __DIR__ . '/../views/clientes/detalle.php';
}

// ════════════════════════════════════════════════════════════════════════
// ELIMINAR
// ════════════════════════════════════════════════════════════════════════
function accion_eliminar($cliente) {
    $id = intval($_GET['id'] ?? 0);
    if ($id) {
        $cliente->cliente_id = $id;
        if ($cliente->eliminar()) {
            $_SESSION['mensaje']  = '✅ Cliente eliminado correctamente.';
            $_SESSION['tipo_msg'] = 'success';
        } else {
            $_SESSION['mensaje']  = '❌ No se puede eliminar: el cliente tiene reservas activas.';
            $_SESSION['tipo_msg'] = 'error';
        }
    }
    header('Location: /hotel-system/controllers/ClienteController.php');
    exit;
}

// ════════════════════════════════════════════════════════════════════════
// API AJAX — buscar clientes (reutilizado por ReservaController)
// ════════════════════════════════════════════════════════════════════════
function api_buscar($cliente) {
    header('Content-Type: application/json');
    $termino = trim($_GET['q'] ?? '');
    if (strlen($termino) < 2) { echo json_encode([]); exit; }
    echo json_encode($cliente->buscar($termino));
    exit;
}
?>