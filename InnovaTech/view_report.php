<?php
require_once 'conexion.php';

// Verificar autenticación
if (!isLoggedIn()) {
    redirect('index.php');
}

$maintenanceId = $_GET['id'] ?? '';
if (empty($maintenanceId)) {
    redirect('clinicas.php');
}

// Obtener información del mantenimiento y su informe
$stmt = $pdo->prepare("
    SELECT m.*, i.id as informe_id, i.titulo, i.contenido, i.fecha_informe, 
           i.archivo_adjunto, u.name as creador_nombre
    FROM mantenimientos m
    LEFT JOIN informes i ON m.id = i.mantenimiento_id
    LEFT JOIN usuarios u ON i.creado_por = u.id
    WHERE m.id = ?
");
$stmt->execute([$maintenanceId]);
$data = $stmt->fetchAll();

if (empty($data)) {
    redirect('clinicas.php');
}

$maintenance = $data[0];
$informe = null;

// Buscar el informe que tiene archivo adjunto
foreach ($data as $item) {
    if (!empty($item['archivo_adjunto'])) {
        $informe = $item;
        break;
    }
}

$pageTitle = "Reporte de Mantenimiento";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Mantenimiento - InnovaTech</title>
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
                        <div class="user-role">Reporte de Mantenimiento</div>
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
                        <h1>Reporte de Mantenimiento</h1>
                        <p>Información detallada del mantenimiento y sus informes</p>
                    </div>
                    <a href="javascript:history.back()" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                </div>
            </div>

            <!-- Información del Mantenimiento -->
            <section class="clinic-detail-section">
                <h2><i class="fas fa-tools"></i> Información del Mantenimiento</h2>
                <div class="info-grid">
                    <div class="info-card">
                        <h3><i class="fas fa-wrench"></i> Tipo</h3>
                        <p><?php echo ucfirst($maintenance['tipo_mantenimiento']); ?></p>
                    </div>
                    <div class="info-card">
                        <h3><i class="fas fa-align-left"></i> Título del trabajo</h3>
                        <p><?php echo htmlspecialchars(($maintenance['titulo'] ?? $maintenance['descripcion'] ?? '') ?: 'Sin título'); ?></p>
                    </div>
                    <div class="info-card">
                        <h3><i class="fas fa-calendar"></i> Fecha Programada</h3>
                        <p><?php echo date('d/m/Y', strtotime($maintenance['fecha_programada'])); ?></p>
                    </div>
                    <div class="info-card">
                        <h3><i class="fas fa-check-circle"></i> Estado</h3>
                        <p><span class="status-badge status-<?php echo $maintenance['estado']; ?>">
                            <i class="fas fa-circle"></i>
                            <?php echo ucfirst(str_replace('_', ' ', $maintenance['estado'])); ?>
                        </span></p>
                    </div>
                </div>
            </section>

                    </main>
    </div>

    <style>
        .document-details h3 {
            margin: 0 0 1rem 0;
            color: #1e293b;
            font-size: 1.5rem;
        }
        
        .document-meta {
            display: flex;
            gap: 2rem;
        }
        
        .empty-state p {
            margin: 0 0 2rem 0;
            color: #64748b;
        }
    </style>
    
    <script>
        function descargarInforme() {
            // Crear el contenido del informe
            const informeContent = `
INFORME DE CLÍNICA
=====================================

TÍTULO: <?php echo htmlspecialchars($maintenance['titulo'] ?? $maintenance['descripcion'] ?? ''); ?>
CLÍNICA: <?php echo htmlspecialchars($maintenance['clinica_nombre'] ?? ''); ?>
FECHA: <?php echo date('d/m/Y', strtotime($maintenance['fecha_programada'])); ?>
CREADO POR: <?php echo htmlspecialchars($maintenance['tecnico_nombre'] ?? ''); ?>
CONTENIDO DEL INFORME:
-------------------------------------

<?php echo htmlspecialchars($maintenance['descripcion'] ?? ''); ?>
=====================================
Fin del Informe
            `;
            
            // Crear un Blob con el contenido
            const blob = new Blob([informeContent], { type: 'text/plain;charset=utf-8' });
            const url = window.URL.createObjectURL(blob);
            
            // Crear un enlace temporal y hacer clic
            const a = document.createElement('a');
            a.href = url;
            a.download = 'informe_<?php echo date('Y-m-d', strtotime($maintenance['fecha_programada'])); ?>.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            // Limpiar el URL
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
