<?php
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
if ($_SESSION['rol'] === 'cliente') {
    header("Location: ../../controllers/ReservaController.php");
    exit();
}

$username = $_SESSION['username'];
$rol = $_SESSION['rol'];

$total_reservas = (int) ($kpis['total_reservas'] ?? 0);
$total_ingresos = (float) ($kpis['ingresos'] ?? 0);
$total_huespedes = (int) ($kpis['total_huespedes'] ?? 0);
$promedio_noches = (float) ($kpis['promedio_noches'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Reservas - HotelManager</title>
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
            padding: 15px 20px; display: flex; align-items: center; gap: 12px;
            border-left: 4px solid transparent; text-decoration: none; color: white;
            transition: all .2s;
        }
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            border-left-color: #1b98e0;
        }

        .main-content { margin-left: 260px; }
        .header {
            background: white; padding: 20px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        .header h1 { color: #333; font-size: 24px; margin-bottom: 4px; }
        .header-subtitle { color: #666; font-size: 14px; }

        .content-area { padding: 30px; }

        .filters {
            background: white; padding: 15px 20px; border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: grid; grid-template-columns: 1fr 1fr 1fr auto auto;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white; border-radius: 12px; padding: 18px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-title { font-size: 12px; color: #556; margin-bottom: 8px; text-transform: uppercase; font-weight: 700; }
        .stat-value { font-size: 28px; color: #12355b; font-weight: 800; }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .card {
            background: white; border-radius: 12px; padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .card h3 {
            font-size: 16px; color: #333; margin-bottom: 14px;
            border-bottom: 1px solid #f0f0f0; padding-bottom: 8px;
        }

        .estado-list { display: grid; gap: 8px; }
        .estado-item {
            display: flex; justify-content: space-between; align-items: center;
            background: #f8f9fa; border: 1px solid #ebeff5;
            border-radius: 8px; padding: 10px 12px;
            font-size: 14px;
        }

        .bars { display: grid; gap: 10px; }
        .bar-row { display: grid; grid-template-columns: 70px 1fr 80px; gap: 10px; align-items: center; }
        .bar-track { width: 100%; height: 10px; background: #ecf1f7; border-radius: 20px; overflow: hidden; }
        .bar-fill { height: 100%; background: linear-gradient(90deg, #1b98e0, #0b2545); }

        .table-card {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #12355b; color: white; }
        thead th { padding: 12px 14px; text-align: left; font-size: 13px; }
        tbody tr { border-bottom: 1px solid #f0f0f0; }
        tbody tr:hover { background: #fafafa; }
        tbody td { padding: 12px 14px; font-size: 13px; color: #444; }

        .badge {
            padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
            background: #eef3f8; color: #3a4a5e;
        }

        .table-footer { padding: 12px 14px; color: #888; font-size: 13px; background: #fafafa; }

        @media (max-width: 980px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .filters { grid-template-columns: 1fr; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-section">
        <div class="logo">🏨 HotelManager</div>
        <div class="role">Panel de <?= ucfirst(htmlspecialchars($rol)) ?></div>
    </div>
    <a href="../views/admin/dashboard.php" class="menu-item">📊 Dashboard</a>
    <a href="../../controllers/HabitacionController.php" class="menu-item">🛏️ Habitaciones</a>
    <a href="../../controllers/ReservaController.php" class="menu-item">📅 Reservas</a>
    <a href="../../controllers/ClienteController.php" class="menu-item">👥 Clientes</a>
    <a href="../../controllers/ReservaController.php?accion=reportes" class="menu-item active">📈 Reportes</a>
    <a href="../../controllers/UsuarioController.php?action=logout" class="menu-item">🚪 Cerrar Sesion</a>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h1>📈 Reportes de Reservas</h1>
            <div class="header-subtitle">Analitica operativa e ingresos del modulo de reservas</div>
        </div>
        <div style="font-size:13px;color:#666">Usuario: <strong><?= htmlspecialchars($username) ?></strong></div>
    </div>

    <div class="content-area">
        <form method="GET" action="../../controllers/ReservaController.php">
            <input type="hidden" name="accion" value="reportes">
            <div class="filters">
                <div class="filter-group">
                    <label>Estado</label>
                    <select name="estado">
                        <option value="">Todos</option>
                        <?php foreach (['pendiente','confirmada','cancelada','completada','no_show'] as $estado): ?>
                        <option value="<?= $estado ?>" <?= ($filtros['estado'] ?? '') === $estado ? 'selected' : '' ?>>
                            <?= ucfirst($estado) ?>
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
                <button type="submit" class="btn-filter">Aplicar</button>
                <a href="../../controllers/ReservaController.php?accion=reportes" class="btn-clear">Limpiar</a>
            </div>
        </form>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total reservas</div>
                <div class="stat-value"><?= number_format($total_reservas) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Ingresos confirmados</div>
                <div class="stat-value">$<?= number_format($total_ingresos, 2) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Huespedes proyectados</div>
                <div class="stat-value"><?= number_format($total_huespedes) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Promedio de noches</div>
                <div class="stat-value"><?= number_format($promedio_noches, 1) ?></div>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h3>Resumen por estado</h3>
                <div class="estado-list">
                    <?php if (empty($resumen_estados)): ?>
                        <div class="estado-item"><span>Sin datos</span><strong>0</strong></div>
                    <?php else: ?>
                        <?php foreach ($resumen_estados as $e): ?>
                        <div class="estado-item">
                            <span><?= ucfirst(htmlspecialchars($e['estado'])) ?></span>
                            <strong><?= (int) $e['total'] ?></strong>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h3>Ingresos mensuales (ultimos 6)</h3>
                <?php
                $max_total = 0;
                foreach ($ingresos_mes as $im) {
                    $valor = (float) $im['total'];
                    if ($valor > $max_total) $max_total = $valor;
                }
                ?>
                <div class="bars">
                    <?php if (empty($ingresos_mes)): ?>
                        <div style="font-size:13px;color:#888">Sin datos de ingresos para mostrar.</div>
                    <?php else: ?>
                        <?php foreach ($ingresos_mes as $im):
                            $valor = (float) $im['total'];
                            $pct = $max_total > 0 ? ($valor * 100 / $max_total) : 0;
                        ?>
                        <div class="bar-row">
                            <div style="font-size:12px;color:#666"><?= htmlspecialchars($im['periodo']) ?></div>
                            <div class="bar-track"><div class="bar-fill" style="width: <?= round($pct, 2) ?>%"></div></div>
                            <div style="font-size:12px;color:#333;text-align:right">$<?= number_format($valor, 0) ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Codigo</th>
                        <th>Cliente</th>
                        <th>Habitacion</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Noches</th>
                        <th>Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportes)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center;color:#999;padding:20px;">No hay reservas en el rango seleccionado.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($reportes as $r): ?>
                    <tr>
                        <td><?= (int) $r['reserva_id'] ?></td>
                        <td><?= htmlspecialchars($r['codigo_confirmacion']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($r['nombre_cliente']) ?></strong><br>
                            <small style="color:#888"><?= htmlspecialchars($r['email']) ?></small>
                        </td>
                        <td>#<?= htmlspecialchars($r['numero_habitacion']) ?> (<?= ucfirst(htmlspecialchars($r['tipo_habitacion'])) ?>)</td>
                        <td><?= date('d/m/Y', strtotime($r['fecha_entrada'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($r['fecha_salida'])) ?></td>
                        <td><?= (int) $r['noches'] ?></td>
                        <td style="color:#2a9d8f;font-weight:700">$<?= number_format((float) $r['precio_total'], 2) ?></td>
                        <td><span class="badge"><?= ucfirst(htmlspecialchars($r['estado'])) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="table-footer">Total listado: <strong><?= count($reportes) ?></strong> reserva(s)</div>
        </div>
    </div>
</div>

</body>
</html>

