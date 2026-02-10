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
    
    // Depuración: Mostrar datos recibidos
    error_log('=== DEPURACIÓN save_clinic.php ===');
    error_log('JSON recibido: ' . $jsonInput);
    
    $data = json_decode($jsonInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }
    
    error_log('Datos parseados: ' . print_r($data, true));
    
    if (!$data) {
        throw new Exception('Datos inválidos');
    }
    
    // Validar campos obligatorios y asignar valores por defecto
    if (empty($data['nombre'])) {
        throw new Exception('El nombre de la clínica es obligatorio');
    }
    
    if (empty($data['direccion'])) {
        throw new Exception('La dirección de la clínica es obligatoria');
    }
    
    if (empty($data['estado'])) {
        throw new Exception('El estado de la clínica es obligatorio');
    }
    
    // Asegurar que los campos opcionales tengan valores por defecto
    $data['telefono'] = $data['telefono'] ?? null;
    $data['email'] = $data['email'] ?? null;
    $data['contacto_responsable'] = $data['contacto_responsable'] ?? null;
    $data['telefono_contacto'] = $data['telefono_contacto'] ?? null;
    $data['ciudad'] = $data['ciudad'] ?? 'Sin especificar';
    $data['observaciones'] = $data['observaciones'] ?? null;
    
    error_log('Datos validados y preparados para guardar:');
    error_log('Nombre: ' . $data['nombre']);
    error_log('Dirección: ' . $data['direccion']);
    error_log('Estado: ' . $data['estado']);
    error_log('Ciudad: ' . $data['ciudad']);
    error_log('Teléfono: ' . $data['telefono']);
    error_log('Email: ' . $data['email']);
    error_log('Contacto: ' . $data['contacto_responsable']);
    error_log('Teléfono Contacto: ' . $data['telefono_contacto']);
    error_log('Observaciones: ' . $data['observaciones']);
    
    // Determinar si es edición o nueva clínica
    $isEditing = !empty($data['id']);
    
    if ($isEditing) {
        // Actualizar clínica existente
        $stmt = $pdo->prepare("UPDATE clinicas SET 
            nombre = ?, 
            direccion = ?, 
            telefono = ?, 
            email = ?, 
            contacto_responsable = ?, 
            ciudad = ?, 
            telefono_contacto = ?, 
            estado = ?,
            observaciones = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?");
        
        $result = $stmt->execute([
            $data['nombre'],
            $data['direccion'],
            $data['telefono'] ?? null,
            $data['email'] ?? null,
            $data['contacto_responsable'] ?? null,
            $data['ciudad'] ?? null,
            $data['telefono_contacto'] ?? null,
            $data['estado'],
            $data['observaciones'],
            $data['id']
        ]);
        
        if (!$result) {
            throw new Exception('Error al actualizar la clínica: ' . implode(', ', $stmt->errorInfo()));
        }
        
        echo json_encode(['success' => true, 'message' => 'Clínica actualizada correctamente']);
        
    } else {
        // Insertar nueva clínica
        $stmt = $pdo->prepare("INSERT INTO clinicas 
            (nombre, direccion, telefono, email, contacto_responsable, ciudad, telefono_contacto, estado, observaciones, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        
        $result = $stmt->execute([
            $data['nombre'],
            $data['direccion'],
            $data['telefono'] ?? null,
            $data['email'] ?? null,
            $data['contacto_responsable'] ?? null,
            $data['ciudad'] ?? null,
            $data['telefono_contacto'] ?? null,
            $data['estado'],
            $data['observaciones']
        ]);
        
        if (!$result) {
            throw new Exception('Error al crear la clínica: ' . implode(', ', $stmt->errorInfo()));
        }
        
        // Obtener el ID de la clínica insertada
        $newClinicId = $pdo->lastInsertId();
        error_log('Nueva clínica insertada con ID: ' . $newClinicId);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Clínica creada correctamente',
            'clinic_id' => $newClinicId
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
