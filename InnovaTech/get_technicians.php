<?php
require_once 'conexion.php';

header('Content-Type: application/json');

try {
    // Obtener todos los usuarios como técnicos
    $stmt = $pdo->prepare("SELECT id, name, email FROM usuarios ORDER BY name ASC");
    $stmt->execute();
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay usuarios, crear uno por defecto
    if (empty($technicians)) {
        $defaultPassword = password_hash('tecnico123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (name, email, password) VALUES (?, ?, ?)");
        $result = $stmt->execute(['Técnico Principal', 'tecnico@clinica.com', $defaultPassword]);
        
        if ($result) {
            // Obtener el usuario recién creado
            $stmt = $pdo->prepare("SELECT id, name, email FROM usuarios ORDER BY name ASC");
            $stmt->execute();
            $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    echo json_encode([
        'success' => true,
        'technicians' => $technicians
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error de base de datos'
    ]);
}
?>
