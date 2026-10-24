<?php
/**
 * Staff Profile & Account Settings View
 * GD Goenka Public School CRM
 *
 * @var array $user
 * @var array $stats
 */
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'My Profile & Settings';
?>

<!-- Page Header & Breadcrumb -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0" style="font-size: 0.74rem;">
                <li class="breadcrumb-item"><a href="<?= View::url('') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Staff Profile</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold text-dark mb-0" style="font-size: 1.35rem; letter-spacing: -0.3px;">
                My Profile & Account Settings
            </h4>
            <span class="badge rounded-pill bg-light text-secondary border px-2.5 py-1" style="font-size: 0.72rem; font-weight: 600;">
                <i data-lucide="shield-check" style="width: 12px; height: 12px;" class="text-success me-1"></i>
                <?= Sanitizer::e($user['role_name'] ?? 'Staff') ?>
            </span>
        </div>
        <p class="text-muted small mb-0 mt-1">Manage your official counselor credentials, contact details, and review your CRM pipeline performance.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= View::url('') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 0.8rem; font-weight: 500;">
            <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i>
            <span>Back to Dashboard</span>
        </a>
    </div>
</div>

<!-- Main Profile Layout Grid -->
<div class="row g-3 g-lg-4">
    
    <!-- LEFT COLUMN: Profile Overview & Performance Metrics (col-lg-4) -->
    <div class="col-lg-4 col-xl-4">
        
        <!-- Profile Identity Card -->
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white mb-3">
            <!-- Gradient Cover Banner -->
            <div style="background: var(--gdg-primary-gradient, linear-gradient(135deg, #0e44cc 0%, #1859f2 50%, #2b6bf5 100%)); height: 95px; position: relative;">
                <div class="position-absolute top-0 end-0 p-3 opacity-25 text-white">
                    <i data-lucide="graduation-cap" style="width: 48px; height: 48px;"></i>
                </div>
                <div class="position-absolute top-0 end-0 p-2.5">
                    <span class="badge bg-white text-dark rounded-pill shadow-xs px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size: 0.7rem; font-weight: 600;">
                        <span class="rounded-circle bg-success" style="width: 6px; height: 6px;"></span>
                        <?= ucfirst(Sanitizer::e($user['status'] ?? 'active')) ?>
                    </span>
                </div>
            </div>

            <!-- Avatar & Main Identification -->
            <div class="card-body pt-0 px-3 px-md-4 pb-4 text-center" style="margin-top: -46px;">
                <div class="position-relative d-inline-block mb-2">
                    <div class="bento-avatar avatar-blue shadow-sm mx-auto" style="width: 82px; height: 82px; font-size: 1.85rem; font-weight: 800; border: 4px solid #ffffff; border-radius: 20px;">
                        <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span class="position-absolute bottom-0 end-0 rounded-circle bg-success text-white d-flex align-items-center justify-content-center shadow-xs" style="width: 22px; height: 22px; border: 2px solid #fff; font-size: 0.65rem;" title="Verified Staff Member">
                        <i data-lucide="check" style="width: 12px; height: 12px;"></i>
                    </span>
                </div>

                <h5 class="fw-bold text-navy-dark mb-0" style="font-size: 1.15rem; letter-spacing: -0.2px;">
                    <?= Sanitizer::e($user['name'] ?? 'Staff Member') ?>
                </h5>
                <div class="text-navy fw-semibold text-uppercase small mt-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                    <?= Sanitizer::e(!empty($user['designation']) ? $user['designation'] : ($user['role_name'] ?? 'Staff Member')) ?>
                </div>

                <div class="d-inline-flex align-items-center gap-1.5 px-2.5 py-1 rounded-pill bg-light border text-muted small mt-2" style="font-size: 0.72rem;">
                    <i data-lucide="award" style="width: 13px; height: 13px;" class="text-primary"></i>
                    <span>Access Tier: <strong class="text-dark"><?= Sanitizer::e($user['role_name'] ?? 'Staff') ?></strong></span>
                </div>

                <hr class="my-3 opacity-10">

                <!-- Key Details List -->
                <div class="text-start d-flex flex-column gap-2" style="font-size: 0.8rem;">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted d-flex align-items-center gap-2">
                            <i data-lucide="mail" style="width: 14px; height: 14px;" class="text-muted"></i>
                            <span>Email</span>
                        </span>
                        <span class="fw-semibold text-dark text-truncate text-end ms-2" style="max-width: 180px;" title="<?= Sanitizer::e($user['email'] ?? '') ?>">
                            <?= Sanitizer::e($user['email'] ?? '—') ?>
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted d-flex align-items-center gap-2">
                            <i data-lucide="phone" style="width: 14px; height: 14px;" class="text-muted"></i>
                            <span>Phone</span>
                        </span>
                        <span class="fw-semibold text-dark font-monospace">
                            <?= Sanitizer::e($user['phone'] ?? 'Not set') ?>
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted d-flex align-items-center gap-2">
                            <i data-lucide="calendar" style="width: 14px; height: 14px;" class="text-muted"></i>
                            <span>Joined On</span>
                        </span>
                        <span class="fw-semibold text-dark">
                            <?= !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '—' ?>
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted d-flex align-items-center gap-2">
                            <i data-lucide="clock" style="width: 14px; height: 14px;" class="text-muted"></i>
                            <span>Last Login</span>
                        </span>
                        <span class="fw-semibold text-dark">
                            <?= !empty($user['last_login_at']) ? date('d M Y, g:i A', strtotime($user['last_login_at'])) : 'Active Now' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div> 

    </div>

    <!-- RIGHT COLUMN: Elegant Tabs & Forms (col-lg-8) -->
    <div class="col-lg-8 col-xl-8">
        
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white">
            
            <!-- Elegant Tab Navigation Header -->
            <div class="card-header bg-white border-bottom p-0 px-3 px-md-4 pt-2">
                <ul class="nav nav-pills custom-profile-tabs gap-1" id="profileTabList" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active d-inline-flex align-items-center gap-2 py-2.5 px-3 fw-semibold" 
                                id="tab-personal-btn" 
                                data-bs-toggle="pill" 
                                data-bs-target="#tab-personal" 
                                type="button" 
                                role="tab" 
                                aria-controls="tab-personal" 
                                aria-selected="true"
                                style="font-size: 0.82rem;">
                            <i data-lucide="user-pen" style="width: 15px; height: 15px;"></i>
                            <span>Personal Details</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-inline-flex align-items-center gap-2 py-2.5 px-3 fw-semibold text-muted" 
                                id="tab-security-btn" 
                                data-bs-toggle="pill" 
                                data-bs-target="#tab-security" 
                                type="button" 
                                role="tab" 
                                aria-controls="tab-security" 
                                aria-selected="false"
                                style="font-size: 0.82rem;">
                            <i data-lucide="lock" style="width: 15px; height: 15px;"></i>
                            <span>Security & Password</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-inline-flex align-items-center gap-2 py-2.5 px-3 fw-semibold text-muted" 
                                id="tab-activity-btn" 
                                data-bs-toggle="pill" 
                                data-bs-target="#tab-activity" 
                                type="button" 
                                role="tab" 
                                aria-controls="tab-activity" 
                                aria-selected="false"
                                style="font-size: 0.82rem;">
                            <i data-lucide="history" style="width: 15px; height: 15px;"></i>
                            <span>Recent Activity Log</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content Pane -->
            <div class="card-body p-3 p-md-4">
                <div class="tab-content" id="profileTabContent">

                    <!-- TAB 1: Personal Details Form -->
                    <div class="tab-pane fade show active" id="tab-personal" role="tabpanel" aria-labelledby="tab-personal-btn">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div>
                                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;">Staff Details & Designation</h6>
                                <p class="text-muted small mb-0 mt-0.5">Update your formal name, contact telephone, and official job designation.</p>
                            </div>
                            <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 0.7rem;">Staff ID #<?= (int)$user['id'] ?></span>
                        </div>

                        <form action="<?= View::url('profile/update') ?>" method="POST" id="profileUpdateForm">
                            <?= Csrf::field() ?>

                            <div class="row g-3">
                                <!-- Full Name -->
                                <div class="col-md-6">
                                    <label class="form-field-label fw-bold text-dark small mb-1" for="nameInput">
                                        Full Name <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-icon-wrap position-relative">
                                        <i data-lucide="user" class="position-absolute text-muted" style="width: 15px; height: 15px; top: 11px; left: 12px;"></i>
                                        <input type="text" 
                                               class="form-control form-control-sm compact-input ps-5" 
                                               id="nameInput" 
                                               name="name" 
                                               required 
                                               maxlength="100" 
                                               value="<?= Sanitizer::e($user['name'] ?? '') ?>" 
                                               placeholder="Your full name"
                                               style="height: 38px; font-size: 0.86rem; border-radius: 8px;">
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 0.68rem;">Visible on admission leads assignments, follow-ups, and logs.</div>
                                </div>

                                <!-- Designation / Title -->
                                <div class="col-md-6">
                                    <label class="form-field-label fw-bold text-dark small mb-1" for="designationInput">
                                        Official Designation / Job Title
                                    </label>
                                    <div class="input-icon-wrap position-relative">
                                        <i data-lucide="briefcase" class="position-absolute text-muted" style="width: 15px; height: 15px; top: 11px; left: 12px;"></i>
                                        <input type="text" 
                                               class="form-control form-control-sm compact-input ps-5" 
                                               id="designationInput" 
                                               name="designation" 
                                               maxlength="100" 
                                               value="<?= Sanitizer::e($user['designation'] ?? '') ?>" 
                                               placeholder="e.g. Admission Counselor, Coordinator"
                                               style="height: 38px; font-size: 0.86rem; border-radius: 8px;">
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 0.68rem;">Shown in top navigation bar profile dropdown and staff badges.</div>
                                </div>

                                <!-- Phone / Mobile Number -->
                                <div class="col-md-6">
                                    <label class="form-field-label fw-bold text-dark small mb-1" for="phoneInput">
                                        Mobile / Phone Number
                                    </label>
                                    <div class="input-icon-wrap position-relative">
                                        <i data-lucide="phone" class="position-absolute text-muted" style="width: 15px; height: 15px; top: 11px; left: 12px;"></i>
                                        <input type="text" 
                                               class="form-control form-control-sm compact-input ps-5 font-monospace" 
                                               id="phoneInput" 
                                               name="phone" 
                                               maxlength="20" 
                                               value="<?= Sanitizer::e($user['phone'] ?? '') ?>" 
                                               placeholder="+91 98765 43210"
                                               style="height: 38px; font-size: 0.86rem; border-radius: 8px;">
                                    </div>
                                </div>

                                <!-- Email ID (Login account identifier) -->
                                <div class="col-md-6">
                                    <label class="form-field-label fw-bold text-dark small mb-1" for="emailInput">
                                        Email ID <span class="text-muted fw-normal">(Login Account)</span>
                                    </label>
                                    <div class="input-icon-wrap position-relative">
                                        <i data-lucide="mail" class="position-absolute text-muted" style="width: 15px; height: 15px; top: 11px; left: 12px;"></i>
                                        <input type="email" 
                                               class="form-control form-control-sm compact-input ps-5 bg-light text-muted" 
                                               id="emailInput" 
                                               value="<?= Sanitizer::e($user['email'] ?? '') ?>" 
                                               readonly 
                                               style="height: 38px; font-size: 0.86rem; border-radius: 8px; cursor: not-allowed;">
                                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted" title="Managed by Administrator">
                                            <i data-lucide="lock" style="width: 13px; height: 13px;"></i>
                                        </span>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 0.68rem;">To update your official login email, please contact the CRM Administrator.</div>
                                </div>
                            </div>

                            <!-- Role Permission Notice Strip -->
                            <div class="mt-3 p-2.5 rounded-3 bg-light border d-flex align-items-center justify-content-between" style="font-size: 0.75rem;">
                                <div class="d-flex align-items-center gap-2">
                                    <i data-lucide="shield" style="width: 14px; height: 14px;" class="text-primary"></i>
                                    <span>Assigned Security Role: <strong><?= Sanitizer::e($user['role_name'] ?? 'Staff') ?></strong></span>
                                </div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5">
                                    Account Status: <?= ucfirst(Sanitizer::e($user['status'] ?? 'active')) ?>
                                </span>
                            </div>

                            <!-- Bottom Action Bar -->
                            <div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-end gap-2">
                                <button type="reset" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5" style="font-size: 0.8rem;">
                                    Discard
                                </button>
                                <button type="submit" class="btn btn-navy btn-sm rounded-pill px-4 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 0.8rem;">
                                    <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                    <span>Save Profile Details</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- TAB 2: Security & Password Form -->
                    <div class="tab-pane fade" id="tab-security" role="tabpanel" aria-labelledby="tab-security-btn">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div>
                                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;">Change Account Password</h6>
                                <p class="text-muted small mb-0 mt-0.5">Secure your staff account by entering your current password and choosing a new one.</p>
                            </div>
                            <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 0.7rem;">
                                <i data-lucide="key" style="width: 11px; height: 11px;" class="text-warning me-1"></i>Bcrypt Hash
                            </span>
                        </div>

                        <form action="<?= View::url('profile/change-password') ?>" method="POST" id="passwordChangeForm" style="max-width: 540px;">
                            <?= Csrf::field() ?>

                            <!-- Current Password -->
                            <div class="mb-3">
                                <label class="form-field-label fw-bold text-dark small mb-1" for="current_password">
                                    Current Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-icon-wrap position-relative">
                                    <i data-lucide="key-round" class="position-absolute text-muted" style="width: 15px; height: 15px; top: 11px; left: 12px;"></i>
                                    <input type="password" 
                                           class="form-control form-control-sm compact-input ps-5 pe-5" 
                                           id="current_password" 
                                           name="current_password" 
                                           required 
                                           placeholder="Enter current password"
                                           style="height: 38px; font-size: 0.86rem; border-radius: 8px;">
                                    <button type="button" 
                                            class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted p-0 me-2.5 text-decoration-none shadow-none" 
                                            onclick="toggleEyePassword('current_password', this)">
                                        <i data-lucide="eye" style="width: 15px; height: 15px;"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- New Password -->
                            <div class="mb-3">
                                <label class="form-field-label fw-bold text-dark small mb-1" for="new_password">
                                    New Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-icon-wrap position-relative">
                                    <i data-lucide="lock" class="position-absolute text-muted" style="width: 15px; height: 15px; top: 11px; left: 12px;"></i>
                                    <input type="password" 
                                           class="form-control form-control-sm compact-input ps-5 pe-5" 
                                           id="new_password" 
                                           name="new_password" 
                                           required 
                                           minlength="6" 
                                           placeholder="At least 6 characters"
                                           style="height: 38px; font-size: 0.86rem; border-radius: 8px;">
                                    <button type="button" 
                                            class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted p-0 me-2.5 text-decoration-none shadow-none" 
                                            onclick="toggleEyePassword('new_password', this)">
                                        <i data-lucide="eye" style="width: 15px; height: 15px;"></i>
                                    </button>
                                </div>
                                <div class="text-muted mt-1" style="font-size: 0.68rem;">Minimum 6 characters. Use letters, numbers, and symbols for stronger defense.</div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-3">
                                <label class="form-field-label fw-bold text-dark small mb-1" for="confirm_password">
                                    Confirm New Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-icon-wrap position-relative">
                                    <i data-lucide="shield-check" class="position-absolute text-muted" style="width: 15px; height: 15px; top: 11px; left: 12px;"></i>
                                    <input type="password" 
                                           class="form-control form-control-sm compact-input ps-5 pe-5" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           required 
                                           minlength="6" 
                                           placeholder="Re-type new password"
                                           style="height: 38px; font-size: 0.86rem; border-radius: 8px;">
                                    <button type="button" 
                                            class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted p-0 me-2.5 text-decoration-none shadow-none" 
                                            onclick="toggleEyePassword('confirm_password', this)">
                                        <i data-lucide="eye" style="width: 15px; height: 15px;"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Security Alert Box -->
                            <div class="alert alert-light border rounded-3 p-2.5 mb-3 d-flex align-items-start gap-2" style="font-size: 0.74rem;">
                                <i data-lucide="info" style="width: 15px; height: 15px;" class="text-primary flex-shrink-0 mt-0.5"></i>
                                <span class="text-muted">
                                    Once saved, your password will be updated immediately and future logins will require the new credentials.
                                </span>
                            </div>

                            <div class="pt-2 border-top d-flex align-items-center justify-content-end gap-2">
                                <button type="submit" class="btn btn-navy btn-sm rounded-pill px-4 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 0.8rem;">
                                    <i data-lucide="lock" style="width: 14px; height: 14px;"></i>
                                    <span>Update Password</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- TAB 3: Recent Activity Log -->
                    <div class="tab-pane fade" id="tab-activity" role="tabpanel" aria-labelledby="tab-activity-btn">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div>
                                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;">Recent Account Operations</h6>
                                <p class="text-muted small mb-0 mt-0.5">Audit trail of actions and changes recorded under your counselor user account.</p>
                            </div>
                            <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 0.7rem;">Last 10 Events</span>
                        </div>

                        <?php if (empty($stats['recent_logs'])): ?>
                            <div class="text-center py-5">
                                <div class="bento-avatar avatar-blue mx-auto mb-2" style="width: 44px; height: 44px; border-radius: 12px;">
                                    <i data-lucide="history" style="width: 22px; height: 22px;"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">No Activity Logged Yet</h6>
                                <p class="text-muted small mb-0">Operational actions will automatically be captured here with timestamp and IP.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive rounded-3 border">
                                <table class="table table-hover align-middle mb-0" style="font-size: 0.78rem;">
                                    <thead class="table-light">
                                        <tr class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                            <th class="ps-3 py-2.5">Action Event</th>
                                            <th class="py-2.5">Module / Entity</th>
                                            <th class="py-2.5">IP Address</th>
                                            <th class="pe-3 py-2.5 text-end">Date & Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['recent_logs'] as $log): ?>
                                            <tr>
                                                <td class="ps-3 py-2.5">
                                                    <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1 font-monospace" style="font-weight: 600; font-size: 0.72rem;">
                                                        <i data-lucide="zap" style="width: 10px; height: 10px;" class="text-primary"></i>
                                                        <?= Sanitizer::e(ucwords(str_replace('_', ' ', $log['action'] ?? 'action'))) ?>
                                                    </span>
                                                </td>
                                                <td class="py-2.5">
                                                    <span class="fw-semibold text-dark"><?= Sanitizer::e(ucfirst($log['entity_type'] ?? 'general')) ?></span>
                                                    <?php if (!empty($log['entity_id'])): ?>
                                                        <span class="text-muted small">#<?= (int)$log['entity_id'] ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-2.5 font-monospace text-muted" style="font-size: 0.74rem;">
                                                    <?= Sanitizer::e($log['ip_address'] ?? '—') ?>
                                                </td>
                                                <td class="pe-3 py-2.5 text-end text-muted font-monospace" style="font-size: 0.74rem;">
                                                    <?= !empty($log['created_at']) ? date('d M Y, h:i A', strtotime($log['created_at'])) : '—' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        </div>

    </div>

    <div class="col-lg-6">
                 <!-- Workload & Performance Summary Bento Card -->
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white mb-3">
            <div class="card-header bg-white border-0 py-3 px-3 px-md-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="section-badge-icon section-badge-blue">
                        <i data-lucide="bar-chart-2" style="width: 14px; height: 14px;"></i>
                    </span>
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.88rem;">Pipeline & Workload</h6>
                </div>
                <span class="badge bg-light text-muted border" style="font-size: 0.68rem;">All-Time</span>
            </div>

            <div class="card-body p-3 pt-0">
                <div class="row g-2">
                    <!-- Assigned Leads -->
                    <div class="col-6">
                        <div class="p-2.5 rounded-3 bg-light border text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1 text-muted mb-1" style="font-size: 0.72rem;">
                                <i data-lucide="users" style="width: 12px; height: 12px;" class="text-primary"></i>
                                <span>Assigned Leads</span>
                            </div>
                            <div class="fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">
                                <?= number_format($stats['total_leads'] ?? 0) ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.65rem;">Total Allocated</div>
                        </div>
                    </div>

                    <!-- In Progress -->
                    <div class="col-6">
                        <div class="p-2.5 rounded-3 border text-center" style="background: #F0F9FF; border-color: #BAE6FD !important;">
                            <div class="d-flex align-items-center justify-content-center gap-1 text-primary mb-1" style="font-size: 0.72rem;">
                                <i data-lucide="activity" style="width: 12px; height: 12px;"></i>
                                <span>In Progress</span>
                            </div>
                            <div class="fw-bold text-primary" style="font-size: 1.35rem; line-height: 1.2;">
                                <?= number_format($stats['active_leads'] ?? 0) ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.65rem;">Active Enquiries</div>
                        </div>
                    </div>

                    <!-- Confirmed Admissions -->
                    <div class="col-6">
                        <div class="p-2.5 rounded-3 border text-center" style="background: #ECFDF5; border-color: #A7F3D0 !important;">
                            <div class="d-flex align-items-center justify-content-center gap-1 text-success mb-1" style="font-size: 0.72rem;">
                                <i data-lucide="check-circle" style="width: 12px; height: 12px;"></i>
                                <span>Admissions</span>
                            </div>
                            <div class="fw-bold text-success" style="font-size: 1.35rem; line-height: 1.2;">
                                <?= number_format($stats['confirmed_leads'] ?? 0) ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.65rem;">Confirmed</div>
                        </div>
                    </div>

                    <!-- Pending Tasks -->
                    <div class="col-6">
                        <div class="p-2.5 rounded-3 border text-center" style="background: #FFFBEB; border-color: #FDE68A !important;">
                            <div class="d-flex align-items-center justify-content-center gap-1 text-warning mb-1" style="font-size: 0.72rem;">
                                <i data-lucide="calendar-clock" style="width: 12px; height: 12px;"></i>
                                <span>Open Tasks</span>
                            </div>
                            <div class="fw-bold text-warning" style="font-size: 1.35rem; line-height: 1.2;">
                                <?= number_format($stats['pending_tasks'] ?? 0) ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.65rem;">
                                <?php if (($stats['overdue_tasks'] ?? 0) > 0): ?>
                                    <span class="text-danger fw-bold"><?= $stats['overdue_tasks'] ?> overdue</span>
                                <?php else: ?>
                                    Pending Calls
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-2.5 pt-2.5 border-top d-flex align-items-center justify-content-between small text-muted" style="font-size: 0.75rem;">
                    <span>Completed Follow-ups:</span>
                    <strong class="text-success"><?= number_format($stats['completed_tasks'] ?? 0) ?> tasks completed</strong>
                </div>
            </div>
        </div>

        <!-- School Affiliation Authority Note -->
        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white" style="border-left: 3px solid var(--gdg-primary, #1859F2) !important;">
            <div class="d-flex align-items-start gap-2.5">
                <div class="bento-avatar avatar-blue flex-shrink-0" style="width: 28px; height: 28px; border-radius: 8px;">
                    <i data-lucide="school" style="width: 15px; height: 15px;"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark small" style="font-size: 0.78rem;">GD Goenka Public School, Purnea</div>
                    <p class="text-muted mb-0 mt-0.5" style="font-size: 0.71rem; line-height: 1.35;">
                        Official staff profile ledger. Changes to password and credentials are recorded in security audit logs.
                    </p>
                </div>
            </div>
        </div>                                               
    </div>



</div>

<!-- Profile Page Scripts & Styles -->
<style>
/* Custom Clean Tabs Styling */
.custom-profile-tabs .nav-link {
    border-radius: 8px 8px 0 0;
    border-bottom: 2px solid transparent;
    color: #64748B;
    background: transparent;
    transition: all 0.15s ease;
}
.custom-profile-tabs .nav-link:hover {
    color: var(--gdg-primary, #1859F2);
    background: #F8FAFC;
}
.custom-profile-tabs .nav-link.active {
    color: var(--gdg-primary, #1859F2) !important;
    background: transparent !important;
    border-bottom: 2px solid var(--gdg-primary, #1859F2) !important;
    box-shadow: none !important;
}
</style>

<script>
// Eye toggle function for password fields
function toggleEyePassword(fieldId, btnEl) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    
    // Switch icon between eye and eye-off
    btnEl.innerHTML = isPassword 
        ? '<i data-lucide="eye-off" style="width: 15px; height: 15px;"></i>' 
        : '<i data-lucide="eye" style="width: 15px; height: 15px;"></i>';
    
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Re-render Lucide icons on tab switch
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('#profileTabList button[data-bs-toggle="pill"]');
    tabBtns.forEach(btn => {
        btn.addEventListener('shown.bs.tab', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    });

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
