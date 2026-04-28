document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const target = tab.dataset.tab;
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.auth-form').forEach(f => f.classList.add('hidden'));
        tab.classList.add('active');
        document.getElementById('tab-' + target).classList.remove('hidden');
        hideMsg();
        updateSwitch(target);
    });
});

document.getElementById('switch-link').addEventListener('click', (e) => {
    e.preventDefault();
    const isLogin = !document.getElementById('tab-login').classList.contains('hidden');
    document.querySelector(`[data-tab="${isLogin ? 'register' : 'login'}"]`).click();
});

function updateSwitch(tab) {
    document.getElementById('switch-text').textContent = tab === 'login' ? 'Nu ai cont?' : 'Ai deja cont?';
    document.getElementById('switch-link').textContent = tab === 'login' ? 'Înregistrează-te ♥' : 'Loghează-te';
}

document.getElementById('btn-login').addEventListener('click', async () => {
    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;
    if (!email || !password) { showMsg('Completează toate câmpurile.', 'error'); return; }
    const btn = document.getElementById('btn-login');
    btn.disabled = true; btn.textContent = 'Se procesează...';
    try {
        const res = await api('login', { email, password });
        if (res.success) { showMsg('Autentificare reușită!', 'success'); setTimeout(() => window.location.href = '/pages/home.php', 1000); }
        else showMsg(res.message, 'error');
    } catch { showMsg('Eroare de conexiune.', 'error'); }
    finally { btn.disabled = false; btn.textContent = 'Intră în cont'; }
});

document.getElementById('btn-register').addEventListener('click', async () => {
    const username = document.getElementById('reg-username').value.trim();
    const email    = document.getElementById('reg-email').value.trim();
    const password = document.getElementById('reg-password').value;
    if (!username || !email || !password) { showMsg('Completează toate câmpurile.', 'error'); return; }
    const btn = document.getElementById('btn-register');
    const span = btn.querySelector('span');
    btn.disabled = true; span.textContent = 'Se procesează...';
    try {
        const res = await api('register', { username, email, password });
        if (res.success) { showMsg('Cont creat!', 'success'); setTimeout(() => window.location.href = '/pages/home.php', 1000); }
        else showMsg(res.message, 'error');
    } catch { showMsg('Eroare de conexiune.', 'error'); }
    finally { btn.disabled = false; span.textContent = 'Creează cont ♥'; }
});

async function api(action, data) {
    const r = await fetch(`/api/users.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data).toString()
    });
    return r.json();
}

function showMsg(text, type) {
    const el = document.getElementById('auth-message');
    el.textContent = text; el.className = `msg ${type}`;
}
function hideMsg() {
    document.getElementById('auth-message').className = 'msg hidden';
}