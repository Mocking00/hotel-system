<?php
session_start();

// ── Protección: solo administrador ───────────────────────────────────────
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../views/auth/login.php");
    exit();
}
if (!in_array($_SESSION['rol'], ['administrador', 'recepcionista'])) {
    header("Location: ../views/auth/login.php");
    exit();
}

// ── Dependencias ─────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../utils/Validator.php';

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
    case 'crear_usuario': accion_crear_usuario($db, $cliente); break;
    case 'crear_usuario_nuevo': accion_crear_usuario_nuevo($db, $cliente); break;
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
    if ($_SESSION['rol'] !== 'administrador') {
        $_SESSION['mensaje']  = 'No tienes permisos para crear clientes.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: ./ClienteController.php');
        exit;
    }

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
        if (!empty($datos['nombre']) && !Validator::nombreValido($datos['nombre'])) {
            $errores[] = 'El nombre solo puede contener letras y espacios.';
        }
        if (!empty($datos['apellido']) && !Validator::nombreValido($datos['apellido'])) {
            $errores[] = 'El apellido solo puede contener letras y espacios.';
        }
        if (!empty($datos['cedula']) && !Validator::cedulaValida($datos['cedula'])) {
            $errores[] = 'La cédula solo puede contener números y guiones.';
        }
        if (!empty($datos['telefono']) && !Validator::telefonoValido($datos['telefono'])) {
            $errores[] = 'El teléfono solo puede contener números y caracteres de formato.';
        }
        if (!empty($datos['fecha_nacimiento']) && !Validator::esMayorDeEdad($datos['fecha_nacimiento'], 18)) {
            $errores[] = 'La fecha de nacimiento indica que el cliente es menor de edad (18+ requerido).';
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
                header('Location: ./ClienteController.php');
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
    if ($_SESSION['rol'] !== 'administrador') {
        $_SESSION['mensaje']  = 'No tienes permisos para editar clientes.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: ./ClienteController.php');
        exit;
    }

    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        header('Location: ./ClienteController.php');
        exit;
    }

    $errores = [];

    // Cargar datos actuales
    $cliente->cliente_id = $id;
    $datos = $cliente->leerPorId();
    if (!$datos) {
        header('Location: ./ClienteController.php');
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
        if (!empty($datos['fecha_nacimiento']) && !Validator::esMayorDeEdad($datos['fecha_nacimiento'], 18)) {
            $errores[] = 'La fecha de nacimiento indica que el cliente es menor de edad (18+ requerido).';
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
                header("Location: ./ClienteController.php?accion=detalle&id=$id");
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
        header('Location: ./ClienteController.php');
        exit;
    }

    $cliente->cliente_id = $id;
    $datos = $cliente->leerPorId();
    if (!$datos) {
        header('Location: ./ClienteController.php');
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
    if ($_SESSION['rol'] !== 'administrador') {
        $_SESSION['mensaje']  = 'No tienes permisos para eliminar clientes.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: ./ClienteController.php');
        exit;
    }

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
    header('Location: ./ClienteController.php');
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

function accion_crear_usuario($db, $cliente) {
    $cliente_id = intval($_GET['id'] ?? 0);
    if (!$cliente_id) {
        header('Location: ./ClienteController.php');
        exit;
    }

    $cliente->cliente_id = $cliente_id;
    $datos_cliente = $cliente->leerPorId();
    if (!$datos_cliente) {
        $_SESSION['mensaje'] = 'Cliente no encontrado.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: ./ClienteController.php');
        exit;
    }

    if (!empty($datos_cliente['usuario_id']) || !empty($datos_cliente['username'])) {
        $_SESSION['mensaje'] = 'Este cliente ya tiene un usuario asociado.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: ./ClienteController.php?accion=detalle&id=' . $cliente_id);
        exit;
    }

    $errores = [];
    $datos = [
        'fecha_nacimiento' => !empty($datos_cliente['fecha_nacimiento']) ? $datos_cliente['fecha_nacimiento'] : '',
        'username' => '',
        'password' => '',
        'password_confirm' => '',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $datos['fecha_nacimiento'] = trim($_POST['fecha_nacimiento'] ?? '');
        $datos['username'] = trim($_POST['username'] ?? '');
        $datos['password'] = trim($_POST['password'] ?? '');
        $datos['password_confirm'] = trim($_POST['password_confirm'] ?? '');

        if (empty($datos['fecha_nacimiento'])) $errores[] = 'La fecha de nacimiento es obligatoria.';
        if (!empty($datos['fecha_nacimiento']) && !Validator::esMayorDeEdad($datos['fecha_nacimiento'], 18)) {
            $errores[] = 'El cliente debe ser mayor de edad (18+ años).';
        }
        if (empty($datos['username'])) $errores[] = 'El username es obligatorio.';
        if (strlen($datos['password']) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
        if ($datos['password'] !== $datos['password_confirm']) $errores[] = 'Las contraseñas no coinciden.';

        $usuario = new Usuario($db);
        $usuario->username = $datos['username'];
        if (empty($errores) && $usuario->existeUsername()) {
            $errores[] = 'El username ya está en uso.';
        }

        if (empty($errores)) {
            try {
                $db->beginTransaction();

                $usuario->password = $datos['password'];
                $usuario->rol      = 'cliente';
                $usuario->activo   = 1;

                if (!$usuario->crear()) {
                    throw new Exception('No se pudo crear el usuario.');
                }

                $stmtFecha = $db->prepare("UPDATE CLIENTE SET fecha_nacimiento = :fecha_nacimiento WHERE cliente_id = :cliente_id");
                $stmtFecha->bindValue(':fecha_nacimiento', $datos['fecha_nacimiento']);
                $stmtFecha->bindParam(':cliente_id', $cliente_id);
                if (!$stmtFecha->execute()) {
                    throw new Exception('No se pudo actualizar la fecha de nacimiento del cliente.');
                }

                $stmt = $db->prepare("UPDATE CLIENTE SET usuario_id = :usuario_id WHERE cliente_id = :cliente_id");
                $stmt->bindParam(':usuario_id', $usuario->usuario_id);
                $stmt->bindParam(':cliente_id', $cliente_id);
                if (!$stmt->execute()) {
                    throw new Exception('No se pudo vincular el usuario al cliente.');
                }

                $db->commit();

                $_SESSION['mensaje'] = 'Usuario creado y vinculado correctamente al cliente.';
                $_SESSION['tipo_msg'] = 'success';
                header('Location: ./ClienteController.php?accion=detalle&id=' . $cliente_id);
                exit;
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $errores[] = 'Error al crear usuario asistido: ' . $e->getMessage();
            }
        }
    }

    include __DIR__ . '/../views/clientes/crear_usuario.php';
}

function accion_crear_usuario_nuevo($db, $cliente) {
    if (!in_array($_SESSION['rol'], ['administrador', 'recepcionista'])) {
        $_SESSION['mensaje'] = 'No tienes permisos para crear usuarios cliente.';
        $_SESSION['tipo_msg'] = 'error';
        header('Location: ./ClienteController.php');
        exit;
    }

    $errores = [];
    $datos = [
        'nombre'           => '',
        'apellido'         => '',
        'cedula'           => '',
        'telefono'         => '',
        'email'            => '',
        'direccion'        => '',
        'fecha_nacimiento' => '',
        'username'         => '',
        'password'         => '',
        'password_confirm' => '',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach (array_keys($datos) as $campo) {
            $datos[$campo] = trim($_POST[$campo] ?? '');
        }

        if (empty($datos['nombre'])) $errores[] = 'El nombre es obligatorio.';
        if (empty($datos['apellido'])) $errores[] = 'El apellido es obligatorio.';
        if (empty($datos['cedula'])) $errores[] = 'La cédula es obligatoria.';
        if (empty($datos['telefono'])) $errores[] = 'El teléfono es obligatorio.';
        if (empty($datos['email'])) $errores[] = 'El email es obligatorio.';
        if (empty($datos['fecha_nacimiento'])) $errores[] = 'La fecha de nacimiento es obligatoria.';
        if (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El formato del email no es válido.';
        }
        if (!empty($datos['nombre']) && !Validator::nombreValido($datos['nombre'])) {
            $errores[] = 'El nombre solo puede contener letras y espacios.';
        }
        if (!empty($datos['apellido']) && !Validator::nombreValido($datos['apellido'])) {
            $errores[] = 'El apellido solo puede contener letras y espacios.';
        }
        if (!empty($datos['cedula']) && !Validator::cedulaValida($datos['cedula'])) {
            $errores[] = 'La cédula solo puede contener números y guiones.';
        }
        if (!empty($datos['telefono']) && !Validator::telefonoValido($datos['telefono'])) {
            $errores[] = 'El teléfono solo puede contener números y caracteres de formato.';
        }
        if (!empty($datos['fecha_nacimiento']) && !Validator::esMayorDeEdad($datos['fecha_nacimiento'], 18)) {
            $errores[] = 'El cliente debe ser mayor de edad (18+ años).';
        }
        if (empty($datos['username'])) $errores[] = 'El username es obligatorio.';
        if (strlen($datos['password']) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
        if ($datos['password'] !== $datos['password_confirm']) $errores[] = 'Las contraseñas no coinciden.';

        $usuario = new Usuario($db);
        $usuario->username = $datos['username'];
        if (empty($errores) && $usuario->existeUsername()) {
            $errores[] = 'El username ya está en uso.';
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
            try {
                $db->beginTransaction();

                $usuario->password = $datos['password'];
                $usuario->rol      = 'cliente';
                $usuario->activo   = 1;

                if (!$usuario->crear()) {
                    throw new Exception('No se pudo crear el usuario.');
                }

                $cliente->usuario_id       = $usuario->usuario_id;
                $cliente->nombre           = $datos['nombre'];
                $cliente->apellido         = $datos['apellido'];
                $cliente->cedula           = $datos['cedula'];
                $cliente->telefono         = $datos['telefono'];
                $cliente->email            = $datos['email'];
                $cliente->direccion        = $datos['direccion'];
                $cliente->fecha_nacimiento = !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null;

                if (!$cliente->crear()) {
                    throw new Exception('No se pudo crear el perfil del cliente.');
                }

                $db->commit();

                $_SESSION['mensaje'] = 'Usuario cliente creado correctamente.';
                $_SESSION['tipo_msg'] = 'success';
                header('Location: ./ClienteController.php?accion=detalle&id=' . $cliente->cliente_id);
                exit;
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $errores[] = 'Error al crear usuario cliente: ' . $e->getMessage();
            }
        }
    }

    include __DIR__ . '/../views/clientes/crear_usuario_nuevo.php';
}
?>
