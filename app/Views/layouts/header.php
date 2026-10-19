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
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Custom Design Tokens & Styles -->
    <link rel="stylesheet" href="<?= View::asset('css/style.css') ?>">
</head>
<body class="bg-light">
<div class="app-wrapper">
    <!-- Sidebar -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- Main Container -->
    <div class="main-wrapper d-flex flex-column flex-grow-1 min-vh-100">
        <!-- Top Navbar -->
        <header class="topbar bg-white border-bottom shadow-sm sticky-top px-3 py-2">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-secondary btn-sm me-3 d-lg-none" id="sidebarToggleBtn">
                        <i data-lucide="menu" style="width: 20px; height: 20px;"></i>
                    </button>
                    
                    <!-- Session Selector -->
                    <div class="dropdown me-3">
                        <button class="btn btn-sm btn-session-selector dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i data-lucide="calendar" class="me-1" style="width: 15px; height: 15px;"></i>
                            <span class="fw-semibold">Session: <?= Sanitizer::e($currentSession['session_label']) ?></span>
                            <?php if (!empty($currentSession['is_active'])): ?>
                                <span class="badge bg-success ms-1" style="font-size: 0.65rem;">Active</span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu shadow-sm">
                            <li class="dropdown-header text-uppercase text-muted" style="font-size: 0.7rem;">Filter By Session</li>
                            <?php foreach ($allSessions as $s): ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-center justify-content-between <?= (int)$s['id'] === (int)$selectedSessionId ? 'active' : '' ?>" 
                                   href="<?= View::url('dashboard/switch-session&session_id=' . $s['id']) ?>">
                                    <span><?= Sanitizer::e($s['session_label']) ?></span>
                                    <?php if (!empty($s['is_active'])): ?>
                                        <span class="badge bg-success ms-2" style="font-size: 0.65rem;">Active</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="d-flex align-items-center">
                    <!-- Quick Lead Create Button -->
                    <a href="<?= View::url('leads/create') ?>" class="btn btn-sm btn-navy d-flex align-items-center me-3 shadow-sm">
                        <i data-lucide="user-plus" class="me-1" style="width: 15px; height: 15px;"></i>
                        <span class="d-none d-sm-inline">Add Enquiry</span>
                    </a>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar-circle me-2">
                                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <span class="d-none d-md-inline fw-semibold small text-truncate" style="max-width: 140px;">
                                <?= Sanitizer::e($user['name'] ?? 'User') ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li class="dropdown-header">
                                <div class="fw-bold"><?= Sanitizer::e($user['name'] ?? '') ?></div>
                                <div class="small text-muted"><?= Sanitizer::e($user['email'] ?? '') ?></div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= View::url('leads') ?>">
                                    <i data-lucide="users" class="me-2 text-muted" style="width: 16px; height: 16px;"></i> My Leads
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= View::url('tasks') ?>">
                                    <i data-lucide="check-square" class="me-2 text-muted" style="width: 16px; height: 16px;"></i> My Tasks
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= View::url('logout') ?>">
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
