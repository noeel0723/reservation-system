<?php
/**
 * Sidebar User (Staff) - Skillset Style
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Desktop -->
<nav class="sidebar d-none d-lg-flex" id="sidebarDesktop">
    <div class="sidebar-content">
        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="d-flex flex-column align-items-center">
                <img src="<?= BASE_URL ?>/assets/pictures/Logo_TVRI.svg.png" alt="TVRI" class="sidebar-logo mb-2" width="72" height="72" style="width:72px;height:72px;object-fit:contain;display:block">
                <span class="brand-text"><?= SITE_NAME ?></span>
            </div>
        </div>

        <!-- Main Navigation -->
        <div class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/user/dashboard.php">
                        <i class="bi bi-grid me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'reservasi_baru.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/user/reservasi_baru.php">
                        <i class="bi bi-plus-circle me-2"></i>Reservasi Baru
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'riwayat.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/user/riwayat.php">
                        <i class="bi bi-clock-history me-2"></i>Riwayat Reservasi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'calendar.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/user/calendar.php">
                        <i class="bi bi-calendar3 me-2"></i>Kalender
                    </a>
                </li>
            </ul>
        </div>

        <!-- Bottom Navigation -->
        <div class="sidebar-bottom">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/user/profile.php">
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
            <img src="<?= BASE_URL ?>/assets/pictures/Logo_TVRI.svg.png" alt="TVRI" class="sidebar-logo me-2" width="60" height="60" style="width:60px;height:60px;object-fit:contain;">
            <span class="fw-bold" style="color: var(--color-midnight-green)"><?= SITE_NAME ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="nav flex-column px-2">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/user/dashboard.php">
                    <i class="bi bi-grid me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'reservasi_baru.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/user/reservasi_baru.php">
                    <i class="bi bi-plus-circle me-2"></i>Reservasi Baru
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'riwayat.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/user/riwayat.php">
                    <i class="bi bi-clock-history me-2"></i>Riwayat Reservasi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'calendar.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/user/calendar.php">
                    <i class="bi bi-calendar3 me-2"></i>Kalender
                </a>
            </li>
        </ul>
        <hr class="mx-3">
        <ul class="nav flex-column px-2">
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>/user/profile.php">
                    <i class="bi bi-gear me-2"></i>Pengaturan
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
                    <?= strtoupper(substr($user['nama_lengkap'] ?? 'U', 0, 1)) ?>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text fw-semibold"><?= htmlspecialchars($user['nama_lengkap'] ?? '') ?></span></li>
                    <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars($user['role'] ?? '') ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/profile.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="content-area">
