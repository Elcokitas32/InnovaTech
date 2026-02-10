    <i class="fas fa-paperclip"></i>
                                                <a href="uploads/698a910da8622_92e89c8de61cd50b.pdf" target="_blank" class="btn btn-sm btn-blue">
                                                    Ver Archivo Adjunto<?php
require_once 'conexion.php';

// Verificar autenticación
if (!isLoggedIn()) {
    redirect('index.php');
}

// Obtener información de la clínica
$clinicId = $_GET['id'] ?? '';
if (empty($clinicId)) {
    redirect('clinicas.php');
}

$currentUser = getCurrentUser();

// Obtener datos de la clínica
$stmt = $pdo->prepare("SELECT * FROM clinicas WHERE id = ?");
$stmt->execute([$clinicId]);
$clinic = $stmt->fetch();

if (!$clinic) {
    redirect('clinicas.php');
}

// Obtener mantenimientos de la clínica con sus archivos adjuntos
$stmt = $pdo->prepare("SELECT m.*, uc.name as tecnico_nombre, i.archivo_adjunto, i.titulo as informe_titulo
                       FROM mantenimientos m 
                       LEFT JOIN usuarios uc ON m.usuario_creador_id = uc.id 
                       LEFT JOIN informes i ON m.id = i.mantenimiento_id
                       WHERE m.clinica_id = ? 
                       ORDER BY m.fecha_programada DESC");
$stmt->execute([$clinicId]);
$mantenimientos = $stmt->fetchAll();

// Obtener informes de la clínica (de la nueva tabla informes_clinica)
$stmt = $pdo->prepare("SELECT ic.*, u.name as creador_nombre 
                       FROM informes_clinica ic 
                       LEFT JOIN usuarios u ON ic.creado_por = u.id 
                       WHERE ic.clinic_id = ? 
                       ORDER BY ic.fecha_informe DESC");
$stmt->execute([$clinicId]);
$informes = $stmt->fetchAll();

$pageTitle = "Detalle Clínica - " . htmlspecialchars($clinic['nombre']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($clinic['nombre']); ?> - InnovaTech</title>
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
                <a href="clinicas.php" class="nav-link active">
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
                    <div class="user-avatar"><?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?></div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                        <div class="user-role">Detalles de Clínica</div>
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
            <!-- Header Profesional -->
            <header class="clinic-detail-header">
                <div class="clinic-header-background">
                    <div class="clinic-header-content">
                        <div class="clinic-info">
                            <div class="clinic-icon">
                                <i class="fas fa-hospital"></i>
                            </div>
                            <div class="clinic-details">
                                <h1><?php echo htmlspecialchars($clinic['nombre']); ?></h1>
                                <p class="clinic-subtitle">Centro Médico Especializado</p>
                                <div class="clinic-meta">
                                    <span class="clinic-id">ID: #<?php echo str_pad($clinic['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                    <span class="clinic-status status-<?php echo $clinic['estado']; ?>">
                                        <i class="fas fa-circle"></i>
                                        <?php echo ucfirst($clinic['estado']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="clinic-actions">
                            <a href="clinicas.php" class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i>
                                Volver
                            </a>
                            <button class="btn btn-primary" onclick="editClinic(<?php echo $clinic['id']; ?>)">
                                <i class="fas fa-edit"></i>
                                Editar Clínica
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-body">
                <!-- Información Principal -->
                <section class="clinic-detail-section">
                    <h2><i class="fas fa-info-circle"></i> Información de la Clínica</h2>
                    <div class="info-grid">
                        <div class="info-card">
                            <h3><i class="fas fa-hospital"></i> Nombre</h3>
                            <p><?php echo htmlspecialchars($clinic['nombre']); ?></p>
                        </div>
                        <div class="info-card">
                            <h3><i class="fas fa-map-marker-alt"></i> Dirección</h3>
                            <p><?php echo htmlspecialchars($clinic['direccion']); ?></p>
                            <div class="location-actions">
                                <?php 
                                $direccion = $clinic['direccion'];
                                $isUrl = (strpos($direccion, 'http://') === 0) || (strpos($direccion, 'https://') === 0);
                                ?>
                                <?php if ($isUrl): ?>
                                    <a href="<?php echo htmlspecialchars($direccion); ?>" 
                                       target="_blank" class="btn btn-sm btn-blue">
                                        <i class="fas fa-map"></i> Ver en Maps
                                    </a>
                                    <a href="<?php echo htmlspecialchars($direccion); ?>" 
                                       target="_blank" class="btn btn-sm btn-green">
                                        <i class="fas fa-directions"></i> Cómo Llegar
                                    </a>
                                <?php else: ?>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($direccion); ?>" 
                                       target="_blank" class="btn btn-sm btn-blue">
                                        <i class="fas fa-map"></i> Ver en Maps
                                    </a>
                                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($direccion); ?>" 
                                       target="_blank" class="btn btn-sm btn-green">
                                        <i class="fas fa-directions"></i> Cómo Llegar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-card">
                            <h3><i class="fas fa-city"></i> Ciudad</h3>
                            <p><?php echo htmlspecialchars($clinic['ciudad'] ?? 'No especificada'); ?></p>
                        </div>
                        <div class="info-card">
                            <h3><i class="fas fa-phone"></i> Teléfono</h3>
                            <p><?php echo htmlspecialchars($clinic['telefono'] ?? 'No especificado'); ?></p>
                        </div>
                        <div class="info-card">
                            <h3><i class="fas fa-envelope"></i> Email</h3>
                            <p><?php echo htmlspecialchars($clinic['email'] ?? 'No especificado'); ?></p>
                        </div>
                        <div class="info-card">
                            <h3><i class="fas fa-user"></i> Contacto Responsable</h3>
                            <p><?php echo htmlspecialchars($clinic['contacto_responsable'] ?? 'No especificado'); ?></p>
                        </div>
                        <div class="info-card">
                            <h3><i class="fas fa-check-circle"></i> Estado</h3>
                            <p><span class="status-badge status-<?php echo $clinic['estado']; ?>">
                                <i class="fas fa-circle"></i>
                                <?php echo ucfirst($clinic['estado']); ?>
                            </span></p>
                        </div>
                        <?php if (!empty($clinic['observaciones'])): ?>
                        <div class="info-card">
                            <h3><i class="fas fa-sticky-note"></i> Observaciones</h3>
                            <p><?php echo htmlspecialchars($clinic['observaciones']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Información de Contacto -->
                <section class="clinic-detail-section">
                    <h2><i class="fas fa-phone"></i> Información de Contacto</h2>
                    <div class="info-grid">
                        <div class="info-card">
                            <h3><i class="fas fa-phone"></i> Teléfono</h3>
                            <p><?php echo htmlspecialchars($clinic['telefono'] ?? 'No especificado'); ?></p>
                        </div>
                        <div class="info-card">
                            <h3><i class="fas fa-envelope"></i> Email</h3>
                            <p><?php echo htmlspecialchars($clinic['email'] ?? 'No especificado'); ?></p>
                        </div>
                        <div class="info-card">
                            <h3><i class="fas fa-user"></i> Contacto Responsable</h3>
                            <p><?php echo htmlspecialchars($clinic['contacto_responsable'] ?? 'No especificado'); ?></p>
                        </div>
                        <div class="info-card">
                            <h3><i class="fas fa-mobile-alt"></i> Teléfono de Contacto</h3>
                            <p><?php echo htmlspecialchars($clinic['telefono_contacto'] ?? 'No especificado'); ?></p>
                        </div>
                    </div>
                </section>

                <!-- Observaciones -->
                <?php if (!empty($clinic['observaciones'])): ?>
                <section class="clinic-detail-section">
                    <h2><i class="fas fa-sticky-note"></i> Observaciones</h2>
                    <div class="info-card">
                        <p><?php echo htmlspecialchars($clinic['observaciones']); ?></p>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Reportes -->
                <section class="clinic-detail-section">
                    <h2><i class="fas fa-file-alt"></i> Reportes</h2>
                    <?php if (empty($mantenimientos)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h3>No hay reportes registrados</h3>
                            <p>No se han encontrado reportes para esta clínica.</p>
                        </div>
                    <?php else: ?>
                        <div class="maintenance-list">
                            <?php foreach ($mantenimientos as $mantenimiento): ?>
                                <div class="maintenance-item" data-maintenance-id="<?php echo $mantenimiento['id']; ?>">
                                    <div class="maintenance-header">
                                        <div class="maintenance-title">
                                            <h4><?php echo htmlspecialchars(($mantenimiento['titulo'] ?? $mantenimiento['descripcion'] ?? '') ?: 'Sin título'); ?></h4>
                                            <span class="maintenance-type <?php echo $mantenimiento['tipo_mantenimiento']; ?>">
                                                <?php echo ucfirst($mantenimiento['tipo_mantenimiento']); ?>
                                            </span>
                                        </div>
                                        <div class="maintenance-meta">
                                            <span class="maintenance-status <?php echo $mantenimiento['estado']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $mantenimiento['estado'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="maintenance-details">
                                        <p><i class="fas fa-user"></i> Técnico: <?php echo htmlspecialchars($mantenimiento['tecnico_nombre']); ?></p>
                                        <p><i class="fas fa-calendar"></i> Programado: <?php echo date('d/m/Y', strtotime($mantenimiento['fecha_programada'])); ?></p>
                                        <?php if ($mantenimiento['fecha_completado']): ?>
                                            <p><i class="fas fa-check"></i> Completado: <?php echo date('d/m/Y', strtotime($mantenimiento['fecha_completado'])); ?></p>
                                        <?php endif; ?>
                                        <?php if ($mantenimiento['archivo_adjunto']): ?>
                                            <div class="maintenance-attachment">
                                                <i class="fas fa-paperclip"></i>
                                                <strong>Documento:</strong>
                                                <a href="uploads/informes/<?php echo date('Y/m', strtotime($mantenimiento['fecha_programada'])); ?>/<?php echo htmlspecialchars($mantenimiento['archivo_adjunto']); ?>" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-blue"
                                                   download="<?php echo htmlspecialchars($mantenimiento['archivo_adjunto']); ?>">
                                                    <i class="fas fa-download"></i> Descargar <?php echo htmlspecialchars($mantenimiento['informe_titulo'] ?? 'Documento'); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="maintenance-actions">
                                            <button class="btn btn-info btn-sm" onclick="verInforme(<?php echo $mantenimiento['id']; ?>)">
                                                <i class="fas fa-file-alt"></i> Ver Reporte
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="eliminarMantenimiento(<?php echo $mantenimiento['id']; ?>)">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Informes -->
                <section class="clinic-detail-section">
                    <h2><i class="fas fa-file-alt"></i> Informes Técnicos</h2>
                    <?php if (empty($informes)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <h3>No hay informes</h3>
                            <p>Esta clínica no tiene informes técnicos registrados</p>
                        </div>
                    <?php else: ?>
                        <div class="reports-list">
                            <?php foreach ($informes as $informe): ?>
                                <div class="report-item" id="informe-<?php echo $informe['id']; ?>">
                                    <div class="report-header">
                                        <div class="report-title">
                                            <h4><?php echo htmlspecialchars($informe['titulo']); ?></h4>
                                            <span class="report-type general">
                                                Informe de Clínica
                                            </span>
                                        </div>
                                        <div class="report-meta">
                                            <span class="report-date">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('d/m/Y', strtotime($informe['fecha_informe'])); ?>
                                            </span>
                                            <span class="report-author">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($informe['creador_nombre']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="report-actions">
                                        <button class="btn btn-info btn-sm" onclick="verInformeClinica(<?php echo $informe['id']; ?>)">
                                            <i class="fas fa-eye"></i> Ver Detalles
                                        </button>
                                        <button class="btn btn-warning btn-sm" onclick="editarInformeClinica(<?php echo $informe['id']; ?>)">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="eliminarInformeClinica(<?php echo $informe['id']; ?>)">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Acciones Rápidas -->
                <section class="clinic-detail-section">
                    <h2><i class="fas fa-bolt"></i> Acciones Rápidas</h2>
                    <div class="actions-grid">
                        <button class="btn btn-primary" onclick="openMaintenanceModal()">
                            <i class="fas fa-tools"></i> Programar Mantenimiento
                        </button>
                        <button class="btn btn-green" onclick="openReportModal()">
                            <i class="fas fa-file-medical"></i> Generar Informe
                        </button>
                        <button class="btn btn-yellow" onclick="editClinic(<?php echo $clinic['id']; ?>)">
                            <i class="fas fa-edit"></i> Editar Clínica
                        </button>
                        <button class="btn btn-red" onclick="confirmDeleteClinic()">
                            <i class="fas fa-trash-alt"></i> Eliminar Clínica
                        </button>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Modal para Nuevo Mantenimiento -->
    <div id="maintenanceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-wrench"></i> Programar Mantenimiento</h3>
                <button type="button" class="modal-close" onclick="closeMaintenanceModal()" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="maintenanceForm" onsubmit="saveMaintenance(event)">
                    <input type="hidden" id="maintenanceClinicId" value="<?php echo $clinic['id']; ?>">
                    
                    <div class="form-group">
                        <label><i class="fas fa-tools"></i> Tipo de Mantenimiento</label>
                        <select id="maintenanceType" required>
                            <option value="">Seleccionar tipo...</option>
                            <option value="preventivo">Preventivo</option>
                            <option value="correctivo">Correctivo</option>
                            <option value="calibracion">Calibración</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-info-circle"></i> Estado</label>
                        <select id="maintenanceStatus" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="en_progreso">En Progreso</option>
                            <option value="completado">Completado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Título / Nombre del trabajo</label>
                        <textarea id="maintenanceDescription" rows="4" placeholder="Cambio de filtro, revisión de conexiones, limpieza general..."></textarea>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Fecha Programada</label>
                        <input type="date" id="maintenanceDate" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeMaintenanceModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" onclick="document.getElementById('maintenanceForm').requestSubmit()">Guardar Mantenimiento</button>
            </div>
        </div>
    </div>

    <!-- Modal para Nuevo Informe -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-medical"></i> Generar Informe</h3>
                <button type="button" class="modal-close" onclick="closeReportModal()" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="reportForm" onsubmit="saveReport(event)">
                    <input type="hidden" id="reportClinicId" value="<?php echo $clinic['id']; ?>">
                    
                    <div class="form-group">
                        <label><i class="fas fa-heading"></i> Título del Informe</label>
                        <input type="text" id="reportTitle" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-paperclip"></i> Archivos Adjuntos</label>
                        <div class="file-upload-area" id="fileUploadArea">
                            <input type="file" id="reportFiles" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xlsx,.xls,.ppt,.pptx">
                            <div class="file-upload-content">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Arrastra archivos aquí o haz clic para seleccionar</p>
                                <span class="file-types">PDF, DOC, DOCX, JPG, PNG, XLSX, PPT (Máx. 10MB)</span>
                            </div>
                        </div>
                        <div id="filePreview" class="file-preview"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeReportModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" onclick="document.getElementById('reportForm').requestSubmit()">Guardar Informe</button>
            </div>
        </div>
    </div>

    <style>
        .modal-close {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease, transform 0.15s ease;
        }
        
        .modal-close:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
            transform: translateY(-1px);
        }
        
        .modal-close:active {
            transform: translateY(0);
        }
        
        .modal-close i {
            font-size: 1rem;
            line-height: 1;
        }

        .maintenance-attachment {
            margin-top: 0.75rem;
            padding: 0.75rem;
            background: #f0f9ff;
            border: 1px solid #3b82f6;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }
        
        .maintenance-attachment i {
            color: #3b82f6;
            margin-right: 0.5rem;
        }
        
        .maintenance-attachment strong {
            color: #1e40af;
            margin-right: 0.5rem;
        }
        
        .maintenance-attachment a {
            margin-left: 0.5rem;
            text-decoration: none;
            font-weight: 500;
        }
        
        .maintenance-attachment a:hover {
            text-decoration: underline;
        }
        
        .report-actions {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .report-actions .btn {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            border-radius: 0.375rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }
        
        .report-actions .btn-info {
            background: #3b82f6;
            color: white;
        }
        
        .report-actions .btn-info:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .report-actions .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .report-actions .btn-warning:hover {
            background: #d97706;
            transform: translateY(-1px);
        }
        
        .report-actions .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .report-actions .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }
    </style>
    
    <script>
        // Funciones para informes de clínica
        function verInformeClinica(informeId) {
            // Redirigir a página de detalles del informe
            window.location.href = 'view_clinic_report.php?id=' + informeId;
        }

        function editarInformeClinica(informeId) {
            // Redirigir a página de edición del informe
            window.location.href = 'edit_clinic_report.php?id=' + informeId;
        }

        function eliminarInformeClinica(informeId) {
            if (confirm('¿Está seguro de que desea eliminar este informe?\n\nEsta acción no se puede deshacer.')) {
                fetch('delete_clinic_report.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ informe_id: informeId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Informe eliminado correctamente');
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Error al eliminar el informe');
                });
            }
        }

        // Funciones para modales
        function openMaintenanceModal() {
            console.log('Abriendo modal de mantenimiento...');
            document.getElementById('maintenanceModal').classList.add('active');
            
            // Establecer fecha actual
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('maintenanceDate').value = today;
        }

        function closeMaintenanceModal() {
            console.log('Cerrando modal de mantenimiento...');
            document.getElementById('maintenanceModal').classList.remove('active');
            document.getElementById('maintenanceForm').reset();
        }

        function openReportModal() {
            console.log('Abriendo modal de informe...');
            document.getElementById('reportModal').classList.add('active');
            initializeFileUpload();
        }

        function closeReportModal() {
            console.log('Cerrando modal de informe...');
            document.getElementById('reportModal').classList.remove('active');
            document.getElementById('reportForm').reset();
            document.getElementById('filePreview').innerHTML = '';
            selectedFiles = [];
        }

        // File Upload System
        let selectedFiles = [];

        function initializeFileUpload() {
            const fileUploadArea = document.getElementById('fileUploadArea');
            const fileInput = document.getElementById('reportFiles');
            const filePreview = document.getElementById('filePreview');

            // Click to upload
            fileUploadArea.addEventListener('click', () => {
                fileInput.click();
            });

            // File selection
            fileInput.addEventListener('change', handleFileSelect);

            // Drag and drop
            fileUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                fileUploadArea.classList.add('dragover');
            });

            fileUploadArea.addEventListener('dragleave', () => {
                fileUploadArea.classList.remove('dragover');
            });

            fileUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                fileUploadArea.classList.remove('dragover');
                handleFiles(e.dataTransfer.files);
            });
        }

        function handleFileSelect(e) {
            handleFiles(e.target.files);
        }

        function handleFiles(files) {
            const filePreview = document.getElementById('filePreview');
            const maxSize = 10 * 1024 * 1024; // 10MB

            for (let file of files) {
                if (file.size > maxSize) {
                    alert(`El archivo "${file.name}" excede el tamaño máximo de 10MB`);
                    continue;
                }

                // Check for duplicates
                if (selectedFiles.find(f => f.name === file.name)) {
                    alert(`El archivo "${file.name}" ya está seleccionado`);
                    continue;
                }

                selectedFiles.push(file);
                displayFilePreview(file);
            }
        }

        function displayFilePreview(file) {
            const filePreview = document.getElementById('filePreview');
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            const fileIcon = getFileIcon(file.name);
            const fileSize = formatFileSize(file.size);
            
            fileItem.innerHTML = `
                <i class="${fileIcon}"></i>
                <span class="file-name" title="${file.name}">${file.name}</span>
                <span class="file-size">${fileSize}</span>
                <button type="button" class="file-remove" onclick="removeFile('${file.name}')">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            filePreview.appendChild(fileItem);
        }

        function getFileIcon(fileName) {
            const extension = fileName.split('.').pop().toLowerCase();
            const iconMap = {
                'pdf': 'fas fa-file-pdf',
                'doc': 'fas fa-file-word',
                'docx': 'fas fa-file-word',
                'jpg': 'fas fa-file-image',
                'jpeg': 'fas fa-file-image',
                'png': 'fas fa-file-image',
                'xlsx': 'fas fa-file-excel',
                'xls': 'fas fa-file-excel',
                'ppt': 'fas fa-file-powerpoint',
                'pptx': 'fas fa-file-powerpoint'
            };
            return iconMap[extension] || 'fas fa-file';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function removeFile(fileName) {
            selectedFiles = selectedFiles.filter(f => f.name !== fileName);
            
            const filePreview = document.getElementById('filePreview');
            const fileItems = filePreview.querySelectorAll('.file-item');
            
            fileItems.forEach(item => {
                if (item.querySelector('.file-name').textContent === fileName) {
                    item.remove();
                }
            });
        }

        // Guardar informe
        function saveReport(event) {
            event.preventDefault();
            
            // Validar formulario
            const titulo = document.getElementById('reportTitle').value.trim();
            
            if (!titulo) {
                alert('Por favor complete el título del informe');
                return;
            }
            
            // Crear FormData para enviar archivos
            const formData = new FormData();
            formData.append('clinic_id', document.getElementById('reportClinicId').value);
            formData.append('titulo', titulo);
            formData.append('contenido', 'Informe generado para ' + new Date().toLocaleDateString('es-ES'));
            
            // Agregar archivos seleccionados
            selectedFiles.forEach((file, index) => {
                formData.append(`files[${index}]`, file);
            });
            
            // Enviar al servidor
            fetch('save_report.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Informe guardado correctamente');
                    closeReportModal();
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error al guardar el informe');
            });
        }

        // Guardar mantenimiento
        function saveMaintenance(event) {
            event.preventDefault();
            
            // Validar formulario
            const tipo = document.getElementById('maintenanceType').value;
            const titulo = document.getElementById('maintenanceDescription').value.trim();
            const fecha = document.getElementById('maintenanceDate').value;
            
            if (!tipo || !fecha) {
                alert('Por favor complete todos los campos obligatorios');
                return;
            }
            
            const formData = {
                clinica_id: document.getElementById('maintenanceClinicId').value,
                tipo_mantenimiento: tipo,
                titulo: titulo,
                estado: 'pendiente',
                fecha_programada: fecha
            };

            fetch('save_maintenance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Mantenimiento creado correctamente');
                    closeMaintenanceModal();
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error al guardar el mantenimiento');
            });
        }

        // Concluir mantenimiento
        function concluirMantenimiento(mantenimientoId) {
            if (confirm('✅ ¿Está seguro de que desea concluir este mantenimiento?')) {
                fetch('update_maintenance_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        mantenimiento_id: mantenimientoId,
                        estado: 'completado',
                        fecha_completado: new Date().toISOString().split('T')[0]
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Mantenimiento concluido correctamente');
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Error al concluir el mantenimiento');
                });
            }
        }

        // Reprogramar mantenimiento
        function reprogramarMantenimiento(mantenimientoId) {
            const nuevaFecha = prompt('📅 Ingrese la nueva fecha para el mantenimiento (formato: YYYY-MM-DD):');
            if (nuevaFecha) {
                // Validar formato de fecha
                const fechaRegex = /^\d{4}-\d{2}-\d{2}$/;
                if (!fechaRegex.test(nuevaFecha)) {
                    alert('❌ Formato de fecha inválido. Use el formato YYYY-MM-DD');
                    return;
                }
                
                fetch('update_maintenance_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        mantenimiento_id: mantenimientoId,
                        estado: 'pendiente',
                        fecha_programada: nuevaFecha
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Mantenimiento reprogramado correctamente');
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al reprogramar el mantenimiento');
                });
            }
        }

        // Ver informe de mantenimiento
        function verInforme(mantenimientoId) {
            // Redirigir a la página de informe
            window.location.href = 'view_report.php?id=' + mantenimientoId;
        }

        // Eliminar mantenimiento
        function eliminarMantenimiento(mantenimientoId) {
            if (confirm('¿Está seguro de que desea eliminar este mantenimiento?\n\nEsta acción no se puede deshacer.')) {
                fetch('delete_maintenance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        mantenimiento_id: mantenimientoId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Mantenimiento eliminado correctamente');
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Error al eliminar el mantenimiento');
                });
            }
        }

        // Editar clínica
        function editClinic(clinicId) {
            // Redirigir a la página de edición con el ID de la clínica
            window.location.href = 'add_clinic.php?edit=' + clinicId;
        }

        // Confirmar eliminación
        function confirmDeleteClinic() {
            if (confirm('¿Está seguro de que desea eliminar esta clínica?\n\nEsta acción no se puede deshacer.')) {
                window.location.href = 'delete_clinic.php?id=<?php echo $clinic['id']; ?>';
            }
        }

        // Cerrar modales con ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeMaintenanceModal();
                closeReportModal();
            }
        }); 
        
        console.log('✅ Página de detalles cargada correctamente');
        console.log('DEBUG: ID de la clínica:', <?php echo $clinic['id']; ?>);
        
        document.addEventListener('DOMContentLoaded', function() {
            const editButton = document.querySelector('button[onclick*="editClinic"]');
            const deleteButton = document.querySelector('button[onclick*="confirmDeleteClinic"]');
            
            console.log('DEBUG: Botón editar encontrado:', editButton);
            console.log('DEBUG: Botón eliminar encontrado:', deleteButton);
            
            if (editButton) {
                console.log('DEBUG: Botón editar onclick:', editButton.getAttribute('onclick'));
            }
            
            if (deleteButton) {
                console.log('DEBUG: Botón eliminar onclick:', deleteButton.getAttribute('onclick'));
            }
        });
    </script>
</body>
</html>
