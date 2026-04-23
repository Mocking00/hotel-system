<?php
// Acceso directo a la vista sin pasar por el controlador → redirigir
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$requireUrlHelper = __DIR__ . '/../../utils/url_helper.php';
require_once $requireUrlHelper;
$username = $_SESSION['username'];
$rol = $_SESSION['rol'];
$solo_lectura = $rol === 'recepcionista';
$es_admin = $rol === 'administrador';
$appBase = app_base_path();

$dashboard_url = $appBase . ($es_admin ? '/views/admin/dashboard.php' : '/views/recepcionista/dashboard.php');
$habitacionesUrl = ($appBase !== '' ? $appBase : '') . '/controllers/HabitacionController.php';
$reservasUrl = ($appBase !== '' ? $appBase : '') . '/controllers/ReservaController.php';
$clientesUrl = ($appBase !== '' ? $appBase : '') . '/controllers/ClienteController.php';
$reportesUrl = ($appBase !== '' ? $appBase : '') . '/controllers/ReservaController.php?accion=reportes';
$logoutUrl = ($appBase !== '' ? $appBase : '') . '/controllers/UsuarioController.php?action=logout';
$panel_label = 'Panel de Administracion';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habitaciones - HotelManager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f3f8fb; }

        .sidebar {
            width: 260px; background: #12355b; color: white;
            min-height: 100vh; padding: 20px 0; position: fixed; left: 0; top: 0;
        }
        .logo-section { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .logo  { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .role  { font-size: 13px; opacity: 0.8; }
        .menu-item {
            padding: 15px 20px; cursor: pointer; transition: all 0.3s;
            display: flex; align-items: center; gap: 12px;
            border-left: 4px solid transparent; text-decoration: none; color: white;
            font-size: 14px; min-height: 52px;
        }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left-color: #1b98e0; }
        .menu-icon { font-size: 20px; width: 24px; }

        .main-content { margin-left: 260px; }
        .header {
            background: white; padding: 20px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        .header-title h1 { color: #333; font-size: 24px; margin-bottom: 5px; }
        .header-subtitle  { color: #666; font-size: 14px; }
        .user-section { display: flex; align-items: center; gap: 15px; }
        .user-info    { display: flex; align-items: center; gap: 10px; }
        .user-avatar {
            width: 40px; height: 40px; background: #1b98e0; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold;
        }
        .btn-logout {
            padding: 8px 20px; background: #e76f51; color: white;
            border: none; border-radius: 8px; font-size: 14px; text-decoration: none;
        }
        .btn-logout:hover { background: #c0392b; }

        .content-area { padding: 30px; }

        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .toolbar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; flex-wrap: wrap; gap: 15px;
        }
        .toolbar h2 { color: #333; font-size: 20px; }

        .btn-primary {
            padding: 10px 22px; background: #1b98e0; color: white;
            border: none; border-radius: 8px; font-size: 14px;
            text-decoration: none; transition: all 0.3s;
        }
        .btn-primary:hover { background: #1475ae; }

        .filters {
            background: white; padding: 15px 20px; border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex; gap: 15px; align-items: flex-end;
            margin-bottom: 20px; flex-wrap: wrap;
        }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 12px; font-weight: 600; color: #555; }
        .filter-group select {
            padding: 8px 12px; border: 1px solid #ddd;
            border-radius: 6px; font-size: 13px; color: #333;
        }
        .btn-filter {
            padding: 8px 18px; background: #1b98e0; color: white;
            border: none; border-radius: 6px; cursor: pointer; font-size: 13px;
            text-decoration: none;
        }
        .btn-filter:hover { background: #5a6fd6; }
        .btn-clear {
            padding: 8px 18px; background: #f8f9fa; color: #555;
            border: 1px solid #ddd; border-radius: 6px; font-size: 13px; text-decoration: none;
        }

        .table-card {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #12355b; color: white; }
        thead th { padding: 14px 16px; text-align: left; font-size: 13px; font-weight: 600; }
        tbody tr { border-bottom: 1px solid #f0f0f0; transition: background 0.2s; }
        tbody tr:hover { background: #f8f9fa; }
        tbody td { padding: 14px 16px; font-size: 14px; color: #444; }
        .table-footer { padding: 12px 16px; color: #888; font-size: 13px; background: #fafafa; }

        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-disponible    { background: #d4edda; color: #155724; }
        .badge-ocupada       { background: #f8d7da; color: #721c24; }
        .badge-mantenimiento { background: #fff3cd; color: #856404; }

        .btn-edit {
            padding: 5px 12px; background: #fff3cd; color: #856404;
            border: 1px solid #ffc107; border-radius: 6px; font-size: 12px; text-decoration: none;
        }
        .btn-edit:hover { background: #ffc107; color: #333; }
        .btn-delete {
            padding: 5px 12px; background: #f8d7da; color: #721c24;
            border: 1px solid #f5c6cb; border-radius: 6px; font-size: 12px; text-decoration: none;
        }
        .btn-delete:hover { background: #f5c6cb; }

        .dropdown { position: relative; display: inline-block; }
        .btn-status {
            padding: 5px 12px; background: #d1ecf1; color: #0c5460;
            border: 1px solid #bee5eb; border-radius: 6px; font-size: 12px; cursor: pointer;
        }
        .dropdown-menu {
            display: none; position: absolute; right: 0; top: 110%;
            background: white; border: 1px solid #ddd; border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.12); min-width: 150px; z-index: 100;
        }
        .dropdown:hover .dropdown-menu { display: block; }
        .dropdown-menu a {
            display: block; padding: 8px 14px; font-size: 13px;
            color: #333; text-decoration: none;
        }
        .dropdown-menu a:hover { background: #eef7fb; }
        .dropdown-header { padding: 6px 14px; font-size: 11px; color: #888; font-weight: 600; border-bottom: 1px solid #eee; }

        .empty-state { text-align: center; padding: 60px; color: #aaa; }
        .empty-state span { font-size: 48px; display: block; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-section">
        <div class="logo">🏨 HotelManager</div>
        <div class="role"><?= htmlspecialchars($panel_label) ?></div>
    </div>
    <a href="<?= htmlspecialchars($dashboard_url) ?>" class="menu-item">
        <span class="menu-icon">📊</span><span>Dashboard</span>
    </a>
    <a href="<?= htmlspecialchars($reservasUrl) ?>" class="menu-item"><span class="menu-icon">📅</span><span>Reservas</span></a>
    <a href="<?= htmlspecialchars($habitacionesUrl) ?>" class="menu-item active">
        <span class="menu-icon">🛏️</span><span>Habitaciones</span>
    </a>
    <a href="<?= htmlspecialchars($clientesUrl) ?>" class="menu-item"><span class="menu-icon">👥</span><span>Clientes</span></a>
    <?php if ($es_admin): ?>
    <a href="<?= htmlspecialchars($reportesUrl) ?>" class="menu-item"><span class="menu-icon">📈</span><span>Reportes</span></a>
    <?php endif; ?>
</div>

<div class="main-content">
    <div class="header">
        <div class="header-title">
            <h1>🛏️ Gestión de Habitaciones</h1>
            <div class="header-subtitle">Administra el inventario de habitaciones del hotel</div>
        </div>
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($username, 0, 2)) ?></div>
                <div>
                    <div style="font-weight:600;color:#333;"><?= htmlspecialchars($username) ?></div>
                    <div style="font-size:12px;color:#666;"><?= ucfirst($_SESSION['rol']) ?></div>
                </div>
            </div>
            <a href="<?= htmlspecialchars($logoutUrl) ?>" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <div class="content-area">

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_msg ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <div class="toolbar">
            <h2>Habitaciones registradas</h2>
            <?php if (!$solo_lectura): ?>
            <a href="<?= htmlspecialchars($habitacionesUrl) ?>?accion=crear" class="btn-primary">
                ➕ Nueva Habitación
            </a>
            <?php endif; ?>
        </div>

        <form method="GET" action="<?= htmlspecialchars($habitacionesUrl) ?>">
            <input type="hidden" name="accion" value="listar">
            <div class="filters">
                <div class="filter-group">
                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="">Todos los tipos</option>
                        <?php foreach (['simple','doble','suite','presidencial'] as $t): ?>
                            <option value="<?= $t ?>" <?= ($tipo ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Estado</label>
                    <select name="estado">
                        <option value="">Todos los estados</option>
                        <?php foreach (['disponible','ocupada','mantenimiento'] as $e): ?>
                            <option value="<?= $e ?>" <?= ($estado ?? '') === $e ? 'selected' : '' ?>><?= ucfirst($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-filter">🔍 Filtrar</button>
                <a href="<?= htmlspecialchars($habitacionesUrl) ?>" class="btn-clear">✕ Limpiar</a>
            </div>
        </form>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Tipo</th>
                        <th>Piso</th>
                        <th>Capacidad</th>
                        <th>Precio / Noche</th>
                        <th>Estado</th>
                        <th style="text-align:center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($habitaciones)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <span>🛏️</span>
                                No hay habitaciones registradas aún.<br>
                                <small>Haz clic en "Nueva Habitación" para agregar una.</small>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $iconos = ['simple'=>'🛏️','doble'=>'👫','suite'=>'⭐','presidencial'=>'💎'];
                    foreach ($habitaciones as $h):
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($h['numero']) ?></strong></td>
                        <td><?= $iconos[$h['tipo']] ?? '🛏️' ?> <?= ucfirst($h['tipo']) ?></td>
                        <td><?= htmlspecialchars($h['piso']) ?>°</td>
                        <td>👤 <?= htmlspecialchars($h['capacidad']) ?> pers.</td>
                        <td style="color:#2a9d8f;font-weight:600;">$<?= number_format($h['precio_noche'], 2) ?></td>
                        <td>
                            <span class="badge badge-<?= $h['estado'] ?>">
                                <?= ucfirst($h['estado']) ?>
                            </span>
                        </td>
                        <td style="text-align:center;white-space:nowrap;">

                                     <?php if (!$solo_lectura): ?>
                                     <a href="<?= htmlspecialchars($habitacionesUrl) ?>?accion=editar&id=<?= $h['habitacion_id'] ?>"
                                         class="btn-edit">✏️ Editar</a>
                                     <?php endif; ?>

                                     <?php if (!$solo_lectura): ?>
                                     <div class="dropdown" style="display:inline-block;margin:0 4px;">
                                <button class="btn-status">🔄 Estado ▾</button>
                                <div class="dropdown-menu">
                                    <div class="dropdown-header">Cambiar a:</div>
                                    <?php foreach (['disponible','ocupada','mantenimiento'] as $e):
                                        if ($e === $h['estado']) continue; ?>
                                    <a href="<?= htmlspecialchars($habitacionesUrl) ?>?accion=estado&id=<?= $h['habitacion_id'] ?>&nuevo_estado=<?= $e ?>">
                                        <?= ucfirst($e) ?>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($_SESSION['rol'] === 'administrador'): ?>
                                     <a href="<?= htmlspecialchars($habitacionesUrl) ?>?accion=eliminar&id=<?= $h['habitacion_id'] ?>"
                               class="btn-delete"
                               onclick="return confirm('¿Eliminar habitación <?= htmlspecialchars($h['numero']) ?>?\nEsta acción no se puede deshacer.')">
                               🗑️
                            </a>
                            <?php endif; ?>

                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            <div class="table-footer">
                Total: <strong><?= count($habitaciones) ?></strong> habitación(es)
            </div>
        </div>

    </div>
</div>
</body>
</html>
