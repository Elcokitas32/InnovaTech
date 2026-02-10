<?php
// Conexión directa a la base de datos
$host = 'localhost';
$dbname = 'innovatech_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Iniciar sesión
session_start();

// Función para redirigir
function redirect($url) {
    header("Location: $url");
    exit;
}

// Función para verificar si está logueado
function isLoggedIn() {
    // Verificar sesión primero
    if (isset($_SESSION['user'])) {
        return true;
    }
    
    return false;
}

// Función para obtener usuario actual
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

// Función para limpiar datos
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>
