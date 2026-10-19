<?php
use App\Helpers\Auth;
use App\Helpers\View;

$currentRoute = $_GET['route'] ?? 'dashboard';
?>
<aside class="sidebar-wrapper bg-white border-end shadow-sm" id="sidebar">
    <div class="sidebar-header p-3 border-bottom d-flex align-items-center justify-content-between">
        <a href="<?= View::url('dashboard') ?>" class="d-flex align-items-center text-decoration-none">
            <div class="brand-badge me-2">GDG</div>
            <div>
                <div class="fw-bold text-navy lh-1" style="font-size: 1.05rem;">GD GOENKA</div>
                <div class="text-gold small fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">PUBLIC SCHOOL, PURNEA</div>
            </div>
        </a>
        <button class="btn btn-sm btn-light d-lg-none" id="sidebarCloseBtn">
            <i data-lucide="x" style="width: 18px; height: 18px;"></i>
        </button>
    </div>

    <div class="sidebar-body py-3">
        <div class="sidebar-label px-3 mb-2 text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Main Menu</div>
        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'dashboard' ? 'active' : '' ?>" href="<?= View::url('dashboard') ?>">
                    <i data-lucide="layout-dashboard" class="nav-icon"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with($currentRoute, 'leads') && $currentRoute !== 'leads/unassigned' ? 'active' : '' ?>" href="<?= View::url('leads') ?>">
                    <i data-lucide="users" class="nav-icon"></i>
                    <span>Leads Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'leads/unassigned' ? 'active' : '' ?>" href="<?= View::url('leads/unassigned') ?>">
                    <i data-lucide="user-x" class="nav-icon"></i>
                    <span>Unassigned Leads</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'tasks' ? 'active' : '' ?>" href="<?= View::url('tasks') ?>">
                    <i data-lucide="check-square" class="nav-icon"></i>
                    <span>Tasks & Actions</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'followups' ? 'active' : '' ?>" href="<?= View::url('followups') ?>">
                    <i data-lucide="phone-call" class="nav-icon"></i>
                    <span>Follow-ups & Calls</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'documents' ? 'active' : '' ?>" href="<?= View::url('documents') ?>">
                    <i data-lucide="file-check-2" class="nav-icon"></i>
                    <span>Admission Documents</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'reports' ? 'active' : '' ?>" href="<?= View::url('reports') ?>">
                    <i data-lucide="bar-chart-3" class="nav-icon"></i>
                    <span>Reports & Analytics</span>
                </a>
            </li>
        </ul>

        <?php if (Auth::isAdmin()): ?>
        <div class="sidebar-label px-3 mb-2 text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Administration</div>
        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'sessions' ? 'active' : '' ?>" href="<?= View::url('sessions') ?>">
                    <i data-lucide="calendar" class="nav-icon"></i>
                    <span>Academic Sessions</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'users' ? 'active' : '' ?>" href="<?= View::url('users') ?>">
                    <i data-lucide="user-cog" class="nav-icon"></i>
                    <span>Staff & Counselors</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with($currentRoute, 'import') ? 'active' : '' ?>" href="<?= View::url('import') ?>">
                    <i data-lucide="file-spreadsheet" class="nav-icon"></i>
                    <span>Import Leads (Excel)</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'settings' ? 'active' : '' ?>" href="<?= View::url('settings') ?>">
                    <i data-lucide="settings" class="nav-icon"></i>
                    <span>Settings</span>
                </a>
            </li>
            <?php if (Auth::isSuperAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'audit' ? 'active' : '' ?>" href="<?= View::url('audit') ?>">
                    <i data-lucide="shield-check" class="nav-icon"></i>
                    <span>Audit Logs</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer p-3 border-top bg-light">
        <div class="d-flex align-items-center justify-content-between">
            <div class="text-truncate me-2">
                <div class="fw-semibold text-truncate small"><?= htmlspecialchars(Auth::user()['name'] ?? 'User') ?></div>
                <div class="badge bg-navy-subtle text-navy text-truncate" style="font-size: 0.7rem;">
                    <?= htmlspecialchars(Auth::user()['role_name'] ?? 'Staff') ?>
                </div>
            </div>
            <a href="<?= View::url('logout') ?>" class="btn btn-sm btn-outline-danger p-1" title="Sign Out">
                <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
            </a>
        </div>
    </div>
</aside>
