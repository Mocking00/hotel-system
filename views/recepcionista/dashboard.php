<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
if ($_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Reserva.php';

// Construye una URL absoluta al dashboard para evitar bucles por rutas relativas.
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$appBase = '';
$marker = '/views/recepcionista/dashboard.php';
$pos = strpos($scriptName, $marker);
if ($pos !== false) {
    $appBase = substr($scriptName, 0, $pos);
}
$dashboardUrl = ($appBase !== '' ? $appBase : '') . '/views/recepcionista/dashboard.php';
$reservasUrl = ($appBase !== '' ? $appBase : '') . '/controllers/ReservaController.php';
$habitacionesUrl = ($appBase !== '' ? $appBase : '') . '/controllers/HabitacionController.php';
$clientesUrl = ($appBase !== '' ? $appBase : '') . '/controllers/ClienteController.php';
$logoutUrl = ($appBase !== '' ? $appBase : '') . '/controllers/UsuarioController.php?action=logout';

$database = new Database();
$db       = $database->getConnection();

// ── Stats generales ───────────────────────────────────────────────────────
$stmt = $db->query("SELECT COUNT(*) FROM HABITACION");
$total_hab = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM HABITACION WHERE estado = 'disponible'");
$hab_disponibles = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM HABITACION WHERE estado = 'ocupada'");
$hab_ocupadas = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM RESERVA WHERE estado IN ('pendiente','confirmada')");
$reservas_activas = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM RESERVA WHERE DATE(fecha_checkin) = CURDATE()");
$checkins_hoy = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM RESERVA WHERE DATE(fecha_checkout) = CURDATE()");
$checkouts_hoy = $stmt->fetchColumn();

// ── Reservas recientes ────────────────────────────────────────────────────
$stmt = $db->query(
    "SELECT r.reserva_id, r.codigo_confirmacion, r.fecha_entrada, r.fecha_salida,
            r.precio_total, r.estado, r.fecha_checkin,
            CONCAT(c.nombre,' ',c.apellido) AS nombre_cliente,
            h.numero AS numero_habitacion, h.tipo AS tipo_habitacion
     FROM RESERVA r
     JOIN CLIENTE c    ON r.cliente_id    = c.cliente_id
     JOIN HABITACION h ON r.habitacion_id = h.habitacion_id
     WHERE r.estado IN ('pendiente','confirmada')
     ORDER BY r.fecha_entrada ASC
     LIMIT 15"
);
$reservas_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Check-ins de hoy ──────────────────────────────────────────────────────
$stmt = $db->prepare(
    "SELECT r.reserva_id, r.codigo_confirmacion, r.fecha_entrada, r.fecha_checkin,
            r.estado,
            CONCAT(c.nombre,' ',c.apellido) AS nombre_cliente,
            c.telefono,
            h.numero AS numero_habitacion
     FROM RESERVA r
     JOIN CLIENTE c    ON r.cliente_id    = c.cliente_id
     JOIN HABITACION h ON r.habitacion_id = h.habitacion_id
     WHERE DATE(r.fecha_entrada) = CURDATE()
       AND r.estado IN ('pendiente','confirmada')
     ORDER BY r.fecha_entrada ASC"
);
$stmt->execute();
$checkins_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

$username = $_SESSION['username'];

$badge = [
    'pendiente'  => ['bg'=>'#fff3cd','color'=>'#856404', 'icon'=>'⏳'],
    'confirmada' => ['bg'=>'#d4edda','color'=>'#155724', 'icon'=>'✅'],
    'cancelada'  => ['bg'=>'#f8d7da','color'=>'#721c24', 'icon'=>'❌'],
    'completada' => ['bg'=>'#cce5ff','color'=>'#004085', 'icon'=>'🏁'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recepción - HotelManager</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f3f8fb; }

        .sidebar { width:260px; background:#12355b; color:white; min-height:100vh; padding:20px 0; position:fixed; left:0; top:0; }
        .logo-section { padding:0 20px 20px; border-bottom:1px solid rgba(255,255,255,.1); margin-bottom:20px; }
        .logo { font-size:24px; font-weight:bold; margin-bottom:5px; }
        .role { font-size:13px; opacity:0.8; }
        .menu-item { padding:15px 20px; display:flex; align-items:center; gap:12px; border-left:4px solid transparent; text-decoration:none; color:white; transition:all .3s; font-size:14px; }
        .menu-item:hover,.menu-item.active { background:rgba(255,255,255,.1); border-left-color:#1b98e0; }
        .menu-icon { font-size:20px; width:24px; }

        .main-content { margin-left:260px; }
        .header { background:white; padding:18px 28px; box-shadow:0 2px 5px rgba(0,0,0,.05); display:flex; justify-content:space-between; align-items:center; }
        .header-title h1 { color:#333; font-size:22px; margin-bottom:3px; }
        .header-subtitle { color:#888; font-size:13px; }
        .user-section { display:flex; align-items:center; gap:12px; }
        .user-info { display:flex; align-items:center; gap:10px; }
        .user-avatar { width:38px; height:38px; background:#2a9d8f; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; }
        .btn-logout { padding:7px 18px; background:#e76f51; color:white; border:none; border-radius:8px; font-size:13px; text-decoration:none; }

        .content-area { padding:24px 28px; }

        /* Stats */
        .stats-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:14px; margin-bottom:24px; }
        .stat-card { background:white; border-radius:10px; padding:16px; box-shadow:0 2px 8px rgba(0,0,0,.05); text-align:center; }
        .stat-card .icon { font-size:26px; margin-bottom:6px; }
        .stat-card .val  { font-size:22px; font-weight:bold; color:#333; }
        .stat-card .lbl  { font-size:11px; color:#888; margin-top:3px; }
        .stat-card.highlight { background:linear-gradient(135deg,#1b98e0,#0b2545); color:white; }
        .stat-card.highlight .val,.stat-card.highlight .lbl { color:white; }

        /* Ocupación */
        .occ-bar { background:#e9ecef; border-radius:20px; height:8px; margin-top:6px; overflow:hidden; }
        .occ-fill { background:linear-gradient(90deg,#2a9d8f,#2ec4b6); height:100%; border-radius:20px; }

        /* Grid layout */
        .two-col { display:grid; grid-template-columns:1fr 380px; gap:20px; }

        .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
        .section-title { font-size:16px; font-weight:700; color:#333; }
        .btn-ver-todo { font-size:12px; color:#1b98e0; text-decoration:none; }

        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.05); margin-bottom:20px; }
        table { width:100%; border-collapse:collapse; }
        thead { background:#12355b; color:white; }
        thead th { padding:11px 12px; text-align:left; font-size:12px; }
        tbody tr { border-bottom:1px solid #f0f0f0; transition:background .2s; }
        tbody tr:hover { background:#fafafa; }
        tbody td { padding:11px 12px; font-size:13px; color:#444; }
        .badge { padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
        .btn-sm { padding:4px 10px; border-radius:6px; font-size:11px; text-decoration:none; border:1px solid; display:inline-block; }
        .btn-ci { background:#d4edda; color:#155724; border-color:#c3e6cb; }
        .btn-co { background:#fff3cd; color:#856404; border-color:#ffc107; }
        .btn-det{ background:#e3f4ff; color:#0b6fa4; border-color:#a9d9f5; }
        .empty-row td { text-align:center; padding:30px; color:#bbb; font-size:13px; }

        /* Checkins hoy */
        .checkin-item { padding:14px 16px; border-bottom:1px solid #f5f5f5; display:flex; align-items:center; gap:12px; }
        .checkin-item:last-child { border-bottom:none; }
        .checkin-avatar { width:36px; height:36px; background:#1b98e0; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-size:13px; font-weight:bold; flex-shrink:0; }
        .checkin-info .nombre { font-size:13px; font-weight:600; color:#333; }
        .checkin-info .sub    { font-size:11px; color:#888; }
        .checkin-actions { margin-left:auto; }
        .ci-hecho { background:#d4edda; color:#155724; padding:3px 8px; border-radius:10px; font-size:11px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-section">
        <div class="logo">🏨 HotelManager</div>
        <div class="role">Panel de Recepción</div>
    </div>

    <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="menu-item active">
        <span class="menu-icon">📊</span><span>Dashboard</span>
    </a>
    <a href="<?= htmlspecialchars($reservasUrl) ?>" class="menu-item">
        <span class="menu-icon">📅</span><span>Reservas</span>
    </a>
    <a href="<?= htmlspecialchars($habitacionesUrl) ?>" class="menu-item">
        <span class="menu-icon">🛏️</span><span>Habitaciones</span>
    </a>
    <a href="<?= htmlspecialchars($clientesUrl) ?>" class="menu-item">
        <span class="menu-icon">👥</span><span>Clientes</span>
    </a>
    <a href="<?= htmlspecialchars($logoutUrl) ?>" class="menu-item">
        <span class="menu-icon">🚪</span><span>Cerrar Sesión</span>
    </a>
</div>

<div class="main-content">
    <div class="header">
        <div class="header-title">
            <h1>📋 Panel de Recepción</h1>
            <div class="header-subtitle">📅 <?= date('l, d \d\e F \d\e Y') ?></div>
        </div>
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($username,0,2)) ?></div>
                <div>
                    <div style="font-weight:600;color:#333;font-size:14px"><?= htmlspecialchars($username) ?></div>
                    <div style="font-size:11px;color:#888">Recepcionista</div>
                </div>
            </div>
            <a href="<?= htmlspecialchars($logoutUrl) ?>" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <div class="content-area">

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">🛏️</div>
                <div class="val"><?= $total_hab ?></div>
                <div class="lbl">Total Habitaciones</div>
                <?php $occ = $total_hab > 0 ? round(($hab_ocupadas/$total_hab)*100) : 0; ?>
                <div class="occ-bar"><div class="occ-fill" style="width:<?= $occ ?>%"></div></div>
                <div style="font-size:10px;color:#aaa;margin-top:3px"><?= $occ ?>% ocupado</div>
            </div>
            <div class="stat-card" style="border-top:3px solid #2a9d8f">
                <div class="icon">✅</div>
                <div class="val" style="color:#2a9d8f"><?= $hab_disponibles ?></div>
                <div class="lbl">Disponibles</div>
            </div>
            <div class="stat-card" style="border-top:3px solid #e76f51">
                <div class="icon">🔴</div>
                <div class="val" style="color:#e76f51"><?= $hab_ocupadas ?></div>
                <div class="lbl">Ocupadas</div>
            </div>
            <div class="stat-card" style="border-top:3px solid #1b98e0">
                <div class="icon">📅</div>
                <div class="val" style="color:#1b98e0"><?= $reservas_activas ?></div>
                <div class="lbl">Reservas Activas</div>
            </div>
            <div class="stat-card highlight">
                <div class="icon">⬇</div>
                <div class="val"><?= $checkins_hoy ?></div>
                <div class="lbl">Check-ins Hoy</div>
            </div>
            <div class="stat-card highlight" style="background:linear-gradient(135deg,#f4a261,#e9c46a)">
                <div class="icon">⬆</div>
                <div class="val"><?= $checkouts_hoy ?></div>
                <div class="lbl">Check-outs Hoy</div>
            </div>
        </div>

        <div class="two-col">

            <!-- Reservas activas -->
            <div>
                <div class="section-header">
                    <div class="section-title">📅 Reservas Activas</div>
                    <a href="<?= htmlspecialchars($reservasUrl) ?>" class="btn-ver-todo">Ver todas →</a>
                </div>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Hab.</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($reservas_pendientes)): ?>
                            <tr class="empty-row"><td colspan="7">🎉 No hay reservas pendientes</td></tr>
                        <?php else: ?>
                            <?php foreach ($reservas_pendientes as $r):
                                $bc = $badge[$r['estado']] ?? $badge['pendiente']; ?>
                            <tr>
                                <td><code style="font-size:10px;background:#f0f0f0;padding:1px 5px;border-radius:3px"><?= htmlspecialchars($r['codigo_confirmacion']) ?></code></td>
                                <td style="font-weight:600"><?= htmlspecialchars($r['nombre_cliente']) ?></td>
                                <td><strong>#<?= htmlspecialchars($r['numero_habitacion']) ?></strong></td>
                                <td><?= date('d/m', strtotime($r['fecha_entrada'])) ?></td>
                                <td><?= date('d/m', strtotime($r['fecha_salida'])) ?></td>
                                <td><span class="badge" style="background:<?= $bc['bg'] ?>;color:<?= $bc['color'] ?>"><?= $bc['icon'] ?> <?= ucfirst($r['estado']) ?></span></td>
                                <td style="white-space:nowrap">
                                    <a href="../../controllers/ReservaController.php?accion=detalle&id=<?= $r['reserva_id'] ?>" class="btn-sm btn-det">Ver</a>
                                    <?php if (empty($r['fecha_checkin'])): ?>
                                    <a href="../../controllers/ReservaController.php?accion=checkin&id=<?= $r['reserva_id'] ?>"
                                       class="btn-sm btn-ci"
                                       onclick="return confirm('¿Check-in?')">CI</a>
                                    <?php else: ?>
                                    <a href="../../controllers/ReservaController.php?accion=checkout&id=<?= $r['reserva_id'] ?>"
                                       class="btn-sm btn-co"
                                       onclick="return confirm('¿Check-out?')">CO</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Check-ins de hoy -->
            <div>
                <div class="section-header">
                    <div class="section-title">⬇ Check-ins de Hoy</div>
                    <span style="font-size:12px;color:#888"><?= count($checkins_lista) ?> esperados</span>
                </div>
                <div class="card">
                    <?php if (empty($checkins_lista)): ?>
                        <div style="text-align:center;padding:30px;color:#bbb;font-size:13px">
                            😊 Sin check-ins programados para hoy
                        </div>
                    <?php else: ?>
                        <?php foreach ($checkins_lista as $ci): ?>
                        <div class="checkin-item">
                            <div class="checkin-avatar"><?= strtoupper(substr($ci['nombre_cliente'],0,1)) ?></div>
                            <div class="checkin-info">
                                <div class="nombre"><?= htmlspecialchars($ci['nombre_cliente']) ?></div>
                                <div class="sub">Hab. #<?= htmlspecialchars($ci['numero_habitacion']) ?> · <?= htmlspecialchars($ci['telefono'] ?? '') ?></div>
                            </div>
                            <div class="checkin-actions">
                                <?php if (!empty($ci['fecha_checkin'])): ?>
                                    <span class="ci-hecho">✅ Hecho</span>
                                <?php else: ?>
                                    <a href="../../controllers/ReservaController.php?accion=checkin&id=<?= $ci['reserva_id'] ?>"
                                       class="btn-sm btn-ci"
                                       onclick="return confirm('¿Check-in para <?= htmlspecialchars($ci['nombre_cliente']) ?>?')">
                                       ⬇ CI
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Acceso rápido -->
                <div class="section-title" style="margin-bottom:12px">⚡ Acciones de Recepción</div>
                <div class="card" style="padding:12px">
                    <a href="../../controllers/ClienteController.php?accion=crear_usuario_nuevo"
                       style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;text-decoration:none;color:#333;margin-bottom:4px"
                       onmouseover="this.style.background='#eef7fb'" onmouseout="this.style.background=''">
                        <span style="font-size:20px">🔐</span>
                        <div><div style="font-weight:600;font-size:13px">Crear Usuario Cliente</div>
                        <div style="font-size:11px;color:#888">Alta directa de cliente con credenciales</div></div>
                    </a>
                    <a href="../../controllers/ReservaController.php"
                       style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;text-decoration:none;color:#333"
                       onmouseover="this.style.background='#eef7fb'" onmouseout="this.style.background=''">
                        <span style="font-size:20px">✅</span>
                        <div><div style="font-weight:600;font-size:13px">Confirmar CI/CO</div>
                        <div style="font-size:11px;color:#888">Gestión operativa de llegadas/salidas</div></div>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
