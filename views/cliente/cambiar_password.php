<?php
session_start();

require_once __DIR__ . '/../../utils/url_helper.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
if (($_SESSION['rol'] ?? '') !== 'cliente') {
    header("Location: ../auth/login.php");
    exit();
}

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - HotelManager</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',Tahoma,sans-serif;background:#f3f8fb;min-height:100vh}
        .wrap{max-width:650px;margin:40px auto;padding:0 16px}
        .card{background:#fff;border-radius:14px;box-shadow:0 10px 26px rgba(0,0,0,.08);padding:24px}
        .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
        .title{font-size:24px;font-weight:800;color:#12355b}
        .sub{font-size:13px;color:#556;margin-bottom:18px}
        .alert{padding:12px 14px;border-radius:8px;margin-bottom:12px;font-size:14px}
        .alert-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
        .alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
        label{display:block;font-size:12px;font-weight:700;color:#555;margin:10px 0 6px}
        input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px}
        input:focus{outline:none;border-color:#1b98e0;box-shadow:0 0 0 3px rgba(27,152,224,.18)}
        .actions{display:flex;gap:10px;justify-content:flex-end;margin-top:18px}
        .btn{padding:10px 16px;border-radius:8px;border:1px solid;cursor:pointer;text-decoration:none;font-size:14px}
        .btn-back{background:#f8f9fa;color:#555;border-color:#ddd}
        .btn-save{background:#1b98e0;color:white;border-color:#1b98e0;font-weight:700}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="top">
            <div class="title">Seguridad de Cuenta</div>
            <a class="btn btn-back" href="<?php echo app_url('views/cliente/dashboard.php'); ?>">Volver</a>
        </div>
        <div class="sub">Usuario: <strong><?= htmlspecialchars($username) ?></strong>. Cambia tu contraseña para mantener tu cuenta segura.</div>

        <?php if (!empty($error)): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo app_url('controllers/UsuarioController.php?action=cambiar_password'); ?>">
            <label>Contraseña actual *</label>
            <input type="password" name="password_actual" required>

            <label>Nueva contraseña *</label>
            <input type="password" name="password_nueva" minlength="6" required>

            <label>Confirmar nueva contraseña *</label>
            <input type="password" name="password_confirm" minlength="6" required>

            <div class="actions">
                <a class="btn btn-back" href="<?php echo app_url('views/cliente/dashboard.php'); ?>">Cancelar</a>
                <button class="btn btn-save" type="submit">Actualizar contraseña</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>

