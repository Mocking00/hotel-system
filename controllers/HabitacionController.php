<?php
session_start();

// ── Protección de acceso ────────────────────────────────────────────────
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /hotel-system/views/auth/login.php");
    exit();
}
if (!in_array($_SESSION['rol'], ['administrador', 'recepcionista'])) {
    header("Location: /hotel-system/views/auth/login.php");
    exit();
}

// ── Dependencias ────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Habitacion.php';

$database = new Database();
$db       = $database->getConnection();
$hab      = new Habitacion($db);

// ── Router ───────────────────────────────────────────────────────────────
$accion = $_GET['accion'] ?? 'listar';

if ($_SESSION['rol'] === 'recepcionista' && $accion !== 'listar') {
    $_SESSION['mensaje']  = 'No tienes permisos para modificar habitaciones.';
    $_SESSION['tipo_msg'] = 'error';
    header('Location: /hotel-system/controllers/HabitacionController.php');
    exit;
}

switch ($accion) {
    case 'crear':    accion_crear($hab);    break;
    case 'editar':   accion_editar($hab);   break;
    case 'eliminar': accion_eliminar($hab); break;
    case 'estado':   accion_estado($hab);   break;
    default:         accion_listar($hab);   break;
}

// ════════════════════════════════════════════════════════════════════════
// LISTAR
// ════════════════════════════════════════════════════════════════════════
function accion_listar($hab) {
    $tipo   = $_GET['tipo']   ?? '';
    $estado = $_GET['estado'] ?? '';

    $resultado    = $hab->leerTodas($tipo, $estado);
    $habitaciones = $resultado->fetchAll(PDO::FETCH_ASSOC);

    $mensaje  = $_SESSION['mensaje']  ?? '';
    $tipo_msg = $_SESSION['tipo_msg'] ?? '';
    unset($_SESSION['mensaje'], $_SESSION['tipo_msg']);

    include __DIR__ . '/../views/habitaciones/listar.php';
}

// ════════════════════════════════════════════════════════════════════════
// CREAR
// ════════════════════════════════════════════════════════════════════════
function accion_crear($hab) {
    $errores = [];

    // Datos del formulario (para repoblar en caso de error)
    $datos = [
        'numero'       => '',
        'tipo'         => '',
        'precio_noche' => '',
        'capacidad'    => '',
        'estado'       => 'disponible',
        'piso'         => '',
        'descripcion'  => '',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $datos['numero']       = trim($_POST['numero']       ?? '');
        $datos['tipo']         = trim($_POST['tipo']         ?? '');
        $datos['precio_noche'] = trim($_POST['precio_noche'] ?? '');
        $datos['capacidad']    = trim($_POST['capacidad']    ?? '');
        $datos['estado']       = trim($_POST['estado']       ?? 'disponible');
        $datos['piso']         = trim($_POST['piso']         ?? '');
        $datos['descripcion']  = trim($_POST['descripcion']  ?? '');

        // Validaciones
        if (empty($datos['numero']))
            $errores[] = 'El número de habitación es obligatorio.';
        if (empty($datos['tipo']))
            $errores[] = 'Debes seleccionar el tipo de habitación.';
        if (!is_numeric($datos['precio_noche']) || $datos['precio_noche'] <= 0)
            $errores[] = 'El precio debe ser un número positivo.';
        if (!is_numeric($datos['capacidad']) || $datos['capacidad'] <= 0)
            $errores[] = 'La capacidad debe ser un número positivo.';
        if (!is_numeric($datos['piso']))
            $errores[] = 'El piso debe ser un número.';

        if (empty($errores)) {
            $hab->numero      = $datos['numero'];
            $hab->numeroExiste();  // verificar duplicado
            if ($hab->numeroExiste()) {
                $errores[] = 'Ya existe una habitación con ese número.';
            }
        }

        if (empty($errores)) {
            $hab->numero       = $datos['numero'];
            $hab->tipo         = $datos['tipo'];
            $hab->precio_noche = $datos['precio_noche'];
            $hab->capacidad    = $datos['capacidad'];
            $hab->estado       = $datos['estado'];
            $hab->piso         = $datos['piso'];
            $hab->descripcion  = $datos['descripcion'];

            if ($hab->crear()) {
                $_SESSION['mensaje']  = '✅ Habitación creada exitosamente.';
                $_SESSION['tipo_msg'] = 'success';
                header('Location: /hotel-system/controllers/HabitacionController.php');
                exit;
            } else {
                $errores[] = 'Error al guardar en la base de datos. Intenta de nuevo.';
            }
        }
    }

    include __DIR__ . '/../views/habitaciones/crear.php';
}

// ════════════════════════════════════════════════════════════════════════
// EDITAR
// ════════════════════════════════════════════════════════════════════════
function accion_editar($hab) {
    $errores = [];
    $id = intval($_GET['id'] ?? 0);

    if (!$id) {
        header('Location: /hotel-system/controllers/HabitacionController.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Leer POST
        $datos = [
            'habitacion_id' => $id,
            'numero'        => trim($_POST['numero']       ?? ''),
            'tipo'          => trim($_POST['tipo']         ?? ''),
            'precio_noche'  => trim($_POST['precio_noche'] ?? ''),
            'capacidad'     => trim($_POST['capacidad']    ?? ''),
            'estado'        => trim($_POST['estado']       ?? ''),
            'piso'          => trim($_POST['piso']         ?? ''),
            'descripcion'   => trim($_POST['descripcion']  ?? ''),
        ];

        // Validaciones
        if (empty($datos['numero']))
            $errores[] = 'El número de habitación es obligatorio.';
        if (empty($datos['tipo']))
            $errores[] = 'Debes seleccionar el tipo de habitación.';
        if (!is_numeric($datos['precio_noche']) || $datos['precio_noche'] <= 0)
            $errores[] = 'El precio debe ser un número positivo.';
        if (!is_numeric($datos['capacidad']) || $datos['capacidad'] <= 0)
            $errores[] = 'La capacidad debe ser un número positivo.';
        if (!is_numeric($datos['piso']))
            $errores[] = 'El piso debe ser un número.';

        if (empty($errores)) {
            $hab->numero = $datos['numero'];
            if ($hab->numeroExiste($id)) {
                $errores[] = 'Ya existe otra habitación con ese número.';
            }
        }

        if (empty($errores)) {
            $hab->habitacion_id = $id;
            $hab->numero        = $datos['numero'];
            $hab->tipo          = $datos['tipo'];
            $hab->precio_noche  = $datos['precio_noche'];
            $hab->capacidad     = $datos['capacidad'];
            $hab->estado        = $datos['estado'];
            $hab->piso          = $datos['piso'];
            $hab->descripcion   = $datos['descripcion'];

            if ($hab->actualizar()) {
                $_SESSION['mensaje']  = '✅ Habitación actualizada exitosamente.';
                $_SESSION['tipo_msg'] = 'success';
                header('Location: /hotel-system/controllers/HabitacionController.php');
                exit;
            } else {
                $errores[] = 'Error al actualizar. Intenta de nuevo.';
            }
        }

    } else {
        // GET: cargar datos actuales de la BD
        $hab->habitacion_id = $id;
        if (!$hab->leerPorId()) {
            header('Location: /hotel-system/controllers/HabitacionController.php');
            exit;
        }
        $datos = [
            'habitacion_id' => $id,
            'numero'        => $hab->numero,
            'tipo'          => $hab->tipo,
            'precio_noche'  => $hab->precio_noche,
            'capacidad'     => $hab->capacidad,
            'estado'        => $hab->estado,
            'piso'          => $hab->piso,
            'descripcion'   => $hab->descripcion,
        ];
    }

    include __DIR__ . '/../views/habitaciones/editar.php';
}

// ════════════════════════════════════════════════════════════════════════
// ELIMINAR
// ════════════════════════════════════════════════════════════════════════
function accion_eliminar($hab) {
    if ($_SESSION['rol'] !== 'administrador') {
        $_SESSION['mensaje']  = '❌ No tienes permiso para eliminar habitaciones.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: /hotel-system/controllers/HabitacionController.php');
        exit;
    }

    $id = intval($_GET['id'] ?? 0);
    if ($id) {
        $hab->habitacion_id = $id;
        if ($hab->eliminar()) {
            $_SESSION['mensaje']  = '✅ Habitación eliminada correctamente.';
            $_SESSION['tipo_msg'] = 'success';
        } else {
            $_SESSION['mensaje']  = '❌ No se pudo eliminar. Puede tener reservas asociadas.';
            $_SESSION['tipo_msg'] = 'error';
        }
    }

    header('Location: /hotel-system/controllers/HabitacionController.php');
    exit;
}

// ════════════════════════════════════════════════════════════════════════
// CAMBIAR ESTADO
// ════════════════════════════════════════════════════════════════════════
function accion_estado($hab) {
    $id     = intval($_GET['id']         ?? 0);
    $estado = trim($_GET['nuevo_estado'] ?? '');

    if ($id && in_array($estado, ['disponible', 'ocupada', 'mantenimiento'])) {
        $hab->habitacion_id = $id;
        $hab->estado        = $estado;

        if ($hab->cambiarEstado()) {
            $_SESSION['mensaje']  = '✅ Estado actualizado a: ' . ucfirst($estado);
            $_SESSION['tipo_msg'] = 'success';
        } else {
            $_SESSION['mensaje']  = '❌ Error al cambiar el estado.';
            $_SESSION['tipo_msg'] = 'error';
        }
    }

    header('Location: /hotel-system/controllers/HabitacionController.php');
    exit;
}
?>