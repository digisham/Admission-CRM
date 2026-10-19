<?php
use App\Helpers\Auth;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Models\AcademicSession;

$activeSession = AcademicSession::getActive();
$selectedSessionId = $_SESSION['selected_session_id'] ?? $activeSession['id'];
$currentSession = AcademicSession::find((int)$selectedSessionId) ?: $activeSession;
$allSessions = AcademicSession::all();
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? Sanitizer::e($pageTitle) . ' | ' : '' ?>GD Goenka Public School, Purnea — Admission CRM</title>
    
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Modern SaaS Design System Styles -->
    <link rel="stylesheet" href="<?= View::asset('css/style.css') ?>">
</head>
<body>
<div class="app-wrapper">
    <!-- Sidebar -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- Main Container -->
    <div class="main-wrapper">
        <!-- Top Navbar -->
        <header class="topbar px-3 px-md-4">
            <div class="d-flex align-items-center justify-content-between w-100">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-light border d-lg-none" id="sidebarToggleBtn">
                        <i data-lucide="menu" style="width: 18px; height: 18px;"></i>
                    </button>
                    
                    <!-- Session Selector Pill -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border rounded-pill px-3 py-1 dropdown-toggle d-flex align-items-center gap-2 fw-semibold text-dark shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.82rem;">
                            <i data-lucide="calendar" class="text-primary" style="width: 15px; height: 15px;"></i>
                            <span>Session: <?= Sanitizer::e($currentSession['session_label']) ?></span>
                            <?php if (!empty($currentSession['is_active'])): ?>
                                <span class="badge bg-success rounded-pill" style="font-size: 0.65rem;">Active</span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu shadow-sm border-0" style="border-radius: 14px;">
                            <li class="dropdown-header text-uppercase text-muted" style="font-size: 0.68rem;">Switch Academic Intake</li>
                            <?php foreach ($allSessions as $s): ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-center justify-content-between py-2 <?= (int)$s['id'] === (int)$selectedSessionId ? 'active bg-primary' : '' ?>" 
                                   href="<?= View::url('dashboard/switch-session&session_id=' . $s['id']) ?>" style="border-radius: 8px;">
                                    <span><?= Sanitizer::e($s['session_label']) ?></span>
                                    <?php if (!empty($s['is_active'])): ?>
                                        <span class="badge <?= (int)$s['id'] === (int)$selectedSessionId ? 'bg-white text-primary' : 'bg-success' ?> rounded-pill ms-2" style="font-size: 0.65rem;">Active</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Right Side Info & Profile -->
                <div class="d-flex align-items-center gap-3">
                    <!-- Live Time Clock Pill -->
                    <div class="d-none d-md-flex align-items-center gap-1 text-muted small fw-semibold px-3 py-1 bg-light rounded-pill border" style="font-size: 0.78rem;">
                        <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
                        <span id="liveClockDisplay"><?= date('h:i A') ?></span>
                    </div>

                    <!-- Quick Add Enquiry Action -->
                    <a href="<?= View::url('leads/create') ?>" class="btn btn-navy btn-sm btn-pill d-flex align-items-center gap-1 shadow-sm">
                        <i data-lucide="user-plus" style="width: 15px; height: 15px;"></i>
                        <span class="d-none d-sm-inline">Add Enquiry</span>
                    </a>

                    <!-- Profile Dropdown -->
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="bento-avatar avatar-blue" style="width: 34px; height: 34px;">
                                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <span class="d-none d-md-inline fw-bold small text-truncate" style="max-width: 130px;">
                                <?= Sanitizer::e($user['name'] ?? 'User') ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 p-2" style="border-radius: 14px; min-width: 200px;">
                            <li class="px-3 py-2">
                                <div class="fw-bold text-dark"><?= Sanitizer::e($user['name'] ?? '') ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= Sanitizer::e($user['email'] ?? '') ?></div>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 rounded-2" href="<?= View::url('leads') ?>">
                                    <i data-lucide="users" class="me-2 text-muted" style="width: 16px; height: 16px;"></i> All Enquiries
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 rounded-2" href="<?= View::url('tasks') ?>">
                                    <i data-lucide="check-square" class="me-2 text-muted" style="width: 16px; height: 16px;"></i> Action Tasks
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 rounded-2 text-danger" href="<?= View::url('logout') ?>">
                                    <i data-lucide="log-out" class="me-2" style="width: 16px; height: 16px;"></i> Sign Out
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="content-body flex-grow-1 p-3 p-md-4">
            <div class="container-fluid px-0">
                <?php require __DIR__ . '/alerts.php'; ?>
