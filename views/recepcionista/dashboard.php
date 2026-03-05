<?php
session_start();

// Verificar que haya sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Verificar que sea recepcionista
if ($_SESSION['rol'] !== 'recepcionista') {
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
    <title>Dashboard Recepcionista - HotelManager</title>
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
        
        /* Similar styles to admin dashboard but adapted for receptionist */
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
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .welcome {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .btn-logout {
            padding: 8px 20px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">
            <h1>🏨 Panel de Recepción</h1>
        </div>
        <a href="/hotel-system/controllers/UsuarioController.php?action=logout" class="btn-logout">Cerrar Sesión</a>
    </div>
    
    <div class="container">
        <div class="success-message">
            ✅ <strong>¡Login exitoso!</strong> Bienvenido al panel de recepción.
        </div>
        
        <div class="welcome">
            <h2>¡Bienvenido, <?php echo htmlspecialchars($username); ?>! 👋</h2>
            <p>Panel de operaciones de recepción. Los módulos estarán disponibles próximamente.</p>
        </div>
    </div>
</body>
</html>