<?php
session_start();

// Verificar si hay sesión activa
if (isset($_SESSION['usuario_id'])) {
    // Redirigir según el rol
    switch ($_SESSION['rol']) {
        case 'cliente':
            header("Location: views/cliente/dashboard.php");
            break;
        case 'recepcionista':
            header("Location: views/recepcionista/dashboard.php");
            break;
        case 'administrador':
            header("Location: views/admin/dashboard.php");
            break;
    }
    exit();
} else {
    // Redirigir al login
    header("Location: views/auth/login.php");
    exit();
}
?>