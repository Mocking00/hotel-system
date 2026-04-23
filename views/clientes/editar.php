<?php if (!isset($_SESSION['usuario_id'])) { header("Location: ../auth/login.php"); exit(); }
require_once __DIR__ . '/../../utils/url_helper.php';
$username = $_SESSION['username'];
$fecha_max_mayoria_edad = date('Y-m-d', strtotime('-18 years')); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - HotelManager</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',sans-serif;background:#f3f8fb}
        .sidebar{width:260px;background:#12355b;color:white;min-height:100vh;padding:20px 0;position:fixed;left:0;top:0}
        .logo-section{padding:0 20px 20px;border-bottom:1px solid rgba(255,255,255,.1);margin-bottom:20px}
        .logo{font-size:24px;font-weight:bold;margin-bottom:5px}
        .menu-item{padding:15px 20px;display:flex;align-items:center;gap:12px;border-left:4px solid transparent;text-decoration:none;color:white;transition:all .3s;font-size:14px;min-height:52px}
        .menu-item:hover,.menu-item.active{background:rgba(255,255,255,.1);border-left-color:#1b98e0}
        .menu-icon{font-size:20px;width:24px}
        .main-content{margin-left:260px}
        .header{background:white;padding:20px 30px;box-shadow:0 2px 5px rgba(0,0,0,.05);display:flex;justify-content:space-between;align-items:center}
        .header h1{color:#333;font-size:24px;margin-bottom:4px}
        .header-subtitle{color:#666;font-size:14px}
        .user-section{display:flex;align-items:center;gap:12px}
        .user-avatar{width:40px;height:40px;background:#1b98e0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold}
        .btn-logout{padding:8px 20px;background:#e76f51;color:white;border:none;border-radius:8px;font-size:14px;text-decoration:none}
        .content-area{padding:30px}
        .breadcrumb{margin-bottom:20px;font-size:13px;color:#888}
        .breadcrumb a{color:#1b98e0;text-decoration:none}
        .alert-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;padding:15px 20px;border-radius:8px;margin-bottom:20px;max-width:750px}
        .alert-error ul{margin:8px 0 0 20px}
        .form-card{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.05);padding:28px 32px;max-width:750px}
        .form-card h2{color:#333;font-size:18px;margin-bottom:22px;padding-bottom:14px;border-bottom:2px solid #f0f0f0;display:flex;align-items:center;gap:10px}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
        .form-group{display:flex;flex-direction:column;gap:6px}
        .form-group.full{grid-column:1/-1}
        .form-group label{font-size:13px;font-weight:600;color:#555}
        .form-group input{padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px;color:#333;font-family:inherit}
        .form-group input:focus{outline:none;border-color:#1b98e0;box-shadow:0 0 0 3px rgba(52,152,219,.1)}
        .form-footer{display:flex;justify-content:flex-end;gap:12px;margin-top:24px}
        .btn-cancel{padding:10px 24px;background:#f8f9fa;color:#555;border:1px solid #ddd;border-radius:8px;text-decoration:none;font-size:14px}
        .btn-save{padding:10px 28px;background:#f4a261;color:white;border:none;border-radius:8px;cursor:pointer;font-size:14px;font-weight:600}
        .btn-save:hover{background:#d68910}
    </style>
</head>
<body>
<div class="sidebar">
    <div class="logo-section">
        <div class="logo">🏨 HotelManager</div>
        <div style="font-size:13px;opacity:.8">Panel de Administracion</div>
    </div>
    <a href="<?php echo app_url('views/admin/dashboard.php'); ?>" class="menu-item"><span class="menu-icon">📊</span>Dashboard</a>
    <a href="./ReservaController.php" class="menu-item"><span class="menu-icon">📅</span>Reservas</a>
    <a href="./HabitacionController.php" class="menu-item"><span class="menu-icon">🛏️</span>Habitaciones</a>
    <a href="./ClienteController.php" class="menu-item active"><span class="menu-icon">👥</span>Clientes</a>
    <a href="./ReservaController.php?accion=reportes" class="menu-item"><span class="menu-icon">📈</span>Reportes</a>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h1>✏️ Editar Cliente</h1>
            <div class="header-subtitle"><?= htmlspecialchars($datos['nombre'].' '.$datos['apellido']) ?></div>
        </div>
        <div class="user-section">
            <div class="user-avatar"><?= strtoupper(substr($username,0,2)) ?></div>
            <div>
                <div style="font-weight:600;color:#333"><?= htmlspecialchars($username) ?></div>
                <div style="font-size:12px;color:#666">Administrador</div>
            </div>
            <a href="./UsuarioController.php?action=logout" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <div class="content-area">
        <div class="breadcrumb">
            <a href="./ClienteController.php">👥 Clientes</a> ›
            <a href="./ClienteController.php?accion=detalle&id=<?= $datos['cliente_id'] ?>"><?= htmlspecialchars($datos['nombre'].' '.$datos['apellido']) ?></a> ›
            Editar
        </div>

        <?php if (!empty($errores)): ?>
        <div class="alert-error">
            <strong>⚠️ Corrige los siguientes errores:</strong>
            <ul><?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <form method="POST" action="./ClienteController.php?accion=editar&id=<?= $datos['cliente_id'] ?>">
        <div class="form-card">
            <h2>
                <span style="width:38px;height:38px;background:#f4a261;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:16px">✏️</span>
                Datos del Cliente
            </h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($datos['nombre'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Apellido *</label>
                    <input type="text" name="apellido" value="<?= htmlspecialchars($datos['apellido'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Cédula *</label>
                    <input type="text" name="cedula" value="<?= htmlspecialchars($datos['cedula'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Teléfono *</label>
                    <input type="text" name="telefono" value="<?= htmlspecialchars($datos['telefono'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($datos['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento"
                           max="<?= $fecha_max_mayoria_edad ?>"
                           value="<?= htmlspecialchars(!empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : '') ?>">
                </div>
                <div class="form-group full">
                    <label>Dirección</label>
                    <input type="text" name="direccion" value="<?= htmlspecialchars($datos['direccion'] ?? '') ?>">
                </div>
            </div>
            <div class="form-footer">
                <a href="./ClienteController.php?accion=detalle&id=<?= $datos['cliente_id'] ?>" class="btn-cancel">✕ Cancelar</a>
                <button type="submit" class="btn-save">💾 Guardar Cambios</button>
            </div>
        </div>
        </form>
    </div>
</div>
</body>
</html>


