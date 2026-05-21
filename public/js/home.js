const logoutButton = document.getElementById('logout-btn');

if (logoutButton) {
    logoutButton.addEventListener('click', async () => {
        try {
            const response = await fetch('/api/users.php?action=logout', {
                method: 'POST'
            });

            const data = await response.json();

            if (data.success && data.redirect) {
                window.location.href = data.redirect;
                return;
            }

            window.location.href = '/pages/auth.php';
        } catch (error) {
            window.location.href = '/pages/auth.php';
        }
    });
}