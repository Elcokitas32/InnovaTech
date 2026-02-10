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
$estado = $data['estado'] ?? '';
$fechaCompletado = $data['fecha_completado'] ?? null;
$fechaProgramada = $data['fecha_programada'] ?? null;

if (empty($mantenimientoId) || empty($estado)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

try {
    // Construir la consulta SQL dinámicamente
    $sql = "UPDATE mantenimientos SET estado = ?";
    $params = [$estado];
    
    if ($fechaCompletado) {
        $sql .= ", fecha_completado = ?";
        $params[] = $fechaCompletado;
    }
    
    if ($fechaProgramada) {
        $sql .= ", fecha_programada = ?";
        $params[] = $fechaProgramada;
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $mantenimientoId;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode(['success' => true, 'message' => 'Mantenimiento actualizado correctamente']);
    
} catch (PDOException $e) {
    error_log('Error en update_maintenance_status.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
}
?>
