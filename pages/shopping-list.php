<?php

/**
 * pages/shopping-list.php
 *
 * Doua moduri:
 *  1. ?token=xxx  → pagina publica read-only (fara auth)
 *  2. normal      → pagina privata cu liste, iteme, share etc.
 */

session_start();
error_reporting(E_ALL);

require_once __DIR__ . '/../config/Bootstrap.php';
require_once __DIR__ . '/../models/ShopppingList.php';
require_once __DIR__ . '/../models/ShoppingListItem.php';
require_once __DIR__ . '/../repositories/ShoppingListRepository.php';

/** @var AuthGuard  $guard */
/** @var UserService $userService */
/** @var PDO        $pdo */

$sharedToken  = trim($_GET['token'] ?? '');
$isSharedView = $sharedToken !== '';

// modul public cu share token
if ($isSharedView) {
    $repo       = new ShoppingListRepository($pdo);
    $sharedList = $repo->findByToken($sharedToken);

    $title    = $sharedList
            ? 'SOr — ' . htmlspecialchars($sharedList->name, ENT_QUOTES, 'UTF-8')
            : 'SOr — Lista partajata';
    $extraCss = ['/public/css/shopping-list.css'];
    $extraJs  = [];

    require_once __DIR__ . '/../templates/header.php';
    ?>
    <div class="shared-page">
        <?php if ($sharedList === null): ?>
            <div class="shared-empty">
                <span class="shared-empty-icon">🔗</span>
                <h2>Link invalid sau expirat</h2>
                <p>Lista nu mai este partajata sau link-ul este gresit.</p>
                <a href="/" class="btn-primary">Inapoi acasa</a>
            </div>
        <?php else: ?>
            <header class="shared-header">
                <div class="shared-brand">
                    <a href="/" class="brand-link">SOr</a>
                </div>
                <div class="shared-title-wrap">
                    <h1 class="shared-list-title">
                        <?= htmlspecialchars($sharedList->name, ENT_QUOTES, 'UTF-8') ?>
                    </h1>
                    <span class="shared-badge">Lista partajata</span>
                </div>
            </header>

            <div class="shared-content" id="shared-content"
                 data-token="<?= htmlspecialchars($sharedToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Se incarca lista…</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $extraJs = ['/public/js/shopping-list.js'];
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

// modul privat, necesita autentificare
$guard->requireAuth();

$currentUser = $userService->getCurrentUser();
$username    = $currentUser?->username ?? 'Utilizator';
$initials    = strtoupper(substr($username, 0, 1));

$title      = 'SOr — Listele mele';
$extraCss   = ['/public/css/shopping-list.css'];
$extraJs    = ['/public/js/shopping-list.js'];
$activePage = 'shopping-list';

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/navbar.php';
?>

    <main class="sl-layout">

        <!-- sidebar stanga cu listele -->
        <aside class="sl-sidebar" id="sl-sidebar">

            <div class="sl-sidebar-header">
                <h2 class="sl-sidebar-title">Listele mele</h2>
                <button class="sl-mobile-close" id="sl-mobile-close" aria-label="Inchide sidebar">✕</button>
            </div>

            <!-- loader si continutul listelor -->
            <div class="sl-lists-container" id="sl-lists-container">
                <div class="sl-loading">
                    <div class="spinner"></div>
                </div>
            </div>

            <!-- formular lista noua cu mood picker -->
            <div class="sl-new-list">
                <!-- mood picker, randarea se face din JS -->
                <div class="sl-mood-picker-wrap">
                    <span class="sl-mood-picker-label">Tip lista</span>
                    <div id="sl-mood-picker"></div>
                </div>
                <div class="sl-new-list-input-wrap" style="margin-top:0.5rem">
                    <input
                            type="text"
                            id="sl-new-list-name"
                            class="sl-input"
                            placeholder="Nume lista noua…"
                            maxlength="200"
                            autocomplete="off"
                    >
                    <button class="sl-btn-create" id="sl-btn-create" title="Creeaza">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M8 2v12M2 8h12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- continut dreapta cu itemele listei selectate -->
        <section class="sl-main" id="sl-main">

            <!-- stare initiala, nicio lista selectata -->
            <div class="sl-empty-state" id="sl-empty-state">
                <span class="sl-empty-icon">🛒</span>
                <h3>Nicio lista selectata</h3>
                <p>Selecteaza o lista din stanga sau creeaza una noua.</p>
                <a href="/pages/catalog.php" class="btn-primary">Mergi la catalog</a>
            </div>

            <!-- header lista selectata, ascuns initial -->
            <div class="sl-list-header" id="sl-list-header" style="display:none">
                <div class="sl-list-header-left">

                    <!-- numele listei si badge-ul de mood -->
                    <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap">
                        <h2 class="sl-list-name" id="sl-list-name">—</h2>
                        <!-- mood badge cu dropdown pentru schimbarea mood-ului -->
                        <div class="sl-mood-change-wrap">
                            <button class="sl-mood-badge" id="sl-mood-badge">🛒 General</button>
                            <div class="sl-mood-change-dropdown">
                                <button class="sl-mood-change-btn" data-mood="general"><span>🛒</span><span>General</span></button>
                                <button class="sl-mood-change-btn" data-mood="picnic"><span>☀️</span><span>Picnic</span></button>
                                <button class="sl-mood-change-btn" data-mood="acasa"><span>🏠</span><span>Acasa</span></button>
                                <button class="sl-mood-change-btn" data-mood="petrecere"><span>🎉</span><span>Petrecere</span></button>
                                <button class="sl-mood-change-btn" data-mood="sport"><span>💪</span><span>Sport</span></button>
                                <button class="sl-mood-change-btn" data-mood="birou"><span>📚</span><span>Birou</span></button>
                            </div>
                        </div>
                    </div>

                    <!-- bara de progres cumparate -->
                    <div class="sl-progress-wrap" id="sl-progress-wrap">
                        <div class="sl-progress-bar">
                            <div class="sl-progress-fill" id="sl-progress-fill"></div>
                        </div>
                        <span class="sl-progress-label" id="sl-progress-label">0 / 0 cumparate</span>
                    </div>

                    <!-- buget tracker, ascuns daca nu e setat un buget -->
                    <div class="sl-budget-tracker" id="sl-budget-tracker">
                        <div class="sl-budget-track">
                            <div class="sl-budget-fill" id="sl-budget-fill"></div>
                        </div>
                        <span class="sl-budget-label" id="sl-budget-label">0 RON</span>
                    </div>

                    <!-- buton si input pentru setarea bugetului -->
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.4rem;flex-wrap:wrap">
                        <button class="sl-budget-edit-btn" id="sl-budget-edit">
                            💰 Buget
                        </button>
                        <div class="sl-budget-input-wrap" id="sl-budget-input-wrap">
                            <input type="number" class="sl-budget-input" id="sl-budget-input"
                                   placeholder="ex: 80" min="0" step="0.01">
                            <button class="sl-budget-save-btn" id="sl-budget-save">Salveaza</button>
                            <button class="sl-budget-clear-btn" id="sl-budget-clear">✕</button>
                        </div>
                    </div>
                </div>

                <!-- butoanele de actiuni din header -->
                <div class="sl-list-header-actions">
                    <button class="sl-btn-icon sl-btn-shop-mode" id="sl-btn-shop-mode" title="Mod cumparaturi">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="2"/>
                            <path d="M16 10a4 4 0 01-8 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Cumpara</span>
                    </button>
                    <button class="sl-btn-icon sl-btn-share" id="sl-btn-share" title="Partajeaza">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <circle cx="18" cy="5" r="3" stroke="currentColor" stroke-width="2"/>
                            <circle cx="6"  cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            <circle cx="18" cy="19" r="3" stroke="currentColor" stroke-width="2"/>
                            <path d="M8.59 13.51l6.83 3.98M15.41 6.51l-6.82 3.98" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Partajeaza</span>
                    </button>
                    <button class="sl-btn-icon sl-btn-clear" id="sl-btn-clear" title="Sterge cumparate">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <polyline points="20 6 9 17 4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Sterge cumparate</span>
                    </button>
                    <button class="sl-btn-icon sl-btn-export" id="sl-btn-export" title="Export CSV">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M12 3v13M7 11l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M3 19h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Export</span>
                    </button>
                </div>
            </div>

            <!-- lista de iteme -->
            <div class="sl-items-container" id="sl-items-container"></div>

            <!-- footer sticky cu totalul estimat -->
            <div class="sl-footer" id="sl-footer" style="display:none">
                <div class="sl-footer-inner">
                    <span class="sl-total-label">Total estimat</span>
                    <span class="sl-total-value" id="sl-total-value">0,00 RON</span>
                </div>
            </div>

        </section>

        <!-- buton mobil pentru a deschide sidebar-ul -->
        <button class="sl-mobile-fab" id="sl-mobile-fab" aria-label="Listele mele">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="5"  width="18" height="2" rx="1" fill="currentColor"/>
                <rect x="3" y="11" width="18" height="2" rx="1" fill="currentColor"/>
                <rect x="3" y="17" width="11" height="2" rx="1" fill="currentColor"/>
            </svg>
            <span>Liste</span>
        </button>

    </main>

    <!-- modal share -->
    <div class="modal-overlay" id="share-modal" role="dialog" aria-modal="true" aria-labelledby="share-modal-title" style="display:none">
        <div class="modal-box">
            <button class="modal-close" id="share-modal-close" aria-label="Inchide">✕</button>
            <div class="modal-icon">🔗</div>
            <h3 class="modal-title" id="share-modal-title">Partajeaza lista</h3>
            <p class="modal-desc">Oricine are acest link poate vizualiza lista (read-only).</p>

            <div class="share-link-wrap">
                <input type="text" class="share-link-input" id="share-link-input" readonly>
                <button class="share-link-copy" id="share-link-copy">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="2"/>
                        <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    Copiaza
                </button>
            </div>

            <button class="btn-danger-outline" id="share-disable-btn">
                Dezactiveaza partajarea
            </button>
        </div>
    </div>

    <!-- overlay mod cumparaturi -->
    <div class="shop-mode-overlay" id="shop-mode-overlay" style="display:none" role="dialog" aria-modal="true">

        <div class="sm-header">
            <div class="sm-progress-info">
                <span class="sm-counter" id="sm-counter">1 / 5</span>
                <div class="sm-progress-track">
                    <div class="sm-progress-fill" id="sm-progress-fill"></div>
                </div>
            </div>
            <button class="sm-close" id="sm-close">✕ Iesi</button>
        </div>

        <div class="sm-stage">
            <div class="sm-card" id="sm-card">
                <div class="sm-card-inner" id="sm-card-inner"></div>
            </div>
        </div>

        <div class="sm-actions">
            <button class="sm-btn sm-btn-skip" id="sm-btn-skip">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                    <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
                <span>Skip</span>
            </button>
            <div class="sm-qty-wrap">
                <button class="sm-qty-btn" id="sm-qty-dec">−</button>
                <span class="sm-qty-val" id="sm-qty-val">1</span>
                <button class="sm-qty-btn" id="sm-qty-inc">+</button>
            </div>
            <button class="sm-btn sm-btn-check" id="sm-btn-check">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                    <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Cumparat</span>
            </button>
        </div>

        <!-- ecranul final dupa ce ai parcurs toate produsele -->
        <div class="sm-done" id="sm-done" style="display:none">
            <div class="sm-done-icon">🎉</div>
            <h2 class="sm-done-title">Lista completa!</h2>
            <p class="sm-done-sub" id="sm-done-sub"></p>
            <div class="sm-done-total" id="sm-done-total"></div>
            <button class="sm-done-btn" id="sm-done-btn">Inapoi la lista</button>
        </div>

    </div>

    <!-- modal confirmare stergere -->
    <div class="modal-overlay" id="confirm-modal" role="dialog" aria-modal="true" style="display:none">
        <div class="modal-box modal-box--sm">
            <div class="modal-icon">🗑</div>
            <h3 class="modal-title" id="confirm-modal-title">Confirmare</h3>
            <p class="modal-desc" id="confirm-modal-desc">Esti sigur?</p>
            <div class="modal-actions">
                <button class="btn-secondary" id="confirm-cancel">Anuleaza</button>
                <button class="btn-danger"    id="confirm-ok">Sterge</button>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>