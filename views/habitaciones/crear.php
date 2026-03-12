<?php
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /hotel-system/views/auth/login.php");
    exit();
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Habitación - HotelManager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }

        .sidebar {
            width: 260px; background: #2c3e50; color: white;
            min-height: 100vh; padding: 20px 0; position: fixed; left: 0; top: 0;
        }
        .logo-section { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .role { font-size: 13px; opacity: 0.8; }
        .menu-item {
            padding: 15px 20px; cursor: pointer; transition: all 0.3s;
            display: flex; align-items: center; gap: 12px;
            border-left: 4px solid transparent; text-decoration: none; color: white;
        }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left-color: #3498db; }
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
            width: 40px; height: 40px; background: #3498db; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold;
        }
        .btn-logout {
            padding: 8px 20px; background: #e74c3c; color: white;
            border: none; border-radius: 8px; font-size: 14px; text-decoration: none;
        }

        .content-area { padding: 30px; }

        .breadcrumb { margin-bottom: 20px; font-size: 13px; color: #888; }
        .breadcrumb a { color: #3498db; text-decoration: none; }

        .alert-error {
            background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
            padding: 15px 20px; border-radius: 8px; margin-bottom: 20px;
        }
        .alert-error ul { margin: 8px 0 0 20px; }

        .form-card {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 30px;
        }
        .form-card h2 { color: #333; font-size: 18px; margin-bottom: 25px;
            padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 13px; font-weight: 600; color: #555; }
        .form-group input, .form-group select, .form-group textarea {
            padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 14px; color: #333; font-family: inherit;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }
        .form-hint { font-size: 11px; color: #aaa; }

        .tipo-section { grid-column: 1 / -1; }
        .tipo-section > label { font-size: 13px; font-weight: 600; color: #555; display: block; margin-bottom: 12px; }
        .tipo-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .tipo-card {
            border: 2px solid #e1e8ed; border-radius: 10px; padding: 18px 10px;
            text-align: center; cursor: pointer; transition: all 0.2s;
        }
        .tipo-card:hover { border-color: #3498db; background: #f0f7ff; }
        .tipo-card.selected { border-color: #3498db; background: #ebf5fb; }
        .tipo-card .icon { font-size: 32px; margin-bottom: 8px; }
        .tipo-card .name { font-weight: 600; font-size: 14px; color: #333; }
        .tipo-card .desc { font-size: 11px; color: #888; margin-top: 3px; }

        .form-footer {
            display: flex; justify-content: flex-end; gap: 12px;
            margin-top: 30px; padding-top: 20px; border-top: 1px solid #f0f0f0;
        }
        .btn-cancel {
            padding: 10px 24px; background: #f8f9fa; color: #555;
            border: 1px solid #ddd; border-radius: 8px; text-decoration: none; font-size: 14px;
        }
        .btn-save {
            padding: 10px 28px; background: #27ae60; color: white;
            border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600;
        }
        .btn-save:hover { background: #219a52; }
        .btn-save:disabled { background: #95a5a6; cursor: not-allowed; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-section">
        <div class="logo">🏨 HotelManager</div>
        <div class="role">Panel de Administración</div>
    </div>
    <a href="/hotel-system/views/admin/dashboard.php" class="menu-item">
        <span class="menu-icon">📊</span><span>Dashboard</span>
    </a>
    <a href="/hotel-system/controllers/HabitacionController.php" class="menu-item active">
        <span class="menu-icon">🛏️</span><span>Habitaciones</span>
    </a>
    <a href="#" class="menu-item"><span class="menu-icon">📅</span><span>Reservas</span></a>
    <a href="#" class="menu-item"><span class="menu-icon">👥</span><span>Usuarios</span></a>
    <a href="#" class="menu-item"><span class="menu-icon">⭐</span><span>Servicios</span></a>
    <a href="#" class="menu-item"><span class="menu-icon">📈</span><span>Reportes</span></a>
    <a href="/hotel-system/controllers/UsuarioController.php?action=logout" class="menu-item">
        <span class="menu-icon">🚪</span><span>Cerrar Sesión</span>
    </a>
</div>

<div class="main-content">
    <div class="header">
        <div class="header-title">
            <h1>➕ Nueva Habitación</h1>
            <div class="header-subtitle">Completa el formulario para registrar una habitación</div>
        </div>
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($username, 0, 2)) ?></div>
                <div>
                    <div style="font-weight:600;color:#333;"><?= htmlspecialchars($username) ?></div>
                    <div style="font-size:12px;color:#666;"><?= ucfirst($_SESSION['rol']) ?></div>
                </div>
            </div>
            <a href="/hotel-system/controllers/UsuarioController.php?action=logout" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <div class="content-area">
        <div class="breadcrumb">
            <a href="/hotel-system/controllers/HabitacionController.php">🛏️ Habitaciones</a> &rsaquo; Nueva
        </div>

        <?php if (!empty($errores)): ?>
        <div class="alert-error">
            <strong>⚠️ Corrige los siguientes errores:</strong>
            <ul>
                <?php foreach ($errores as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="form-card">
            <h2>📋 Datos de la Habitación</h2>
            <form method="POST"
                  action="/hotel-system/controllers/HabitacionController.php?accion=crear"
                  id="formCrear">

                <div class="form-grid">

                    <div class="form-group">
                        <label>Número de Habitación *</label>
                        <input type="text" name="numero" required placeholder="Ej: 101"
                               value="<?= htmlspecialchars($datos['numero'] ?? '') ?>">
                        <span class="form-hint">Debe ser único.</span>
                    </div>

                    <div class="form-group">
                        <label>Piso *</label>
                        <input type="number" name="piso" required min="1" max="50" placeholder="Ej: 1"
                               value="<?= htmlspecialchars($datos['piso'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Capacidad (personas) *</label>
                        <input type="number" name="capacidad" required min="1" max="20" placeholder="Ej: 2"
                               value="<?= htmlspecialchars($datos['capacidad'] ?? '') ?>">
                    </div>

                    <!-- Tipo visual -->
                    <div class="tipo-section">
                        <label>Tipo de Habitación *</label>
                        <div class="tipo-grid">
                            <?php
                            $tipos = [
                                'simple'       => ['icon'=>'🛏️', 'desc'=>'1 cama individual'],
                                'doble'        => ['icon'=>'👫',  'desc'=>'2 camas / matrimonial'],
                                'suite'        => ['icon'=>'⭐',  'desc'=>'Lujo y comodidades'],
                                'presidencial' => ['icon'=>'💎',  'desc'=>'Experiencia premium'],
                            ];
                            $tipoSel = $datos['tipo'] ?? '';
                            foreach ($tipos as $val => $info): ?>
                            <div class="tipo-card <?= $tipoSel === $val ? 'selected' : '' ?>"
                                 onclick="seleccionarTipo('<?= $val ?>', this)">
                                <div class="icon"><?= $info['icon'] ?></div>
                                <div class="name"><?= ucfirst($val) ?></div>
                                <div class="desc"><?= $info['desc'] ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="tipo" id="tipoInput" value="<?= htmlspecialchars($tipoSel) ?>">
                    </div>

                    <div class="form-group">
                        <label>Precio por Noche (USD) *</label>
                        <input type="number" name="precio_noche" required step="0.01" min="0.01" placeholder="Ej: 89.99"
                               value="<?= htmlspecialchars($datos['precio_noche'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Estado inicial</label>
                        <select name="estado">
                            <option value="disponible"    <?= ($datos['estado'] ?? 'disponible') === 'disponible'    ? 'selected' : '' ?>>✅ Disponible</option>
                            <option value="mantenimiento" <?= ($datos['estado'] ?? '') === 'mantenimiento' ? 'selected' : '' ?>>🔧 En mantenimiento</option>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label>Descripción (opcional)</label>
                        <textarea name="descripcion" rows="3"
                                  placeholder="Características especiales..."><?= htmlspecialchars($datos['descripcion'] ?? '') ?></textarea>
                    </div>

                </div>

                <div class="form-footer">
                    <a href="/hotel-system/controllers/HabitacionController.php" class="btn-cancel">✕ Cancelar</a>
                    <button type="submit" class="btn-save" id="btnGuardar">💾 Guardar Habitación</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
function seleccionarTipo(tipo, el) {
    document.querySelectorAll('.tipo-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('tipoInput').value = tipo;
}

document.getElementById('formCrear').addEventListener('submit', function(e) {
    if (!document.getElementById('tipoInput').value) {
        e.preventDefault();
        alert('Por favor selecciona el tipo de habitación.');
        return;
    }
    const btn = document.getElementById('btnGuardar');
    btn.disabled = true;
    btn.textContent = '⏳ Guardando...';
});
</script>
</body>
</html>