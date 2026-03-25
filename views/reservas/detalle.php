<?php
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /hotel-system/views/auth/login.php");
    exit();
}

$username = $_SESSION['username'];
$rol = $_SESSION['rol'];
$es_cliente = $rol === 'cliente';
$es_recepcion = $rol === 'recepcionista';

$badge = [
    'pendiente'  => ['bg'=>'#fff3cd','color'=>'#856404', 'icon'=>'⏳'],
    'confirmada' => ['bg'=>'#d4edda','color'=>'#155724', 'icon'=>'✅'],
    'cancelada'  => ['bg'=>'#f8d7da','color'=>'#721c24', 'icon'=>'❌'],
    'completada' => ['bg'=>'#cce5ff','color'=>'#004085', 'icon'=>'🏁'],
    'no_show'    => ['bg'=>'#e2e3e5','color'=>'#383d41', 'icon'=>'👻'],
];
$bc = $badge[$reserva_detalle['estado']] ?? $badge['pendiente'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Reserva - HotelManager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }

        .sidebar {
            width: 260px; background: #2c3e50; color: white;
            min-height: 100vh; padding: 20px 0;
            position: fixed; left: 0; top: 0;
        }
        .logo-section { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .role { font-size: 13px; opacity: 0.8; }
        .menu-item {
            padding: 15px 20px; display: flex; align-items: center; gap: 12px;
            border-left: 4px solid transparent; text-decoration: none; color: white;
            transition: all .2s;
        }
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            border-left-color: #3498db;
        }

        .topbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 14px 24px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 3px 14px rgba(102,126,234,0.35);
        }
        .topbar .left { font-size: 20px; font-weight: 700; }
        .topbar .right { display: flex; align-items: center; gap: 12px; }
        .topbar .btn {
            text-decoration: none; color: white; border: 1px solid rgba(255,255,255,0.5);
            padding: 7px 14px; border-radius: 8px; font-size: 13px;
        }

        .main-content { margin-left: 260px; }
        .main-content.full { margin-left: 0; }

        .header {
            background: white; padding: 20px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        .header h1 { color: #333; font-size: 24px; margin-bottom: 4px; }
        .header-subtitle { color: #666; font-size: 14px; }

        .content-area { padding: 30px; }

        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; }
        .card {
            background: white; border-radius: 12px; padding: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .card h2 {
            font-size: 18px; color: #333; margin-bottom: 18px;
            border-bottom: 1px solid #f0f0f0; padding-bottom: 10px;
        }

        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }

        .meta-row {
            display: grid; grid-template-columns: 170px 1fr;
            gap: 12px; margin-bottom: 12px; font-size: 14px;
        }
        .meta-row .label { color: #777; font-weight: 600; }
        .meta-row .value { color: #333; }

        .code-box {
            background: #f2f4f8; border: 1px solid #dbe3ee;
            padding: 8px 12px; border-radius: 8px;
            display: inline-block; font-family: Consolas, monospace;
            font-size: 13px;
        }

        .actions {
            display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;
        }
        .btn {
            text-decoration: none; border-radius: 8px; border: 1px solid;
            padding: 8px 14px; font-size: 13px; font-weight: 600;
            display: inline-block;
        }
        .btn-back { background: #f8f9fa; color: #555; border-color: #ddd; }
        .btn-ci { background: #d4edda; color: #155724; border-color: #c3e6cb; }
        .btn-co { background: #fff3cd; color: #856404; border-color: #ffe08a; }
        .btn-can { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        .timeline {
            border-left: 3px solid #dce7f2; padding-left: 14px;
            margin-top: 10px;
        }
        .timeline-item { margin-bottom: 14px; }
        .timeline-item .t-title { font-weight: 700; color: #2c3e50; font-size: 13px; }
        .timeline-item .t-val { color: #666; font-size: 13px; margin-top: 2px; }

        @media (max-width: 980px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php if (!$es_cliente): ?>
<div class="sidebar">
    <div class="logo-section">
        <div class="logo">🏨 HotelManager</div>
        <div class="role">Panel de <?= ucfirst(htmlspecialchars($rol)) ?></div>
    </div>
    <a href="/hotel-system/views/admin/dashboard.php" class="menu-item">📊 Dashboard</a>
    <a href="/hotel-system/controllers/HabitacionController.php" class="menu-item">🛏️ Habitaciones</a>
    <a href="/hotel-system/controllers/ReservaController.php" class="menu-item active">📅 Reservas</a>
    <a href="/hotel-system/controllers/ClienteController.php" class="menu-item">👥 Clientes</a>
    <a href="/hotel-system/controllers/ReservaController.php?accion=reportes" class="menu-item">📈 Reportes</a>
    <a href="/hotel-system/controllers/UsuarioController.php?action=logout" class="menu-item">🚪 Cerrar Sesion</a>
</div>
<?php else: ?>
<div class="topbar">
    <div class="left">🏨 Detalle de Reserva</div>
    <div class="right">
        <div><?= htmlspecialchars($username) ?></div>
        <a href="/hotel-system/views/cliente/dashboard.php" class="btn">Panel</a>
        <a href="/hotel-system/controllers/UsuarioController.php?action=logout" class="btn">Salir</a>
    </div>
</div>
<?php endif; ?>

<div class="main-content <?= $es_cliente ? 'full' : '' ?>">
    <div class="header">
        <div>
            <h1>📄 Reserva #<?= (int) $reserva_detalle['reserva_id'] ?></h1>
            <div class="header-subtitle">Codigo: <span class="code-box"><?= htmlspecialchars($reserva_detalle['codigo_confirmacion']) ?></span></div>
        </div>
        <div>
            <span class="badge" style="background:<?= $bc['bg'] ?>; color:<?= $bc['color'] ?>">
                <?= $bc['icon'] ?> <?= ucfirst($reserva_detalle['estado']) ?>
            </span>
        </div>
    </div>

    <div class="content-area">
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_msg === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="grid">
            <div class="card">
                <h2>Datos de la reserva</h2>

                <div class="meta-row"><div class="label">Cliente</div><div class="value"><strong><?= htmlspecialchars($reserva_detalle['nombre'] . ' ' . $reserva_detalle['apellido']) ?></strong></div></div>
                <div class="meta-row"><div class="label">Documento</div><div class="value"><?= htmlspecialchars($reserva_detalle['cedula']) ?></div></div>
                <div class="meta-row"><div class="label">Telefono</div><div class="value"><?= htmlspecialchars($reserva_detalle['telefono']) ?></div></div>
                <div class="meta-row"><div class="label">Email</div><div class="value"><?= htmlspecialchars($reserva_detalle['email']) ?></div></div>

                <hr style="border:none;border-top:1px solid #f0f0f0;margin:14px 0;">

                <div class="meta-row"><div class="label">Habitacion</div><div class="value"><strong>#<?= htmlspecialchars($reserva_detalle['numero_habitacion']) ?></strong> (<?= ucfirst(htmlspecialchars($reserva_detalle['tipo_habitacion'])) ?>)</div></div>
                <div class="meta-row"><div class="label">Capacidad</div><div class="value"><?= (int) $reserva_detalle['capacidad'] ?> persona(s)</div></div>
                <div class="meta-row"><div class="label">Precio por noche</div><div class="value">$<?= number_format((float) $reserva_detalle['precio_noche'], 2) ?></div></div>

                <hr style="border:none;border-top:1px solid #f0f0f0;margin:14px 0;">

                <div class="meta-row"><div class="label">Entrada</div><div class="value"><?= date('d/m/Y', strtotime($reserva_detalle['fecha_entrada'])) ?></div></div>
                <div class="meta-row"><div class="label">Salida</div><div class="value"><?= date('d/m/Y', strtotime($reserva_detalle['fecha_salida'])) ?></div></div>
                <div class="meta-row"><div class="label">Noches</div><div class="value"><?= (int) $reserva_detalle['noches'] ?></div></div>
                <div class="meta-row"><div class="label">Personas</div><div class="value"><?= (int) $reserva_detalle['numero_personas'] ?></div></div>
                <div class="meta-row"><div class="label">Total</div><div class="value" style="color:#27ae60;font-weight:700;font-size:18px">$<?= number_format((float) $reserva_detalle['precio_total'], 2) ?></div></div>
                <div class="meta-row"><div class="label">Notas</div><div class="value"><?= !empty($reserva_detalle['notas_especiales']) ? nl2br(htmlspecialchars($reserva_detalle['notas_especiales'])) : '<span style="color:#999">Sin notas especiales</span>' ?></div></div>
            </div>

            <div>
                <div class="card" style="margin-bottom:20px;">
                    <h2>Acciones</h2>
                    <div class="actions">
                        <a href="/hotel-system/controllers/ReservaController.php" class="btn btn-back">← Volver al listado</a>

                        <?php if (!$es_cliente && in_array($reserva_detalle['estado'], ['pendiente','confirmada'])): ?>
                            <?php if (empty($reserva_detalle['fecha_checkin'])): ?>
                                <a href="/hotel-system/controllers/ReservaController.php?accion=checkin&id=<?= (int) $reserva_detalle['reserva_id'] ?>"
                                   class="btn btn-ci" onclick="return confirm('¿Registrar check-in de esta reserva?')">Registrar Check-in</a>
                            <?php elseif (empty($reserva_detalle['fecha_checkout'])): ?>
                                <a href="/hotel-system/controllers/ReservaController.php?accion=checkout&id=<?= (int) $reserva_detalle['reserva_id'] ?>"
                                   class="btn btn-co" onclick="return confirm('¿Registrar check-out de esta reserva?')">Registrar Check-out</a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!$es_recepcion && in_array($reserva_detalle['estado'], ['pendiente','confirmada']) && empty($reserva_detalle['fecha_checkin'])): ?>
                            <a href="/hotel-system/controllers/ReservaController.php?accion=cancelar&id=<?= (int) $reserva_detalle['reserva_id'] ?>"
                               class="btn btn-can" onclick="return confirm('¿Cancelar esta reserva?')">Cancelar Reserva</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h2>Linea de tiempo</h2>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="t-title">Reserva creada</div>
                            <div class="t-val"><?= !empty($reserva_detalle['fecha_reserva']) ? date('d/m/Y H:i', strtotime($reserva_detalle['fecha_reserva'])) : 'No disponible' ?></div>
                        </div>
                        <div class="timeline-item">
                            <div class="t-title">Check-in</div>
                            <div class="t-val"><?= !empty($reserva_detalle['fecha_checkin']) ? date('d/m/Y H:i', strtotime($reserva_detalle['fecha_checkin'])) : 'Pendiente' ?></div>
                        </div>
                        <div class="timeline-item">
                            <div class="t-title">Check-out</div>
                            <div class="t-val"><?= !empty($reserva_detalle['fecha_checkout']) ? date('d/m/Y H:i', strtotime($reserva_detalle['fecha_checkout'])) : 'Pendiente' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
