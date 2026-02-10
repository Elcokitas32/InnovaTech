<?php
require_once 'conexion.php';

// Verificar autenticación
if (!isLoggedIn()) {
    redirect('index.php');
}

// Obtener el ID de la clínica a eliminar
$clinicId = $_GET['id'] ?? null;

if (!$clinicId) {
    redirect('clinicas.php');
}

try {
    // Obtener información de la clínica para confirmación
    $stmt = $pdo->prepare("SELECT nombre FROM clinicas WHERE id = ?");
    $stmt->execute([$clinicId]);
    $clinic = $stmt->fetch();

    if (!$clinic) {
        redirect('clinicas.php');
    }

    // Procesar eliminación
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
        // Eliminar primero los mantenimientos relacionados
        $stmt = $pdo->prepare("DELETE FROM mantenimientos WHERE clinica_id = ?");
        $stmt->execute([$clinicId]);
        
        // Eliminar la clínica
        $stmt = $pdo->prepare("DELETE FROM clinicas WHERE id = ?");
        $result = $stmt->execute([$clinicId]);
        
        if ($result) {
            // Redirigir con mensaje de éxito
            $_SESSION['success_message'] = "Clínica '{$clinic['nombre']}' eliminada correctamente.";
            redirect('clinicas.php');
        } else {
            // Redirigir con mensaje de error
            $_SESSION['error_message'] = "Error al eliminar la clínica.";
            redirect('clinicas.php');
        }
    }
} catch (PDOException $e) {
    error_log('Error al eliminar clínica: ' . $e->getMessage());
    $_SESSION['error_message'] = "Error en la base de datos al eliminar la clínica.";
    redirect('clinicas.php');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Clínica - InnovaTech</title>
    <link rel="stylesheet" href="styles.css">
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
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user']['name'], 0, 1)); ?></div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></div>
                        <div class="user-role">Eliminar Clínica</div>
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
                <h1>Eliminar Clínica</h1>
                <p>Está seguro de que desea eliminar esta clínica?</p>
            </div>

            <div class="content-body">
                <div class="info-card">
                    <h3><i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> ¿Está seguro?</h3>
                    <p><strong><?php echo htmlspecialchars($clinic['nombre']); ?></strong></p>
                    <p><em>ID: #<?php echo $clinicId; ?></em></p>
                </div>

                <form method="POST" action="" class="confirmation-form">
                    <input type="hidden" name="confirm_delete" value="1">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-red">
                            <i class="fas fa-trash"></i>
                            Sí, eliminar
                        </button>
                        <a href="clinica_detalle.php?id=<?php echo $clinicId; ?>" class="btn btn-outline">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
