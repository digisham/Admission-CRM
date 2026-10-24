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
    
    <!-- Summernote Lite CSS for Rich Text Box Formatting -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Chart.js (Local with CDN fallback) -->
    <script src="<?= View::asset('js/chart.umd.min.js') ?>"></script>
    <script>
        if (typeof Chart === 'undefined') {
            document.write('<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"><\/script>');
        }
    </script>
    
    <!-- Modern SaaS Design System Styles -->
    <link rel="stylesheet" href="<?= View::asset('css/style.css') ?>">
    <script>
        window.APP_BASE_URL = <?= json_encode(\App\Helpers\View::baseUrl()) ?>;
        window.CSRF_TOKEN = <?= json_encode(\App\Helpers\Csrf::token()) ?>;
        window.APP_URL = function(route = '', params = '') {
            let base = window.APP_BASE_URL || '';
            let url = (base ? base : '') + '/index.php';
            if (route) {
                url += '?route=' + encodeURIComponent(route).replace(/%2F/g, '/');
                if (params) {
                    url += (params.startsWith('&') ? '' : '&') + params;
                }
            } else if (params) {
                url += '?' + (params.startsWith('?') ? params.substring(1) : params);
            }
            return url;
        };
    </script>
</head>
<body>
<div id="crm-instant-loader" class="crm-instant-loader" aria-hidden="true"></div>
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
                <div class="d-flex align-items-center gap-1">
                    <!-- School Network IP Badge & 1-Click Copy -->
                    <?php 
                    $schoolIp = \App\Helpers\Network::getServerLanIp(); 
                    $schoolUrl = \App\Helpers\Network::getSchoolNetworkUrl();
                    ?>
                    <?php if ($schoolIp && $schoolUrl): ?>
                    <button type="button" 
                            class="d-none d-lg-inline-flex align-items-center gap-1 px-2 py-1 bg-light rounded-pill border text-muted small shadow-xs btn btn-sm p-0" 
                            style="font-size: 0.76rem; border-color: #e2e8f0 !important;" 
                            title="School Network IP: Click to copy full IP URL for other computers/phones" 
                            onclick="navigator.clipboard.writeText('<?= $schoolUrl ?>'); alert('Copied School Network URL:\n<?= $schoolUrl ?>\n\nOther staff on school Wi-Fi can open this link.');">
                        <span class="spinner-grow spinner-grow-sm text-success" style="width: 7px; height: 7px;" role="status"></span>
                        <i data-lucide="wifi" style="width: 13px; height: 13px;" class="text-success"></i>
                        <span class="fw-bold text-dark font-monospace"><?= $schoolIp ?></span>
                        <i data-lucide="copy" style="width: 11px; height: 11px;" class="text-muted ms-0.5"></i>
                    </button>
                    <?php endif; ?>

                    <!-- Quick Add Enquiry Action -->
                    <!-- <a href="<?= View::url('leads/create') ?>" class="btn btn-navy btn-sm btn-pill d-flex align-items-center gap-1 shadow-sm">
                        <i data-lucide="user-plus" style="width: 15px; height: 15px;"></i>
                        <span class="d-none d-sm-inline">Add Enquiry</span>
                    </a> -->

                    <!-- Notification Bell (Left side of Profile) -->
                    <?php
                    $unreadNotifCount = $user ? \App\Models\Notification::getUnreadCount($user['id']) : 0;
                    $initialNotifs = $user ? \App\Models\Notification::getUnreadForUser($user['id'], 35) : [];
                    ?>
                    <div class="me-1 position-relative" id="notificationTriggerContainer">
                        <button class="btn btn-sm btn-light border rounded-circle d-flex align-items-center justify-content-center position-relative shadow-sm notification-bell-btn" 
                                type="button" id="notificationBellBtn" data-bs-toggle="offcanvas" data-bs-target="#notificationOffcanvas" aria-controls="notificationOffcanvas" 
                                style="width: 38px; height: 38px; padding: 0;" title="Notifications">
                            <i data-lucide="bell" style="width: 18px; height: 18px;" class="text-secondary" id="notificationBellIcon"></i>
                            <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $unreadNotifCount > 0 ? '' : 'd-none' ?>" 
                                  style="font-size: 0.65rem; border: 2px solid #fff; padding: 0.25em 0.5em;">
                                <?= $unreadNotifCount ?>
                            </span>
                        </button>
                    </div>

                    <!-- Profile Dropdown: Avatar -> Name -> Designation -> Dropdown -->
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle gap-2.5 py-1 px-2 rounded-3 hover-bg-light" data-bs-toggle="dropdown" aria-expanded="false" style="transition: all 0.15s ease;">
                         
                            <div class="d-none d-md-flex flex-column text-end lh-1 pe-1" style="max-width: 160px;">
                                <span class="fw-bold text-black text-truncate" style="font-size: 0.85rem; line-height: 1.25;">
                                    <?= Sanitizer::e($user['name'] ?? 'User') ?>
                                </span>
                                <span class="text-muted text-truncate" style="font-size: 0.72rem; line-height: 1.25; margin-top: 2px;">
                                    <?= Sanitizer::e(!empty($user['designation']) ? $user['designation'] : ($user['role_name'] ?? 'Staff')) ?>
                                </span>
                            </div>
                               <div class="bento-avatar avatar-blue shadow-xs flex-shrink-0" style="width: 36px; height: 36px; font-weight: 700; font-size: 0.92rem; border-radius: 10px;">
                                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 p-2 mt-2" style="border-radius: 14px; min-width: 220px;">
                            <li class="px-3 py-2.5 bg-light rounded-3 mb-1">
                                <div class="fw-bold text-navy" style="font-size: 0.88rem;"><?= Sanitizer::e($user['name'] ?? '') ?></div>
                                <div class="text-muted text-truncate" style="font-size: 0.74rem;"><?= Sanitizer::e($user['email'] ?? '') ?></div>
                                <div class="badge bg-primary-subtle text-primary mt-1.5" style="font-size: 0.68rem; font-weight: 600;">
                                    <?= Sanitizer::e(!empty($user['designation']) ? $user['designation'] : ($user['role_name'] ?? 'Staff')) ?>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 rounded-2 fw-semibold d-flex align-items-center gap-2" href="<?= View::url('profile') ?>">
                                    <i data-lucide="user" class="text-primary" style="width: 16px; height: 16px;"></i>
                                    <span>My Profile</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 rounded-2 d-flex align-items-center gap-2" href="<?= View::url('leads') ?>">
                                    <i data-lucide="users" class="text-muted" style="width: 16px; height: 16px;"></i>
                                    <span>All Enquiries</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 rounded-2 d-flex align-items-center gap-2" href="<?= View::url('tasks') ?>">
                                    <i data-lucide="check-square" class="text-muted" style="width: 16px; height: 16px;"></i>
                                    <span>Action Tasks</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 rounded-2 text-danger d-flex align-items-center gap-2" href="<?= View::url('logout') ?>">
                                    <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                                    <span>Sign Out</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
        </header>

        <!-- Notification Right Sidebar Offcanvas -->
        <div class="offcanvas offcanvas-end notification-offcanvas" tabindex="-1" id="notificationOffcanvas" aria-labelledby="notificationOffcanvasLabel" style="width: 440px; max-width: 95vw;">
            <!-- Header -->
            <div class="offcanvas-header text-white px-3 py-3 border-bottom border-white-10">
                <div class="d-flex align-items-center gap-2">
                    <div class="notif-header-icon-box d-flex align-items-center justify-content-center rounded-3 bg-white bg-opacity-15" style="width: 36px; height: 36px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bell-dot preview-icon"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M11.68 2.009A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673c-.824-.85-1.678-1.731-2.21-3.348"/><circle cx="18" cy="5" r="3"/></svg>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="offcanvas-title fw-bold mb-0 text-white" id="notificationOffcanvasLabel" style="font-size: 1.02rem;">Notifications</h5>
                            <span id="notifOffcanvasCount" class="badge bg-danger rounded-pill px-2 py-0.5 <?= $unreadNotifCount > 0 ? '' : 'd-none' ?>" style="font-size: 0.68rem;">
                                <?= $unreadNotifCount ?> unread
                            </span>
                        </div>
                        <div class="text-white-50" style="font-size: 0.72rem;">Real-time admission & lead updates</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-light border-0 py-1 px-2 rounded-2" id="markAllReadBtn" title="Mark all as read" style="font-size: 0.74rem;">
                        <i data-lucide="check-check" style="width: 14px; height: 14px;" class="me-1"></i> Mark Read
                    </button>
                    <button type="button" class="btn-close btn-close-white ms-1" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
            </div>

            <!-- Filter Pills Bar -->
            <div class="bg-light border-bottom px-3 py-2 d-flex align-items-center gap-1 notif-filters-bar">
                <button type="button" class="btn btn-xs rounded-pill px-2.5 py-1 filter-notif-btn active" data-filter="all">
                    All <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1" id="filterCountAll"><?= count($initialNotifs) ?></span>
                </button>
                <button type="button" class="btn btn-xs rounded-pill px-2.5 py-1 filter-notif-btn" data-filter="unread">
                    Unread <span class="badge bg-danger text-white rounded-pill ms-1" id="filterCountUnread"><?= $unreadNotifCount ?></span>
                </button>
                <button type="button" class="btn btn-xs rounded-pill px-2.5 py-1 filter-notif-btn" data-filter="status">
                    Status Changes
                </button>
                <button type="button" class="btn btn-xs rounded-pill px-2.5 py-1 filter-notif-btn" data-filter="assigned">
                    Assignments
                </button>
            </div>

            <!-- Offcanvas Body (Scrollable List with all details) -->
            <div class="offcanvas-body p-3 bg-slate-50" id="notificationList" style="overflow-y: auto;">
                <?php if (empty($initialNotifs)): ?>
                    <div class="p-4 text-center text-muted" id="notifEmptyState">
                        <div class="d-inline-flex p-3 rounded-circle bg-light border mb-3">
                            <i data-lucide="bell-off" class="text-secondary" style="width: 32px; height: 32px; opacity: 0.6;"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">No notifications yet</h6>
                        <p class="small text-muted mb-0">You're completely caught up with all admission activities!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($initialNotifs as $n): ?>
                        <?php 
                        $isUnread = empty($n['is_read']);
                        $isAssign = ($n['type'] ?? '') === 'lead_assigned';
                        $isStatus = ($n['type'] ?? '') === 'lead_status_changed';
                        $cardClass = $isUnread ? 'notif-card notif-unread' : 'notif-card';
                        $iconClass = $isAssign ? 'icon-assign' : ($isStatus ? 'icon-status' : 'icon-general');
                        $iconName = $isAssign ? 'user-check' : ($isStatus ? 'activity' : 'bell');
                        $typeLabel = $isAssign ? 'Assignment' : ($isStatus ? 'Status Update' : 'Update');
                        ?>
                        <div class="<?= $cardClass ?>" data-id="<?= $n['id'] ?>" data-type="<?= Sanitizer::e($n['type'] ?? 'general') ?>" data-unread="<?= $isUnread ? '1' : '0' ?>">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="notif-icon-circle <?= $iconClass ?>">
                                        <i data-lucide="<?= $iconName ?>" style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <span class="badge bg-light text-secondary border fw-medium" style="font-size: 0.68rem;"><?= $typeLabel ?></span>
                                </div>
                                <span class="text-muted" style="font-size: 0.72rem;">
                                    <i data-lucide="clock" style="width: 11px; height: 11px;" class="me-1"></i><?= Sanitizer::e($n['time_ago'] ?? '') ?>
                                </span>
                            </div>

                            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.88rem;"><?= Sanitizer::e($n['title']) ?></h6>
                            <p class="text-secondary small mb-2" style="font-size: 0.81rem; line-height: 1.45;"><?= Sanitizer::e($n['message']) ?></p>

                            <?php if (!empty($n['lead_id']) || !empty($n['lead_ref'])): ?>
                                <div class="notif-lead-preview">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1">
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-navy text-white fw-bold px-2 py-0.5" style="font-size: 0.7rem;">
                                                <?= Sanitizer::e($n['lead_ref'] ?? ('#ID-' . $n['lead_id'])) ?>
                                            </span>
                                            <?php if (!empty($n['class_applying_for'])): ?>
                                                <span class="badge bg-white text-dark border px-2 py-0.5" style="font-size: 0.68rem;">
                                                    <?= Sanitizer::e($n['class_applying_for']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($n['lead_status'])): ?>
                                            <?php $stSlug = trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $n['lead_status']), '-'); ?>
                                            <span class="badge badge-status-<?= $stSlug ?>" style="font-size: 0.68rem;">
                                                <?= Sanitizer::e($n['lead_status']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between text-dark fw-medium small mt-1">
                                        <span>
                                            <i data-lucide="graduation-cap" style="width: 13px; height: 13px;" class="text-primary me-1"></i>
                                            <?= Sanitizer::e($n['student_name'] ?? 'Student') ?>
                                        </span>
                                        <?php if (!empty($n['parent_name'])): ?>
                                            <span class="text-muted small">P/o <?= Sanitizer::e($n['parent_name']) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($n['primary_mobile'])): ?>
                                        <div class="d-flex align-items-center justify-content-between pt-1 mt-1 border-top text-muted" style="font-size: 0.72rem;">
                                            <span>
                                                <i data-lucide="phone" style="width: 11px; height: 11px;" class="me-1"></i>
                                                <?= Sanitizer::e($n['primary_mobile']) ?>
                                            </span>
                                            <a href="https://wa.me/91<?= preg_replace('/\D/', '', $n['primary_mobile']) ?>" target="_blank" class="text-success text-decoration-none fw-medium d-inline-flex align-items-center gap-1" onclick="event.stopPropagation();">
                                                <i data-lucide="message-circle" style="width: 12px; height: 12px;"></i> WhatsApp
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex align-items-center justify-content-between pt-1 mt-1">
                                <span class="text-muted" style="font-size: 0.72rem;">
                                    <?= !empty($n['sender_name']) ? 'by ' . Sanitizer::e($n['sender_name']) : '' ?>
                                </span>
                                <div class="d-flex align-items-center gap-1">
                                    <?php if ($isUnread): ?>
                                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0.5 btn-mark-single-read" data-id="<?= $n['id'] ?>" style="font-size: 0.7rem;" title="Mark as Read">
                                            <i data-lucide="check" style="width: 11px; height: 11px;"></i> Mark Read
                                        </button>
                                    <?php endif; ?>
                                    <a href="<?= View::url('notifications/read&id=' . $n['id']) ?>" class="btn btn-xs btn-primary rounded-pill px-2.5 py-0.5 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                        <span>View Details</span>
                                        <i data-lucide="arrow-right" style="width: 11px; height: 11px;"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="offcanvas-footer p-3 bg-white border-top">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <button type="button" id="enableDesktopAlertsBtn" class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1 d-none" style="font-size: 0.76rem;">
                        <i data-lucide="bell-ring" style="width: 14px; height: 14px;" class="me-1"></i> Enable Desktop Alerts
                    </button>
                    <div id="desktopAlertsEnabledLabel" class="text-muted small py-1 d-none flex-grow-1 text-center" style="font-size: 0.74rem;">
                        <i data-lucide="check-circle" style="width: 13px; height: 13px;" class="text-success me-1"></i> Desktop alerts active
                    </div>
                    <a href="<?= View::url('leads') ?>" class="btn btn-sm btn-light border rounded-pill px-3" style="font-size: 0.76rem;">
                        <i data-lucide="external-link" style="width: 13px; height: 13px;" class="me-1"></i> All Leads
                    </a>
                </div>
            </div>
        </div>

        <!-- Notification Permission Request Popup (Yes / No) -->
        <div class="modal fade" id="notifPermissionPopupModal" tabindex="-1" aria-labelledby="notifPermissionPopupModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
                <div class="modal-content border-0 shadow-lg text-center" style="border-radius: 20px; overflow: hidden;">
                    <div class="modal-body p-4 pt-4 pb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary mb-3 shadow-xs" style="width: 64px; height: 64px;">
                            <i data-lucide="bell-ring" style="width: 32px; height: 32px;" class="text-primary"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2" id="notifPermissionPopupModalLabel" style="font-size: 1.15rem;">Enable Website Notifications?</h5>
                        <p class="text-secondary small mb-4" style="font-size: 0.88rem; line-height: 1.55;">
                            Stay updated with real-time alerts for new admission enquiries, parent calls, and assigned follow-up tasks on your device.
                        </p>
                        <div class="d-flex align-items-center justify-content-center gap-2.5">
                            <button type="button" class="btn btn-light border px-4 py-2 rounded-pill fw-semibold text-secondary" id="btnDenyNotifPopup" data-bs-dismiss="modal" style="font-size: 0.88rem; min-width: 120px;">
                                No / Later
                            </button>
                            <button type="button" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center justify-content-center gap-1.5" id="btnAllowNotifPopup" style="font-size: 0.88rem; min-width: 150px;">
                                <i data-lucide="bell" style="width: 16px; height: 16px;"></i>
                                <span>Yes, Enable</span>
                            </button>
                        </div>
                    </div>
                    <div class="bg-light py-2 px-3 border-top text-muted" style="font-size: 0.72rem;">
                        Supported on Chrome, Firefox, Edge, Safari & Mobile
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="content-body flex-grow-1 p-3 p-md-4" id="app-main-content">
            <div class="container-fluid px-0" id="app-page-container">
                <?php require __DIR__ . '/alerts.php'; ?>
