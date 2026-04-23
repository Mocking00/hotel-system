<?php
session_start();
require_once __DIR__ . '/../../utils/url_helper.php';
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
$fecha_max_mayoria_edad = date('Y-m-d', strtotime('-18 years'));
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Cliente - HotelManager</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',Tahoma,sans-serif;background:linear-gradient(135deg,#1b98e0 0%,#0b2545 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .card{background:white;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:760px;padding:28px}
        .title{font-size:28px;font-weight:800;color:#12355b;text-align:center;margin-bottom:6px}
        .sub{font-size:14px;color:#556;text-align:center;margin-bottom:20px}
        .alert{padding:12px 14px;border-radius:8px;margin-bottom:14px;font-size:14px}
        .alert-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
        .alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .full{grid-column:1/-1}
        label{display:block;font-size:12px;font-weight:700;color:#555;margin-bottom:6px}
        input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px}
        input:focus{outline:none;border-color:#1b98e0;box-shadow:0 0 0 3px rgba(27,152,224,.18)}
        .actions{display:flex;gap:10px;justify-content:flex-end;margin-top:18px}
        .btn{padding:10px 16px;border-radius:8px;font-size:14px;border:1px solid;cursor:pointer;text-decoration:none}
        .btn-back{background:#f8f9fa;color:#555;border-color:#ddd}
        .btn-save{background:#1b98e0;color:white;border-color:#1b98e0;font-weight:700}
        @media (max-width:700px){.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="card">
    <div class="title">🏨 Crear Cuenta de Cliente</div>
    <div class="sub">Regístrate para acceder al dashboard de cliente y hacer tu pre-reserva.</div>

    <?php if (!empty($error)): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo app_url('controllers/UsuarioController.php?action=registrar'); ?>">
        <div class="grid">
            <div>
                <label>Nombre *</label>
                <input type="text" name="nombre" required pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s'-]+" title="Solo letras y espacios">
            </div>
            <div>
                <label>Apellido *</label>
                <input type="text" name="apellido" required pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s'-]+" title="Solo letras y espacios">
            </div>
            <div>
                <label>Cédula *</label>
                <input type="text" name="cedula" required pattern="[0-9\s-]+" title="Solo números y guiones">
            </div>
            <div>
                <label>Teléfono *</label>
                <input type="text" name="telefono" required pattern="[0-9\s+\-()]+" title="Solo números y caracteres de formato">
            </div>
            <div class="full">
                <label>Email *</label>
                <input type="email" name="email" required>
            </div>
            <div class="full">
                <label>Dirección</label>
                <input type="text" name="direccion">
            </div>
            <div>
                <label>Fecha de nacimiento *</label>
                <input type="date" name="fecha_nacimiento" required max="<?= $fecha_max_mayoria_edad ?>">
            </div>
            <div>
                <label>Username *</label>
                <input type="text" name="username" required>
            </div>
            <div>
                <label>Contraseña *</label>
                <input type="password" name="password" minlength="6" required>
            </div>
            <div>
                <label>Confirmar contraseña *</label>
                <input type="password" name="password_confirm" minlength="6" required>
            </div>
        </div>

        <div class="actions">
            <a class="btn btn-back" href="<?php echo app_url('views/auth/login.php'); ?>">Volver al login</a>
            <button class="btn btn-save" type="submit">Crear cuenta</button>
        </div>
    </form>
</div>
</body>
</html>

