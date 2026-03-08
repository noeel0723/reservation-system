<?php
/**
 * Sidebar Admin - Skillset Style
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Desktop -->
<nav class="sidebar d-none d-lg-flex" id="sidebarDesktop">
    <div class="sidebar-content">
        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="d-flex flex-column align-items-center">
                <img src="<?= BASE_URL ?>/assets/pictures/Logo_TVRI.svg.png" alt="TVRI" class="sidebar-logo mb-2" width="92" height="92" style="width:92px;height:92px;object-fit:contain;display:block">
                <span class="brand-text"><?= SITE_NAME ?></span>
            </div>
        </div>

        <!-- Main Navigation -->
        <div class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php">
                        <i class="bi bi-grid me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'kelola_reservasi.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/kelola_reservasi.php">
                        <i class="bi bi-calendar-check me-2"></i>Reservations
                        <?php
                        $stmtBadge = $pdo->query("SELECT COUNT(*) as cnt FROM reservations WHERE status = 'Pending'");
                        $pendingCount = $stmtBadge->fetch()['cnt'];
                        if ($pendingCount > 0): ?>
                            <span class="badge bg-danger ms-auto"><?= $pendingCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'kelola_resource.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/kelola_resource.php">
                        <i class="bi bi-hdd-stack me-2"></i>Resources
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'kelola_user.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/kelola_user.php">
                        <i class="bi bi-people me-2"></i>Users
                    </a>
                </li>
            </ul>
        </div>

        <!-- Tools Navigation -->
        <div class="sidebar-nav">
            <div class="px-3 pt-2 pb-1" style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted)">Configuration</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'aturan_reservasi.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/aturan_reservasi.php">
                        <i class="bi bi-sliders me-2"></i>Reservation Rules
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'waitlist.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/waitlist.php">
                        <i class="bi bi-list-ol me-2"></i>Queue
                        <?php
                        $wCount = $pdo->query("SELECT COUNT(*) FROM waitlist WHERE status = 'Waiting'")->fetchColumn();
                        if ($wCount > 0): ?>
                            <span class="badge bg-warning text-dark ms-auto" style="font-size:0.65rem"><?= $wCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'log_aktivitas.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/log_aktivitas.php">
                        <i class="bi bi-journal-text me-2"></i>Activity Log
                    </a>
                </li>
            </ul>
        </div>

        <!-- Bottom Navigation -->
        <div class="sidebar-bottom">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/profile.php">
                        <i class="bi bi-gear me-2"></i>Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="<?= BASE_URL ?>/logout.php">
                        <i class="bi bi-box-arrow-left me-2"></i>Log out
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Sidebar Mobile (Offcanvas) -->
<div class="offcanvas offcanvas-start" id="sidebarMobile" tabindex="-1">
    <div class="offcanvas-header">
        <div class="d-flex align-items-center">
            <img src="<?= BASE_URL ?>/assets/pictures/Logo_TVRI.svg.png" alt="TVRI" class="sidebar-logo me-2" width="72" height="72" style="width:72px;height:72px;object-fit:contain;">
            <span class="fw-bold" style="color: var(--color-midnight-green)"><?= SITE_NAME ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="nav flex-column px-2">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php">
                    <i class="bi bi-grid me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'kelola_reservasi.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/kelola_reservasi.php">
                    <i class="bi bi-calendar-check me-2"></i>Reservations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'kelola_resource.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/kelola_resource.php">
                    <i class="bi bi-hdd-stack me-2"></i>Resources
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'kelola_user.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/kelola_user.php">
                    <i class="bi bi-people me-2"></i>Users
                </a>
            </li>
        </ul>
        <div class="px-3 pt-2 pb-1" style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted)">Configuration</div>
        <ul class="nav flex-column px-2">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'aturan_reservasi.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/aturan_reservasi.php">
                    <i class="bi bi-sliders me-2"></i>Reservation Rules
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'waitlist.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/waitlist.php">
                    <i class="bi bi-list-ol me-2"></i>Queue
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'log_aktivitas.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/log_aktivitas.php">
                    <i class="bi bi-journal-text me-2"></i>Activity Log
                </a>
            </li>
        </ul>
        <hr class="mx-3">
        <ul class="nav flex-column px-2">
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>/admin/profile.php">
                    <i class="bi bi-gear me-2"></i>Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" href="<?= BASE_URL ?>/logout.php">
                    <i class="bi bi-box-arrow-left me-2"></i>Log out
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Main Content Area -->
<main class="main-content flex-grow-1">
    <!-- Top Bar -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-link text-dark d-lg-none p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMobile">
                <i class="bi bi-list fs-4"></i>
            </button>
            <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
        </div>
        <div class="topbar-actions">
            <div class="date-chip d-none d-lg-flex">
                <i class="bi bi-calendar3"></i>
                <?= date('d M Y') ?>
            </div>
            <div class="dropdown">
                <div class="avatar-circle" role="button" data-bs-toggle="dropdown">
                    <?= strtoupper(substr($user['nama_lengkap'] ?? 'A', 0, 1)) ?>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text fw-semibold"><?= htmlspecialchars($user['nama_lengkap'] ?? '') ?></span></li>
                    <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars($user['role'] ?? '') ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="content-area">
