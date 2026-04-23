<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
if ($_SESSION['rol'] !== 'cliente') {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Cliente.php';
require_once __DIR__ . '/../../models/Reserva.php';
require_once __DIR__ . '/../../utils/url_helper.php';

$database = new Database();
$db       = $database->getConnection();

// ── Cargar datos del cliente vinculado a este usuario ─────────────────────
$stmt = $db->prepare("SELECT * FROM CLIENTE WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$cliente_data = $stmt->fetch(PDO::FETCH_ASSOC);
$cliente_id   = $cliente_data['cliente_id'] ?? null;

// ── Reservas del cliente ───────────────────────────────────────────────────
$reservas = [];
if ($cliente_id) {
    $stmt = $db->prepare(
        "SELECT r.reserva_id, r.codigo_confirmacion, r.fecha_entrada, r.fecha_salida,
                r.precio_total, r.estado, r.fecha_reserva,
                h.numero AS numero_habitacion, h.tipo AS tipo_habitacion,
                DATEDIFF(r.fecha_salida, r.fecha_entrada) AS noches
         FROM RESERVA r
         JOIN HABITACION h ON r.habitacion_id = h.habitacion_id
         WHERE r.cliente_id = ?
         ORDER BY r.fecha_reserva DESC
         LIMIT 10"
    );
    $stmt->execute([$cliente_id]);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Habitaciones disponibles (próximas 3) ─────────────────────────────────
$stmt = $db->prepare(
    "SELECT habitacion_id, numero, tipo, precio_noche, capacidad, piso, descripcion
     FROM HABITACION
     WHERE estado = 'disponible'
     ORDER BY precio_noche ASC
     LIMIT 6"
);
$stmt->execute();
$habitaciones_disp = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Contadores ────────────────────────────────────────────────────────────
$total_reservas   = count($reservas);
$reservas_activas = count(array_filter($reservas, fn($r) => in_array($r['estado'], ['pendiente','confirmada'])));
$reservas_pasadas = count(array_filter($reservas, fn($r) => $r['estado'] === 'completada'));

$username = $_SESSION['username'];

// Construye rutas internas robustas para evitar errores por profundidad de carpetas.
$appBase = app_base_path();

$dashboardUrl = ($appBase !== '' ? $appBase : '') . '/views/cliente/dashboard.php';
$reservasUrl = ($appBase !== '' ? $appBase : '') . '/controllers/ReservaController.php';
$logoutUrl = ($appBase !== '' ? $appBase : '') . '/controllers/UsuarioController.php?action=logout';
$cambiarPasswordUrl = ($appBase !== '' ? $appBase : '') . '/views/cliente/cambiar_password.php';

$badge = [
    'pendiente'  => ['bg'=>'#fff3cd','color'=>'#856404', 'icon'=>'⏳'],
    'confirmada' => ['bg'=>'#d4edda','color'=>'#155724', 'icon'=>'✅'],
    'cancelada'  => ['bg'=>'#f8d7da','color'=>'#721c24', 'icon'=>'❌'],
    'completada' => ['bg'=>'#cce5ff','color'=>'#004085', 'icon'=>'🏁'],
    'no_show'    => ['bg'=>'#e2e3e5','color'=>'#383d41', 'icon'=>'👻'],
];
$iconos_tipo = ['simple'=>'🛏️','doble'=>'👫','suite'=>'⭐','presidencial'=>'💎'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - HotelManager</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f3f8fb; }

        /* ── Header ── */
        .header {
            background:linear-gradient(135deg,#1b98e0 0%,#0b2545 100%);
            color:white; padding:0 30px;
            display:flex; justify-content:space-between; align-items:center;
            height:65px; position:sticky; top:0; z-index:100;
            box-shadow:0 2px 12px rgba(0,0,0,.15);
        }
        .logo { font-size:22px; font-weight:bold; }
        .user-area { display:flex; align-items:center; gap:12px; }
        .user-avatar {
            width:38px; height:38px; background:rgba(255,255,255,.25);
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            font-weight:bold; font-size:15px;
        }
        .btn-logout {
            padding:8px 16px; background:#dc3545;
            border:1px solid #dc3545; color:white;
            border-radius:8px; font-size:13px; text-decoration:none; transition:all .2s;
        }
        .btn-logout:hover { background:#bb2d3b; border-color:#bb2d3b; color:white; }

        /* ── Layout ── */
        .container { max-width:1300px; margin:30px auto; padding:0 24px; }

        /* ── Bienvenida ── */
        .welcome-card {
            background:white; border-radius:14px; padding:28px 32px;
            box-shadow:0 2px 10px rgba(0,0,0,.06); margin-bottom:24px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .welcome-card h1 { color:#333; font-size:24px; margin-bottom:5px; }
        .welcome-card p  { color:#888; font-size:14px; }
        .btn-nueva-reserva {
            padding:12px 26px; background:linear-gradient(135deg,#1b98e0,#0b2545);
            color:white; border:none; border-radius:10px; font-size:14px;
            font-weight:600; text-decoration:none; white-space:nowrap;
            box-shadow:0 4px 15px rgba(27,152,224,.4); transition:all .2s;
        }
        .btn-nueva-reserva:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(27,152,224,.5); }

        /* ── Stats ── */
        .stats-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px; }
        .stat-card {
            background:white; border-radius:12px; padding:20px 22px;
            box-shadow:0 2px 8px rgba(0,0,0,.05); text-align:center;
        }
        .stat-card .icon { font-size:32px; margin-bottom:8px; }
        .stat-card .val  { font-size:28px; font-weight:bold; color:#333; }
        .stat-card .lbl  { font-size:13px; color:#888; margin-top:4px; }

        /* ── Secciones ── */
        .section-title {
            font-size:18px; font-weight:700; color:#333;
            margin-bottom:16px; display:flex; align-items:center; gap:8px;
        }
        .card { background:white; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.05); }

        /* ── Tabla reservas ── */
        .table-card { overflow:hidden; margin-bottom:24px; }
        table { width:100%; border-collapse:collapse; }
        thead { background:#12355b; color:white; }
        thead th { padding:12px 14px; text-align:left; font-size:13px; }
        tbody tr { border-bottom:1px solid #f0f0f0; transition:background .2s; }
        tbody tr:hover { background:#fafafa; }
        tbody td { padding:12px 14px; font-size:13px; color:#444; }
        .badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .btn-ver { padding:4px 12px; background:#e3f4ff; color:#0b6fa4; border:1px solid #a9d9f5; border-radius:6px; font-size:12px; text-decoration:none; }
        .empty-state { text-align:center; padding:40px; color:#bbb; }
        .empty-state span { font-size:40px; display:block; margin-bottom:10px; }

        /* ── Habitaciones disponibles ── */
        .hab-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:14px; padding:20px; margin-bottom:24px; }
        .hab-card {
            border:2px solid #e8edf3; border-radius:10px; padding:16px;
            transition:all .2s; text-align:center;
        }
        .hab-card:hover { border-color:#1b98e0; box-shadow:0 4px 15px rgba(27,152,224,.15); transform:translateY(-2px); }
        .hab-card .hab-icon { font-size:28px; margin-bottom:8px; }
        .hab-card .hab-num  { font-size:16px; font-weight:bold; color:#333; }
        .hab-card .hab-tipo { font-size:12px; color:#888; margin:3px 0; }
        .hab-card .hab-precio { font-size:15px; font-weight:600; color:#2a9d8f; margin:6px 0; }
        .hab-card .hab-cap { font-size:11px; color:#aaa; }
        .btn-reservar {
            display:block; margin-top:10px; padding:7px 0;
            background:linear-gradient(135deg,#1b98e0,#0b2545);
            color:white; border-radius:8px; font-size:12px;
            font-weight:600; text-decoration:none; text-align:center;
        }
        .btn-reservar:hover { opacity:.9; }

        /* ── Perfil ── */
        .perfil-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; padding:20px; }
        .perfil-item .lbl { font-size:11px; color:#888; font-weight:600; text-transform:uppercase; margin-bottom:4px; }
        .perfil-item .val { font-size:14px; color:#333; }
        .perfil-item.full { grid-column:1/-1; }
        .perfil-avatar {
            width:70px; height:70px; background:linear-gradient(135deg,#1b98e0,#0b2545);
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            color:white; font-size:26px; font-weight:bold; margin:0 auto 15px;
        }
        .perfil-header { text-align:center; padding:20px 20px 10px; border-bottom:1px solid #f0f0f0; }
        .perfil-header .nombre { font-size:18px; font-weight:700; color:#333; }
        .perfil-header .email  { font-size:13px; color:#888; }
        .sin-perfil { text-align:center; padding:30px; color:#aaa; font-size:14px; }

        /* ── Main layout ── */
        .main-grid { display:grid; grid-template-columns:1fr 300px; gap:20px; }
        .col-left  { min-width:0; }
        .col-right { }

        @media (max-width:900px) {
            .main-grid { grid-template-columns:1fr; }
            .stats-grid { grid-template-columns:1fr 1fr; }
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="logo">🏨 HotelManager</div>
    <div class="user-area">
        <div class="user-avatar"><?= strtoupper(substr($username,0,2)) ?></div>
        <div>
            <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($username) ?></div>
            <div style="font-size:11px;opacity:.8">Cliente</div>
        </div>
        <a href="<?= htmlspecialchars($logoutUrl) ?>" class="btn-logout">
            🚪 Salir
        </a>
    </div>
</div>

<div class="container">

    <!-- Bienvenida -->
    <div class="welcome-card">
        <div>
            <h1>¡Bienvenido, <?= htmlspecialchars($cliente_data['nombre'] ?? $username) ?>! 👋</h1>
            <p>Gestiona tus reservas y descubre nuestras habitaciones disponibles.</p>
        </div>
        <a href="<?= htmlspecialchars($reservasUrl) ?>?accion=crear" class="btn-nueva-reserva">
            ➕ Nueva Reserva
        </a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon">📅</div>
            <div class="val"><?= $total_reservas ?></div>
            <div class="lbl">Total de Reservas</div>
        </div>
        <div class="stat-card">
            <div class="icon">✅</div>
            <div class="val"><?= $reservas_activas ?></div>
            <div class="lbl">Reservas Activas</div>
        </div>
        <div class="stat-card">
            <div class="icon">🏁</div>
            <div class="val"><?= $reservas_pasadas ?></div>
            <div class="lbl">Estadías Completadas</div>
        </div>
    </div>

    <!-- Grid principal -->
    <div class="main-grid">

        <!-- Columna izquierda -->
        <div class="col-left">

            <!-- Mis Reservas -->
            <div class="section-title" id="mis-reservas">📅 Mis Reservas</div>
            <div class="card table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Habitación</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($reservas)): ?>
                        <tr><td colspan="7">
                            <div class="empty-state">
                                <span>📅</span>
                                Aún no tienes reservas.<br>
                                          <a href="<?= htmlspecialchars($reservasUrl) ?>?accion=crear"
                                   style="color:#1b98e0;text-decoration:none;font-weight:600">
                                   Haz tu primera reserva →
                                </a>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($reservas as $r):
                            $bc = $badge[$r['estado']] ?? $badge['pendiente']; ?>
                        <tr>
                            <td><code style="font-size:11px;background:#f0f0f0;padding:2px 6px;border-radius:4px"><?= htmlspecialchars($r['codigo_confirmacion']) ?></code></td>
                            <td>
                                <strong>#<?= htmlspecialchars($r['numero_habitacion']) ?></strong>
                                <div style="font-size:11px;color:#888"><?= ucfirst($r['tipo_habitacion']) ?></div>
                            </td>
                            <td><?= date('d/m/Y', strtotime($r['fecha_entrada'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($r['fecha_salida'])) ?></td>
                            <td style="color:#2a9d8f;font-weight:600">$<?= number_format($r['precio_total'],2) ?></td>
                            <td>
                                <span class="badge" style="background:<?= $bc['bg'] ?>;color:<?= $bc['color'] ?>">
                                    <?= $bc['icon'] ?> <?= ucfirst($r['estado']) ?>
                                </span>
                            </td>
                            <td>
                                          <a href="<?= htmlspecialchars($reservasUrl) ?>?accion=detalle&id=<?= $r['reserva_id'] ?>"
                                   class="btn-ver">Ver</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Habitaciones disponibles -->
            <div class="section-title" id="habitaciones">🛏️ Habitaciones Disponibles</div>
            <div class="card" style="margin-bottom:24px">
                <div class="hab-grid">
                    <?php if (empty($habitaciones_disp)): ?>
                        <div class="empty-state" style="grid-column:1/-1">
                            <span>😔</span>No hay habitaciones disponibles en este momento.
                        </div>
                    <?php else: ?>
                        <?php foreach ($habitaciones_disp as $h): ?>
                        <div class="hab-card">
                            <div class="hab-icon"><?= $iconos_tipo[$h['tipo']] ?? '🛏️' ?></div>
                            <div class="hab-num">Hab. <?= htmlspecialchars($h['numero']) ?></div>
                            <div class="hab-tipo"><?= ucfirst($h['tipo']) ?> · Piso <?= $h['piso'] ?></div>
                            <div class="hab-precio">$<?= number_format($h['precio_noche'],2) ?>/noche</div>
                            <div class="hab-cap">👤 Capacidad: <?= $h['capacidad'] ?></div>
                                     <a href="<?= htmlspecialchars($reservasUrl) ?>?accion=crear&hab=<?= $h['habitacion_id'] ?>"
                               class="btn-reservar">Reservar</a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Columna derecha: Perfil -->
        <div class="col-right">
            <div class="section-title" id="mi-perfil">👤 Mi Perfil</div>
            <div class="card" style="margin-bottom:20px">
                <?php if ($cliente_data): ?>
                <div class="perfil-header">
                    <div class="perfil-avatar"><?= strtoupper(substr($cliente_data['nombre'],0,1)) ?></div>
                    <div class="nombre"><?= htmlspecialchars($cliente_data['nombre'].' '.$cliente_data['apellido']) ?></div>
                    <div class="email"><?= htmlspecialchars($cliente_data['email']) ?></div>
                </div>
                <div class="perfil-grid">
                    <div class="perfil-item full">
                        <div class="lbl">Usuario</div>
                        <div class="val">@<?= htmlspecialchars($username) ?></div>
                    </div>
                    <div class="perfil-item full">
                        <div class="lbl">Cédula</div>
                        <div class="val"><?= htmlspecialchars($cliente_data['cedula']) ?></div>
                    </div>
                    <div class="perfil-item full">
                        <div class="lbl">Teléfono</div>
                        <div class="val"><?= htmlspecialchars($cliente_data['telefono']) ?></div>
                    </div>
                    <?php if (!empty($cliente_data['direccion'])): ?>
                    <div class="perfil-item full">
                        <div class="lbl">Dirección</div>
                        <div class="val"><?= htmlspecialchars($cliente_data['direccion']) ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="perfil-item full">
                        <div class="lbl">Cliente desde</div>
                        <div class="val"><?= date('d/m/Y', strtotime($cliente_data['fecha_registro'])) ?></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="sin-perfil">
                    <div style="font-size:36px;margin-bottom:10px">👤</div>
                    Perfil no completado.<br>
                    <small>Contacta a recepción para completar tus datos.</small>
                </div>
                <?php endif; ?>
            </div>

            <!-- Acceso rápido -->
            <div class="section-title">⚡ Acceso Rápido</div>
            <div class="card" style="padding:15px">
                     <a href="<?= htmlspecialchars($reservasUrl) ?>?accion=crear"
                   style="display:flex;align-items:center;gap:10px;padding:12px;border-radius:8px;text-decoration:none;color:#333;transition:background .2s;margin-bottom:5px"
                   onmouseover="this.style.background='#eef7fb'" onmouseout="this.style.background=''">
                    <span style="font-size:20px">➕</span>
                    <div><div style="font-weight:600;font-size:14px">Nueva Reserva</div>
                    <div style="font-size:12px;color:#888">Reserva tu habitación</div></div>
                </a>
                                         <a href="<?= htmlspecialchars($reservasUrl) ?>"
                   style="display:flex;align-items:center;gap:10px;padding:12px;border-radius:8px;text-decoration:none;color:#333;transition:background .2s;margin-bottom:5px"
                   onmouseover="this.style.background='#eef7fb'" onmouseout="this.style.background=''">
                    <span style="font-size:20px">📅</span>
                    <div><div style="font-weight:600;font-size:14px">Mis Reservas</div>
                    <div style="font-size:12px;color:#888">Ver historial</div></div>
                </a>
                                         <a href="<?= htmlspecialchars($reservasUrl) ?>?accion=crear"
                   style="display:flex;align-items:center;gap:10px;padding:12px;border-radius:8px;text-decoration:none;color:#333;transition:background .2s"
                   onmouseover="this.style.background='#eef7fb'" onmouseout="this.style.background=''">
                    <span style="font-size:20px">🛏️</span>
                    <div><div style="font-weight:600;font-size:14px">Habitaciones</div>
                    <div style="font-size:12px;color:#888">Ver disponibilidad</div></div>
                </a>
                          <a href="<?= htmlspecialchars($cambiarPasswordUrl) ?>"
                       style="display:flex;align-items:center;gap:10px;padding:12px;border-radius:8px;text-decoration:none;color:#333;transition:background .2s"
                       onmouseover="this.style.background='#eef7fb'" onmouseout="this.style.background=''">
                        <span style="font-size:20px">🔒</span>
                        <div><div style="font-weight:600;font-size:14px">Cambiar Contraseña</div>
                        <div style="font-size:12px;color:#888">Actualizar seguridad de cuenta</div></div>
                    </a>
            </div>
        </div>

    </div>
</div>

</body>
</html>
