<?php
session_start();

// Verificar que haya sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Verificar que sea cliente
if ($_SESSION['rol'] !== 'cliente') {
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
    <title>Dashboard Cliente - HotelManager</title>
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
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .user-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-weight: bold;
        }
        
        .btn-logout {
            padding: 8px 20px;
            background: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-logout:hover {
            background: white;
            color: #667eea;
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* Welcome */
        .welcome {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .welcome h1 {
            color: #333;
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
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .info-text {
            color: #666;
            line-height: 1.6;
        }
        
        .btn-primary {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">🏨 HotelManager</div>
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 2)); ?></div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($username); ?></div>
                    <div style="font-size: 12px; opacity: 0.9;">Cliente</div>
                </div>
            </div>
            <a href="/hotel-system/controllers/UsuarioController.php?action=logout" class="btn-logout">
                Cerrar Sesión
            </a>
        </div>
    </div>
    
    <!-- Container -->
    <div class="container">
        <!-- Success Message -->
        <div class="success-message">
            ✅ <strong>¡Login exitoso!</strong> Bienvenido a tu panel de cliente.
        </div>
        
        <!-- Welcome -->
        <div class="welcome">
            <h1>¡Bienvenido, <?php echo htmlspecialchars($username); ?>! 👋</h1>
            <p class="info-text">Gestiona tus reservas y descubre nuestros servicios</p>
        </div>
        
        <!-- Sección de Búsqueda -->
        <div class="section">
            <h2>🔍 Buscar Habitaciones</h2>
            <p class="info-text" style="margin-bottom: 15px;">
                Esta funcionalidad estará disponible próximamente. Podrás buscar habitaciones disponibles por fechas y tipo.
            </p>
            <a href="#" class="btn-primary">Buscar Disponibilidad</a>
        </div>
        
        <!-- Mis Reservas -->
        <div class="section">
            <h2>📅 Mis Reservas</h2>
            <p class="info-text" style="margin-bottom: 15px;">
                Aquí aparecerán tus reservas. El módulo de reservas se implementará próximamente.
            </p>
            <a href="#" class="btn-primary">Ver Todas las Reservas</a>
        </div>
        
        <!-- Servicios -->
        <div class="section">
            <h2>⭐ Servicios Adicionales</h2>
            <p class="info-text" style="margin-bottom: 15px;">
                Descubre nuestros servicios adicionales: desayuno, spa, transporte y más.
            </p>
            <a href="#" class="btn-primary">Ver Servicios</a>
        </div>
    </div>
</body>
</html>