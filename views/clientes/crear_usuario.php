<?php if (!isset($_SESSION['usuario_id'])) { header("Location: ../auth/login.php"); exit(); }
require_once __DIR__ . '/../../utils/url_helper.php';
$username = $_SESSION['username'];
$rol = $_SESSION['rol'];
$fecha_max_mayoria_edad = date('Y-m-d', strtotime('-18 years'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario Asistido - HotelManager</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',sans-serif;background:#f3f8fb}
        .sidebar{width:260px;background:#12355b;color:white;min-height:100vh;padding:20px 0;position:fixed;left:0;top:0}
        .logo-section{padding:0 20px 20px;border-bottom:1px solid rgba(255,255,255,.1);margin-bottom:20px}
        .logo{font-size:24px;font-weight:bold;margin-bottom:5px}
        .menu-item{padding:15px 20px;display:flex;align-items:center;gap:12px;border-left:4px solid transparent;text-decoration:none;color:white;transition:all .3s;font-size:14px}
        .menu-item:hover,.menu-item.active{background:rgba(255,255,255,.1);border-left-color:#1b98e0}
        .main-content{margin-left:260px}
        .header{background:white;padding:20px 30px;box-shadow:0 2px 5px rgba(0,0,0,.05);display:flex;justify-content:space-between;align-items:center}
        .content-area{padding:30px}
        .card{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.05);padding:25px;max-width:700px}
        .card h2{margin-bottom:12px;color:#333}
        .sub{color:#666;margin-bottom:20px}
        .alert-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;padding:15px 20px;border-radius:8px;margin-bottom:20px}
        .alert-error ul{margin:8px 0 0 18px}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .full{grid-column:1/-1}
        label{display:block;font-size:12px;font-weight:700;color:#555;margin-bottom:6px}
        input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px}
        .actions{display:flex;gap:10px;justify-content:flex-end;margin-top:20px}
        .btn{padding:10px 16px;border-radius:8px;border:1px solid;cursor:pointer;text-decoration:none;font-size:14px}
        .btn-cancel{background:#f8f9fa;color:#555;border-color:#ddd}
        .btn-save{background:#2a9d8f;color:white;border-color:#2a9d8f}
    </style>
</head>
<body>
<div class="sidebar">
    <div class="logo-section">
        <div class="logo">🏨 HotelManager</div>
        <div style="font-size:13px;opacity:.8">Panel de <?= ucfirst(htmlspecialchars($rol)) ?></div>
    </div>
    <a href="./ClienteController.php" class="menu-item active">👥 Clientes</a>
    <a href="./ReservaController.php" class="menu-item">📅 Reservas</a>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h1>🔐 Crear Usuario Asistido</h1>
            <div style="font-size:13px;color:#666">Cliente: <?= htmlspecialchars($datos_cliente['nombre'] . ' ' . $datos_cliente['apellido']) ?></div>
        </div>
        <div style="font-size:13px;color:#666">Operador: <strong><?= htmlspecialchars($username) ?></strong></div>
    </div>

    <div class="content-area">
        <?php if (!empty($errores)): ?>
        <div class="alert-error">
            <strong>Corrige los errores:</strong>
            <ul>
                <?php foreach ($errores as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>Credenciales de acceso del cliente</h2>
            <div class="sub">Este usuario se vinculará al cliente seleccionado y luego no se podrá crear otro.</div>

            <form method="POST" action="./ClienteController.php?accion=crear_usuario&id=<?= (int)$datos_cliente['cliente_id'] ?>">
                <div class="grid">
                    <div class="full">
                        <label>Fecha de nacimiento del cliente *</label>
                        <input type="date" name="fecha_nacimiento" required max="<?= $fecha_max_mayoria_edad ?>" value="<?= htmlspecialchars($datos['fecha_nacimiento'] ?? '') ?>">
                    </div>
                    <div class="full">
                        <label>Username *</label>
                        <input type="text" name="username" required value="<?= htmlspecialchars($datos['username'] ?? '') ?>" placeholder="ej: cliente.<?= (int)$datos_cliente['cliente_id'] ?>">
                    </div>
                    <div>
                        <label>Contraseña *</label>
                        <input type="password" name="password" required minlength="6" placeholder="mínimo 6 caracteres">
                    </div>
                    <div>
                        <label>Confirmar contraseña *</label>
                        <input type="password" name="password_confirm" required minlength="6" placeholder="repite la contraseña">
                    </div>
                </div>
                <div class="actions">
                    <a class="btn btn-cancel" href="./ClienteController.php?accion=detalle&id=<?= (int)$datos_cliente['cliente_id'] ?>">Cancelar</a>
                    <button class="btn btn-save" type="submit">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>



