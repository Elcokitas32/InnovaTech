<?php
require_once 'conexion.php';

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$mantenimientoId = $data['mantenimiento_id'] ?? '';

if (empty($mantenimientoId)) {
    echo json_encode(['success' => false, 'message' => 'Falta el ID del mantenimiento']);
    exit;
}

try {
    // Primero verificar si el mantenimiento existe
    $stmt = $pdo->prepare("SELECT id FROM mantenimientos WHERE id = ?");
    $stmt->execute([$mantenimientoId]);
    $mantenimiento = $stmt->fetch();
    
    if (!$mantenimiento) {
        echo json_encode(['success' => false, 'message' => 'El mantenimiento no existe']);
        exit;
    }
    
    // Eliminar registros relacionados (informes) primero
    $stmt = $pdo->prepare("DELETE FROM informes WHERE mantenimiento_id = ?");
    $stmt->execute([$mantenimientoId]);
    
    // Eliminar el mantenimiento
    $stmt = $pdo->prepare("DELETE FROM mantenimientos WHERE id = ?");
    $stmt->execute([$mantenimientoId]);
    
    echo json_encode(['success' => true, 'message' => 'Mantenimiento eliminado correctamente']);
    
} catch (PDOException $e) {
    error_log('Error en delete_maintenance.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
}
?>
