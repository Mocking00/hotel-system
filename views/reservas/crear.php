<?php
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /hotel-system/views/auth/login.php");
    exit();
}

$username = $_SESSION['username'];
$rol = $_SESSION['rol'];
$es_cliente = $rol === 'cliente';
$es_recepcion = $rol === 'recepcionista';
$dashboard_url = $es_recepcion
    ? '/hotel-system/views/recepcionista/dashboard.php'
    : '/hotel-system/views/admin/dashboard.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Reserva - HotelManager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }

        .sidebar {
            width: 260px; background: #2c3e50; color: white;
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
            border-left-color: #3498db;
        }

        .topbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 14px 24px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 3px 14px rgba(102,126,234,0.35);
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
        .form-card h2 {
            color: #333; font-size: 18px; margin-bottom: 25px;
            padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;
        }

        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
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

        .habitaciones-wrap {
            border: 1px dashed #ccd8e6; border-radius: 10px;
            padding: 14px; background: #f9fbff;
        }
        .habitaciones-wrap h3 { font-size: 14px; color: #2c3e50; margin-bottom: 8px; }
        .hint { font-size: 12px; color: #667; margin-top: 5px; }

        .resumen {
            margin-top: 10px; background: #ebf5fb;
            border: 1px solid #bcdff6; border-radius: 8px;
            padding: 10px 12px; font-size: 13px; color: #24536a;
        }

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
        .btn-save:disabled { background: #95a5a6; cursor: not-allowed; }

        @media (max-width: 980px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .form-grid { grid-template-columns: 1fr; }
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
    <a href="<?= $dashboard_url ?>" class="menu-item">📊 Dashboard</a>
    <a href="/hotel-system/controllers/HabitacionController.php" class="menu-item">🛏️ Habitaciones</a>
    <a href="/hotel-system/controllers/ReservaController.php" class="menu-item active">📅 Reservas</a>
    <a href="/hotel-system/controllers/ClienteController.php" class="menu-item">👥 Clientes</a>
    <?php if (!$es_recepcion): ?>
    <a href="/hotel-system/controllers/ReservaController.php?accion=reportes" class="menu-item">📈 Reportes</a>
    <?php endif; ?>
    <a href="/hotel-system/controllers/UsuarioController.php?action=logout" class="menu-item">🚪 Cerrar Sesion</a>
</div>
<?php else: ?>
<div class="topbar">
    <div class="left">🏨 Nueva Reserva</div>
    <div class="right">
        <div><?= htmlspecialchars($username) ?></div>
        <a href="/hotel-system/views/cliente/dashboard.php" class="btn">Panel</a>
        <a href="/hotel-system/controllers/UsuarioController.php?action=logout" class="btn">Salir</a>
    </div>
</div>
<?php endif; ?>

<div class="main-content <?= $es_cliente ? 'full' : '' ?>">
    <div class="header">
        <div>
            <h1>➕ Crear Reserva</h1>
            <div class="header-subtitle">Selecciona fechas, cliente y habitacion disponible</div>
        </div>
        <div style="font-size:13px;color:#666">Usuario: <strong><?= htmlspecialchars($username) ?></strong></div>
    </div>

    <div class="content-area">
        <div class="breadcrumb">
            <a href="/hotel-system/controllers/ReservaController.php">📅 Reservas</a> &rsaquo; Nueva
        </div>

        <?php if (!empty($errores)): ?>
        <div class="alert-error">
            <strong>Corrige los siguientes errores:</strong>
            <ul>
                <?php foreach ($errores as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="form-card">
            <h2>Datos de la Reserva</h2>

            <form method="POST" action="/hotel-system/controllers/ReservaController.php?accion=crear" id="formReserva">
                <div class="form-grid">

                    <?php if (!$es_cliente): ?>
                    <div class="form-group full">
                        <label>Cliente *</label>
                        <select name="cliente_id" required>
                            <option value="">Selecciona un cliente</option>
                            <?php foreach ($clientes as $c):
                                $cid = (string) $c['cliente_id'];
                                $seleccionado = ($datos['cliente_id'] ?? '') === $cid;
                            ?>
                            <option value="<?= $cid ?>" <?= $seleccionado ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['apellido'] . ', ' . $c['nombre']) ?> -
                                <?= htmlspecialchars($c['cedula']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="cliente_id" value="<?= htmlspecialchars($datos['cliente_id']) ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Fecha de entrada *</label>
                        <input type="date" name="fecha_entrada" id="fecha_entrada" required
                               value="<?= htmlspecialchars($datos['fecha_entrada'] ?? '') ?>"
                               min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label>Fecha de salida *</label>
                        <input type="date" name="fecha_salida" id="fecha_salida" required
                               value="<?= htmlspecialchars($datos['fecha_salida'] ?? '') ?>"
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>

                    <div class="form-group">
                        <label>Numero de personas *</label>
                        <input type="number" name="numero_personas" id="numero_personas" required min="1" max="12"
                               value="<?= htmlspecialchars($datos['numero_personas'] ?? '1') ?>">
                    </div>

                    <div class="form-group">
                        <label>Tipo de habitacion (opcional)</label>
                        <select name="tipo_habitacion" id="tipo_habitacion">
                            <option value="">Cualquier tipo</option>
                            <?php foreach (['simple','doble','suite','presidencial'] as $tipo): ?>
                            <option value="<?= $tipo ?>" <?= ($datos['tipo_habitacion'] ?? '') === $tipo ? 'selected' : '' ?>>
                                <?= ucfirst($tipo) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group full habitaciones-wrap">
                        <h3>Habitaciones disponibles *</h3>
                        <select name="habitacion_id" id="habitacion_id" required>
                            <option value="">Selecciona una habitacion</option>
                            <?php foreach ($habitaciones_disponibles as $h):
                                $hid = (string) $h['habitacion_id'];
                                $sel = ($datos['habitacion_id'] ?? '') === $hid;
                            ?>
                            <option value="<?= $hid ?>"
                                    data-precio="<?= htmlspecialchars($h['precio_noche']) ?>"
                                    data-capacidad="<?= htmlspecialchars($h['capacidad']) ?>"
                                    <?= $sel ? 'selected' : '' ?>>
                                #<?= htmlspecialchars($h['numero']) ?> | <?= ucfirst(htmlspecialchars($h['tipo'])) ?> | <?= (int) $h['capacidad'] ?> pers. | $<?= number_format((float) $h['precio_noche'], 2) ?>/noche
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="hint">Solo se muestran habitaciones libres para el rango de fechas seleccionado.</div>
                        <div class="resumen" id="resumenTotal">Selecciona una habitacion para ver precio estimado.</div>
                    </div>

                    <div class="form-group full">
                        <label>Notas especiales (opcional)</label>
                        <textarea name="notas_especiales" rows="3" placeholder="Ej: llegada tardia, requerimientos especiales..."><?= htmlspecialchars($datos['notas_especiales'] ?? '') ?></textarea>
                    </div>

                </div>

                <div class="form-footer">
                    <a href="/hotel-system/controllers/ReservaController.php" class="btn-cancel">Cancelar</a>
                    <button type="submit" class="btn-save" id="btnGuardar">Guardar Reserva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function diasEntre(fechaEntrada, fechaSalida) {
    if (!fechaEntrada || !fechaSalida) return 0;
    const a = new Date(fechaEntrada + 'T00:00:00');
    const b = new Date(fechaSalida + 'T00:00:00');
    const diff = b.getTime() - a.getTime();
    return Math.floor(diff / (1000 * 60 * 60 * 24));
}

function actualizarResumen() {
    const entrada = document.getElementById('fecha_entrada').value;
    const salida = document.getElementById('fecha_salida').value;
    const selectHab = document.getElementById('habitacion_id');
    const opcion = selectHab.options[selectHab.selectedIndex];
    const resumen = document.getElementById('resumenTotal');

    const noches = diasEntre(entrada, salida);
    if (!opcion || !opcion.value || noches <= 0) {
        resumen.textContent = 'Selecciona una habitacion valida y un rango de fechas correcto para calcular el total.';
        return;
    }

    const precio = parseFloat(opcion.dataset.precio || '0');
    const total = precio * noches;
    resumen.textContent = 'Estimado: ' + noches + ' noche(s) x $' + precio.toFixed(2) + ' = $' + total.toFixed(2);
}

async function recargarHabitaciones() {
    const entrada = document.getElementById('fecha_entrada').value;
    const salida = document.getElementById('fecha_salida').value;
    const personas = document.getElementById('numero_personas').value;
    const tipo = document.getElementById('tipo_habitacion').value;
    const select = document.getElementById('habitacion_id');

    if (!entrada || !salida || !personas) {
        return;
    }

    const url = '/hotel-system/controllers/ReservaController.php?accion=api_habitaciones'
        + '&fecha_entrada=' + encodeURIComponent(entrada)
        + '&fecha_salida=' + encodeURIComponent(salida)
        + '&numero_personas=' + encodeURIComponent(personas)
        + '&tipo=' + encodeURIComponent(tipo);

    try {
        const res = await fetch(url);
        const data = await res.json();

        select.innerHTML = '<option value="">Selecciona una habitacion</option>';
        data.forEach(h => {
            const opt = document.createElement('option');
            opt.value = h.habitacion_id;
            opt.dataset.precio = h.precio_noche;
            opt.dataset.capacidad = h.capacidad;
            opt.textContent = '#'+h.numero+' | '+h.tipo.charAt(0).toUpperCase()+h.tipo.slice(1)
                +' | '+h.capacidad+' pers. | $'+Number(h.precio_noche).toFixed(2)+'/noche';
            select.appendChild(opt);
        });

        if (data.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'No hay habitaciones disponibles para esos filtros';
            select.appendChild(opt);
        }

        actualizarResumen();
    } catch (err) {
        console.error(err);
    }
}

['fecha_entrada','fecha_salida','numero_personas','tipo_habitacion'].forEach(id => {
    document.getElementById(id).addEventListener('change', recargarHabitaciones);
});
document.getElementById('habitacion_id').addEventListener('change', actualizarResumen);

actualizarResumen();

document.getElementById('formReserva').addEventListener('submit', function(e) {
    const noches = diasEntre(
        document.getElementById('fecha_entrada').value,
        document.getElementById('fecha_salida').value
    );
    if (noches <= 0) {
        e.preventDefault();
        alert('La fecha de salida debe ser posterior a la fecha de entrada.');
        return;
    }

    const btn = document.getElementById('btnGuardar');
    btn.disabled = true;
    btn.textContent = 'Guardando...';
});
</script>
</body>
</html>
