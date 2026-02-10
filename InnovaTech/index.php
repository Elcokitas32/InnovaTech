<?php
require_once 'conexion.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

$rememberedEmail = $_COOKIE['remembered_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor ingrese email y contraseña';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && $password === $user['password']) {
                unset($user['password']);
                $_SESSION['user'] = $user;
                
                // Recuérdame: guardar SOLO el correo (no auto-login)
                if ($remember) {
                    $expires = time() + (30 * 24 * 60 * 60); // 30 días
                    setcookie('remembered_email', $email, $expires, '/', '', false, true);
                } else {
                    // Si se desmarca, limpiar el correo recordado
                    setcookie('remembered_email', '', time() - 3600, '/', '', false, true);
                }
                
                redirect('dashboard.php');
            } else {
                $error = 'Email o contraseña incorrectos';
            }
        } catch(PDOException $e) {
            $error = 'Error en el sistema';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - InnovaTech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Logo y Header -->
            <div class="logo-container">
                <div class="logo">
                    <?php 
                    $logoPath = __DIR__ . '/logo.png';
                    if (file_exists($logoPath)): 
                    ?>
                        <img src="logo.png" alt="Logo" class="login-logo" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMDAgMjAwIiBmaWxsPSIjNjY3ZWVhIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2YwZjdmYyIvPjx0ZXh0CB4PSIxMDAiIHk9IjEwMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjI0IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBhbGlnbm1lbnQtYmFzZWxpbmU9Im1pZGRsZSI+TG9nbzwvdGV4dD48L3N2Zz4=';" onload="this.style.opacity=1">
                    <?php else: ?>
                        <div class="logo-placeholder">
                            <i class="fas fa-hospital"></i>
                        </div>
                    <?php endif; ?>
                    <h1>InnovaTech</h1>
                </div>
                <p>Gestión de Mantenimiento Biomédico</p>
            </div>

            <!-- Formulario de Login -->
            <form method="POST">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="correo@ejemplo.com" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? $rememberedEmail); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" required 
                               placeholder="Ingrese su contraseña">
                        <button type="button" class="password-toggle" id="togglePassword" aria-label="Mostrar contraseña">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="remember" name="remember" <?php echo !empty($rememberedEmail) ? 'checked' : ''; ?>>
                        Recuérdame
                    </label>
                </div>

                <button type="submit" class="btn-primary">
                    Iniciar Sesión
                </button>
            </form>

            <!-- Footer -->
            <div class="login-hint">
                <p class="login-subtitle">InnovaTech Solutions</p>
                <p class="login-copyright">© Diego Armando Rivera Miranda</p>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.getElementById('togglePassword');

            if (!passwordInput || !toggleBtn) return;

            toggleBtn.addEventListener('click', function() {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                toggleBtn.setAttribute('aria-label', isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña');

                const icon = toggleBtn.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye', !isHidden);
                    icon.classList.toggle('fa-eye-slash', isHidden);
                }
            });
        })();
    </script>
</body>
</html>
