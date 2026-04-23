<?php
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../../utils/url_helper.php';

$username = $_SESSION['username'];
$rol = $_SESSION['rol'];
$es_cliente = $rol === 'cliente';
$es_recepcion = $rol === 'recepcionista';
$appBase = app_base_path();
$dashboard_url = $es_recepcion
    ? $appBase . '/views/recepcionista/dashboard.php'
    : $appBase . '/views/admin/dashboard.php';

$badge = [
    'pendiente'  => ['bg'=>'#fff3cd','color'=>'#856404', 'icon'=>'⏳'],
    'confirmada' => ['bg'=>'#d4edda','color'=>'#155724', 'icon'=>'✅'],
    'cancelada'  => ['bg'=>'#f8d7da','color'=>'#721c24', 'icon'=>'❌'],
    'completada' => ['bg'=>'#cce5ff','color'=>'#004085', 'icon'=>'🏁'],
    'no_show'    => ['bg'=>'#e2e3e5','color'=>'#383d41', 'icon'=>'👻'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas - HotelManager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f3f8fb; }

        .sidebar {
            width: 260px; background: #12355b; color: white;
            min-height: 100vh; padding: 20px 0;
            position: fixed; left: 0; top: 0;
        }
        .logo-section { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .role { font-size: 13px; opacity: 0.8; }
        .menu-item {
            padding: 15px 20px; cursor: pointer; transition: all 0.3s;
            display: flex; align-items: center; gap: 12px;
            border-left: 4px solid transparent;
            text-decoration: none; color: white;
        }
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            border-left-color: #1b98e0;
        }

        .topbar {
            background: linear-gradient(135deg, #1b98e0 0%, #0b2545 100%);
            color: white; padding: 14px 24px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 3px 14px rgba(27,152,224,0.35);
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

        .toolbar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; flex-wrap: wrap; gap: 12px;
        }
        .toolbar h2 { color: #333; font-size: 20px; }
        .btn-primary {
            padding: 10px 22px; background: #1b98e0; color: white;
            border: none; border-radius: 8px; font-size: 14px;
            text-decoration: none;
        }
        .btn-primary:hover { background: #1475ae; }

        .filters {
            background: white; padding: 15px 20px; border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto auto;
            gap: 12px; align-items: end; margin-bottom: 20px;
        }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 12px; font-weight: 600; color: #555; }
        .filter-group input, .filter-group select {
            padding: 8px 12px; border: 1px solid #ddd;
            border-radius: 6px; font-size: 13px;
        }
        .btn-filter {
            padding: 8px 18px; background: #1b98e0; color: white;
            border: none; border-radius: 6px; cursor: pointer; font-size: 13px;
            text-decoration: none;
        }
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
        thead th { padding: 13px 14px; text-align: left; font-size: 13px; }
        tbody tr { border-bottom: 1px solid #f0f0f0; transition: background .2s; }
        tbody tr:hover { background: #fafafa; }
        tbody td { padding: 13px 14px; font-size: 13px; color: #444; }
        .table-footer { padding: 12px 14px; color: #888; font-size: 13px; background: #fafafa; }

        .badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .btn-sm {
            padding: 4px 10px; border-radius: 6px; font-size: 12px;
            text-decoration: none; border: 1px solid; display: inline-block;
            margin: 1px;
        }
        .btn-det { background: #e3f4ff; color: #0b6fa4; border-color: #a9d9f5; }
        .btn-ci  { background: #d4edda; color: #155724; border-color: #c3e6cb; }
        .btn-co  { background: #fff3cd; color: #856404; border-color: #ffe08a; }
        .btn-can { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        .empty-state { text-align: center; padding: 60px; color: #aaa; }
        .empty-state span { font-size: 48px; display: block; margin-bottom: 12px; }

        @media (max-width: 980px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .filters { grid-template-columns: 1fr; }
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
    <a href="<?= $dashboard_url ?>" class="menu-item">
        <span>📊 Dashboard</span>
    </a>
    <a href="./HabitacionController.php" class="menu-item">
        <span>🛏️ Habitaciones</span>
    </a>
    <a href="./ReservaController.php" class="menu-item active">
        <span>📅 Reservas</span>
    </a>
    <a href="./ClienteController.php" class="menu-item">
        <span>👥 Clientes</span>
    </a>
    <?php if (!$es_recepcion): ?>
    <a href="./ReservaController.php?accion=reportes" class="menu-item">
        <span>📈 Reportes</span>
    </a>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="topbar">
    <div class="left">🏨 Mis Reservas</div>
    <div class="right">
        <div><?= htmlspecialchars($username) ?></div>
        <a href="<?php echo app_url('views/cliente/dashboard.php'); ?>" class="btn">Panel</a>
        <a href="./UsuarioController.php?action=logout" class="btn">Salir</a>
    </div>
</div>
<?php endif; ?>

<div class="main-content <?= $es_cliente ? 'full' : '' ?>">
    <div class="header">
        <div>
            <h1>📅 Gestion de Reservas</h1>
            <div class="header-subtitle">Lista, filtros y acciones operativas</div>
        </div>
        <div style="font-size:13px;color:#666">Usuario: <strong><?= htmlspecialchars($username) ?></strong></div>
    </div>

    <div class="content-area">
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_msg === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="toolbar">
            <h2>Reservas registradas</h2>
            <?php if (!$es_cliente): ?>
            <a href="./ReservaController.php?accion=crear" class="btn-primary">➕ Nueva Reserva</a>
            <?php endif; ?>
        </div>

        <form method="GET" action="./ReservaController.php">
            <input type="hidden" name="accion" value="listar">
            <div class="filters">
                <div class="filter-group">
                    <label>Buscar</label>
                    <input type="text" name="buscar" placeholder="Codigo, cliente, cedula o habitacion"
                           value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>">
                </div>
                <div class="filter-group">
                    <label>Estado</label>
                    <select name="estado">
                        <option value="">Todos</option>
                        <?php foreach (['pendiente','confirmada','cancelada','completada','no_show'] as $estadoOpt): ?>
                        <option value="<?= $estadoOpt ?>" <?= ($filtros['estado'] ?? '') === $estadoOpt ? 'selected' : '' ?>>
                            <?= ucfirst($estadoOpt) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Desde</label>
                    <input type="date" name="fecha_desde" value="<?= htmlspecialchars($filtros['fecha_desde'] ?? '') ?>">
                </div>
                <div class="filter-group">
                    <label>Hasta</label>
                    <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-filter">🔍 Filtrar</button>
                <a href="./ReservaController.php" class="btn-clear">✕ Limpiar</a>
            </div>
        </form>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Codigo</th>
                        <?php if (!$es_cliente): ?><th>Cliente</th><?php endif; ?>
                        <th>Habitacion</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th style="text-align:center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($reservas)): ?>
                    <tr>
                        <td colspan="<?= $es_cliente ? '8' : '9' ?>">
                            <div class="empty-state">
                                <span>📅</span>
                                No hay reservas para mostrar.
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reservas as $r):
                        $bc = $badge[$r['estado']] ?? $badge['pendiente']; ?>
                    <tr>
                        <td><?= (int) $r['reserva_id'] ?></td>
                        <td><code style="font-size:11px;background:#f0f0f0;padding:2px 6px;border-radius:4px"><?= htmlspecialchars($r['codigo_confirmacion']) ?></code></td>
                        <?php if (!$es_cliente): ?>
                        <td>
                            <strong><?= htmlspecialchars($r['nombre_cliente']) ?></strong><br>
                            <small style="color:#888"><?= htmlspecialchars($r['email']) ?></small>
                        </td>
                        <?php endif; ?>
                        <td>
                            <strong>#<?= htmlspecialchars($r['numero_habitacion']) ?></strong><br>
                            <small style="color:#888"><?= ucfirst(htmlspecialchars($r['tipo_habitacion'])) ?></small>
                        </td>
                        <td><?= date('d/m/Y', strtotime($r['fecha_entrada'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($r['fecha_salida'])) ?></td>
                        <td style="color:#2a9d8f;font-weight:600">$<?= number_format((float) $r['precio_total'], 2) ?></td>
                        <td>
                            <span class="badge" style="background:<?= $bc['bg'] ?>; color:<?= $bc['color'] ?>">
                                <?= $bc['icon'] ?> <?= ucfirst($r['estado']) ?>
                            </span>
                        </td>
                        <td style="text-align:center; white-space:nowrap;">
                            <a href="./ReservaController.php?accion=detalle&id=<?= (int) $r['reserva_id'] ?>" class="btn-sm btn-det">Ver</a>

                            <?php if (!$es_cliente && in_array($r['estado'], ['pendiente','confirmada'])): ?>
                                <?php if (empty($r['fecha_checkin'])): ?>
                                    <a href="./ReservaController.php?accion=checkin&id=<?= (int) $r['reserva_id'] ?>"
                                       class="btn-sm btn-ci" onclick="return confirm('¿Registrar check-in de esta reserva?')">CI</a>
                                <?php elseif (empty($r['fecha_checkout'])): ?>
                                    <a href="./ReservaController.php?accion=checkout&id=<?= (int) $r['reserva_id'] ?>"
                                       class="btn-sm btn-co" onclick="return confirm('¿Registrar check-out de esta reserva?')">CO</a>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (!$es_recepcion && in_array($r['estado'], ['pendiente','confirmada']) && empty($r['fecha_checkin'])): ?>
                                <a href="./ReservaController.php?accion=cancelar&id=<?= (int) $r['reserva_id'] ?>"
                                   class="btn-sm btn-can" onclick="return confirm('¿Cancelar esta reserva?')">Cancelar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            <div class="table-footer">Total: <strong><?= count($reservas) ?></strong> reserva(s)</div>
        </div>
    </div>
</div>

</body>
</html>



