<?php
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
$stmt = $pdo->prepare("SELECT * FROM clinics WHERE id = ?");
$stmt->execute([$clinicId]);
$clinic = $stmt->fetch();

if (!$clinic) {
    redirect('clinicas.php');
}

// Obtener informes de la clínica
$stmt = $pdo->prepare("SELECT * FROM maintenance_reports WHERE clinic_id = ? ORDER BY date DESC");
$stmt->execute([$clinicId]);
$reports = $stmt->fetchAll();

$pageTitle = "Detalle Clínica - " . htmlspecialchars($clinic['name']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($clinic['name']); ?> - InnovaTech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
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
                <a href="informes.php" class="nav-link">
                    <i class="fas fa-file-alt"></i>
                    <span>Todos los Informes</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?></div>
                    <div class="user-details">
                        <p><?php echo htmlspecialchars($currentUser['name']); ?></p>
                        <p class="email"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                    </div>
                </div>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Back Button -->
            <a href="clinicas.php" class="btn btn-sm" style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                ← Volver a Clínicas
            </a>

            <!-- Clinic Info Card -->
            <div class="card">
                <div class="flex gap-4" style="margin-bottom: 1rem;">
                    <div style="width: 64px; height: 64px; background: #dbeafe; color: var(--primary); border-radius: 1rem; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                        <i class="fas fa-hospital"></i>
                    </div>
                    <div style="flex: 1;">
                        <h1 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($clinic['name']); ?></h1>
                        <p class="text-muted"><?php echo count($reports); ?> <?php echo count($reports) === 1 ? 'informe técnico' : 'informes técnicos'; ?></p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div style="padding: 1rem; background: var(--slate-50); border-radius: 0.75rem;">
                        <div style="display: flex; align-items: start; gap: 0.75rem;">
                            <span style="font-size: 1.25rem;"><i class="fas fa-map-marker-alt"></i></span>
                            <div>
                                <p class="text-sm text-muted">Dirección</p>
                                <p style="margin: 0.25rem 0;"><?php echo htmlspecialchars($clinic['address']); ?></p>
                                <p class="text-muted"><?php echo htmlspecialchars($clinic['zone']); ?>, <?php echo htmlspecialchars($clinic['city']); ?></p>
                                <?php if (!empty($clinic['map_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($clinic['map_url']); ?>" target="_blank" class="text-sm" style="color: var(--primary);">Ver en mapa →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div style="padding: 1rem; background: var(--slate-50); border-radius: 0.75rem;">
                        <div style="display: flex; align-items: start; gap: 0.75rem;">
                            <span style="font-size: 1.25rem;"><i class="fas fa-phone"></i></span>
                            <div>
                                <p class="text-sm text-muted">Teléfonos</p>
                                <?php 
                                $phones = json_decode($clinic['phones'] ?? '[]', true);
                                foreach ($phones as $phone): ?>
                                    <p style="margin: 0.25rem 0;"><?php echo htmlspecialchars($phone); ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Section -->
            <div class="page-header">
                <div class="flex justify-between items-center">
                    <h2>Informes Técnicos</h2>
                    <button class="btn btn-primary" onclick="openNewReportModal()">
                        <i class="fas fa-plus"></i> Nuevo Informe
                    </button>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <div class="filters-header">
                    <h2><i class="fas fa-filter"></i> Filtrar Informes</h2>
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
                            <input type="text" id="searchInput" placeholder="Buscar por título, equipo, área..." onkeyup="filterReports()">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-flag"></i>
                            Estado
                        </label>
                        <select id="statusFilter" onchange="filterReports()" class="filter-select">
                            <option value="all">Todos los estados</option>
                            <option value="completado"><i class="fas fa-check-circle"></i> Completado</option>
                            <option value="en-progreso"><i class="fas fa-spinner"></i> En Progreso</option>
                            <option value="pendiente"><i class="fas fa-clock"></i> Pendiente</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-wrench"></i>
                            Tipo
                        </label>
                        <select id="typeFilter" onchange="filterReports()" class="filter-select">
                            <option value="all">Todos los tipos</option>
                            <option value="preventivo"><i class="fas fa-shield-alt"></i> Preventivo</option>
                            <option value="correctivo"><i class="fas fa-tools"></i> Correctivo</option>
                            <option value="calibracion"><i class="fas fa-tachometer-alt"></i> Calibración</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-calendar"></i>
                            Fecha
                        </label>
                        <select id="dateFilter" onchange="filterReports()" class="filter-select">
                            <option value="all">Todas las fechas</option>
                            <option value="today"><i class="fas fa-calendar-day"></i> Hoy</option>
                            <option value="week"><i class="fas fa-calendar-week"></i> Esta semana</option>
                            <option value="month"><i class="fas fa-calendar-alt"></i> Este mes</option>
                            <option value="overdue"><i class="fas fa-exclamation-triangle"></i> Vencidos</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-user"></i>
                            Técnico
                        </label>
                        <select id="technicianFilter" onchange="filterReports()" class="filter-select">
                            <option value="all">Todos los técnicos</option>
                            <?php
                            $technicians = [];
                            foreach ($reports as $report) {
                                if (!empty($report['technician'])) {
                                    $technicians[$report['technician']] = $report['technician'];
                                }
                            }
                            $technicians = array_unique($technicians);
                            sort($technicians);
                            foreach ($technicians as $technician): ?>
                                <option value="<?php echo strtolower($technician); ?>"><?php echo htmlspecialchars($technician); ?></option>
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

            <!-- Reports List -->
            <div id="reportsList">
                <?php if (empty($reports)): ?>
                    <div class="card" style="text-align: center; padding: 3rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <p class="text-muted">No hay informes para esta clínica</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <div class="card report-item" 
                             data-title="<?php echo htmlspecialchars(strtolower($report['title'])); ?>"
                             data-equipment="<?php echo htmlspecialchars(strtolower($report['equipment'])); ?>"
                             data-area="<?php echo htmlspecialchars(strtolower($report['area'])); ?>"
                             data-status="<?php echo htmlspecialchars($report['status']); ?>"
                             data-type="<?php echo htmlspecialchars($report['maintenance_type']); ?>"
                             data-date="<?php echo htmlspecialchars($report['date']); ?>"
                             data-technician="<?php echo htmlspecialchars(strtolower($report['technician'])); ?>">
                            <div class="flex gap-4">
                                <div style="width: 48px; height: 48px; background: #dbeafe; color: var(--primary); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin-bottom: 0.75rem;"><?php echo htmlspecialchars($report['title']); ?></h3>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                                        <?php
                                        $statusBadges = [
                                            'completado' => '<span class="badge badge-green">Completado</span>',
                                            'en-progreso' => '<span class="badge badge-yellow">En Progreso</span>',
                                            'pendiente' => '<span class="badge badge-red">Pendiente</span>'
                                        ];
                                        echo $statusBadges[$report['status']] ?? '';
                                        
                                        $typeBadges = [
                                            'preventivo' => '<span class="badge badge-blue">Preventivo</span>',
                                            'correctivo' => '<span class="badge badge-orange">Correctivo</span>',
                                            'calibracion' => '<span class="badge badge-purple">Calibración</span>'
                                        ];
                                        echo $typeBadges[$report['maintenance_type']] ?? '';
                                        ?>
                                    </div>

                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin-bottom: 1rem; font-size: 0.875rem; color: var(--slate-600);">
                                        <div><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($report['date'])); ?></div>
                                        <div><i class="fas fa-user"></i> <?php echo htmlspecialchars($report['technician']); ?></div>
                                        <?php if (!empty($report['file_name'])): ?>
                                            <div><i class="fas fa-paperclip"></i> <?php echo htmlspecialchars($report['file_name']); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div style="padding: 0.75rem; background: var(--slate-50); border-radius: 0.5rem; margin-bottom: 1rem;">
                                        <p class="text-sm"><strong>Equipo:</strong> <?php echo htmlspecialchars($report['equipment']); ?></p>
                                        <p class="text-sm"><strong>Área:</strong> <?php echo htmlspecialchars($report['area']); ?></p>
                                        <p class="text-sm text-muted" style="margin-top: 0.5rem;"><?php echo htmlspecialchars($report['description']); ?></p>
                                    </div>

                                    <div class="flex gap-2">
                                        <button class="btn btn-yellow btn-sm" onclick="editReport('<?php echo $report['id']; ?>')">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button class="btn btn-red btn-sm" onclick="deleteReportConfirm('<?php echo $report['id']; ?>')">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Nuevo/Editar Informe -->
    <div class="modal" id="reportModal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <div class="modal-title-section">
                    <div class="modal-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="modal-title-content">
                        <h2 id="reportModalTitle">Nuevo Informe Técnico</h2>
                        <p class="modal-subtitle">Complete todos los campos requeridos</p>
                    </div>
                </div>
                <button class="btn-close" onclick="closeReportModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="reportForm" onsubmit="saveReport(event)">
                <div class="modal-body">
                    <input type="hidden" id="reportId">
                    <input type="hidden" id="reportClinicId" value="<?php echo $clinicId; ?>">

                    <!-- Información Principal -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <h3><i class="fas fa-info-circle"></i> Información Principal</h3>
                            <div class="section-divider"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-heading"></i>
                                Título del Informe *
                            </label>
                            <input type="text" id="reportTitle" required 
                                   placeholder="Ej: Mantenimiento preventivo resonador magnético" 
                                   class="form-input">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-tools"></i>
                                    Equipo *
                                </label>
                                <input type="text" id="reportEquipment" required 
                                       placeholder="Ej: Resonador Magnético" 
                                       class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-tag"></i>
                                    Tipo de Equipo *
                                </label>
                                <input type="text" id="reportEquipmentType" required 
                                       placeholder="Ej: Imagenología" 
                                       class="form-input">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-wrench"></i>
                                    Tipo de Mantenimiento *
                                </label>
                                <select id="reportMaintenanceType" required class="form-select">
                                    <option value="">Seleccionar tipo</option>
                                    <option value="preventivo">
                                        <i class="fas fa-shield-alt"></i> Preventivo
                                    </option>
                                    <option value="correctivo">
                                        <i class="fas fa-tools"></i> Correctivo
                                    </option>
                                    <option value="calibracion">
                                        <i class="fas fa-tachometer-alt"></i> Calibración
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-flag"></i>
                                    Estado *
                                </label>
                                <select id="reportStatus" required class="form-select">
                                    <option value="">Seleccionar estado</option>
                                    <option value="pendiente">
                                        <i class="fas fa-clock"></i> Pendiente
                                    </option>
                                    <option value="en-progreso">
                                        <i class="fas fa-spinner"></i> En Progreso
                                    </option>
                                    <option value="completado">
                                        <i class="fas fa-check-circle"></i> Completado
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Detalles Adicionales -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <h3><i class="fas fa-list-alt"></i> Detalles Adicionales</h3>
                            <div class="section-divider"></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Área
                                </label>
                                <input type="text" id="reportArea" 
                                       placeholder="Ej: Radiología" 
                                       class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar"></i>
                                    Fecha
                                </label>
                                <input type="date" id="reportDate" 
                                       class="form-input">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user"></i>
                                Técnico Asignado
                            </label>
                            <input type="text" id="reportTechnician" 
                                   placeholder="Nombre del técnico" 
                                   class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-align-left"></i>
                                Descripción Detallada
                            </label>
                            <textarea id="reportDescription" rows="4" 
                                      placeholder="Describa detalladamente el trabajo realizado..." 
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
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Arrastre archivos aquí o haga clic para seleccionar</p>
                                <p class="file-upload-hint">PDF, DOC, DOCX, JPG, PNG (Máx. 10MB)</p>
                                <input type="file" id="reportFile" class="file-input" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            </div>
                        </div>
                        <div id="fileList" class="file-list"></div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeReportModal()" style="flex: 1; background: var(--slate-100);">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Funciones locales
        function openNewReportModal() {
            alert('Función en desarrollo');
        }
        
        function closeReportModal() {
            document.getElementById('reportModal').classList.remove('active');
        }
        
        function saveReport(event) {
            event.preventDefault();
            alert('Función en desarrollo');
        }
        
        function editReport(id) {
            // Redirigir a la página de edición del informe
            window.location.href = 'edit_report.php?id=' + id;
        }
        
        function deleteReportConfirm(id) {
            if (confirm('¿Está seguro de eliminar este informe?')) {
                alert('Función en desarrollo');
            }
        }
        
        function filterReports() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            const technicianFilter = document.getElementById('technicianFilter').value;
            
            const reportItems = document.querySelectorAll('.report-item');
            const activeFiltersDiv = document.getElementById('activeFilters');
            const activeFiltersList = document.getElementById('activeFiltersList');
            
            // Limpiar filtros activos
            activeFiltersList.innerHTML = '';
            
            // Mostrar/ocultar sección de filtros activos
            if (statusFilter !== 'all' || typeFilter !== 'all' || dateFilter !== 'all' || technicianFilter !== 'all' || searchTerm !== '') {
                activeFiltersDiv.style.display = 'block';
                
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
                if (technicianFilter !== 'all') {
                    const technicianText = document.getElementById('technicianFilter').options[document.getElementById('technicianFilter').selectedIndex].text;
                    activeFiltersList.innerHTML += `<span class="active-filter-tag">${technicianText}</span>`;
                }
                if (searchTerm !== '') {
                    activeFiltersList.innerHTML += `<span class="active-filter-tag">"${searchTerm}"</span>`;
                }
            } else {
                activeFiltersDiv.style.display = 'none';
            }
            
            // Filtrar elementos
            reportItems.forEach(item => {
                const title = item.dataset.title;
                const equipment = item.dataset.equipment;
                const area = item.dataset.area;
                const status = item.dataset.status;
                const type = item.dataset.type;
                const date = item.dataset.date || '';
                const technician = item.dataset.technician || '';
                
                // Filtros de texto
                const matchesSearch = searchTerm === '' || title.includes(searchTerm) || equipment.includes(searchTerm) || area.includes(searchTerm);
                
                // Filtros de selectores
                const matchesStatus = statusFilter === 'all' || status === statusFilter;
                const matchesType = typeFilter === 'all' || type === typeFilter;
                const matchesTechnician = technicianFilter === 'all' || technician === technicianFilter;
                
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
                if (matchesSearch && matchesStatus && matchesType && matchesDate && matchesTechnician) {
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
            document.getElementById('technicianFilter').value = 'all';
            document.getElementById('activeFilters').style.display = 'none';
            document.getElementById('activeFiltersList').innerHTML = '';
            
            // Mostrar todos los elementos
            const reportItems = document.querySelectorAll('.report-item');
            reportItems.forEach(item => {
                item.style.display = 'block';
            });
        }
    </script>
</body>
</html>
