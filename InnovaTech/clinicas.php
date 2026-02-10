<?php
require_once 'conexion.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$currentUser = getCurrentUser();

// Obtener todas las clínicas
$stmt = $pdo->query("SELECT * FROM clinicas ORDER BY nombre");
$clinicas = $stmt->fetchAll();

// Obtener estadísticas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM clinicas WHERE estado = 'activa'");
$activas = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM clinicas WHERE estado = 'inactiva'");
$inactivas = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM mantenimientos");
$totalMantenimientos = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clínicas - InnovaTech</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="clinicas-folder-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                        <div class="user-role">Clínicas y Hospitales</div>
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
                        <h1>Clínicas y Hospitales</h1>
                        <p>Gestión de clínicas y sus mantenimientos</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.location.href='add_clinic.php'">
                        <i class="fas fa-plus"></i> Nueva Clínica
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hospital"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $activas; ?></h3>
                        <p>Clínicas Activas</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hospital-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $inactivas; ?></h3>
                        <p>Clínicas Inactivas</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $totalMantenimientos; ?></h3>
                        <p>Total Mantenimientos</p>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <div class="filters-header">
                    <h2><i class="fas fa-filter"></i> Filtros</h2>
                    <button class="btn btn-sm btn-outline" onclick="toggleFilters()">
                        <i class="fas fa-chevron-down"></i> Mostrar Filtros
                    </button>
                </div>
                <div class="filters-container" id="filtersContainer" style="display: none;">
                    <div class="filter-group">
                        <label for="searchInput">Buscar por nombre o dirección</label>
                        <input type="text" id="searchInput" placeholder="Ej: Hospital Central..." onkeyup="filterClinics()">
                    </div>
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="statusFilter">Estado</label>
                            <select id="statusFilter" onchange="filterClinics()">
                                <option value="all">Todos</option>
                                <option value="activa">Activa</option>
                                <option value="inactiva">Inactiva</option>
                                <option value="mantenimiento">En Mantenimiento</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="cityFilter">Ciudad</label>
                            <select id="cityFilter" onchange="filterClinics()">
                                <option value="all">Todas</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button class="btn btn-sm btn-outline" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Limpiar Todo
                        </button>
                    </div>
                    <div class="active-filters-list" id="activeFiltersList"></div>
                </div>
            </div>

            <!-- Clinics Grid -->
            <div class="clinics-grid" id="clinicsList">
                <?php if (empty($clinicas)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <h3>No hay clínicas registradas</h3>
                        <p>Comienza agregando tu primera clínica al sistema</p>
                        <button class="btn btn-primary" onclick="window.location.href='add_clinic.php'">
                            <i class="fas fa-plus"></i> Agregar Clínica
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($clinicas as $clinica): ?>
                        <?php 
                        // Asegurar que todos los campos tengan valores por defecto
                        $clinica['ciudad'] = $clinica['ciudad'] ?? 'Sin especificar';
                        $clinica['telefono'] = $clinica['telefono'] ?? 'Sin teléfono';
                        $clinica['direccion'] = $clinica['direccion'] ?? 'Dirección no especificada';
                        $clinica['estado'] = $clinica['estado'] ?? 'activa';
                        
                        // Debug: mostrar datos de cada clínica
                        error_log('DEBUG - Clínica: ' . $clinica['nombre'] . 
                                 ', Estado: ' . $clinica['estado'] . 
                                 ', Ciudad: ' . $clinica['ciudad'] . 
                                 ', Dirección: ' . $clinica['direccion'] . 
                                 ', Teléfono: ' . $clinica['telefono']);
                        ?>
                        <div class="clinic-folder" 
                             data-name="<?php echo htmlspecialchars(strtolower($clinica['nombre'])); ?>"
                             data-address="<?php echo htmlspecialchars(strtolower($clinica['direccion'])); ?>"
                             data-status="<?php echo htmlspecialchars($clinica['estado']); ?>"
                             data-city="<?php echo htmlspecialchars(strtolower($clinica['ciudad'])); ?>"
                             onclick="window.location.href='clinica_detalle.php?id=<?php echo $clinica['id']; ?>'">
                            <div class="folder-header">
                                <div class="folder-icon">
                                    <i class="fas fa-folder"></i>
                                </div>
                                <div class="folder-info">
                                    <h4><?php echo htmlspecialchars($clinica['nombre']); ?></h4>
                                    <div class="folder-meta">
                                        <span class="folder-city">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($clinica['ciudad']); ?>
                                        </span>
                                        <span class="folder-status <?php 
                                            if ($clinica['estado'] === 'activa') echo 'status-active'; 
                                            elseif ($clinica['estado'] === 'inactiva') echo 'status-inactive'; 
                                            else echo 'status-maintenance'; 
                                        ?>">
                                            <i class="fas fa-circle"></i>
                                            <?php 
                                            if ($clinica['estado'] === 'activa') echo 'Activa'; 
                                            elseif ($clinica['estado'] === 'inactiva') echo 'Inactiva'; 
                                            else echo 'En Mantenimiento'; 
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="folder-preview">
                                <div class="preview-info">
                                    <div class="preview-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars(substr($clinica['direccion'], 0, 50)); ?>...</span>
                                    </div>
                                    <div class="preview-item">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($clinica['telefono']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="folder-actions">
                                <button class="btn btn-sm btn-blue" onclick="event.stopPropagation(); openClinicOnMap('<?php echo addslashes($clinica['direccion']); ?>')" title="Ver en Google Maps">
                                    <i class="fas fa-map"></i>
                                </button>
                            </div>
        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        let editingClinicId = null;

        function openNewClinicModal() {
            window.location.href = 'add_clinic.php';
        }

        function closeClinicModal() {
            console.log('Cerrando modal de clínica...');
        }

        function editClinic(id) {
            alert('Función en desarrollo');
        }

        function deleteClinic(id) {
            if (confirm('¿Está seguro de eliminar esta clínica?')) {
                alert('Función en desarrollo');
            }
        }

        function toggleFilters() {
            const filtersContainer = document.getElementById('filtersContainer');
            if (filtersContainer.style.display === 'none') {
                filtersContainer.style.display = 'block';
            } else {
                filtersContainer.style.display = 'none';
            }
        }

        function filterClinics() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const cityFilter = document.getElementById('cityFilter').value;
            const clinicItems = document.querySelectorAll('.clinic-folder');
            
            clinicItems.forEach(item => {
                const name = item.dataset.name;
                const address = item.dataset.address;
                const status = item.dataset.status;
                const city = item.dataset.city;
                
                const matchesSearch = searchTerm === '' || name.includes(searchTerm) || address.includes(searchTerm);
                const matchesStatus = statusFilter === 'all' || status === statusFilter;
                const matchesCity = cityFilter === 'all' || city === cityFilter;
                
                if (matchesSearch && matchesStatus && matchesCity) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = 'all';
            document.getElementById('cityFilter').value = 'all';
            filterClinics();
        }

        function openClinicOnMap(address) {
            // Verificar si es una URL (empieza con http:// o https://)
            if (address.startsWith('http://') || address.startsWith('https://')) {
                // Es una URL, abrirla directamente
                window.open(address, '_blank');
            } else {
                // Es una dirección física, usar Google Maps search con urlencode
                window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`, '_blank');
            }
        }
    </script>
</body>
</html>
