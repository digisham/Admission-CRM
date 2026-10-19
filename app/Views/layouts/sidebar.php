<?php
use App\Helpers\Auth;
use App\Helpers\View;
use App\Models\Lead;

$currentRoute = $_GET['route'] ?? 'dashboard';
?>
<aside class="sidebar-wrapper" id="sidebar">
    <!-- Brand Header -->
    <div class="sidebar-header d-flex align-items-center justify-content-between">
        <a href="<?= View::url('dashboard') ?>" class="brand-logo-container">
            <div class="brand-icon-box">
                <i data-lucide="sparkles" style="width: 20px; height: 20px;"></i>
            </div>
            <div>
                <div class="fw-bold text-dark lh-1" style="font-size: 0.98rem; letter-spacing: -0.01em;">GD GOENKA</div>
                <div class="text-muted fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.05em;">PURNEA &bull; ADMISSION CRM</div>
            </div>
        </a>
        <button class="btn btn-sm btn-light border-0 d-lg-none" id="sidebarCloseBtn">
            <i data-lucide="x" style="width: 18px; height: 18px;"></i>
        </button>
    </div>

    <!-- Navigation List -->
    <div class="sidebar-body">
        <div class="sidebar-label">Navigation</div>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'dashboard' ? 'active' : '' ?>" href="<?= View::url('dashboard') ?>">
                    <div class="nav-left">
                        <i data-lucide="home" class="nav-icon"></i>
                        <span>Home</span>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with($currentRoute, 'leads') && $currentRoute !== 'leads/unassigned' ? 'active' : '' ?>" href="<?= View::url('leads') ?>">
                    <div class="nav-left">
                        <i data-lucide="users" class="nav-icon"></i>
                        <span>All Enquiries</span>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'leads/unassigned' ? 'active' : '' ?>" href="<?= View::url('leads/unassigned') ?>">
                    <div class="nav-left">
                        <i data-lucide="user-x" class="nav-icon"></i>
                        <span>Unassigned</span>
                    </div>
                    <span class="sidebar-badge <?= $currentRoute === 'leads/unassigned' ? 'bg-white text-navy' : 'bg-warning-subtle text-dark' ?>">Queue</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'tasks' ? 'active' : '' ?>" href="<?= View::url('tasks') ?>">
                    <div class="nav-left">
                        <i data-lucide="check-square" class="nav-icon"></i>
                        <span>Action Tasks</span>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'followups' ? 'active' : '' ?>" href="<?= View::url('followups') ?>">
                    <div class="nav-left">
                        <i data-lucide="phone-call" class="nav-icon"></i>
                        <span>Call Logs</span>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'documents' ? 'active' : '' ?>" href="<?= View::url('documents') ?>">
                    <div class="nav-left">
                        <i data-lucide="file-check-2" class="nav-icon"></i>
                        <span>Admission Docs</span>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'reports' ? 'active' : '' ?>" href="<?= View::url('reports') ?>">
                    <div class="nav-left">
                        <i data-lucide="bar-chart-2" class="nav-icon"></i>
                        <span>Analytics</span>
                    </div>
                </a>
            </li>
        </ul>

        <?php if (Auth::isAdmin()): ?>
        <div class="sidebar-label mt-2">Administration</div>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'sessions' ? 'active' : '' ?>" href="<?= View::url('sessions') ?>">
                    <div class="nav-left">
                        <i data-lucide="calendar" class="nav-icon"></i>
                        <span>Sessions (2027–28)</span>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'users' ? 'active' : '' ?>" href="<?= View::url('users') ?>">
                    <div class="nav-left">
                        <i data-lucide="user-check" class="nav-icon"></i>
                        <span>Staff & Counselors</span>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with($currentRoute, 'import') ? 'active' : '' ?>" href="<?= View::url('import') ?>">
                    <div class="nav-left">
                        <i data-lucide="file-spreadsheet" class="nav-icon"></i>
                        <span>Excel Import</span>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'settings' ? 'active' : '' ?>" href="<?= View::url('settings') ?>">
                    <div class="nav-left">
                        <i data-lucide="settings" class="nav-icon"></i>
                        <span>Settings</span>
                    </div>
                </a>
            </li>
            <?php if (Auth::isSuperAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentRoute === 'audit' ? 'active' : '' ?>" href="<?= View::url('audit') ?>">
                    <div class="nav-left">
                        <i data-lucide="shield-check" class="nav-icon"></i>
                        <span>Audit Log</span>
                    </div>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>
    </div>

    <!-- User Profile Footer -->
    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2 text-truncate">
                <div class="bento-avatar avatar-blue">
                    <?= strtoupper(substr(Auth::user()['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="text-truncate" style="max-width: 130px;">
                    <div class="fw-bold text-dark small text-truncate"><?= htmlspecialchars(Auth::user()['name'] ?? 'User') ?></div>
                    <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars(Auth::user()['role_name'] ?? 'Staff') ?></div>
                </div>
            </div>
            <a href="<?= View::url('logout') ?>" class="action-dot-btn text-danger" title="Sign Out">
                <i data-lucide="log-out" style="width: 17px; height: 17px;"></i>
            </a>
        </div>
    </div>
</aside>
