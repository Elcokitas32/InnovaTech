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

// Obtener información del informe de clínica
$stmt = $pdo->prepare("
    SELECT ic.*, c.nombre as nombre_clinica, u.name as creador_nombre 
    FROM informes_clinica ic 
    LEFT JOIN clinicas c ON ic.clinic_id = c.id 
    LEFT JOIN usuarios u ON ic.creado_por = u.id 
    WHERE ic.id = ?
");
$stmt->execute([$informeId]);
$informe = $stmt->fetch();

if (!$informe) {
    redirect('clinicas.php');
}

$pageTitle = "Detalles del Informe - " . htmlspecialchars($informe['titulo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Informe - InnovaTech</title>
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
                    <div class="user-avatar">F</div>
                    <div class="user-details">
                        <div class="user-name">Franz</div>
                        <div class="user-role">Detalles de Informe</div>
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
                        <h1>Detalles del Informe</h1>
                        <p>Información completa del informe de clínica</p>
                    </div>
                    <div class="report-content">
                        <p>Informe generado para 9/2/2026</p>
                    </div>
                    <div>
                        <a href="clinica_detalle.php?id=<?php echo $informe['clinic_id']; ?>" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i>
                            Volver a la Clínica
                        </a>
                        <a href="edit_clinic_report.php?id=<?php echo $informe['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i>
                            Editar Informe
                        </a>
                    </div>
                </div>
            </div>

            <!-- Información del Informe -->
            <section class="clinic-detail-section">
                <h2><i class="fas fa-file-alt"></i> Información del Informe</h2>
                <div class="info-grid">
                    <div class="info-card">
                        <h3><i class="fas fa-heading"></i> Título</h3>
                        <p><?php echo htmlspecialchars($informe['titulo']); ?></p>
                    </div>
                    <div class="info-card">
                        <h3><i class="fas fa-hospital"></i> Clínica</h3>
                        <p><?php echo htmlspecialchars($informe['nombre_clinica']); ?></p>
                    </div>
                    <div class="info-card">
                        <h3><i class="fas fa-calendar"></i> Fecha</h3>
                        <p><?php echo date('d/m/Y', strtotime($informe['fecha_informe'])); ?></p>
                    </div>
                    <div class="info-card">
                        <h3><i class="fas fa-user"></i> Creado por</h3>
                        <p><?php echo htmlspecialchars($informe['creador_nombre']); ?></p>
                    </div>
                </div>
            </section>

            <!-- Archivo Adjunto -->
            <?php if ($informe['archivo_adjunto']): ?>
            <section class="clinic-detail-section">
                <h2><i class="fas fa-paperclip"></i> Archivo Adjunto</h2>
                <div class="attachment-card">
                    <div class="attachment-info">
                        <i class="fas fa-file-pdf attachment-icon"></i>
                        <div class="attachment-details">
                            <h4><?php echo htmlspecialchars($informe['archivo_adjunto']); ?></h4>
                            <p>Documento adjunto del informe</p>
                        </div>
                    </div>
                    <div class="attachment-actions">
                        <a href="uploads/informes/<?php echo date('Y/m', strtotime($informe['fecha_informe'])); ?>/<?php echo htmlspecialchars($informe['archivo_adjunto']); ?>" 
                           target="_blank" 
                           class="btn btn-success"
                           download="<?php echo htmlspecialchars($informe['archivo_adjunto']); ?>">
                            <i class="fas fa-download"></i>
                            Descargar Archivo
                        </a>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </main>
    </div>

    <style>
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-actions {
            display: flex;
            gap: 1rem;
        }
        
        .content-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            line-height: 1.6;
        }
        
        .attachment-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .attachment-info {
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .attachment-icon {
            font-size: 3rem;
            color: #3b82f6;
        }
        
        .attachment-details h4 {
            margin: 0 0 0.5rem 0;
            color: #1e293b;
            font-size: 1.5rem;
        }
        
        .attachment-details p {
            margin: 0;
            color: #64748b;
        }
        
        .attachment-actions {
            padding: 1.5rem 2rem;
            background: #f8fafc;
            display: flex;
            gap: 1rem;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 12px rgba(16, 185, 129, 0.3);
        }
    </style>
    
    <script>
        function descargarInforme() {
            // Crear el contenido del informe
            const informeContent = `
INFORME DE CLÍNICA
=====================================

TÍTULO: <?php echo htmlspecialchars($informe['titulo']); ?>
CLÍNICA: <?php echo htmlspecialchars($informe['nombre_clinica']); ?>
FECHA: <?php echo date('d/m/Y', strtotime($informe['fecha_informe'])); ?>
CREADO POR: <?php echo htmlspecialchars($informe['creador_nombre']); ?>
CONTENIDO DEL INFORME:
-------------------------------------

<?php echo htmlspecialchars($informe['contenido']); ?>
=====================================
Fin del Informe
            `;
            
            // Crear un Blob con el contenido
            const blob = new Blob([informeContent], { type: 'text/plain;charset=utf-8' });
            const url = window.URL.createObjectURL(blob);
            
            // Crear un enlace temporal y hacer clic
            const a = document.createElement('a');
            a.href = url;
            a.download = 'informe_<?php echo date('Y-m-d', strtotime($informe['fecha_informe'])); ?>.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            // Limpiar el URL
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
