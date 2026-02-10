<?php
require_once 'conexion.php';

session_destroy();

// Limpiar cookie antigua de auto-login si existiera
setcookie('remember_token', '', time() - 3600, '/', '', false, true);

redirect('index.php');
?>
