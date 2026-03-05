<?php
/**
 * test-conexion.php
 * Script para verificar la conexión a la base de datos
 * 
 * INSTRUCCIONES:
 * 1. Copia este archivo a la RAÍZ de tu proyecto (hotel-system/)
 * 2. Abre en navegador: http://localhost/hotel-system/test-conexion.php
 * 3. Verifica los resultados
 * 4. ELIMINA este archivo después de usarlo (por seguridad)
 */

echo "<h1>🔍 Diagnóstico del Sistema</h1>";
echo "<hr>";

// TEST 1: Verificar que PHP funciona
echo "<h2>✓ PHP está funcionando</h2>";
echo "Versión de PHP: " . phpversion() . "<br><br>";

// TEST 2: Verificar que el archivo database.php existe
echo "<h2>TEST 2: Verificar archivo database.php</h2>";
if (file_exists('config/database.php')) {
    echo "✓ Archivo config/database.php encontrado<br><br>";
    require_once 'config/database.php';
} else {
    echo "✗ ERROR: No se encuentra config/database.php<br>";
    echo "Verifica que el archivo esté en la carpeta config/<br><br>";
    exit();
}

// TEST 3: Intentar conectar a la base de datos
echo "<h2>TEST 3: Conexión a Base de Datos</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✓ Conexión exitosa a la base de datos<br><br>";
    } else {
        echo "✗ ERROR: No se pudo conectar a la base de datos<br>";
        echo "Verifica que MySQL esté corriendo en XAMPP<br><br>";
        exit();
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br><br>";
    exit();
}

// TEST 4: Verificar que la base de datos hotel_gestion existe
echo "<h2>TEST 4: Verificar Base de Datos</h2>";
try {
    $query = "SELECT DATABASE() as db_name";
    $stmt = $db->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['db_name'] === 'hotel_gestion') {
        echo "✓ Base de datos correcta: hotel_gestion<br><br>";
    } else {
        echo "✗ ERROR: Base de datos incorrecta: " . $result['db_name'] . "<br>";
        echo "Se esperaba: hotel_gestion<br><br>";
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br><br>";
}

// TEST 5: Verificar que la tabla USUARIO existe
echo "<h2>TEST 5: Verificar Tabla USUARIO</h2>";
try {
    $query = "SHOW TABLES LIKE 'USUARIO'";
    $stmt = $db->query($query);
    
    if ($stmt->rowCount() > 0) {
        echo "✓ Tabla USUARIO existe<br><br>";
    } else {
        echo "✗ ERROR: Tabla USUARIO no existe<br>";
        echo "¿Ejecutaste el script crear_base_datos.sql en phpMyAdmin?<br><br>";
        exit();
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br><br>";
}

// TEST 6: Verificar que existe el usuario admin
echo "<h2>TEST 6: Verificar Usuario Admin</h2>";
try {
    $query = "SELECT usuario_id, username, rol FROM USUARIO WHERE username = 'admin' LIMIT 1";
    $stmt = $db->query($query);
    
    if ($stmt->rowCount() > 0) {
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Usuario admin encontrado<br>";
        echo "ID: " . $usuario['usuario_id'] . "<br>";
        echo "Username: " . $usuario['username'] . "<br>";
        echo "Rol: " . $usuario['rol'] . "<br><br>";
    } else {
        echo "✗ ERROR: Usuario admin no existe<br>";
        echo "Ejecuta este SQL en phpMyAdmin:<br>";
        echo "<pre>";
        echo "INSERT INTO USUARIO (username, password, rol) VALUES \n";
        echo "('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador');";
        echo "</pre><br>";
        exit();
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br><br>";
}

// TEST 7: Verificar el hash de la contraseña
echo "<h2>TEST 7: Verificar Hash de Contraseña</h2>";
try {
    $query = "SELECT password FROM USUARIO WHERE username = 'admin' LIMIT 1";
    $stmt = $db->query($query);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $password_prueba = "password";
    
    if (password_verify($password_prueba, $usuario['password'])) {
        echo "✓ La contraseña 'password' es correcta para el usuario admin<br><br>";
    } else {
        echo "✗ ERROR: El hash de la contraseña no coincide<br>";
        echo "Hash en BD: " . substr($usuario['password'], 0, 30) . "...<br>";
        echo "Ejecuta este SQL para arreglar:<br>";
        echo "<pre>";
        echo "UPDATE USUARIO SET password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' \n";
        echo "WHERE username = 'admin';";
        echo "</pre><br>";
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br><br>";
}

// TEST 8: Verificar el modelo Usuario
echo "<h2>TEST 8: Verificar Modelo Usuario</h2>";
if (file_exists('models/Usuario.php')) {
    echo "✓ Archivo models/Usuario.php encontrado<br>";
    require_once 'models/Usuario.php';
    
    try {
        $usuario = new Usuario($db);
        echo "✓ Clase Usuario instanciada correctamente<br><br>";
    } catch (Exception $e) {
        echo "✗ ERROR al crear instancia de Usuario: " . $e->getMessage() . "<br><br>";
    }
} else {
    echo "✗ ERROR: No se encuentra models/Usuario.php<br><br>";
}

// TEST 9: Probar login con credenciales correctas
echo "<h2>TEST 9: Simular Login</h2>";
try {
    $usuario = new Usuario($db);
    $usuario->username = 'admin';
    $usuario->password = 'password';
    
    $resultado = $usuario->login();
    
    if ($resultado) {
        echo "✓ Login EXITOSO con admin/password<br>";
        echo "Datos retornados:<br>";
        echo "<pre>";
        print_r($resultado);
        echo "</pre><br>";
    } else {
        echo "✗ ERROR: Login FALLÓ con admin/password<br>";
        echo "Hay un problema en el método login() de la clase Usuario<br><br>";
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br><br>";
}

// TEST 10: Probar login con credenciales incorrectas
echo "<h2>TEST 10: Probar Credenciales Incorrectas</h2>";
try {
    $usuario = new Usuario($db);
    $usuario->username = 'admin';
    $usuario->password = 'password_incorrecta';
    
    $resultado = $usuario->login();
    
    if ($resultado === false) {
        echo "✓ Login rechazado correctamente con contraseña incorrecta<br><br>";
    } else {
        echo "✗ ERROR: Login NO rechazó contraseña incorrecta<br>";
        echo "Problema de seguridad: cualquier contraseña funciona<br><br>";
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br><br>";
}

echo "<hr>";
echo "<h2>🎯 RESUMEN</h2>";
echo "<p>Si todos los tests pasaron (✓), el sistema debería funcionar correctamente.</p>";
echo "<p style='color: red;'><strong>⚠️ IMPORTANTE: ELIMINA este archivo después de usarlo por seguridad.</strong></p>";
?>