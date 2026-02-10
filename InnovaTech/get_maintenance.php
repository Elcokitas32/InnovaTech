<?php
require_once 'conexion.php';

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

$maintenanceId = $_GET['id'] ?? '';

if (empty($maintenanceId)) {
    echo json_encode(['success' => false, 'message' => 'ID de mantenimiento no proporcionado']);
    exit;
}

try {
    // Obtener datos del mantenimiento con información de clínica y técnico
    $stmt = $pdo->prepare("
        SELECT m.*, c.nombre as clinica_nombre, u.name as tecnico_nombre 
        FROM mantenimientos m 
        LEFT JOIN clinicas c ON m.clinica_id = c.id 
        LEFT JOIN usuarios u ON m.tecnico_id = u.id 
        WHERE m.id = ?
    ");
    $stmt->execute([$maintenanceId]);
    $mantenimiento = $stmt->fetch();
    
    if (!$mantenimiento) {
        echo json_encode(['success' => false, 'message' => 'Mantenimiento no encontrado']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'mantenimiento' => $mantenimiento
    ]);
    
} catch (PDOException $e) {
    error_log('Error en get_maintenance.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
}
?>
