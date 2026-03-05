<?php
session_start();

// Si ya hay sesión activa, redirigir
if (isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Obtener mensajes de error o éxito
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
$debug = $_SESSION['debug'] ?? '';

// Limpiar mensajes de la sesión
unset($_SESSION['error']);
unset($_SESSION['success']);
unset($_SESSION['debug']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Hotelero</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            padding: 50px 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-debug {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            font-size: 12px;
            white-space: pre-wrap;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .remember-forgot label {
            display: flex;
            align-items: center;
            color: #666;
            cursor: pointer;
        }
        
        .remember-forgot input[type="checkbox"] {
            margin-right: 8px;
            cursor: pointer;
        }
        
        .remember-forgot a {
            color: #667eea;
            text-decoration: none;
        }
        
        .remember-forgot a:hover {
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            color: #999;
            font-size: 14px;
            position: relative;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #ddd;
        }
        
        .divider::before {
            left: 0;
        }
        
        .divider::after {
            right: 0;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .demo-credentials {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
        }
        
        .demo-credentials strong {
            color: #856404;
        }
        
        .test-button {
            width: 100%;
            padding: 10px;
            background: #17a2b8;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            margin-top: 15px;
        }
        
        .test-button:hover {
            background: #138496;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 25px;
            }
            
            .logo h1 {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🏨 HotelManager</h1>
            <p>Sistema de Gestión Hotelera</p>
        </div>
        
        <?php if ($debug): ?>
            <div class="alert alert-debug">
                🔍 Debug Info:
                <?php echo htmlspecialchars($debug); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/hotel-system/controllers/UsuarioController.php?action=login">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" placeholder="Ingrese su usuario" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required>
            </div>
            
            <div class="remember-forgot">
                <label>
                    <input type="checkbox" name="remember">
                    Recordarme
                </label>
                <a href="#">¿Olvidaste tu contraseña?</a>
            </div>
            
            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>
        
        <button class="test-button" onclick="window.location.href='../../test-conexion.php'">
            🔧 Ejecutar Test de Diagnóstico
        </button>
        
        <div class="divider">o</div>
        
        <div class="register-link">
            ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
        </div>
        
        <div class="demo-credentials">
            <strong>🔑 Credenciales de Prueba:</strong><br>
            Usuario: <code>admin</code><br>
            Contraseña: <code>password</code>
        </div>
    </div>
</body>
</html>