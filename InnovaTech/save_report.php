<?php
require_once 'conexion.php';

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

// Obtener datos del formulario
$clinicId = $_POST['clinic_id'] ?? '';
$titulo = $_POST['titulo'] ?? '';
$contenido = $_POST['contenido'] ?? '';

if (empty($clinicId) || empty($titulo) || empty($contenido)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
    exit;
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Crear el informe de clínica formal
    $stmt = $pdo->prepare("
        INSERT INTO informes_clinica (clinic_id, titulo, contenido, fecha_informe, creado_por) 
        VALUES (?, ?, ?, CURDATE(), ?)
    ");
    $stmt->execute([
        $clinicId,
        $titulo,
        $contenido,
        getCurrentUser()['id']
    ]);
    
    $informeId = $pdo->lastInsertId();
    
    // Procesar archivos adjuntos
    if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
        $files = $_FILES['files'];
        
        // Crear directorio de uploads si no existe
        $uploadDir = 'uploads/informes/' . date('Y/m') . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        foreach ($files['name'] as $index => $name) {
            if ($files['error'][$index] === UPLOAD_ERR_OK) {
                $tmpName = $files['tmp_name'][$index];
                $fileSize = $files['size'][$index];
                
                // Validar tamaño (10MB máximo)
                if ($fileSize > 10 * 1024 * 1024) {
                    continue;
                }
                
                // Generar nombre único
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                $uniqueName = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                $uploadPath = $uploadDir . $uniqueName;
                
                // Mover archivo
                if (move_uploaded_file($tmpName, $uploadPath)) {
                    // Actualizar el informe con el archivo adjunto
                    $stmt = $pdo->prepare("
                        UPDATE informes_clinica 
                        SET archivo_adjunto = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$uniqueName, $informeId]);
                    
                    break; // Solo guardar el primer archivo
                }
            }
        }
    }
    
    // Confirmar transacción
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Informe guardado correctamente',
        'informe_id' => $informeId
    ]);
    
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Error en save_report.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
} catch (Exception $e) {
    error_log('Error en save_report.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al procesar el informe']);
}
?>
