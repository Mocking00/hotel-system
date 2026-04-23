<?php if (!isset($_SESSION['usuario_id'])) { header("Location: ../auth/login.php"); exit(); }
require_once __DIR__ . '/../../utils/url_helper.php';
$username = $_SESSION['username'];
$rol = $_SESSION['rol'];
$es_admin = $rol === 'administrador';
$es_recepcion = $rol === 'recepcionista';
$appBase = app_base_path();
$dashboard_url = $es_admin ? $appBase . '/views/admin/dashboard.php' : $appBase . '/views/recepcionista/dashboard.php';
$badge = [
    'pendiente'  => ['bg'=>'#fff3cd','color'=>'#856404', 'icon'=>'⏳'],
    'confirmada' => ['bg'=>'#d4edda','color'=>'#155724', 'icon'=>'✅'],
    'cancelada'  => ['bg'=>'#f8d7da','color'=>'#721c24', 'icon'=>'❌'],
    'completada' => ['bg'=>'#cce5ff','color'=>'#004085', 'icon'=>'🏁'],
    'no_show'    => ['bg'=>'#e2e3e5','color'=>'#383d41', 'icon'=>'👻'],
];
$total_gastado = array_sum(array_column(array_filter($reservas, fn($r) => $r['estado'] === 'completada'), 'precio_total'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($datos['nombre'].' '.$datos['apellido']) ?> - HotelManager</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',sans-serif;background:#f3f8fb}
        .sidebar{width:260px;background:#12355b;color:white;min-height:100vh;padding:20px 0;position:fixed;left:0;top:0}
        .logo-section{padding:0 20px 20px;border-bottom:1px solid rgba(255,255,255,.1);margin-bottom:20px}
        .logo{font-size:24px;font-weight:bold;margin-bottom:5px}
        .menu-item{padding:15px 20px;display:flex;align-items:center;gap:12px;border-left:4px solid transparent;text-decoration:none;color:white;transition:all .3s;font-size:14px}
        .menu-item:hover,.menu-item.active{background:rgba(255,255,255,.1);border-left-color:#1b98e0}
        .menu-icon{font-size:18px;width:22px}
        .main-content{margin-left:260px}
        .header{background:white;padding:20px 30px;box-shadow:0 2px 5px rgba(0,0,0,.05);display:flex;justify-content:space-between;align-items:center}
        .header h1{color:#333;font-size:22px;margin-bottom:4px}
        .header-subtitle{color:#666;font-size:14px}
        .user-section{display:flex;align-items:center;gap:12px}
        .user-avatar{width:40px;height:40px;background:#1b98e0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold}
        .btn-logout{padding:8px 20px;background:#e76f51;color:white;border:none;border-radius:8px;font-size:14px;text-decoration:none}
        .content-area{padding:30px}
        .breadcrumb{margin-bottom:20px;font-size:13px;color:#888}
        .breadcrumb a{color:#1b98e0;text-decoration:none}
        .alert{padding:15px 20px;border-radius:8px;margin-bottom:20px;font-size:14px}
        .alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
        .alert-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
        .layout{display:grid;grid-template-columns:1fr 300px;gap:20px}
        .card{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.05);margin-bottom:20px}
        .card-header{padding:18px 22px;border-bottom:1px solid #f0f0f0;font-size:16px;font-weight:700;color:#333}
        .card-body{padding:20px 22px}
        .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .info-item .lbl{font-size:11px;color:#888;font-weight:600;text-transform:uppercase;margin-bottom:4px}
        .info-item .val{font-size:14px;color:#333}
        .info-item.full{grid-column:1/-1}
        .cliente-avatar{width:64px;height:64px;background:linear-gradient(135deg,#1b98e0,#0b2545);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:24px;font-weight:bold;margin:0 auto 12px}
        .stat-mini{text-align:center;padding:12px;background:#f8f9fa;border-radius:8px}
        .stat-mini .val{font-size:20px;font-weight:bold;color:#333}
        .stat-mini .lbl{font-size:11px;color:#888;margin-top:3px}
        .stats-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:15px}
        table{width:100%;border-collapse:collapse}
        thead{background:#12355b;color:white}
        thead th{padding:11px 12px;text-align:left;font-size:12px}
        tbody tr{border-bottom:1px solid #f0f0f0}
        tbody tr:hover{background:#fafafa}
        tbody td{padding:11px 12px;font-size:13px;color:#444}
        .badge{padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600}
        .btn-sm{padding:4px 10px;border-radius:6px;font-size:12px;text-decoration:none;border:1px solid;display:inline-block}
        .btn-det{background:#e3f4ff;color:#0b6fa4;border-color:#a9d9f5}
        .empty-row td{text-align:center;padding:30px;color:#bbb}
        .action-btns{display:flex;flex-direction:column;gap:10px}
        .btn-action{padding:10px 16px;border-radius:8px;font-size:13px;font-weight:600;text-align:center;text-decoration:none;display:block;border:1px solid}
        .btn-edit{background:#fff3cd;color:#856404;border-color:#ffc107}
        .btn-edit:hover{background:#ffc107;color:#333}
        .btn-reserva{background:#d4edda;color:#155724;border-color:#c3e6cb}
        .btn-reserva:hover{background:#c3e6cb}
        .btn-del{background:#f8d7da;color:#721c24;border-color:#f5c6cb}
        .btn-del:hover{background:#f5c6cb}
        .btn-back{background:#f8f9fa;color:#555;border-color:#ddd}
        .btn-back:hover{background:#e9ecef}
    </style>
</head>
<body>
<div class="sidebar">
    <div class="logo-section">
        <div class="logo">🏨 HotelManager</div>
        <div style="font-size:13px;opacity:.8">Panel de <?= ucfirst(htmlspecialchars($rol)) ?></div>
    </div>
    <a href="<?= $dashboard_url ?>" class="menu-item"><span class="menu-icon">📊</span>Dashboard</a>
    <a href="./HabitacionController.php" class="menu-item"><span class="menu-icon">🛏️</span>Habitaciones</a>
    <a href="./ReservaController.php" class="menu-item"><span class="menu-icon">📅</span>Reservas</a>
    <a href="./ClienteController.php" class="menu-item active"><span class="menu-icon">👥</span>Clientes</a>
    <?php if ($es_admin): ?>
    <a href="./ReservaController.php?accion=reportes" class="menu-item"><span class="menu-icon">📈</span>Reportes</a>
    <?php endif; ?>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h1>👤 <?= htmlspecialchars($datos['nombre'].' '.$datos['apellido']) ?></h1>
            <div class="header-subtitle">Cliente #<?= $datos['cliente_id'] ?> · Registrado el <?= date('d/m/Y', strtotime($datos['fecha_registro'])) ?></div>
        </div>
        <div class="user-section">
            <div class="user-avatar"><?= strtoupper(substr($username,0,2)) ?></div>
            <div>
                <div style="font-weight:600;color:#333"><?= htmlspecialchars($username) ?></div>
                <div style="font-size:12px;color:#666"><?= ucfirst(htmlspecialchars($rol)) ?></div>
            </div>
            <a href="./UsuarioController.php?action=logout" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <div class="content-area">
        <div class="breadcrumb">
            <a href="./ClienteController.php">👥 Clientes</a> ›
            <?= htmlspecialchars($datos['nombre'].' '.$datos['apellido']) ?>
        </div>

        <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $tipo_msg ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="layout">
            <!-- Izquierda: info + reservas -->
            <div>
                <!-- Datos personales -->
                <div class="card">
                    <div class="card-header">📋 Datos Personales</div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="lbl">Nombre completo</div>
                                <div class="val" style="font-size:16px;font-weight:600"><?= htmlspecialchars($datos['nombre'].' '.$datos['apellido']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="lbl">Cédula</div>
                                <div class="val"><?= htmlspecialchars($datos['cedula']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="lbl">Teléfono</div>
                                <div class="val"><?= htmlspecialchars($datos['telefono']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="lbl">Email</div>
                                <div class="val"><?= htmlspecialchars($datos['email']) ?></div>
                            </div>
                            <?php if (!empty($datos['fecha_nacimiento'])): ?>
                            <div class="info-item">
                                <div class="lbl">Fecha de Nacimiento</div>
                                <div class="val"><?= date('d/m/Y', strtotime($datos['fecha_nacimiento'])) ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($datos['username'])): ?>
                            <div class="info-item">
                                <div class="lbl">Usuario del sistema</div>
                                <div class="val" style="color:#1b98e0">@<?= htmlspecialchars($datos['username']) ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($datos['direccion'])): ?>
                            <div class="info-item full">
                                <div class="lbl">Dirección</div>
                                <div class="val"><?= htmlspecialchars($datos['direccion']) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Reservas -->
                <div class="card">
                    <div class="card-header">📅 Historial de Reservas</div>
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
                            <tr class="empty-row"><td colspan="7">📅 Este cliente no tiene reservas aún.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reservas as $r):
                                $bc = $badge[$r['estado']] ?? $badge['pendiente']; ?>
                            <tr>
                                <td><code style="font-size:10px;background:#f0f0f0;padding:1px 5px;border-radius:3px"><?= htmlspecialchars($r['codigo_confirmacion']) ?></code></td>
                                <td><strong>#<?= htmlspecialchars($r['numero_habitacion']) ?></strong><br><span style="font-size:11px;color:#888"><?= ucfirst($r['tipo_habitacion']) ?></span></td>
                                <td><?= date('d/m/Y', strtotime($r['fecha_entrada'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($r['fecha_salida'])) ?></td>
                                <td style="color:#2a9d8f;font-weight:600">$<?= number_format($r['precio_total'],2) ?></td>
                                <td><span class="badge" style="background:<?= $bc['bg'] ?>;color:<?= $bc['color'] ?>"><?= $bc['icon'] ?> <?= ucfirst($r['estado']) ?></span></td>
                                <td><a href="./ReservaController.php?accion=detalle&id=<?= $r['reserva_id'] ?>" class="btn-sm btn-det">Ver</a></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Derecha: stats + acciones -->
            <div>
                <!-- Avatar + stats -->
                <div class="card">
                    <div class="card-body" style="text-align:center">
                        <div class="cliente-avatar"><?= strtoupper(substr($datos['nombre'],0,1)) ?></div>
                        <div style="font-weight:700;font-size:16px;color:#333"><?= htmlspecialchars($datos['nombre'].' '.$datos['apellido']) ?></div>
                        <div style="font-size:13px;color:#888;margin-top:4px"><?= htmlspecialchars($datos['email']) ?></div>
                    </div>
                    <div class="card-body" style="padding-top:0">
                        <div class="stats-row">
                            <div class="stat-mini">
                                <div class="val"><?= count($reservas) ?></div>
                                <div class="lbl">Reservas</div>
                            </div>
                            <div class="stat-mini">
                                <div class="val"><?= count(array_filter($reservas, fn($r) => $r['estado']==='completada')) ?></div>
                                <div class="lbl">Completadas</div>
                            </div>
                            <div class="stat-mini">
                                <div class="val" style="font-size:14px;color:#2a9d8f">$<?= number_format($total_gastado,0) ?></div>
                                <div class="lbl">Total gastado</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="card">
                    <div class="card-header">⚡ Acciones</div>
                    <div class="card-body">
                        <div class="action-btns">
                            <?php if ($es_admin): ?>
                            <a href="./ClienteController.php?accion=editar&id=<?= $datos['cliente_id'] ?>" class="btn-action btn-edit">✏️ Editar Cliente</a>
                            <a href="./ClienteController.php?accion=eliminar&id=<?= $datos['cliente_id'] ?>"
                               class="btn-action btn-del"
                               onclick="return confirm('¿Eliminar cliente? No se puede deshacer.')">🗑️ Eliminar</a>
                            <?php endif; ?>

                            <?php if ($es_recepcion && empty($datos['username'])): ?>
                            <a href="./ClienteController.php?accion=crear_usuario&id=<?= $datos['cliente_id'] ?>" class="btn-action btn-reserva">🔐 Crear Usuario Cliente</a>
                            <?php endif; ?>

                            <?php if ($es_recepcion): ?>
                            <a href="./ClienteController.php?accion=crear_usuario_nuevo" class="btn-action btn-reserva">➕ Nuevo Usuario Cliente</a>
                            <?php endif; ?>

                            <a href="./ClienteController.php" class="btn-action btn-back">← Volver al listado</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>


