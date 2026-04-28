<?php
session_start();
require_once __DIR__ . '/../config/Bootstrap.php';
/** @var AuthGuard $guard */
$guard->requireGuest();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOr 🧃 — Login / Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/auth.css">
</head>
<body>
<div class="wrap">

    <div class="left">
        <div class="left-illustration">
            <svg viewBox="0 0 300 420" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50"  cy="80"  r="30" fill="rgba(255,255,255,0.04)"/>
                <circle cx="260" cy="50"  r="50" fill="rgba(255,255,255,0.04)"/>
                <circle cx="20"  cy="320" r="40" fill="rgba(255,255,255,0.04)"/>
                <circle cx="280" cy="340" r="35" fill="rgba(255,255,255,0.04)"/>
                <rect x="95" y="100" width="70" height="180" rx="8" fill="rgba(255,255,255,0.12)"/>
                <rect x="97" y="160" width="66" height="120" fill="rgba(155,93,229,0.6)"/>
                <rect x="97" y="100" width="66" height="60"  fill="rgba(255,255,255,0.08)"/>
                <rect x="105" y="170" width="20" height="20" rx="4" fill="rgba(255,255,255,0.3)"/>
                <rect x="135" y="175" width="18" height="18" rx="4" fill="rgba(255,255,255,0.25)"/>
                <rect x="112" y="195" width="22" height="22" rx="4" fill="rgba(255,255,255,0.2)"/>
                <rect x="145" y="75"  width="6" height="125" rx="3" fill="#f72585"/>
                <rect x="145" y="85"  width="6" height="12" fill="rgba(255,255,255,0.5)"/>
                <rect x="145" y="113" width="6" height="12" fill="rgba(255,255,255,0.5)"/>
                <rect x="145" y="141" width="6" height="12" fill="rgba(255,255,255,0.5)"/>
                <rect x="93"  y="97"  width="74" height="8" rx="4" fill="rgba(255,255,255,0.3)"/>
                <rect x="93"  y="276" width="74" height="8" rx="4" fill="rgba(255,255,255,0.2)"/>
                <circle cx="110" cy="220" r="3" fill="rgba(255,255,255,0.3)"/>
                <circle cx="125" cy="240" r="2" fill="rgba(255,255,255,0.25)"/>
                <circle cx="140" cy="210" r="3" fill="rgba(255,255,255,0.2)"/>
                <ellipse cx="60" cy="272" rx="28" ry="10" fill="rgba(255,255,255,0.1)"/>
                <rect x="32"  y="202" width="56" height="70" rx="6" fill="rgba(255,255,255,0.1)"/>
                <rect x="34"  y="232" width="52" height="40" fill="rgba(194,24,91,0.5)"/>
                <ellipse cx="60" cy="202" rx="28" ry="8" fill="rgba(255,255,255,0.2)"/>
                <circle cx="55" cy="193" r="7" fill="#f72585"/>
                <path d="M55,186 Q65,171 70,176" stroke="#81c784" stroke-width="2" fill="none"/>
                <rect x="210" y="140" width="50" height="145" rx="6" fill="rgba(255,255,255,0.1)"/>
                <rect x="220" y="120" width="30" height="25"  rx="4" fill="rgba(255,255,255,0.15)"/>
                <rect x="225" y="110" width="20" height="15"  rx="3" fill="rgba(255,255,255,0.2)"/>
                <rect x="212" y="200" width="46" height="85"  fill="rgba(247,37,133,0.35)"/>
                <rect x="216" y="175" width="38" height="40"  rx="4" fill="rgba(255,255,255,0.15)"/>
                <text x="235" y="200" text-anchor="middle" font-size="9" fill="rgba(255,255,255,0.9)" font-family="Nunito,sans-serif" font-weight="700">SOr 🧃</text>
                <text x="170" y="118" font-size="18" fill="rgba(247,37,133,0.55)">♥</text>
                <text x="38"  y="148" font-size="13" fill="rgba(247,37,133,0.4)">♥</text>
                <text x="252" y="252" font-size="15" fill="rgba(247,37,133,0.5)">♥</text>
                <text x="80"  y="360" font-size="12" fill="rgba(247,37,133,0.3)">♥</text>
            </svg>
        </div>
        <div class="left-text">
            <h2>Descoperă băuturi<br>pe gustul tău ♥</h2>
            <p>Ceaiuri, sucuri, siropuri și mult mai mult<br>— organizate pentru tine.</p>
        </div>
    </div>

    <div class="right">
        <div class="box">
            <div class="logo">
                <h1>S<span>O</span>r 🧃</h1>
                <p>Soft Drink Organizer</p>
            </div>
            <div class="tabs">
                <button class="tab active" data-tab="login">Login</button>
                <button class="tab" data-tab="register">Register</button>
            </div>
            <div id="auth-message" class="msg hidden"></div>
            <div id="tab-login" class="auth-form">
                <div class="fg">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" placeholder="email@exemplu.com">
                </div>
                <div class="fg">
                    <label for="login-password">Parolă</label>
                    <input type="password" id="login-password" placeholder="••••••••">
                </div>
                <button id="btn-login" class="btn-pill">Intră în cont</button>
            </div>
            <div id="tab-register" class="auth-form hidden">
                <div class="fg">
                    <label for="reg-username">Username</label>
                    <input type="text" id="reg-username" placeholder="username">
                </div>
                <div class="fg">
                    <label for="reg-email">Email</label>
                    <input type="email" id="reg-email" placeholder="email@exemplu.com">
                </div>
                <div class="fg">
                    <label for="reg-password">Parolă</label>
                    <input type="password" id="reg-password" placeholder="minim 8 caractere">
                </div>
                <div class="heart-wrap">
                    <button id="btn-register" class="btn-heart">
                        <svg class="heart-svg" viewBox="0 0 200 72" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100,66 C96,62 8,34 8,18 C8,7 17,2 28,2 C42,2 56,11 64,19 C72,11 86,2 100,2 C114,2 128,11 136,19 C144,11 158,2 172,2 C183,2 192,7 192,18 C192,34 104,62 100,66Z" fill="#f72585"/>
                        </svg>
                        <span>Creează cont ♥</span>
                    </button>
                </div>
            </div>
            <div class="switch">
                <span id="switch-text">Nu ai cont?</span>
                <a href="#" id="switch-link">Înregistrează-te ♥</a>
            </div>
        </div>
    </div>

</div>
<script src="/public/js/auth.js"></script>
</body>
</html>