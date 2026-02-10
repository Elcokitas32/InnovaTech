<?php
require_once 'conexion.php';

// Verificar autenticación
if (!isLoggedIn()) {
    redirect('index.php');
}

$informeId = $_GET['id'] ?? '';
if (empty($informeId)) {
    redirect('clinicas.php');
}

// Obtener información del informe
$stmt = $pdo->prepare("
    SELECT ic.*, c.nombre as nombre_clinica 
    FROM informes_clinica ic 
    LEFT JOIN clinicas c ON ic.clinic_id = c.id 
    WHERE ic.id = ?
");
$stmt->execute([$informeId]);
$informe = $stmt->fetch();

if (!$informe) {
    redirect('clinicas.php');
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $contenido = $_POST['contenido'] ?? '';
    
    if (empty($titulo) || empty($contenido)) {
        $error = 'Por favor complete todos los campos obligatorios';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE informes_clinica 
                SET titulo = ?, contenido = ?, actualizado_en = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$titulo, $contenido, $informeId]);
            
            // Procesar archivo adjunto si se sube uno nuevo
            if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['archivo'];
                $uploadDir = 'uploads/informes/' . date('Y/m') . '/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Eliminar archivo anterior si existe
                if ($informe['archivo_adjunto']) {
                    $oldFilePath = 'uploads/informes/' . date('Y/m', strtotime($informe['fecha_informe'])) . '/' . $informe['archivo_adjunto'];
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                
                // Subir nuevo archivo
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $uniqueName = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                $uploadPath = $uploadDir . $uniqueName;
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $stmt = $pdo->prepare("
                        UPDATE informes_clinica 
                        SET archivo_adjunto = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$uniqueName, $informeId]);
                }
            }
            
            redirect('view_clinic_report.php?id=' . $informeId);
            
        } catch (PDOException $e) {
            $error = 'Error al actualizar el informe: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Editar Informe - " . htmlspecialchars($informe['titulo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Informe - InnovaTech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="clinicas-folder-styles.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>InnovaTech</h1>
                <p class="text-muted text-sm">Gestión de Mantenimiento</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
                <a href="clinicas.php" class="nav-link">
                    <i class="fas fa-hospital"></i>
                    <span>Clínicas y Hospitales</span>
                </a>
                <a href="mantenimientos.php" class="nav-link">
                    <i class="fas fa-tools"></i>
                    <span>Mantenimientos</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr(getCurrentUser()['name'], 0, 1)); ?></div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars(getCurrentUser()['name']); ?></div>
                        <div class="user-role">Editar Informe</div>
                    </div>
                </div>
                <div class="footer-actions">
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <div class="flex justify-between items-center">
                    <div>
                        <h1>Editar Informe</h1>
                        <p>Modificar información del informe de clínica</p>
                    </div>
                    <a href="view_clinic_report.php?id=<?php echo $informeId; ?>" class="btn btn-outline">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </a>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Formulario de Edición -->
            <section class="clinic-detail-section">
                <h2><i class="fas fa-edit"></i> Editar Informe</h2>
                <form method="POST" enctype="multipart/form-data" class="edit-form">
                    <input type="hidden" name="informe_id" value="<?php echo $informeId; ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-heading"></i> Título del Informe</label>
                            <input type="text" name="titulo" value="<?php echo htmlspecialchars($informe['titulo']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-hospital"></i> Clínica</label>
                            <input type="text" value="<?php echo htmlspecialchars($informe['nombre_clinica']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Contenido del Informe</label>
                        <textarea name="contenido" rows="8" required><?php echo htmlspecialchars($informe['contenido']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-paperclip"></i> Archivo Adjunto</label>
                        <div class="file-upload-area">
                            <input type="file" name="archivo" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xlsx,.xls,.ppt,.pptx">
                            <div class="file-upload-content">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Selecciona un nuevo archivo (opcional)</p>
                                <span class="file-types">PDF, DOC, DOCX, JPG, PNG, XLSX, PPT (Máx. 10MB)</span>
                            </div>
                        </div>
                        <?php if ($informe['archivo_adjunto']): ?>
                            <div class="current-file">
                                <i class="fas fa-file"></i>
                                Archivo actual: <?php echo htmlspecialchars($informe['archivo_adjunto']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Guardar Cambios
                        </button>
                        <a href="view_clinic_report.php?id=<?php echo $informeId; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <style>
        .edit-form {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .current-file {
            margin-top: 1rem;
            padding: 1rem;
            background: #f0f9ff;
            border: 1px solid #3b82f6;
            border-radius: 0.5rem;
            color: #1e40af;
        }
        
        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</body>
</html>
