<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../views/auth/login.php");
    exit();
}

if (!in_array($_SESSION['rol'], ['administrador', 'recepcionista', 'cliente'])) {
    header("Location: ../views/auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reserva.php';

$database = new Database();
$db       = $database->getConnection();
$reserva  = new Reserva($db);

$accion = $_GET['accion'] ?? 'listar';

if ($_SESSION['rol'] === 'recepcionista' && !in_array($accion, ['listar', 'crear', 'detalle', 'checkin', 'checkout'])) {
    $_SESSION['mensaje'] = 'Recepcion solo puede listar, crear reservas y confirmar CI/CO.';
    $_SESSION['tipo_msg'] = 'error';
    header('Location: ./ReservaController.php');
    exit;
}

switch ($accion) {
    case 'crear':
        accion_crear($reserva);
        break;
    case 'detalle':
        accion_detalle($reserva);
        break;
    case 'checkin':
        accion_checkin($reserva);
        break;
    case 'checkout':
        accion_checkout($reserva);
        break;
    case 'cancelar':
        accion_cancelar($reserva);
        break;
    case 'api_habitaciones':
        api_habitaciones($reserva);
        break;
    case 'reportes':
        accion_reportes($reserva);
        break;
    default:
        accion_listar($reserva);
        break;
}

function obtener_cliente_id_actual($reserva) {
    if ($_SESSION['rol'] !== 'cliente') {
        return null;
    }

    return $reserva->obtenerClienteIdPorUsuario($_SESSION['usuario_id']);
}

function accion_listar($reserva) {
    $filtros = [
        'estado'      => trim($_GET['estado'] ?? ''),
        'fecha_desde' => trim($_GET['fecha_desde'] ?? ''),
        'fecha_hasta' => trim($_GET['fecha_hasta'] ?? ''),
        'buscar'      => trim($_GET['buscar'] ?? ''),
    ];

    $solo_cliente_id = obtener_cliente_id_actual($reserva);
    if ($_SESSION['rol'] === 'cliente' && empty($solo_cliente_id)) {
        $_SESSION['mensaje'] = 'No se encontro un perfil de cliente asociado a tu usuario.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: ../views/cliente/dashboard.php');
        exit;
    }

    $resultado = $reserva->leerTodas($filtros, $solo_cliente_id);
    $reservas  = $resultado->fetchAll(PDO::FETCH_ASSOC);

    $mensaje  = $_SESSION['mensaje'] ?? '';
    $tipo_msg = $_SESSION['tipo_msg'] ?? '';
    unset($_SESSION['mensaje'], $_SESSION['tipo_msg']);

    include __DIR__ . '/../views/reservas/listar.php';
}

function accion_crear($reserva) {
    $errores = [];
    $es_cliente = $_SESSION['rol'] === 'cliente';
    $habitacion_preseleccionada = trim($_GET['hab'] ?? '');

    $datos = [
        'cliente_id'       => '',
        'habitacion_id'    => '',
        'fecha_entrada'    => date('Y-m-d', strtotime('+1 day')),
        'fecha_salida'     => date('Y-m-d', strtotime('+2 day')),
        'numero_personas'  => '1',
        'tipo_habitacion'  => '',
        'notas_especiales' => '',
    ];

    $cliente_actual_id = obtener_cliente_id_actual($reserva);
    if ($es_cliente && empty($cliente_actual_id)) {
        $_SESSION['mensaje'] = 'No se encontro un perfil de cliente asociado a tu usuario.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: ../views/cliente/dashboard.php');
        exit;
    }

    if ($es_cliente) {
        $datos['cliente_id'] = (string) $cliente_actual_id;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && ctype_digit($habitacion_preseleccionada)) {
        $datos['habitacion_id'] = $habitacion_preseleccionada;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach (array_keys($datos) as $campo) {
            $datos[$campo] = trim($_POST[$campo] ?? $datos[$campo]);
        }

        if ($es_cliente) {
            $datos['cliente_id'] = (string) $cliente_actual_id;
        }

        if (empty($datos['cliente_id']) || !ctype_digit((string) $datos['cliente_id'])) {
            $errores[] = 'Debes seleccionar un cliente valido.';
        }
        if (empty($datos['fecha_entrada'])) {
            $errores[] = 'La fecha de entrada es obligatoria.';
        }
        if (empty($datos['fecha_salida'])) {
            $errores[] = 'La fecha de salida es obligatoria.';
        }
        if (!ctype_digit((string) $datos['numero_personas']) || (int) $datos['numero_personas'] <= 0) {
            $errores[] = 'El numero de personas debe ser mayor a cero.';
        }

        if (empty($errores)) {
            $entrada = strtotime($datos['fecha_entrada']);
            $salida  = strtotime($datos['fecha_salida']);
            if ($entrada === false || $salida === false) {
                $errores[] = 'Las fechas son invalidas.';
            } elseif ($salida <= $entrada) {
                $errores[] = 'La fecha de salida debe ser posterior a la fecha de entrada.';
            }
        }

        $habitaciones_disponibles = [];
        if (empty($errores)) {
            try {
                $habitaciones_disponibles = $reserva->obtenerHabitacionesDisponibles(
                    $datos['fecha_entrada'],
                    $datos['fecha_salida'],
                    (int) $datos['numero_personas'],
                    $datos['tipo_habitacion']
                );
            } catch (Throwable $e) {
                $errores[] = 'No se pudieron consultar habitaciones disponibles. Intenta nuevamente.';
                $habitaciones_disponibles = [];
            }

            if (empty($datos['habitacion_id']) || !ctype_digit((string) $datos['habitacion_id'])) {
                $errores[] = 'Debes seleccionar una habitacion disponible.';
            } else {
                $hab_valida = false;
                foreach ($habitaciones_disponibles as $h) {
                    if ((int) $h['habitacion_id'] === (int) $datos['habitacion_id']) {
                        $hab_valida = true;
                        break;
                    }
                }
                if (!$hab_valida) {
                    $errores[] = 'La habitacion seleccionada ya no esta disponible para ese rango de fechas.';
                }
            }
        } else {
            $habitaciones_disponibles = [];
        }

        if (empty($errores)) {
            try {
                $precio_total = $reserva->calcularPrecioTotal(
                    (int) $datos['habitacion_id'],
                    $datos['fecha_entrada'],
                    $datos['fecha_salida']
                );

                if ($precio_total === false) {
                    $errores[] = 'No se pudo calcular el precio total de la reserva.';
                } else {
                    $reserva->cliente_id       = (int) $datos['cliente_id'];
                    $reserva->habitacion_id    = (int) $datos['habitacion_id'];
                    $reserva->fecha_entrada    = $datos['fecha_entrada'];
                    $reserva->fecha_salida     = $datos['fecha_salida'];
                    $reserva->numero_personas  = (int) $datos['numero_personas'];
                    $reserva->precio_total     = $precio_total;
                    $reserva->estado           = 'pendiente';
                    $reserva->notas_especiales = $datos['notas_especiales'];

                    if ($reserva->crear()) {
                        $_SESSION['mensaje'] = 'Reserva creada correctamente.';
                        $_SESSION['tipo_msg'] = 'success';
                        header('Location: ./ReservaController.php?accion=detalle&id=' . $reserva->reserva_id);
                        exit;
                    }
                    $errores[] = 'Error al guardar la reserva en la base de datos.';
                }
            } catch (Throwable $e) {
                $errores[] = 'No se pudo completar la reserva. Detalle: ' . $e->getMessage();
            }
        }
    }

    if (!isset($habitaciones_disponibles)) {
        try {
            $habitaciones_disponibles = $reserva->obtenerHabitacionesDisponibles(
                $datos['fecha_entrada'],
                $datos['fecha_salida'],
                (int) $datos['numero_personas'],
                $datos['tipo_habitacion']
            );
        } catch (Throwable $e) {
            $habitaciones_disponibles = [];
            if (empty($errores)) {
                $errores[] = 'No se pudieron cargar habitaciones disponibles en este momento.';
            }
        }
    }

    $clientes = $es_cliente ? [] : $reserva->obtenerClientesSelect();

    include __DIR__ . '/../views/reservas/crear.php';
}

function accion_detalle($reserva) {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        header('Location: ./ReservaController.php');
        exit;
    }

    $solo_cliente_id = obtener_cliente_id_actual($reserva);
    if ($_SESSION['rol'] === 'cliente' && empty($solo_cliente_id)) {
        $_SESSION['mensaje'] = 'No se encontro un perfil de cliente asociado a tu usuario.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: ../views/cliente/dashboard.php');
        exit;
    }

    $reserva_detalle = $reserva->leerPorId($id, $solo_cliente_id);
    if (!$reserva_detalle) {
        $_SESSION['mensaje'] = 'Reserva no encontrada.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: ./ReservaController.php');
        exit;
    }

    $mensaje  = $_SESSION['mensaje'] ?? '';
    $tipo_msg = $_SESSION['tipo_msg'] ?? '';
    unset($_SESSION['mensaje'], $_SESSION['tipo_msg']);

    include __DIR__ . '/../views/reservas/detalle.php';
}

function accion_checkin($reserva) {
    if ($_SESSION['rol'] === 'cliente') {
        $_SESSION['mensaje'] = 'No tienes permisos para registrar check-in.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: ./ReservaController.php');
        exit;
    }

    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        header('Location: ./ReservaController.php');
        exit;
    }

    if ($reserva->registrarCheckin($id)) {
        $_SESSION['mensaje'] = 'Check-in registrado correctamente.';
        $_SESSION['tipo_msg'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'No se pudo registrar el check-in. Verifica el estado actual de la reserva.';
        $_SESSION['tipo_msg'] = 'error';
    }

    header('Location: ./ReservaController.php?accion=detalle&id=' . $id);
    exit;
}

function accion_checkout($reserva) {
    if ($_SESSION['rol'] === 'cliente') {
        $_SESSION['mensaje'] = 'No tienes permisos para registrar check-out.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: ./ReservaController.php');
        exit;
    }

    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        header('Location: ./ReservaController.php');
        exit;
    }

    if ($reserva->registrarCheckout($id)) {
        $_SESSION['mensaje'] = 'Check-out registrado correctamente.';
        $_SESSION['tipo_msg'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'No se pudo registrar el check-out. Verifica que exista un check-in previo.';
        $_SESSION['tipo_msg'] = 'error';
    }

    header('Location: ./ReservaController.php?accion=detalle&id=' . $id);
    exit;
}

function accion_cancelar($reserva) {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        header('Location: ./ReservaController.php');
        exit;
    }

    if ($_SESSION['rol'] === 'cliente') {
        $cliente_id = obtener_cliente_id_actual($reserva);
        $pertenece  = $reserva->leerPorId($id, $cliente_id);
        if (!$pertenece) {
            $_SESSION['mensaje'] = 'No puedes cancelar una reserva que no te pertenece.';
            $_SESSION['tipo_msg'] = 'error';
            header('Location: ./ReservaController.php');
            exit;
        }
    }

    if ($reserva->cancelar($id)) {
        $_SESSION['mensaje'] = 'Reserva cancelada correctamente.';
        $_SESSION['tipo_msg'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'No se pudo cancelar la reserva. Es posible que ya tenga check-in o este en estado final.';
        $_SESSION['tipo_msg'] = 'error';
    }

    header('Location: ./ReservaController.php?accion=detalle&id=' . $id);
    exit;
}

function api_habitaciones($reserva) {
    header('Content-Type: application/json');

    $fecha_entrada   = trim($_GET['fecha_entrada'] ?? '');
    $fecha_salida    = trim($_GET['fecha_salida'] ?? '');
    $numero_personas = intval($_GET['numero_personas'] ?? 1);
    $tipo            = trim($_GET['tipo'] ?? '');

    if (empty($fecha_entrada) || empty($fecha_salida) || $numero_personas <= 0) {
        echo json_encode([]);
        exit;
    }

    try {
        $habitaciones = $reserva->obtenerHabitacionesDisponibles(
            $fecha_entrada,
            $fecha_salida,
            $numero_personas,
            $tipo
        );
        echo json_encode($habitaciones);
    } catch (Throwable $e) {
        echo json_encode([]);
    }
    exit;
}

function accion_reportes($reserva) {
    if ($_SESSION['rol'] !== 'administrador') {
        $_SESSION['mensaje'] = 'No tienes permisos para acceder a reportes.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: ./ReservaController.php');
        exit;
    }

    $filtros = [
        'estado'      => trim($_GET['estado'] ?? ''),
        'fecha_desde' => trim($_GET['fecha_desde'] ?? ''),
        'fecha_hasta' => trim($_GET['fecha_hasta'] ?? ''),
    ];

    try {
        $kpis            = $reserva->obtenerKpisReporte($filtros);
        $resumen_estados = $reserva->obtenerResumenPorEstado($filtros);
        $ingresos_mes    = $reserva->obtenerIngresosMensuales(6);
        $reportes        = $reserva->obtenerReporteReservas($filtros);
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = 'No se pudo cargar el módulo de reportes en este momento.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: ./ReservaController.php');
        exit;
    }

    include __DIR__ . '/../views/reservas/reportes.php';
}
?>
