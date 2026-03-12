<?php if (!isset($_SESSION['usuario_id'])) { header("Location: /hotel-system/views/auth/login.php"); exit(); }
$username = $_SESSION['username']; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - HotelManager</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',sans-serif;background:#f5f7fa}
        .sidebar{width:260px;background:#2c3e50;color:white;min-height:100vh;padding:20px 0;position:fixed;left:0;top:0}
        .logo-section{padding:0 20px 20px;border-bottom:1px solid rgba(255,255,255,.1);margin-bottom:20px}
        .logo{font-size:24px;font-weight:bold;margin-bottom:5px}
        .role{font-size:13px;opacity:.8}
        .menu-item{padding:15px 20px;display:flex;align-items:center;gap:12px;border-left:4px solid transparent;text-decoration:none;color:white;transition:all .3s;font-size:14px}
        .menu-item:hover,.menu-item.active{background:rgba(255,255,255,.1);border-left-color:#3498db}
        .menu-icon{font-size:18px;width:22px}
        .main-content{margin-left:260px}
        .header{background:white;padding:20px 30px;box-shadow:0 2px 5px rgba(0,0,0,.05);display:flex;justify-content:space-between;align-items:center}
        .header h1{color:#333;font-size:24px;margin-bottom:4px}
        .header-subtitle{color:#666;font-size:14px}
        .user-section{display:flex;align-items:center;gap:12px}
        .user-avatar{width:40px;height:40px;background:#3498db;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold}
        .btn-logout{padding:8px 20px;background:#e74c3c;color:white;border:none;border-radius:8px;font-size:14px;text-decoration:none}
        .content-area{padding:30px}
        .alert{padding:15px 20px;border-radius:8px;margin-bottom:20px;font-size:14px}
        .alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
        .alert-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
        .toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px}
        .toolbar h2{color:#333;font-size:20px}
        .btn-primary{padding:10px 22px;background:#3498db;color:white;border:none;border-radius:8px;font-size:14px;text-decoration:none;transition:all .3s}
        .btn-primary:hover{background:#2980b9}
        .filter-bar{background:white;padding:15px 20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.05);display:flex;gap:12px;align-items:flex-end;margin-bottom:20px}
        .filter-group{display:flex;flex-direction:column;gap:5px;flex:1}
        .filter-group label{font-size:12px;font-weight:600;color:#555}
        .filter-group input{padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:13px}
        .btn-filter{padding:8px 18px;background:#667eea;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px}
        .btn-clear{padding:8px 18px;background:#f8f9fa;color:#555;border:1px solid #ddd;border-radius:6px;font-size:13px;text-decoration:none}
        .table-card{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.05);overflow:hidden}
        table{width:100%;border-collapse:collapse}
        thead{background:#2c3e50;color:white}
        thead th{padding:13px 14px;text-align:left;font-size:13px}
        tbody tr{border-bottom:1px solid #f0f0f0;transition:background .2s}
        tbody tr:hover{background:#f8f9fa}
        tbody td{padding:13px 14px;font-size:13px;color:#444}
        .table-footer{padding:12px 14px;color:#888;font-size:13px;background:#fafafa}
        .btn-sm{padding:4px 10px;border-radius:6px;font-size:12px;text-decoration:none;border:1px solid;display:inline-block;margin-right:3px}
        .btn-detail{background:#e8f4fd;color:#1a73e8;border-color:#bee3f8}
        .btn-edit{background:#fff3cd;color:#856404;border-color:#ffc107}
        .btn-del{background:#f8d7da;color:#721c24;border-color:#f5c6cb}
        .empty-state{text-align:center;padding:60px;color:#aaa}
        .empty-state span{font-size:48px;display:block;margin-bottom:15px}
        .badge-reservas{padding:3px 9px;background:#e8f4fd;color:#1a73e8;border-radius:20px;font-size:11px;font-weight:600}
    </style>
</head>
<body>
<div class="sidebar">
    <div class="logo-section">
        <div class="logo">🏨 HotelManager</div>
        <div class="role">Panel de Administración</div>
    </div>
    <a href="/hotel-system/views/admin/dashboard.php" class="menu-item"><span class="menu-icon">📊</span>Dashboard</a>
    <a href="/hotel-system/controllers/HabitacionController.php" class="menu-item"><span class="menu-icon">🛏️</span>Habitaciones</a>
    <a href="/hotel-system/controllers/ReservaController.php" class="menu-item"><span class="menu-icon">📅</span>Reservas</a>
    <a href="/hotel-system/controllers/ClienteController.php" class="menu-item active"><span class="menu-icon">👥</span>Clientes</a>
    <a href="#" class="menu-item"><span class="menu-icon">📈</span>Reportes</a>
    <a href="/hotel-system/controllers/UsuarioController.php?action=logout" class="menu-item"><span class="menu-icon">🚪</span>Cerrar Sesión</a>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h1>👥 Gestión de Clientes</h1>
            <div class="header-subtitle">Registro y administración de clientes</div>
        </div>
        <div class="user-section">
            <div class="user-avatar"><?= strtoupper(substr($username,0,2)) ?></div>
            <div>
                <div style="font-weight:600;color:#333"><?= htmlspecialchars($username) ?></div>
                <div style="font-size:12px;color:#666">Administrador</div>
            </div>
            <a href="/hotel-system/controllers/UsuarioController.php?action=logout" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <div class="content-area">
        <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $tipo_msg ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="toolbar">
            <h2>Clientes registrados</h2>
            <a href="/hotel-system/controllers/ClienteController.php?accion=crear" class="btn-primary">➕ Nuevo Cliente</a>
        </div>

        <!-- Buscador -->
        <form method="GET" action="/hotel-system/controllers/ClienteController.php">
            <input type="hidden" name="accion" value="listar">
            <div class="filter-bar">
                <div class="filter-group">
                    <label>Buscar por nombre, cédula o email</label>
                    <input type="text" name="buscar" placeholder="Buscar cliente..."
                           value="<?= htmlspecialchars($buscar ?? '') ?>">
                </div>
                <button type="submit" class="btn-filter">🔍 Buscar</button>
                <a href="/hotel-system/controllers/ClienteController.php" class="btn-clear">✕ Limpiar</a>
            </div>
        </form>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Reservas</th>
                        <th>Registrado</th>
                        <th style="text-align:center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($clientes)): ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <span>👥</span>
                            No hay clientes registrados.<br>
                            <small>Haz clic en "Nuevo Cliente" para agregar uno.</small>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($clientes as $c): ?>
                    <tr>
                        <td style="color:#aaa;font-size:12px"><?= $c['cliente_id'] ?></td>
                        <td>
                            <div style="font-weight:600"><?= htmlspecialchars($c['apellido'].', '.$c['nombre']) ?></div>
                            <?php if (!empty($c['username'])): ?>
                            <div style="font-size:11px;color:#3498db">@<?= htmlspecialchars($c['username']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($c['cedula']) ?></td>
                        <td><?= htmlspecialchars($c['telefono']) ?></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td style="text-align:center">
                            <span class="badge-reservas">📅 <?= $c['total_reservas'] ?></span>
                        </td>
                        <td style="color:#888;font-size:12px"><?= date('d/m/Y', strtotime($c['fecha_registro'])) ?></td>
                        <td style="text-align:center;white-space:nowrap">
                            <a href="/hotel-system/controllers/ClienteController.php?accion=detalle&id=<?= $c['cliente_id'] ?>" class="btn-sm btn-detail">👁 Ver</a>
                            <a href="/hotel-system/controllers/ClienteController.php?accion=editar&id=<?= $c['cliente_id'] ?>" class="btn-sm btn-edit">✏️ Editar</a>
                            <a href="/hotel-system/controllers/ClienteController.php?accion=eliminar&id=<?= $c['cliente_id'] ?>"
                               class="btn-sm btn-del"
                               onclick="return confirm('¿Eliminar a <?= htmlspecialchars($c['nombre'].' '.$c['apellido']) ?>? Esta acción no se puede deshacer.')">🗑</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            <div class="table-footer">Total: <strong><?= count($clientes) ?></strong> cliente(s)</div>
        </div>
    </div>
</div>
</body>
</html>