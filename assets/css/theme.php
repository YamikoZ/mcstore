<?php
/**
 * Dynamic CSS Variables from Database Settings
 * Loaded as: <link rel="stylesheet" href="/mcstore/assets/css/theme.php">
 */
header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=300');

// Bootstrap minimal — we only need DB + Settings
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/classes/Database.php';
require_once BASE_PATH . '/classes/Settings.php';

$s = fn($key, $default) => Settings::get($key, $default);
?>
@import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap');

:root {
    --color-primary: <?= $s('primary_color', '#38bdf8') ?>;
    --color-secondary: <?= $s('secondary_color', '#0ea5e9') ?>;
    --color-accent: <?= $s('accent_color', '#7dd3fc') ?>;
    --color-bg: <?= $s('bg_color', '#0a1628') ?>;
    --color-surface: <?= $s('card_bg', 'rgba(15,30,60,0.7)') ?>;
    --color-surface-dark: rgba(5,15,35,0.6);
    --color-text: <?= $s('text_color', '#e0f2fe') ?>;
    --color-text-muted: #94a3b8;
    --color-nav-bg: rgba(8,20,45,0.85);
    --color-border: rgba(56,189,248,0.15);
    --font-family: <?= $s('font_family', "'Prompt', 'Plus Jakarta Sans', 'Noto Sans Thai', sans-serif") ?>;

    /* Gradients */
    --gradient-ocean: linear-gradient(135deg, #0ea5e9, #0284c7, #0369a1);
    --gradient-sky: linear-gradient(135deg, #38bdf8, #0ea5e9);
    --gradient-hero: linear-gradient(160deg, #0a1628 0%, #0c1e3a 40%, #0f2847 70%, #112d4e 100%);
    --gradient-card: linear-gradient(145deg, rgba(15,40,71,0.8), rgba(10,22,40,0.9));
    --gradient-btn: linear-gradient(135deg, #0ea5e9, #0284c7);
    --gradient-btn-hover: linear-gradient(135deg, #38bdf8, #0ea5e9);
}

/* ─── Base ─────────────────────────────────────────── */
body {
    background: var(--gradient-hero);
    background-attachment: fixed;
    min-height: 100vh;
}

/* Scrollbar */
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: var(--color-bg); }
::-webkit-scrollbar-thumb { background: var(--gradient-ocean); border-radius: 4px; }

/* Selection */
::selection { background: #0ea5e9; color: #fff; }

/* ─── Animations ───────────────────────────────────── */
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes glow { 0%, 100% { box-shadow: 0 0 5px rgba(56,189,248,0.3); } 50% { box-shadow: 0 0 20px rgba(56,189,248,0.5); } }

.animate-fade-in { animation: fadeIn 0.5s ease-out; }
.animate-slide-up { animation: slideUp 0.6s ease-out; }
.animate-glow { animation: glow 2s ease-in-out infinite; }

/* ─── Cards ────────────────────────────────────────── */
.card {
    background: var(--gradient-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 4px 24px rgba(0,0,0,0.3), 0 0 40px rgba(14,165,233,0.05);
    backdrop-filter: blur(12px);
}

.card-hover { transition: transform 0.3s, box-shadow 0.3s; }
.card-hover:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.4), 0 0 30px rgba(56,189,248,0.1);
}

/* ─── Buttons ──────────────────────────────────────── */
.btn-primary {
    background: var(--gradient-btn) !important;
    color: #fff;
    border: none;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(14,165,233,0.3);
}
.btn-primary:hover {
    background: var(--gradient-btn-hover) !important;
    box-shadow: 0 6px 25px rgba(56,189,248,0.4);
    transform: translateY(-1px);
}

/* ─── Navbar ───────────────────────────────────────── */
nav {
    backdrop-filter: blur(20px) saturate(1.5);
    border-bottom: 1px solid var(--color-border);
}

.nav-link {
    color: var(--color-text);
    transition: color 0.2s;
}
.nav-link:hover {
    color: var(--color-primary);
}

/* ─── Gradient text ────────────────────────────────── */
.gradient-text {
    background: var(--gradient-sky);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ─── Glass effect ─────────────────────────────────── */
.glass {
    background: rgba(15,30,60,0.5);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(56,189,248,0.1);
}

/* ─── Form inputs ──────────────────────────────────── */
input[type="text"], input[type="url"], input[type="number"], input[type="email"], input[type="password"], textarea, select {
    background: rgba(5,15,35,0.6) !important;
    color: var(--color-text) !important;
    border: 1px solid var(--color-border) !important;
    transition: border-color 0.2s, box-shadow 0.2s;
}
input:focus, textarea:focus, select:focus {
    border-color: var(--color-primary) !important;
    box-shadow: 0 0 0 3px rgba(56,189,248,0.15) !important;
}
input::placeholder, textarea::placeholder { color: var(--color-text-muted) !important; opacity: 0.6; }

/* ─── Table ────────────────────────────────────────── */
table thead { color: var(--color-text); }
table tbody tr { border-color: var(--color-border) !important; }
table tbody tr:hover { background: rgba(56,189,248,0.06) !important; }

/* ─── Status badges ────────────────────────────────── */
.badge-count {
    background: var(--gradient-btn);
    color: #fff;
}

/* ─── Override helpers for dark ocean theme ────────── */
.opacity-60 { opacity: 0.7 !important; }
.opacity-50 { opacity: 0.65 !important; }
.opacity-40 { opacity: 0.55 !important; }
.border-white\/10, .border-white\/5 { border-color: var(--color-border) !important; }
.bg-white\/5 { background: rgba(56,189,248,0.06) !important; }
.hover\:bg-white\/5:hover { background: rgba(56,189,248,0.1) !important; }

/* ═══════════════════════════════════════════════════════
   Responsive
   ═══════════════════════════════════════════════════════ */

/* ─── Mobile: ≤ 640px ─────────────────────────────── */
@media (max-width: 640px) {
    body { font-size: 14px; }

    h1 { font-size: 1.5rem !important; }
    h2 { font-size: 1.25rem !important; }
    h3 { font-size: 1.1rem !important; }

    .card { padding: 1rem; border-radius: 0.625rem; }

    .btn-primary { padding: 0.65rem 1rem; font-size: 0.875rem; }

    /* Stack grids */
    .grid-cols-2 { grid-template-columns: 1fr !important; }
    .grid-cols-3 { grid-template-columns: 1fr !important; }
    .grid-cols-4 { grid-template-columns: repeat(2, 1fr) !important; }

    /* Table horizontal scroll */
    .overflow-x-auto { -webkit-overflow-scrolling: touch; }
    table { font-size: 0.8rem; }
    table th, table td { padding: 0.5rem 0.6rem !important; }

    /* Smaller icons on mobile */
    .text-5xl { font-size: 2.5rem !important; }
    .text-3xl { font-size: 1.75rem !important; }

    /* Tighter spacing */
    .gap-6 { gap: 1rem !important; }
    .gap-4 { gap: 0.75rem !important; }
    .mb-8, .mb-10 { margin-bottom: 1.5rem !important; }
    .py-8 { padding-top: 1.25rem !important; padding-bottom: 1.25rem !important; }
    .px-4 { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
    .px-6 { padding-left: 1rem !important; padding-right: 1rem !important; }
    .p-6 { padding: 1rem !important; }

    /* Navbar */
    nav .h-16 { height: 3.5rem; }
    nav .text-xl { font-size: 1.1rem !important; }

    /* Form inputs */
    input, textarea, select { font-size: 16px !important; /* prevent iOS zoom */ }

    /* Card hover — disable on touch */
    .card-hover:hover { transform: none; }
}

/* ─── Small tablets: 641–768px ────────────────────── */
@media (min-width: 641px) and (max-width: 768px) {
    body { font-size: 14.5px; }

    .grid-cols-3 { grid-template-columns: repeat(2, 1fr) !important; }
    .grid-cols-4 { grid-template-columns: repeat(2, 1fr) !important; }

    .gap-6 { gap: 1.25rem !important; }
    .p-6 { padding: 1.25rem !important; }
}

/* ─── Tablet: 769–1024px ──────────────────────────── */
@media (min-width: 769px) and (max-width: 1024px) {
    .grid-cols-4 { grid-template-columns: repeat(3, 1fr) !important; }
}

/* ─── Large desktop: ≥1280px ──────────────────────── */
@media (min-width: 1280px) {
    body { font-size: 15.5px; }
}

/* ─── Safe area (notch phones) ────────────────────── */
@supports (padding: env(safe-area-inset-bottom)) {
    body { padding-bottom: env(safe-area-inset-bottom); }
    nav { padding-left: env(safe-area-inset-left); padding-right: env(safe-area-inset-right); }
}

/* ─── Touch device helpers ────────────────────────── */
@media (hover: none) and (pointer: coarse) {
    .card-hover:hover { transform: none; box-shadow: 0 4px 24px rgba(0,0,0,0.3); }
    .btn-primary:hover { transform: none; }
    .btn-primary:active { transform: scale(0.97); }
    .card-hover:active { transform: scale(0.98); }
}

/* ─── Reduced motion ──────────────────────────────── */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
}

/* ─── Print ───────────────────────────────────────── */
@media print {
    nav, footer, .btn-primary, .sidebar-link { display: none !important; }
    body { background: #fff !important; color: #000 !important; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; background: #fff !important; }
}
