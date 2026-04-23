<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Habitacion.php';
require_once __DIR__ . '/../../utils/url_helper.php';
 
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error de conexion - HotelManager</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f3f8fb; margin: 0; }
            .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
            .card { background: white; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.08); max-width: 640px; width: 100%; padding: 24px; }
            h1 { margin: 0 0 10px; font-size: 22px; color: #12355b; }
            p { margin: 0 0 10px; color: #444; line-height: 1.5; }
            ul { margin: 10px 0 0 20px; color: #444; }
            .muted { color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="wrap">
            <div class="card">
                <h1>No se pudo conectar a la base de datos</h1>
                <p>El panel no puede cargarse porque la conexion PDO devolvio nulo.</p>
                <p class="muted">Verifica lo siguiente:</p>
                <ul>
                    <li>MySQL de XAMPP esta iniciado.</li>
                    <li>Existe la base de datos <strong>hotel_gestion</strong>.</li>
                    <li>Usuario/clave en <strong>config/database.php</strong> son correctos para tu entorno.</li>
                </ul>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$hab = new Habitacion($db);
 
$total_hab     = $hab->leerTodas()->rowCount();
$disponibles   = $hab->leerTodas('', 'disponible')->rowCount();
$ocupadas      = $hab->leerTodas('', 'ocupada')->rowCount();

// Verificar que haya sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Verificar que sea administrador
if ($_SESSION['rol'] !== 'administrador') {
    $_SESSION['error'] = "No tienes permisos para acceder a esta sección";
    header("Location: ../auth/login.php");
    exit();
}

// Stats dinámicas de reservas y clientes
$stmt = $db->query("SELECT COUNT(*) FROM RESERVA WHERE estado IN ('pendiente','confirmada')");
$reservas_activas = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM CLIENTE");
$total_clientes = $stmt->fetchColumn();

$username = $_SESSION['username'];

// URLs absolutas dentro de la app para evitar problemas con rutas relativas.
$appBase = app_base_path();

$urlAdminDashboard = $appBase . '/views/admin/dashboard.php';
$urlHabitaciones = $appBase . '/controllers/HabitacionController.php';
$urlReservas = $appBase . '/controllers/ReservaController.php';
$urlClientes = $appBase . '/controllers/ClienteController.php';
$urlReportes = $appBase . '/controllers/ReservaController.php?accion=reportes';
$urlLogout = $appBase . '/controllers/UsuarioController.php?action=logout';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador - HotelManager</title>
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
        .menu-icon { font-size: 20px; width: 24px; }

        .main-content { margin-left: 260px; }
        .header {
            background: white; padding: 20px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        .header-title h1 { color: #333; font-size: 24px; margin-bottom: 5px; }
        .header-subtitle { color: #666; font-size: 14px; }
        .user-section { display: flex; align-items: center; gap: 15px; }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar {
            width: 40px; height: 40px; background: #1b98e0;
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; color: white; font-weight: bold;
        }
        .btn-logout {
            padding: 8px 20px; background: #e76f51; color: white;
            border: none; border-radius: 8px; cursor: pointer;
            font-size: 14px; transition: all 0.3s; text-decoration: none;
        }
        .btn-logout:hover { background: #c0392b; }

        .content-area { padding: 30px; }
        .welcome-card {
            background: linear-gradient(135deg, #1b98e0 0%, #0b2545 100%);
            color: white; padding: 40px; border-radius: 15px;
            margin-bottom: 30px; box-shadow: 0 10px 30px rgba(102,126,234,0.3);
        }
        .welcome-card h2 { font-size: 32px; margin-bottom: 10px; }
        .welcome-card p { font-size: 16px; opacity: 0.9; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        .stat-card {
            background: white; padding: 25px; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .stat-icon { font-size: 42px; margin-bottom: 15px; }
        .stat-value { font-size: 36px; font-weight: bold; color: #333; margin-bottom: 5px; }
        .stat-label { color: #666; font-size: 14px; }

        .quick-actions {
            background: white; padding: 25px; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .quick-actions h3 { color: #333; margin-bottom: 20px; }
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .action-btn {
            padding: 20px; background: #f8f9fa;
            border: 2px solid #e1e8ed; border-radius: 10px;
            text-align: center; cursor: pointer; transition: all 0.3s;
            text-decoration: none; color: #333;
        }
        .action-btn:hover {
            background: #1b98e0; color: white;
            border-color: #1b98e0; transform: translateY(-3px);
        }
        .action-icon { font-size: 32px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-section">
            <div class="logo">🏨 HotelManager</div>
            <div class="role">Panel de Administración</div>
        </div>
        
        <a href="<?= htmlspecialchars($urlAdminDashboard) ?>" class="menu-item active">
            <span class="menu-icon">📊</span>
            <span>Dashboard</span>
        </a>
        <a href="<?= htmlspecialchars($urlHabitaciones) ?>" class="menu-item">
            <span class="menu-icon">🛏️</span>
            <span>Habitaciones</span>
        </a>
        <!-- ✅ Reservas — enlace real -->
        <a href="<?= htmlspecialchars($urlReservas) ?>" class="menu-item">
            <span class="menu-icon">📅</span>
            <span>Reservas</span>
        </a>
        <!-- ✅ Clientes — enlace real -->
        <a href="<?= htmlspecialchars($urlClientes) ?>" class="menu-item">
            <span class="menu-icon">👥</span>
            <span>Clientes</span>
        </a>
        <a href="<?= htmlspecialchars($urlReportes) ?>" class="menu-item">
            <span class="menu-icon">📈</span>
            <span>Reportes</span>
        </a>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="header-title">
                <h1>Panel de Administración</h1>
                <div class="header-subtitle">📅 <?php echo date('l, d \d\e F \d\e Y'); ?></div>
            </div>
            <div class="user-section">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 2)); ?></div>
                    <div>
                        <div style="font-weight:600;color:#333"><?php echo htmlspecialchars($username); ?></div>
                        <div style="font-size:12px;color:#666">Administrador</div>
                    </div>
                </div>
                <a href="<?= htmlspecialchars($urlLogout) ?>" class="btn-logout">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="content-area">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <h2>¡Bienvenido, <?php echo htmlspecialchars($username); ?>! 👋</h2>
                <p>Este es tu panel de administración. Desde aquí puedes gestionar todo el sistema del hotel.</p>
            </div>
            
            <!-- Stats — todas dinámicas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🛏️</div>
                    <div class="stat-value"><?= $total_hab ?></div>
                    <div class="stat-label">Total Habitaciones</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-value"><?= $reservas_activas ?></div>
                    <div class="stat-label">Reservas Activas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?= $total_clientes ?></div>
                    <div class="stat-label">Clientes Registrados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value"><?= $total_hab > 0 ? round(($ocupadas / $total_hab) * 100) : 0 ?>%</div>
                    <div class="stat-label">Ocupación Actual</div>
                </div>
            </div>
            
            <!-- Acciones rápidas — todas con enlaces reales -->
            <div class="quick-actions">
                <h3>⚡ Acciones Rápidas</h3>
                <div class="actions-grid">
                    <a href="<?= htmlspecialchars($urlReservas) ?>?accion=crear" class="action-btn">
                        <div class="action-icon">➕</div>
                        <div>Nueva Reserva</div>
                    </a>
                    <a href="<?= htmlspecialchars($urlHabitaciones) ?>" class="action-btn">
                        <div class="action-icon">🛏️</div>
                        <div>Gestionar Habitaciones</div>
                    </a>
                    <a href="<?= htmlspecialchars($urlClientes) ?>?accion=crear" class="action-btn">
                        <div class="action-icon">👤</div>
                        <div>Nuevo Cliente</div>
                    </a>
                    <a href="<?= htmlspecialchars($urlReservas) ?>" class="action-btn">
                        <div class="action-icon">📊</div>
                        <div>Ver Reservas</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
