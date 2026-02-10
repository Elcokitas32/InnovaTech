<?php
require_once 'conexion.php';

// Verificar autenticación
if (!isLoggedIn()) {
    redirect('index.php');
}

$currentUser = getCurrentUser();

// Obtener estadísticas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM mantenimientos");
$totalMantenimientos = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM mantenimientos WHERE estado = ?");
$stmt->execute(['completado']);
$completados = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM mantenimientos WHERE estado = ?");
$stmt->execute(['en_progreso']);
$enProgreso = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM mantenimientos WHERE estado = ?");
$stmt->execute(['pendiente']);
$pendientes = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM clinicas");
$totalClinicas = $stmt->fetch()['total'];

// Obtener mantenimientos recientes
$stmt = $pdo->query("
    SELECT m.*, c.nombre as clinica_nombre, u.name as tecnico_nombre 
    FROM mantenimientos m 
    LEFT JOIN clinicas c ON m.clinica_id = c.id 
    LEFT JOIN usuarios u ON m.tecnico_id = u.id 
    ORDER BY m.created_at DESC 
    LIMIT 5
");
$mantenimientosRecientes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - InnovaTech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>InnovaTech</h1>
                <p class="text-muted text-sm">Gestión de Mantenimiento</p>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
                <a href="clinicas.php" class="nav-link">
                    <i class="fas fa-hospital"></i>
                    <span>Clínicas y Hospitales</span>
                </a>
                <a href="mantenimientos.php" class="nav-link">
                    <i class="fas fa-cogs"></i>
                    <span>Mantenimientos</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?></div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                        <div class="user-role">Dashboard</div>
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
                <h1>Dashboard</h1>
                <p>Resumen general del sistema de gestión de mantenimiento</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon blue">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <p>Total Mantenimientos</p>
                            <h3><?php echo $totalMantenimientos; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon purple">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <div class="stat-info">
                            <p>Clínicas y Hospitales</p>
                            <h3><?php echo $totalClinicas; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <p>Completados</p>
                            <h3><?php echo $completados; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon yellow">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="stat-info">
                            <p>En Progreso</p>
                            <h3><?php echo $enProgreso; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon red">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="stat-info">
                            <p>Pendientes</p>
                            <h3><?php echo $pendientes; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Maintenance -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Mantenimientos Recientes</h2>
                    <a href="mantenimientos.php" class="btn btn-blue btn-sm">Ver todos →</a>
                </div>
                <div id="recentMaintenanceList">
                    <?php if (empty($mantenimientosRecientes)): ?>
                        <p class="text-muted">No hay mantenimientos disponibles</p>
                    <?php else: ?>
                        <?php foreach ($mantenimientosRecientes as $mantenimiento): ?>
                            <div style="padding: 1rem; border: 1px solid var(--slate-200); border-radius: 0.5rem; margin-bottom: 0.75rem;">
                                <div class="flex justify-between items-center">
                                    <div style="flex: 1;">
                                        <h4 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars(($mantenimiento['titulo'] ?? $mantenimiento['descripcion'] ?? '') ?: 'Sin título'); ?></h4>
                                        <p class="text-sm text-muted">
                                            <?php echo htmlspecialchars($mantenimiento['clinica_nombre']); ?> • 
                                            <?php echo date('d/m/Y', strtotime($mantenimiento['fecha_programada'])); ?> • 
                                            <?php echo htmlspecialchars($mantenimiento['tecnico_nombre']); ?>
                                        </p>
                                    </div>
                                    <?php
                                    $estadoClass = [
                                        'completado' => 'badge-green',
                                        'en_progreso' => 'badge-yellow',
                                        'pendiente' => 'badge-red',
                                        'cancelado' => 'badge-gray'
                                    ][$mantenimiento['estado']] ?? '';
                                    
                                    $estadoText = [
                                        'completado' => 'Completado',
                                        'en_progreso' => 'En Progreso',
                                        'pendiente' => 'Pendiente',
                                        'cancelado' => 'Cancelado'
                                    ][$mantenimiento['estado']] ?? '';
                                    ?>
                                    <span class="badge <?php echo $estadoClass; ?>"><?php echo $estadoText; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="auth.js"></script>
</body>
</html>
