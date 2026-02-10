<?php
require_once 'conexion.php';

// Verificar autenticación
if (!isLoggedIn()) {
    redirect('index.php');
}

$currentUser = getCurrentUser();

// Variables para el modo de edición
$isEditing = false;
$clinicData = null;
$clinicId = '';

// Verificar si estamos en modo edición
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $isEditing = true;
    $clinicId = $_GET['edit'];
    
    // Obtener datos de la clínica
    $stmt = $pdo->prepare("SELECT * FROM clinicas WHERE id = ?");
    $stmt->execute([$clinicId]);
    $clinicData = $stmt->fetch();
    
    if (!$clinicData) {
        // Si no se encuentra la clínica, redirigir
        redirect('clinicas.php');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEditing ? 'Editar Clínica' : 'Agregar Clínica'; ?> - InnovaTech</title>
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
                    <span>Clínicas</span>
                </a>
                <a href="mantenimientos.php" class="nav-link">
                    <i class="fas fa-tools"></i>
                    <span>Mantenimientos</span>
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <div class="flex justify-between items-center">
                    <div>
                        <h1><?php echo $isEditing ? 'Editar Clínica' : 'Agregar Nueva Clínica'; ?></h1>
                        <p><?php echo $isEditing ? 'Modificar los datos de la clínica existente' : 'Registrar una nueva clínica u hospital en el sistema'; ?></p>
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='clinicas.php'">
                        <i class="fas fa-arrow-left"></i> Volver a Clínicas
                    </button>
                </div>
            </div>

            <!-- Form Container -->
            <div class="form-container">
                <form id="clinicForm" onsubmit="saveClinic(event)">
                    <input type="hidden" id="clinicId" value="<?php echo $isEditing ? $clinicId : ''; ?>">
                    
                    <!-- Información Básica -->
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Información Básica</h3>
                        
                        <div class="form-group">
                            <label for="clinicName">
                                <i class="fas fa-hospital"></i> Nombre de la Clínica *
                            </label>
                            <input type="text" id="clinicName" name="nombre" required 
                                   placeholder="Ej: Hospital Central, Clínica San Juan..."
                                   value="<?php echo $isEditing ? htmlspecialchars($clinicData['nombre']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="clinicAddress">
                                <i class="fas fa-map-marker-alt"></i> Dirección *
                            </label>
                            <input type="text" id="clinicAddress" name="direccion" required 
                                   placeholder="Ej: Calle Principal #123, Ciudad"
                                   value="<?php echo $isEditing ? htmlspecialchars($clinicData['direccion']) : ''; ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="clinicPhone">
                                    <i class="fas fa-phone"></i> Teléfono
                                </label>
                                <input type="tel" id="clinicPhone" name="telefono" 
                                       placeholder="Ej: +1234567890"
                                       value="<?php echo $isEditing ? htmlspecialchars($clinicData['telefono'] ?? '') : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="clinicEmail">
                                    <i class="fas fa-envelope"></i> Email
                                </label>
                                <input type="text" id="clinicEmail" name="email" 
                                       placeholder="Ej: clinica@ejemplo.com (opcional)"
                                       value="<?php echo $isEditing ? htmlspecialchars($clinicData['email'] ?? '') : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Información de Contacto -->
                    <div class="form-section">
                        <h3><i class="fas fa-user-tie"></i> Información de Contacto</h3>
                        
                        <div class="form-group">
                            <label for="clinicContact">
                                <i class="fas fa-user"></i> Contacto Responsable
                            </label>
                            <input type="text" id="clinicContact" name="contacto_responsable" 
                                   placeholder="Nombre del responsable"
                                   value="<?php echo $isEditing ? htmlspecialchars($clinicData['contacto_responsable'] ?? '') : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="clinicContactPhone">
                                <i class="fas fa-phone-alt"></i> Teléfono del Contacto
                            </label>
                            <input type="tel" id="clinicContactPhone" name="telefono_contacto" 
                                   placeholder="Teléfono del responsable"
                                   value="<?php echo $isEditing ? htmlspecialchars($clinicData['telefono_contacto'] ?? '') : ''; ?>">
                        </div>
                    </div>

                    <!-- Ubicación y Estado -->
                    <div class="form-section">
                        <h3><i class="fas fa-map"></i> Ubicación y Estado</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="clinicCity">
                                    <i class="fas fa-city"></i> Ciudad
                                </label>
                                <input type="text" id="clinicCity" name="ciudad" 
                                       placeholder="Ciudad"
                                       value="<?php echo $isEditing ? htmlspecialchars($clinicData['ciudad'] ?? '') : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="clinicStatus">
                                    <i class="fas fa-toggle-on"></i> Estado *
                                </label>
                                <select id="clinicStatus" name="estado" required>
                                    <option value="">Seleccionar estado</option>
                                    <option value="activa" <?php echo $isEditing && $clinicData['estado'] === 'activa' ? 'selected' : ''; ?>>Activa</option>
                                    <option value="inactiva" <?php echo $isEditing && $clinicData['estado'] === 'inactiva' ? 'selected' : ''; ?>>Inactiva</option>
                                    <option value="mantenimiento" <?php echo $isEditing && $clinicData['estado'] === 'mantenimiento' ? 'selected' : ''; ?>>En Mantenimiento</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="clinicNotes">
                                <i class="fas fa-sticky-note"></i> Observaciones
                            </label>
                            <textarea id="clinicNotes" name="observaciones" rows="4" 
                                      placeholder="Notas adicionales sobre la clínica..."><?php echo $isEditing ? htmlspecialchars($clinicData['observaciones'] ?? '') : ''; ?></textarea>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='clinicas.php'">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo $isEditing ? 'Actualizar Clínica' : 'Guardar Clínica'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function saveClinic(event) {
            event.preventDefault();
            console.log('Guardando clínica...');
            
            // Obtener valores del formulario
            const nombre = document.getElementById('clinicName').value.trim();
            const direccion = document.getElementById('clinicAddress').value.trim();
            const telefono = document.getElementById('clinicPhone').value.trim();
            const email = document.getElementById('clinicEmail').value.trim();
            const contacto = document.getElementById('clinicContact').value.trim();
            const telefonoContacto = document.getElementById('clinicContactPhone').value.trim();
            const ciudad = document.getElementById('clinicCity').value.trim();
            const estado = document.getElementById('clinicStatus').value;
            const observaciones = document.getElementById('clinicNotes').value.trim();
            
            console.log('Datos del formulario:', {
                nombre: nombre,
                direccion: direccion,
                telefono: telefono,
                email: email,
                contacto_responsable: contacto,
                telefono_contacto: telefonoContacto,
                ciudad: ciudad,
                estado: estado,
                observaciones: observaciones
            });
            
            // Validación mejorada
            if (!nombre || nombre.length < 2) {
                alert('⚠️ Por favor ingrese un nombre válido para la clínica (mínimo 2 caracteres)');
                return;
            }
            
            if (!direccion || direccion.length < 5) {
                alert('⚠️ Por favor ingrese una dirección válida (mínimo 5 caracteres)');
                return;
            }
            
            if (!estado) {
                alert('⚠️ Por favor seleccione un estado para la clínica');
                return;
            }
            
            // Crear formData con todos los datos
            const formData = {
                id: document.getElementById('clinicId').value || null,
                nombre: nombre,
                direccion: direccion,
                telefono: telefono || null,
                email: email || null,
                contacto_responsable: contacto || null,
                telefono_contacto: telefonoContacto || null,
                ciudad: ciudad || 'Sin especificar',
                estado: estado,
                observaciones: observaciones || null
            };
            
            console.log('FormData final:', formData);
            
            // Enviar datos al servidor
            fetch('save_clinic.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta del servidor:', data);
                if (data.success) {
                    const isEditing = document.getElementById('clinicId').value !== '';
                    const actionText = isEditing ? 'actualizada' : 'agregada';
                    const actionTitle = isEditing ? 'Actualización' : 'Creación';
                    
                    alert(`✅ Clínica ${actionText} correctamente\n${actionTitle}: ${formData.nombre}\nDirección: ${formData.direccion}\n\nSerás redirigido a la lista de clínicas.`);
                    
                    // Redirigir a la lista de clínicas para ver los cambios
                    window.location.href = 'clinicas.php';
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error al guardar la clínica');
            });
        }
    </script>
</body>
</html>
