<?php
session_start();
require_once __DIR__ . '/../config/Bootstrap.php';
/** @var AuthGuard $guard */ //nu executa nimic, ii spune doar IDe ul
$guard->requireGuest(); //permite accesul doar la cei care nu sunt logati

//
if (isset($_GET['tab']) && $_GET['tab'] === 'register')
    $defaultTab = 'register';
else
    $defaultTab = 'login';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOr — <?= $defaultTab === 'register' ? 'Cont nou' : 'Intră în cont' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/auth.css">
</head>
<body>

<div class="auth-page">

    <a href="/pages/landing.php" class="auth-back">← Înapoi</a>

    <div class="auth-card">

        <div class="auth-logo">
            <span class="logo-s">S</span><span class="logo-o">O</span><span class="logo-r">r</span>
            <span class="logo-icon">🧃</span>
        </div>
        <p class="auth-tagline">Soft Drink Organizer</p>

        <div class="auth-tabs">
            <button class="tab <?= $defaultTab === 'login'    ? 'active' : '' ?>" data-tab="login">Intră în cont</button>
            <button class="tab <?= $defaultTab === 'register' ? 'active' : '' ?>" data-tab="register">Cont nou</button>
        </div>

        <div id="auth-message" class="msg hidden"></div>

        <div id="tab-login" class="auth-form <?= $defaultTab !== 'login' ? 'hidden' : '' ?>">
            <div class="fg">
                <label for="login-email">Email</label>
                <input type="email" id="login-email" placeholder="email@exemplu.com" autocomplete="email">
            </div>
            <div class="fg">
                <label for="login-password">Parolă</label>
                <input type="password" id="login-password" placeholder="••••••••" autocomplete="current-password">
            </div>
            <button id="btn-login" class="btn-primary">Intră în cont</button>
        </div>

        <div id="tab-register" class="auth-form <?= $defaultTab !== 'register' ? 'hidden' : '' ?>">
            <div class="fg">
                <label for="reg-username">Username</label>
                <input type="text" id="reg-username" placeholder="username" autocomplete="username">
            </div>
            <div class="fg">
                <label for="reg-email">Email</label>
                <input type="email" id="reg-email" placeholder="email@exemplu.com" autocomplete="email">
            </div>
            <div class="fg">
                <label for="reg-password">Parolă</label>
                <input type="password" id="reg-password" placeholder="minim 8 caractere" autocomplete="new-password">
            </div>
            <button id="btn-register" class="btn-primary">Creează cont</button>
        </div>

        <div class="auth-switch">
            <span id="switch-text"><?= $defaultTab === 'login' ? 'Nu ai cont?' : 'Ai deja cont?' ?></span>
            <a href="#" id="switch-link"><?= $defaultTab === 'login' ? 'Înregistrează-te' : 'Loghează-te' ?></a>
        </div>

    </div>
</div>

<script src="/public/js/auth.js"></script>
</body>
</html>