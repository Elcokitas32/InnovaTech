<?php
// Deshabilitar mostrar errores en pantalla para JSON
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'conexion.php';

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

try {
    // Leer JSON input
    $jsonInput = file_get_contents('php://input');
    if ($jsonInput === false) {
        throw new Exception('Error leyendo datos de entrada');
    }
    
    $data = json_decode($jsonInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }
    
    if (!$data) {
        throw new Exception('Datos inválidos');
    }
    
    $titulo = $data['titulo'] ?? $data['descripcion'] ?? null;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mantenimientos' AND COLUMN_NAME = 'titulo'");
    $stmt->execute();
    $hasTituloColumn = ((int)$stmt->fetchColumn()) > 0;
    $titleColumn = $hasTituloColumn ? 'titulo' : 'descripcion';

    // Validar campos obligatorios
    if (empty($data['clinica_id']) || empty($data['tipo_mantenimiento']) || empty($data['estado']) || empty($data['fecha_programada'])) {
        throw new Exception('Complete todos los campos obligatorios');
    }
    
    // Validar que la clínica exista
    $stmt = $pdo->prepare("SELECT id FROM clinicas WHERE id = ?");
    $stmt->execute([$data['clinica_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('La clínica seleccionada no existe');
    }
    
    // Usar el primer usuario disponible como técnico
    $stmt = $pdo->prepare("SELECT id FROM usuarios LIMIT 1");
    $stmt->execute();
    $tecnico = $stmt->fetch();
    if (!$tecnico) {
        throw new Exception('No hay usuarios disponibles en el sistema');
    }
    $tecnicoId = $tecnico['id'];
    
    // Determinar si es edición o nuevo mantenimiento
    $isEditing = !empty($data['id']);
    
    if ($isEditing) {
        // Actualizar mantenimiento existente
        $stmt = $pdo->prepare("UPDATE mantenimientos SET 
            clinica_id = ?, 
            tecnico_id = ?, 
            tipo_mantenimiento = ?, 
            {$titleColumn} = ?, 
            estado = ?, 
            fecha_programada = ?, 
            observaciones = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?");
        
        $result = $stmt->execute([
            $data['clinica_id'],
            $tecnicoId,
            $data['tipo_mantenimiento'],
            $titulo,
            $data['estado'],
            $data['fecha_programada'],
            $data['observaciones'] ?? null,
            $data['id']
        ]);
        
        if (!$result) {
            throw new Exception('Error al actualizar el mantenimiento: ' . implode(', ', $stmt->errorInfo()));
        }
        
        echo json_encode(['success' => true, 'message' => 'Mantenimiento actualizado correctamente']);
        
    } else {
        // Insertar nuevo mantenimiento
        $stmt = $pdo->prepare("INSERT INTO mantenimientos 
            (clinica_id, tecnico_id, tipo_mantenimiento, {$titleColumn}, estado, fecha_programada, observaciones, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        
        $result = $stmt->execute([
            $data['clinica_id'],
            $tecnicoId,
            $data['tipo_mantenimiento'],
            $titulo,
            $data['estado'],
            $data['fecha_programada'],
            $data['observaciones'] ?? null
        ]);
        
        if (!$result) {
            throw new Exception('Error al crear el mantenimiento: ' . implode(', ', $stmt->errorInfo()));
        }
        
        // Obtener el ID del mantenimiento insertado
        $maintenanceId = $pdo->lastInsertId();
        
        echo json_encode(['success' => true, 'message' => 'Mantenimiento creado correctamente', 'id' => $maintenanceId]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
