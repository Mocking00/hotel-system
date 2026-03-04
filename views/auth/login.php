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
        
        .language-selector {
            text-align: center;
            margin-top: 20px;
        }
        
        .language-selector select {
            padding: 8px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 13px;
        }
        
        .wireframe-note {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
            color: #856404;
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
        
        <form method="POST" action="../../controllers/UsuarioController.php">
            <div class="form-group">
                <label for="username">Usuario o Email</label>
                <input type="text" id="username" name="username" placeholder="Ingrese su usuario o email" required>
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
                <a href="#" onclick="alert('Funcionalidad de recuperación de contraseña'); return false;">¿Olvidaste tu contraseña?</a>
            </div>
            
            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>
        
        <div class="divider">o</div>
        
        <div class="register-link">
            ¿No tienes cuenta? <a href="#" onclick="alert('Redirigir a formulario de registro'); return false;">Regístrate aquí</a>
        </div>
        
        <div class="language-selector">
            <select onchange="alert('Cambiar idioma a: ' + this.value)">
                <option value="es">🇩🇴 Español</option>
                <option value="en">🇺🇸 English</option>
            </select>
        </div>
        
        <div class="wireframe-note">
            📋 <strong>Wireframe - Pantalla de Login</strong><br>
            Este es un prototipo interactivo básico del sistema de autenticación.
        </div>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (username && password) {
                alert('¡Login exitoso!\\n\\nUsuario: ' + username + '\\n\\nEn la aplicación real, aquí se validarían las credenciales y se redirigiría al dashboard correspondiente según el rol del usuario.');
            } else {
                alert('Por favor, complete todos los campos.');
            }
        });
    </script>
</body>
</html>