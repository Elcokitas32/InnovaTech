<?php
require_once 'conexion.php';

// Verificar autenticación
if (!isLoggedIn()) {
    redirect('index.php');
}

$currentUser = getCurrentUser();

// Obtener todos los mantenimientos con información de clínicas y técnicos
$stmt = $pdo->query("
    SELECT m.*, c.nombre as clinica_nombre, u.name as tecnico_nombre 
    FROM mantenimientos m 
    LEFT JOIN clinicas c ON m.clinica_id = c.id 
    LEFT JOIN usuarios u ON m.tecnico_id = u.id 
    ORDER BY m.fecha_programada DESC
");
$mantenimientos = $stmt->fetchAll();

// Obtener estadísticas
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM mantenimientos WHERE estado = ?");
$stmt->execute(['pendiente']);
$pendientes = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM mantenimientos WHERE estado = ?");
$stmt->execute(['en_progreso']);
$enProgreso = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM mantenimientos WHERE estado = ?");
$stmt->execute(['completado']);
$completados = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM mantenimientos WHERE estado = ?");
$stmt->execute(['cancelado']);
$cancelados = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Mantenimientos - InnovaTech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
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
                <a href="mantenimientos.php" class="nav-link active">
                    <i class="fas fa-cogs"></i>
                    <span>Mantenimientos</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?></div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                        <div class="user-role">Mantenimientos</div>
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
                        <h1>Mantenimientos</h1>
                        <p>Gestión de mantenimientos programados</p>
                    </div>
                    <button class="btn btn-primary" onclick="openNewMaintenanceModal()" id="btnNuevoMantenimiento">
                        <i class="fas fa-plus"></i> Nuevo Mantenimiento
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon blue">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-info">
                            <p>Total</p>
                            <h3><?php echo count($mantenimientos); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon red">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stat-info">
                            <p>Pendientes</p>
                            <h3><?php echo $pendientes; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon yellow">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <p>En Progreso</p>
                            <h3><?php echo $enProgreso; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon green">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div class="stat-info">
                            <p>Completados</p>
                            <h3><?php echo $completados; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <div class="filters-header">
                    <h2><i class="fas fa-filter"></i> Filtrar Mantenimientos</h2>
                    <button class="btn btn-secondary btn-sm" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Limpiar Filtros
                    </button>
                </div>

                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-search"></i>
                            Búsqueda
                        </label>
                        <div class="search-input-wrapper">
                            <input type="text" id="searchInput" placeholder="Buscar por descripción, clínica..." onkeyup="filterMaintenances()">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-flag"></i>
                            Estado
                        </label>
                        <select id="statusFilter" onchange="filterMaintenances()" class="filter-select">
                            <option value="all">Todos los estados</option>
                            <option value="pendiente">⏸️ Pendiente</option>
                            <option value="en_progreso">⏳ En Progreso</option>
                            <option value="completado">✅ Completado</option>
                            <option value="cancelado">❌ Cancelado</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-wrench"></i>
                            Tipo
                        </label>
                        <select id="typeFilter" onchange="filterMaintenances()" class="filter-select">
                            <option value="all">Todos los tipos</option>
                            <option value="preventivo">🔧 Preventivo</option>
                            <option value="correctivo">⚡ Correctivo</option>
                            <option value="urgente">🚨 Urgente</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-calendar"></i>
                            Fecha
                        </label>
                        <select id="dateFilter" onchange="filterMaintenances()" class="filter-select">
                            <option value="all">Todas las fechas</option>
                            <option value="today">📅 Hoy</option>
                            <option value="week">📆 Esta semana</option>
                            <option value="month">📅 Este mes</option>
                            <option value="overdue">⚠️ Vencidos</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-hospital"></i>
                            Clínica
                        </label>
                        <select id="clinicFilter" onchange="filterMaintenances()" class="filter-select">
                            <option value="all">Todas las clínicas</option>
                            <?php
                            $clinicasFilter = [];
                            foreach ($mantenimientos as $mantenimiento) {
                                if (!empty($mantenimiento['clinica_nombre'])) {
                                    $clinicasFilter[$mantenimiento['clinica_nombre']] = $mantenimiento['clinica_nombre'];
                                }
                            }
                            $clinicasFilter = array_unique($clinicasFilter);
                            sort($clinicasFilter);
                            foreach ($clinicasFilter as $clinic): ?>
                                <option value="<?php echo strtolower($clinic); ?>"><?php echo htmlspecialchars($clinic); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="active-filters" id="activeFilters" style="display: none;">
                    <div class="active-filters-header">
                        <span class="active-filters-label">
                            <i class="fas fa-filter"></i>
                            Filtros Activos:
                        </span>
                        <div class="active-filters-list" id="activeFiltersList"></div>
                    </div>
                </div>
            </div>

            <!-- Maintenance List -->
            <div id="maintenanceList">
                <?php if (empty($mantenimientos)): ?>
                    <div class="card" style="text-align: center; padding: 3rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">
                            <i class="fas fa-tools"></i>
                        </div>
                        <p class="text-muted">No hay mantenimientos programados</p>
                        <button class="btn btn-primary" onclick="openNewMaintenanceModal()" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Agregar Primer Mantenimiento
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($mantenimientos as $mantenimiento): ?>
                        <?php $maintenanceTitle = ($mantenimiento['titulo'] ?? $mantenimiento['descripcion'] ?? '') ?: 'Sin título'; ?>
                        <div class="card maintenance-item" 
                             data-description="<?php echo htmlspecialchars(strtolower($maintenanceTitle)); ?>"
                             data-clinic="<?php echo htmlspecialchars(strtolower($mantenimiento['clinica_nombre'] ?? '')); ?>"
                             data-status="<?php echo htmlspecialchars($mantenimiento['estado']); ?>"
                             data-type="<?php echo htmlspecialchars($mantenimiento['tipo_mantenimiento']); ?>"
                             data-date="<?php echo htmlspecialchars($mantenimiento['fecha_programada']); ?>">
                            <div class="maintenance-header">
                                <div class="maintenance-icon">
                                    <i class="fas fa-tools"></i>
                                </div>
                                <div class="maintenance-info">
                                    <div class="maintenance-title">
                                        <h4><?php echo htmlspecialchars($mantenimiento['titulo'] ?? $mantenimiento['descripcion'] ?? 'Sin título'); ?></h4>
                                    </div>
                                    <div class="maintenance-meta">
                                        <span class="clinic-tag">
                                            <i class="fas fa-hospital"></i>
                                            <?php echo htmlspecialchars($mantenimiento['clinica_nombre'] ?? 'Sin clínica'); ?>
                                        </span>
                                        <span class="tech-tag">
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($mantenimiento['tecnico_nombre'] ?? 'Sin técnico'); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="maintenance-status">
                                    <?php
                                    $estadoClass = [
                                        'pendiente' => 'status-pending',
                                        'en_progreso' => 'status-progress',
                                        'completado' => 'status-completed',
                                        'cancelado' => 'status-cancelled'
                                    ][$mantenimiento['estado']] ?? '';
                                    
                                    $estadoText = [
                                        'pendiente' => 'Pendiente',
                                        'en_progreso' => 'En Progreso',
                                        'completado' => 'Completado',
                                        'cancelado' => 'Cancelado'
                                    ][$mantenimiento['estado']] ?? '';
                                    ?>
                                    <span class="status-badge <?php echo $estadoClass; ?>">
                                        <i class="fas fa-circle"></i>
                                        <?php echo $estadoText; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="maintenance-body">
                                <div class="maintenance-type">
                                    <?php
                                    $tipoClass = [
                                        'preventivo' => 'type-preventive',
                                        'correctivo' => 'type-corrective',
                                        'urgente' => 'type-urgent'
                                    ][$mantenimiento['tipo_mantenimiento']] ?? '';
                                    
                                    $tipoText = [
                                        'preventivo' => 'Preventivo',
                                        'correctivo' => 'Correctivo',
                                        'urgente' => 'Urgente'
                                    ][$mantenimiento['tipo_mantenimiento']] ?? '';
                                    ?>
                                    <span class="type-badge <?php echo $tipoClass; ?>">
                                        <?php echo $tipoText; ?>
                                    </span>
                                </div>

                                <div class="maintenance-details">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo date('d/m/Y', strtotime($mantenimiento['fecha_programada'])); ?></span>
                                    </div>
                                    <?php if ($mantenimiento['fecha_completado']): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span><?php echo date('d/m/Y', strtotime($mantenimiento['fecha_completado'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($mantenimiento['observaciones'])): ?>
                                    <div class="maintenance-observations">
                                        <p><?php echo htmlspecialchars($mantenimiento['observaciones']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="maintenance-actions">
                                <a href="view_report.php?id=<?php echo $mantenimiento['id']; ?>" class="btn btn-blue btn-sm">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                <button class="btn btn-yellow btn-sm" onclick="editMaintenance(<?php echo $mantenimiento['id']; ?>)">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-red btn-sm" onclick="deleteMaintenance(<?php echo $mantenimiento['id']; ?>)">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Nuevo/Editar Mantenimiento -->
    <div class="modal" id="maintenanceModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <div class="modal-title-section">
                    <div class="modal-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="modal-title-content">
                        <h2 id="maintenanceModalTitle">Nuevo Mantenimiento</h2>
                        <p class="modal-subtitle">Programar nuevo mantenimiento para equipo biomédico</p>
                    </div>
                </div>
                <button class="btn-close" onclick="closeMaintenanceModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="maintenanceForm" onsubmit="saveMaintenance(event)">
                <div class="modal-body">
                    <input type="hidden" id="maintenanceId">
                    
                    <!-- Información Principal -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <h3><i class="fas fa-info-circle"></i> Información del Mantenimiento</h3>
                            <div class="section-divider"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-hospital"></i>
                                Clínica *
                            </label>
                            <select id="clinicId" required class="form-select">
                                <option value="">Seleccionar clínica</option>
                                <?php
                                $clinicasStmt = $pdo->query("SELECT * FROM clinicas WHERE estado = 'activa' ORDER BY nombre");
                                $clinicas = $clinicasStmt->fetchAll();
                                foreach ($clinicas as $clinica): ?>
                                    <option value="<?php echo $clinica['id']; ?>">
                                        <i class="fas fa-hospital"></i> <?php echo htmlspecialchars($clinica['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user"></i>
                                Técnico Asignado *
                            </label>
                            <input type="text" id="tecnicoId" required 
                                   class="form-input"
                                   placeholder="Ej: Juan Pérez, María García..."
                                   list="tecnicosList"
                                   onchange="actualizarNombreTecnico(this.value)">
                            <input type="hidden" id="tecnicoIdHidden" name="tecnico_id">
                            <datalist id="tecnicosList">
                                <option value="">Seleccionar técnico...</option>
                                <?php
                                $tecnicosStmt = $pdo->query("SELECT * FROM usuarios ORDER BY name");
                                $tecnicos = $tecnicosStmt->fetchAll();
                                foreach ($tecnicos as $tecnico): ?>
                                    <option value="<?php echo $tecnico['id']; ?>" data-nombre="<?php echo htmlspecialchars($tecnico['name']); ?>">
                                        <?php echo htmlspecialchars($tecnico['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-wrench"></i>
                                    Tipo de Mantenimiento *
                                </label>
                                <select id="maintenanceType" required class="form-select">
                                    <option value="">Seleccionar tipo</option>
                                    <option value="preventivo">
                                        <i class="fas fa-shield-alt"></i> Preventivo
                                    </option>
                                    <option value="correctivo">
                                        <i class="fas fa-tools"></i> Correctivo
                                    </option>
                                    <option value="urgente">
                                        <i class="fas fa-exclamation-triangle"></i> Urgente
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar"></i>
                                    Fecha Programada *
                                </label>
                                <input type="date" id="scheduledDate" required class="form-input">
                            </div>
                        </div>
                    </div>

                    <!-- Detalles del Mantenimiento -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <h3><i class="fas fa-list-alt"></i> Detalles del Trabajo</h3>
                            <div class="section-divider"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-align-left"></i>
                                Título / Nombre del trabajo *
                            </label>
                            <textarea id="maintenanceDescription" required rows="4" 
                                      placeholder="Cambio de filtro, revisión de conexiones, limpieza general..." 
                                      class="form-textarea"></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-flag"></i>
                                Estado del Mantenimiento *
                            </label>
                            <select id="maintenanceStatus" required class="form-select">
                                <option value="">Seleccionar estado</option>
                                <option value="pendiente">
                                    <i class="fas fa-clock"></i> Pendiente
                                </option>
                                <option value="en_progreso">
                                    <i class="fas fa-spinner"></i> En Progreso
                                </option>
                                <option value="completado">
                                    <i class="fas fa-check-circle"></i> Completado
                                </option>
                                <option value="cancelado">
                                    <i class="fas fa-times-circle"></i> Cancelado
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-sticky-note"></i>
                                Observaciones
                            </label>
                            <textarea id="maintenanceObservations" rows="3" 
                                      placeholder="Notas adicionales sobre el mantenimiento..." 
                                      class="form-textarea"></textarea>
                        </div>
                    </div>

                    <!-- Archivos Adjuntos -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <h3><i class="fas fa-paperclip"></i> Archivos Adjuntos</h3>
                            <div class="section-divider"></div>
                        </div>
                        
                        <div class="file-upload-area" id="fileUploadArea">
                            <div class="file-upload-content">
                                <div class="file-upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="file-upload-text">
                                    <h4>Arrastra archivos aquí</h4>
                                    <p>o haz clic para seleccionar</p>
                                </div>
                                <div class="file-upload-info">
                                    <span class="file-types">PDF, DOC, DOCX, JPG, PNG</span>
                                    <span class="file-size">Máx. 10MB por archivo</span>
                                </div>
                                <input type="file" id="maintenanceFiles" class="file-input" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            </div>
                        </div>
                        
                        <div id="fileList" class="file-list">
                            <!-- Los archivos se mostrarán aquí dinámicamente -->
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btnCloseModal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Mantenimiento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="auth.js"></script>
    <script>
        let editingMaintenanceId = null;

        function openNewMaintenanceModal() {
            console.log('=== INICIANDO openNewMaintenanceModal ===');
            console.log('Timestamp:', new Date().toISOString());
            
            // Forzar que el modal esté oculto primero
            const modal = document.getElementById('maintenanceModal');
            if (modal) {
                console.log('Modal encontrado, forzando reset de clases');
                modal.classList.remove('active');
                modal.style.display = 'none';
            }
            
            // Pequeña demora para asegurar que se apliquen los cambios
            setTimeout(() => {
                console.log('Ejecutando lógica después de timeout...');
                
                editingMaintenanceId = null;
                
                // Limpiar formulario
                const form = document.getElementById('maintenanceForm');
                if (form) {
                    console.log('Limpiando formulario...');
                    form.reset();
                } else {
                    console.error('ERROR: No se encontró el formulario con ID maintenanceForm');
                }
                
                // Establecer título
                const titleElement = document.getElementById('maintenanceModalTitle');
                if (titleElement) {
                    titleElement.textContent = 'Nuevo Mantenimiento';
                    console.log('Título establecido correctamente');
                } else {
                    console.error('ERROR: No se encontró el título con ID maintenanceModalTitle');
                }
                
                // Limpiar ID oculto
                const idElement = document.getElementById('maintenanceId');
                if (idElement) {
                    idElement.value = '';
                    console.log('ID oculto limpiado');
                } else {
                    console.error('ERROR: No se encontró el ID con ID maintenanceId');
                }
                
                // Establecer fecha actual
                const dateElement = document.getElementById('scheduledDate');
                if (dateElement) {
                    const today = new Date().toISOString().split('T')[0];
                    dateElement.value = today;
                    console.log('Fecha establecida:', today);
                } else {
                    console.error('ERROR: No se encontró la fecha con ID scheduledDate');
                }
                
                // Mostrar modal con múltiples métodos
                if (modal) {
                    console.log('Mostrando modal...');
                    modal.style.display = 'flex';
                    modal.classList.add('active');
                    modal.classList.remove('hidden');
                    console.log('Modal display:', modal.style.display);
                    console.log('Modal classes:', modal.className);
                    
                    // Forzar focus al primer input
                    setTimeout(() => {
                        const firstInput = form.querySelector('select, input, textarea');
                        if (firstInput) {
                            firstInput.focus();
                            console.log('Focus establecido en:', firstInput);
                        }
                    }, 100);
                    
                } else {
                    console.error('ERROR CRÍTICO: No se encontró el modal');
                    alert('Error: No se encontró el modal de mantenimiento. Por favor, recargue la página.');
                }
            }, 100);
        }

        function editMaintenance(id) {
            console.log('=== INICIANDO editMaintenance para ID:', id, '===');
            
            // Cargar datos del mantenimiento existente
            fetch('get_maintenance.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    console.log('Datos del mantenimiento:', data);
                    if (data.success) {
                        const mantenimiento = data.mantenimiento;
                        
                        // Establecer título del modal
                        document.getElementById('maintenanceModalTitle').textContent = 'Editar Mantenimiento';
                        
                        // Llenar formulario con datos existentes
                        document.getElementById('maintenanceId').value = mantenimiento.id;
                        document.getElementById('clinicId').value = mantenimiento.clinica_id;
                        document.getElementById('tecnicoIdHidden').value = mantenimiento.tecnico_id;
                        document.getElementById('tecnicoId').value = mantenimiento.tecnico_nombre || '';
                        document.getElementById('maintenanceType').value = mantenimiento.tipo_mantenimiento;
                        document.getElementById('maintenanceDescription').value = mantenimiento.titulo || '';
                        document.getElementById('maintenanceStatus').value = mantenimiento.estado;
                        document.getElementById('scheduledDate').value = mantenimiento.fecha_programada;
                        document.getElementById('maintenanceObservations').value = mantenimiento.observaciones || '';
                        
                        // Actualizar el ID de edición
                        editingMaintenanceId = id;
                        
                        // Abrir modal
                        document.getElementById('maintenanceModal').classList.add('active');
                        
                        console.log('Modal abierto para edición');
                    } else {
                        console.error('Error cargando mantenimiento:', data.message);
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error en fetch:', error);
                    alert('Error al cargar el mantenimiento');
                });
        }

        function deleteMaintenance(id) {
            console.log('=== INICIANDO deleteMaintenance para ID:', id, '===');
            
            if (confirm('¿Está seguro de que desea eliminar este mantenimiento?\n\nEsta acción no se puede deshacer.')) {
                fetch('delete_maintenance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ mantenimiento_id: id })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Respuesta de eliminación:', data);
                    if (data.success) {
                        console.log('Mantenimiento eliminado correctamente');
                        alert('✅ Mantenimiento eliminado correctamente');
                        location.reload(); // Recargar para ver los cambios
                    } else {
                        console.error('Error eliminando mantenimiento:', data.message);
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error en fetch:', error);
                    alert('❌ Error al eliminar el mantenimiento');
                });
            } else {
                console.log('Eliminación cancelada por el usuario');
            }
        }

        function actualizarNombreTecnico(tecnicoId) {
            console.log('Actualizando técnico para ID:', tecnicoId);
            
            if (!tecnicoId) {
                console.log('ID de técnico vacío, limpiando campos');
                document.getElementById('tecnicoId').value = '';
                document.getElementById('tecnicoIdHidden').value = '';
                return;
            }
            
            // Buscar el técnico en la datalist
            const options = document.querySelectorAll('#tecnicosList option');
            let tecnicoEncontrado = null;
            
            for (let option of options) {
                if (option.value === tecnicoId) {
                    tecnicoEncontrado = option;
                    break;
                }
            }
            
            if (tecnicoEncontrado) {
                // Obtener el nombre del técnico
                const nombreTecnico = tecnicoEncontrado.getAttribute('data-nombre');
                const tecnicoIdCompleto = tecnicoId + ' - ' + nombreTecnico;
                
                // Actualizar AMBOS campos: el visible con ID y el oculto con el ID
                document.getElementById('tecnicoId').value = tecnicoIdCompleto;
                document.getElementById('tecnicoIdHidden').value = tecnicoId;
                
                console.log('Técnico asignado:', tecnicoIdCompleto);
                console.log('Campo visible:', document.getElementById('tecnicoId').value);
                console.log('Campo oculto:', document.getElementById('tecnicoIdHidden').value);
            } else {
                console.warn('No se encontró el técnico con ID:', tecnicoId);
            }
        }

        function closeMaintenanceModal() {
            console.log('Cerrando modal de mantenimiento...');
            
            const modal = document.getElementById('maintenanceModal');
            if (modal) {
                console.log('Modal encontrado, removiendo clases');
                modal.classList.remove('active');
                modal.style.display = 'none';
                console.log('Modal cerrado');
            }
            
            editingMaintenanceId = null;
            
            // Limpiar formulario
            const form = document.getElementById('maintenanceForm');
            if (form) {
                form.reset();
                console.log('Formulario limpiado');
            }
            
            // Limpiar campos específicos
            const idElement = document.getElementById('maintenanceId');
            if (idElement) {
                idElement.value = '';
                console.log('ID oculto limpiado');
            }
            
            const tecnicoIdElement = document.getElementById('tecnicoIdHidden');
            if (tecnicoIdElement) {
                tecnicoIdElement.value = '';
                console.log('ID oculto de técnico limpiado');
            }
            
            console.log('Modal cerrado exitosamente');
        }

        // Agregar event listener al botón de cerrar
        document.addEventListener('DOMContentLoaded', function() {
            const btnCloseModal = document.getElementById('btnCloseModal');
            if (btnCloseModal) {
                btnCloseModal.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeMaintenanceModal();
                });
                console.log('Event listener agregado al botón de cerrar');
            }
        });

        function saveMaintenance(event) {
            event.preventDefault();
            console.log('=== INICIANDO saveMaintenance ===');
            console.log('Timestamp:', new Date().toISOString());
            
            const maintenanceId = document.getElementById('maintenanceId').value;
            const isEditing = maintenanceId !== '';
            console.log('Maintenance ID:', maintenanceId);
            console.log('Is Editing:', isEditing);
            
            const formData = {
                id: maintenanceId || null,
                clinica_id: document.getElementById('clinicId').value,
                tecnico_id: document.getElementById('tecnicoId').value,
                tipo_mantenimiento: document.getElementById('maintenanceType').value,
                titulo: document.getElementById('maintenanceDescription').value,
                estado: document.getElementById('maintenanceStatus').value,
                fecha_programada: document.getElementById('scheduledDate').value,
                observaciones: document.getElementById('maintenanceObservations').value,
                archivos: uploadedFiles.map(file => ({
                    nombre: file.name,
                    tipo: file.type,
                    tamaño: file.size
                }))
            };
            
            console.log('FormData a enviar:', formData);
            
            // Validación básica
            if (!formData.clinica_id || !formData.tecnico_id || !formData.tipo_mantenimiento || !formData.estado || !formData.fecha_programada) {
                console.error('ERROR: Campos obligatorios faltantes');
                console.error('clinica_id:', formData.clinica_id);
                console.error('tecnico_id:', formData.tecnico_id);
                console.error('tipo_mantenimiento:', formData.tipo_mantenimiento);
                console.error('titulo:', formData.titulo);
                console.error('estado:', formData.estado);
                console.error('fecha_programada:', formData.fecha_programada);
                alert('Por favor, complete todos los campos obligatorios');
                return;
            }
            
            console.log('Validación pasada, enviando al servidor...');
            
            // Enviar datos al servidor
            fetch('save_maintenance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                console.log('Respuesta del servidor (raw):', response);
                return response.json();
            })
            .then(data => {
                console.log('Respuesta del servidor (JSON):', data);
                if (data.success) {
                    console.log('ÉXITO: Mantenimiento guardado');
                    alert(isEditing ? 'Mantenimiento actualizado correctamente' : 'Mantenimiento agregado correctamente');
                    closeMaintenanceModal();
                    console.log('Recargando página...');
                    location.reload(); // Recargar para ver los cambios
                } else {
                    console.error('ERROR del servidor:', data.message);
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('ERROR en fetch:', error);
                console.error('Error details:', error.message);
                alert('Error al guardar el mantenimiento. Por favor, revise la consola para más detalles.');
            });
        }

        function filterMaintenances() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            const clinicFilter = document.getElementById('clinicFilter').value;
            
            const maintenanceItems = document.querySelectorAll('.maintenance-item');
            const activeFiltersDiv = document.getElementById('activeFilters');
            const activeFiltersList = document.getElementById('activeFiltersList');
            
            // Limpiar filtros activos
            activeFiltersList.innerHTML = '';
            let hasActiveFilters = false;
            
            // Mostrar/ocultar sección de filtros activos
            if (statusFilter !== 'all' || typeFilter !== 'all' || dateFilter !== 'all' || clinicFilter !== 'all' || searchTerm !== '') {
                activeFiltersDiv.style.display = 'block';
                hasActiveFilters = true;
                
                // Agregar filtros activos
                if (statusFilter !== 'all') {
                    const statusText = document.getElementById('statusFilter').options[document.getElementById('statusFilter').selectedIndex].text;
                    activeFiltersList.innerHTML += `<span class="active-filter-tag">${statusText}</span>`;
                }
                if (typeFilter !== 'all') {
                    const typeText = document.getElementById('typeFilter').options[document.getElementById('typeFilter').selectedIndex].text;
                    activeFiltersList.innerHTML += `<span class="active-filter-tag">${typeText}</span>`;
                }
                if (dateFilter !== 'all') {
                    const dateText = document.getElementById('dateFilter').options[document.getElementById('dateFilter').selectedIndex].text;
                    activeFiltersList.innerHTML += `<span class="active-filter-tag">${dateText}</span>`;
                }
                if (clinicFilter !== 'all') {
                    const clinicText = document.getElementById('clinicFilter').options[document.getElementById('clinicFilter').selectedIndex].text;
                    activeFiltersList.innerHTML += `<span class="active-filter-tag">${clinicText}</span>`;
                }
                if (searchTerm !== '') {
                    activeFiltersList.innerHTML += `<span class="active-filter-tag">"${searchTerm}"</span>`;
                }
            } else {
                activeFiltersDiv.style.display = 'none';
            }
            
            // Filtrar elementos
            maintenanceItems.forEach(item => {
                const description = item.dataset.description;
                const clinic = item.dataset.clinic;
                const status = item.dataset.status;
                const type = item.dataset.type;
                const date = item.dataset.date || '';
                
                // Filtros de texto
                const matchesSearch = searchTerm === '' || description.includes(searchTerm) || clinic.includes(searchTerm);
                
                // Filtros de selectores
                const matchesStatus = statusFilter === 'all' || status === statusFilter;
                const matchesType = typeFilter === 'all' || type === typeFilter;
                const matchesClinic = clinicFilter === 'all' || clinic === clinicFilter;
                
                // Filtro de fecha
                let matchesDate = true;
                if (dateFilter !== 'all') {
                    const itemDate = new Date(date);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    switch(dateFilter) {
                        case 'today':
                            matchesDate = itemDate.toDateString() === today.toDateString();
                            break;
                        case 'week':
                            const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                            matchesDate = itemDate >= weekAgo && itemDate <= today;
                            break;
                        case 'month':
                            const monthAgo = new Date(today.getFullYear(), today.getMonth(), 1);
                            const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
                            matchesDate = itemDate >= monthAgo && itemDate < nextMonth;
                            break;
                        case 'overdue':
                            matchesDate = itemDate < today;
                            break;
                    }
                }
                
                // Aplicar todos los filtros
                if (matchesSearch && matchesStatus && matchesType && matchesClinic && matchesDate) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = 'all';
            document.getElementById('typeFilter').value = 'all';
            document.getElementById('dateFilter').value = 'all';
            document.getElementById('clinicFilter').value = 'all';
            document.getElementById('activeFilters').style.display = 'none';
            document.getElementById('activeFiltersList').innerHTML = '';
            
            // Mostrar todos los elementos
            const maintenanceItems = document.querySelectorAll('.maintenance-item');
            maintenanceItems.forEach(item => {
                item.style.display = 'block';
            });
        }

        // Funciones para gestión de archivos
        let uploadedFiles = [];

        document.addEventListener('DOMContentLoaded', function() {
            const fileUploadArea = document.getElementById('fileUploadArea');
            const fileInput = document.getElementById('maintenanceFiles');
            const fileList = document.getElementById('fileList');
            
            if (fileUploadArea && fileInput && fileList) {
                fileUploadArea.addEventListener('click', () => {
                    fileInput.click();
                });
                
                fileInput.addEventListener('change', handleFileSelect);
                
                // Drag and drop
                fileUploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    fileUploadArea.classList.add('drag-over');
                });
                
                fileUploadArea.addEventListener('dragleave', (e) => {
                    e.preventDefault();
                    fileUploadArea.classList.remove('drag-over');
                });
                
                fileUploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    fileUploadArea.classList.remove('drag-over');
                    const files = e.dataTransfer.files;
                    handleFiles(files);
                });
            }
        });

        function handleFileSelect(event) {
            const files = event.target.files;
            handleFiles(files);
        }

        function handleFiles(files) {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                // Validar tipo y tamaño
                const validTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                if (!validTypes.includes(file.type) || file.size > maxSize) {
                    alert(`El archivo ${file.name} no es válido o excede el tamaño máximo de 10MB`);
                    continue;
                }
                
                // Agregar a la lista de archivos
                uploadedFiles.push({
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    lastModified: file.lastModified,
                    file: file
                });
                
                // Mostrar en la lista
                displayFileList();
            }
        }

        function displayFileList() {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';
            
            uploadedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <div class="file-item-info">
                        <div class="file-item-name">
                            <i class="fas fa-file-${getFileIcon(file.type)}"></i>
                            <input type="text" 
                                   class="file-name-input" 
                                   value="${file.name}" 
                                   onchange="updateFileName(${index}, this.value)"
                                   placeholder="Nombre del archivo">
                        </div>
                    </div>
                    <div class="file-item-actions">
                        <button type="button" class="btn btn-sm btn-blue" onclick="viewFile(${index})" title="Ver archivo">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-yellow" onclick="downloadFile(${index})" title="Descargar archivo">
                            <i class="fas fa-download"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-red" onclick="removeFile(${index})" title="Eliminar archivo">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                fileList.appendChild(fileItem);
            });
        }

        function updateFileName(index, newName) {
            if (uploadedFiles[index]) {
                uploadedFiles[index].name = newName;
                // Actualizar el nombre del archivo real
                const file = uploadedFiles[index].file;
                const newFile = new File([file], newName, { type: file.type, lastModified: file.lastModified });
                uploadedFiles[index].file = newFile;
            }
        }

        function getFileIcon(type) {
            const iconMap = {
                'application/pdf': 'pdf',
                'application/msword': 'file-word',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'file-word',
                'image/jpeg': 'file-image',
                'image/png': 'file-image',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'file-excel',
                'application/vnd.ms-excel': 'file-excel',
                'application/vnd.ms-powerpoint': 'file-ppt',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'file-ppt'
            };
            
            return iconMap[type] || 'file';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function formatDate(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function viewFile(index) {
            const file = uploadedFiles[index];
            const fileURL = URL.createObjectURL(file.file);
            window.open(fileURL, '_blank');
        }

        function downloadFile(index) {
            const file = uploadedFiles[index];
            const fileURL = URL.createObjectURL(file.file);
            const a = document.createElement('a');
            a.href = fileURL;
            a.download = file.name;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(fileURL);
        }

        function removeFile(index) {
            uploadedFiles.splice(index, 1);
            displayFileList();
        }
    </script>
</body>
</html>
