<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Habitacion.php';
 
$database = new Database();
$db = $database->getConnection();
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

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador - HotelManager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #2c3e50;
            color: white;
            min-height: 100vh;
            padding: 20px 0;
            position: fixed;
            left: 0;
            top: 0;
        }
        
        .logo-section {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .role {
            font-size: 13px;
            opacity: 0.8;
        }
        
        .menu-item {
            padding: 15px 20px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid transparent;
            text-decoration: none;
            color: white;
        }
        
        .menu-item:hover,
        .menu-item.active {
            background: rgba(255,255,255,0.1);
            border-left-color: #3498db;
        }
        
        .menu-icon {
            font-size: 20px;
            width: 24px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 0;
        }
        
        /* Header */
        .header {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header-subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .btn-logout {
            padding: 8px 20px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-logout:hover {
            background: #c0392b;
        }
        
        /* Content Area */
        .content-area {
            padding: 30px;
        }
        
        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .welcome-card h2 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            font-size: 42px;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        /* Quick Actions */
        .quick-actions {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .quick-actions h3 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            padding: 20px;
            background: #f8f9fa;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }
        
        .action-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-3px);
        }
        
        .action-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-section">
            <div class="logo">🏨 HotelManager</div>
            <div class="role">Panel de Administración</div>
        </div>
        
        <a href="dashboard.php" class="menu-item active">
            <span class="menu-icon">📊</span>
            <span>Dashboard</span>
        </a>
        <a href="/hotel-system/controllers/HabitacionController.php" class="menu-item">
            <span class="menu-icon">🛏️</span>
            <span>Habitaciones</span>
        </a>
        <a href="#" class="menu-item">
            <span class="menu-icon">📅</span>
            <span>Reservas</span>
        </a>
        <a href="#" class="menu-item">
            <span class="menu-icon">👥</span>
            <span>Usuarios</span>
        </a>
        <a href="#" class="menu-item">
            <span class="menu-icon">⭐</span>
            <span>Servicios</span>
        </a>
        <a href="#" class="menu-item">
            <span class="menu-icon">📈</span>
            <span>Reportes</span>
        </a>
        <a href="#" class="menu-item">
            <span class="menu-icon">⚙️</span>
            <span>Configuración</span>
        </a>
        <a href="/hotel-system/controllers/UsuarioController.php?action=logout" class="menu-item">
            <span class="menu-icon">🚪</span>
            <span>Cerrar Sesión</span>
        </a>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Panel de Administración</h1>
                <div class="header-subtitle">📅 <?php echo date('l, d \d\e F \d\e Y'); ?></div>
            </div>
            <div class="user-section">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 2)); ?></div>
                    <div>
                        <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($username); ?></div>
                        <div style="font-size: 12px; color: #666;">Administrador</div>
                    </div>
                </div>
                <a href="/hotel-system/controllers/UsuarioController.php?action=logout" class="btn-logout">Cerrar Sesión</a>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            <!-- Success Message -->
            <div class="success-message">
                ✅ <strong>¡Login exitoso!</strong> Bienvenido al sistema de gestión hotelera.
            </div>
            
            <!-- Welcome Card -->
            <div class="welcome-card">
                <h2>¡Bienvenido, <?php echo htmlspecialchars($username); ?>! 👋</h2>
                <p>Este es tu panel de administración. Desde aquí puedes gestionar todo el sistema del hotel.</p>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🛏️</div>
                    <div class="stat-value"><?= $total_hab ?></div>
                    <div class="stat-label">Total Habitaciones</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-value">18</div>
                    <div class="stat-label">Reservas Activas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value">42</div>
                    <div class="stat-label">Clientes Registrados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value"><?= $total_hab > 0 ? round(($ocupadas / $total_hab) * 100) : 0 ?>%</div>
                    <div class="stat-label">Ocupación Actual</div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>⚡ Acciones Rápidas</h3>
                <div class="actions-grid">
                    <a href="#" class="action-btn">
                        <div class="action-icon">➕</div>
                        <div>Nueva Reserva</div>
                    </a>
                    <a href="/hotel-system/controllers/HabitacionController.php" class="action-btn">
                        <div class="action-icon">🛏️</div>
                        <div>Gestionar Habitaciones</div>
                    </a>
                    <a href="#" class="action-btn">
                        <div class="action-icon">👤</div>
                        <div>Nuevo Usuario</div>
                    </a>
                    <a href="#" class="action-btn">
                        <div class="action-icon">📊</div>
                        <div>Ver Reportes</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>