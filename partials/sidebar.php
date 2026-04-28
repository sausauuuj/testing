<?php
declare(strict_types=1);

$sidebarLogoCandidates = [
    'assets/images/DEPDev_Logo_High-res.svg.png',
    'assets/images/DEPDev_Logo_High-res.svg',
];

$sidebarLogo = null;

foreach ($sidebarLogoCandidates as $candidate) {
    if (is_file(__DIR__ . '/../' . $candidate)) {
        $sidebarLogo = $candidate;
        break;
    }
}
?>
<aside id="sidebar" class="screen-only app-sidebar -translate-x-full lg:translate-x-0">
    <div class="app-sidebar__panel">
        <div class="app-sidebar__brand">
            <button id="toggleSidebarCompact" type="button" class="app-sidebar__brand-main" aria-label="Toggle sidebar">
                <div class="app-sidebar__brand-mark">
                    <?php if ($sidebarLogo !== null): ?>
                        <img src="<?= escape_html($sidebarLogo); ?>" alt="DEPDev IX logo" class="app-sidebar__brand-image">
                    <?php else: ?>
                        <span>IX</span>
                    <?php endif; ?>
                </div>
                <div class="app-sidebar__brand-copy">
                    <h1 class="app-sidebar__brand-title">DEPDev IX</h1>
                    <p class="app-sidebar__brand-subtitle">Inventory Management System</p>
                </div>
            </button>
            <button id="closeSidebar" type="button" class="app-sidebar__close lg:hidden" aria-label="Close menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </div>
        <nav class="sidebar-nav">
            <a href="#dashboard" class="nav-anchor sidebar-link active">
                <span class="sidebar-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5 12 4l9 7.5"></path><path d="M5 10.5V20h14v-9.5"></path></svg>
                </span>
                <span class="sidebar-link__label">Dashboard</span>
            </a>
            <a href="#registration" class="nav-anchor sidebar-link">
                <span class="sidebar-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M17 11h6"></path></svg>
                </span>
                <span class="sidebar-link__label">Officers</span>
            </a>
            <a href="#assets" class="nav-anchor sidebar-link">
                <span class="sidebar-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
                </span>
                <span class="sidebar-link__label">Property</span>
            </a>
            <button id="inventoryNavToggle" type="button" class="sidebar-link sidebar-link--toggle" aria-expanded="false">
                <span class="sidebar-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18"></path><path d="M6 3h12l3 4v13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7l3-4Z"></path><path d="M16 11a4 4 0 0 1-8 0"></path></svg>
                </span>
                <span class="sidebar-link__label">Inventory</span>
                <span class="sidebar-link__caret" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M7 5l5 5-5 5" /></svg>
                </span>
            </button>
            <div id="inventorySubmenu" class="sidebar-submenu hidden">
                <a href="#inventory" data-view="inventory" data-inventory-mode="stock-in" class="nav-anchor sidebar-sublink">
                    <span class="sidebar-sublink__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"></path><path d="m5 12 7-7 7 7"></path></svg>
                    </span>
                    <span class="sidebar-sublink__label">Stock In</span>
                </a>
                <a href="#inventory" data-view="inventory" data-inventory-mode="stock-out" class="nav-anchor sidebar-sublink">
                    <span class="sidebar-sublink__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"></path><path d="m19 12-7 7-7-7"></path></svg>
                    </span>
                    <span class="sidebar-sublink__label">Stock Out</span>
                </a>
            </div>
            <a href="#reports" class="nav-anchor sidebar-link">
                <span class="sidebar-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h8"></path><path d="M8 9h2"></path></svg>
                </span>
                <span class="sidebar-link__label">Reports</span>
            </a>
        </nav>
        <div class="app-sidebar__footer">
            <div class="sidebar-user-info">
                <p class="sidebar-user-name">KRISTY R. MANTE</p>
                <p class="sidebar-user-role">Administrative Assistant III</p>
            </div>
        </div>
    </div>
</aside>
