<!-- Admin Header -->
<header class="admin-header bg-white border-bottom shadow-sm fixed-top" style="margin-left: var(--admin-sidebar-width); height: var(--admin-header-height); z-index: 1000;">
    <div class="container-fluid h-100">
        <div class="row h-100 align-items-center">
            <div class="col-auto">
                <button class="btn btn-link text-dark" id="sidebarToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
            </div>
            
            <!-- Breadcrumb -->
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                                <?php if ($i === array_key_last($breadcrumbs)): ?>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        <?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </li>
                                <?php else: ?>
                                    <li class="breadcrumb-item">
                                        <a href="<?= htmlspecialchars($crumb['url'] ?? '#', ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
            
            <!-- User Menu -->
            <div class="col-auto">
                <div class="dropdown">
                    <button class="btn btn-link text-dark dropdown-toggle d-flex align-items-center gap-2" 
                            type="button" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle bg-primary text-white">
                                <?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?>
                            </div>
                            <div class="d-none d-md-block text-start">
                                <div class="fw-semibold small"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    <?= htmlspecialchars($_SESSION['admin_roles'][0] ?? 'Administrator', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                        <li><a class="dropdown-item" href="../settings.php" target="_blank"><i class="bi bi-gear me-2"></i>App-Einstellungen</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="logout.php" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Abmelden
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
    .avatar-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
    }
</style>
