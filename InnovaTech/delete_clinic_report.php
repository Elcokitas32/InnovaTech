<?php
require_once 'conexion.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);
$informeId = $data['informe_id'] ?? '';

if (empty($informeId)) {
    echo json_encode(['success' => false, 'message' => 'ID de informe no proporcionado']);
    exit;
}

try {
    // Primero eliminar el archivo adjunto si existe
    $stmt = $pdo->prepare("SELECT archivo_adjunto, fecha_informe FROM informes_clinica WHERE id = ?");
    $stmt->execute([$informeId]);
    $informe = $stmt->fetch();
    
    if ($informe && $informe['archivo_adjunto']) {
        $filePath = 'uploads/informes/' . date('Y/m', strtotime($informe['fecha_informe'])) . '/' . $informe['archivo_adjunto'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Eliminar el informe de la base de datos
    $stmt = $pdo->prepare("DELETE FROM informes_clinica WHERE id = ?");
    $result = $stmt->execute([$informeId]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Informe eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el informe']);
    }
    
} catch (PDOException $e) {
    error_log('Error en delete_clinic_report.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
}
?>
