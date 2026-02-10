// Funciones de autenticación
function checkAuth() {
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (!currentUser) {
        window.location.href = 'index.php';
        return false;
    }
    return currentUser;
}

function updateUserInfo() {
    const currentUser = checkAuth();
    if (currentUser) {
        const userNameEl = document.getElementById('userName');
        const userEmailEl = document.getElementById('userEmail');
        const userAvatarEl = document.getElementById('userAvatar');
        
        if (userNameEl) userNameEl.textContent = currentUser.name;
        if (userEmailEl) userEmailEl.textContent = currentUser.email;
        if (userAvatarEl) userAvatarEl.textContent = currentUser.name.charAt(0);
    }
}

function logout() {
    localStorage.removeItem('currentUser');
    window.location.href = 'index.php';
}

// Inicializar autenticación
document.addEventListener('DOMContentLoaded', function() {
    updateUserInfo();
});
