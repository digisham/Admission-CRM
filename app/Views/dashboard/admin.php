<?php
use App\Helpers\Auth;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Admission Overview & Pipeline';
$userName = Auth::user()['name'] ?? 'Counselor';
// First name for friendly greeting
$firstName = explode(' ', $userName)[0];

// Avatar colors palette for variety
$avatarColorClasses = ['avatar-purple', 'avatar-orange', 'avatar-blue', 'avatar-red', 'avatar-green', 'avatar-gold'];
?>

<!-- 1. VIBRANT ROYAL BLUE HERO BANNER (MATCHING REFERENCE DESIGN) -->
<div class="hero-dashboard-banner">
    <!-- Top Row Inside Hero Banner: Avatar + Date Pill, Search Pill, Clock Pill -->
    <div class="hero-top-bar">
        <div class="d-flex align-items-center gap-2">
            <div class="hero-pill">
                <i data-lucide="user" style="width: 15px; height: 15px;"></i>
                <span><?= Sanitizer::e($firstName) ?></span>
            </div>
            <div class="hero-pill">
                <i data-lucide="calendar" style="width: 15px; height: 15px;"></i>
                <span><?= date('d M') ?></span>
            </div>
        </div>

        <!-- Translucent Pill Search -->
        <div class="hero-search-wrapper position-relative">
            <form action="<?= View::url('leads') ?>" method="GET" class="m-0">
                <input type="hidden" name="route" value="leads">
                <i data-lucide="search" class="hero-search-icon"></i>
                <input type="text" name="search" class="hero-search-input" placeholder="Search for Students, Parents, Contacts, or Enquiries...">
            </form>
        </div>

        <div class="d-flex align-items-center gap-2">
            <div class="hero-pill">
                <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
                <span><?= date('g:i a') ?></span>
            </div>
            <a href="<?= View::url('leads/create') ?>" class="hero-pill" title="Add Enquiry">
                <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
            </a>
        </div>
    </div>

    <!-- Greeting Headline -->
    <div class="hero-greeting-title">
        Welcome, <?= Sanitizer::e($firstName) ?>. Today's an admission day.
    </div>
    <div class="hero-greeting-subtitle">
        Your pipeline of student enquiries is waiting to be organized for session <strong>2027–28</strong>.
    </div>

    <!-- Quick Remind Box -->
    <div class="hero-remind-box">
        <i data-lucide="bell" style="width: 15px; height: 15px; color: #FBBF24;"></i>
        <span>
            <?php if (!empty($overdueTasks)): ?>
                <strong>Overdue alert:</strong> <?= Sanitizer::e($overdueTasks[0]['title']) ?> with <strong><?= Sanitizer::e($overdueTasks[0]['student_name']) ?></strong>
            <?php else: ?>
                <strong>Quick remind:</strong> <?= $metrics['followups_due_today'] ?> parent follow-ups scheduled for today.
            <?php endif; ?>
        </span>
    </div>

    <!-- Quick Action Circular Buttons (Directly from User Design) -->
    <div class="hero-action-row">
        <a href="<?= View::url('leads/create') ?>" class="hero-action-item">
            <div class="hero-action-btn">
                <i data-lucide="user-plus" style="width: 22px; height: 22px;"></i>
            </div>
            <span class="hero-action-label">Add Enquiry</span>
        </a>

        <a href="<?= View::url('leads/create') ?>" class="hero-action-item">
            <div class="hero-action-btn">
                <i data-lucide="phone-incoming" style="width: 22px; height: 22px;"></i>
            </div>
            <span class="hero-action-label">Log Walk-in</span>
        </a>

        <a href="<?= View::url('tasks') ?>" class="hero-action-item">
            <div class="hero-action-btn">
                <i data-lucide="calendar-plus" style="width: 22px; height: 22px;"></i>
            </div>
            <span class="hero-action-label">Schedule Visit</span>
        </a>

        <a href="<?= View::url('tasks') ?>" class="hero-action-item">
            <div class="hero-action-btn">
                <i data-lucide="check-square" style="width: 22px; height: 22px;"></i>
            </div>
            <span class="hero-action-label">Create Task</span>
        </a>

        <a href="<?= View::url('followups') ?>" class="hero-action-item">
            <div class="hero-action-btn">
                <i data-lucide="message-circle" style="width: 22px; height: 22px;"></i>
            </div>
            <span class="hero-action-label">WhatsApp / Call</span>
        </a>

        <a href="<?= View::url('followups') ?>" class="hero-action-item">
            <div class="hero-action-btn">
                <i data-lucide="activity" style="width: 22px; height: 22px;"></i>
            </div>
            <span class="hero-action-label">Log Activity</span>
        </a>

        <a href="<?= View::url('import') ?>" class="hero-action-item">
            <div class="hero-action-btn">
                <i data-lucide="file-spreadsheet" style="width: 22px; height: 22px;"></i>
            </div>
            <span class="hero-action-label">Import Excel</span>
        </a>

        <a href="<?= View::url('export/leads?session_id=' . $selectedSessionId) ?>" class="hero-action-item">
            <div class="hero-action-btn">
                <i data-lucide="download" style="width: 22px; height: 22px;"></i>
            </div>
            <span class="hero-action-label">Export CSV</span>
        </a>
    </div>
</div>

<!-- 2. STAT METRIC PILL CARDS (HORIZONTAL ROW FROM SAMPLE) -->
<div class="metrics-pill-row">
    <!-- Total Enquiries -->
    <a href="<?= View::url('leads') ?>" class="metric-pill-card">
        <div class="metric-pill-top">
            <span>Total Enquiries</span>
            <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
        </div>
        <div class="metric-pill-bottom">
            <div class="metric-pill-val">
                <i data-lucide="users" class="text-primary" style="width: 22px; height: 22px;"></i>
                <?= number_format($metrics['total_leads']) ?>
            </div>
            <span class="growth-pill positive">+<?= $metrics['new_leads'] ?> / new</span>
        </div>
    </a>

    <!-- New Applications -->
    <a href="<?= View::url('leads?status=New') ?>" class="metric-pill-card">
        <div class="metric-pill-top">
            <span>New Applications</span>
            <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
        </div>
        <div class="metric-pill-bottom">
            <div class="metric-pill-val">
                <i data-lucide="file-text" class="text-primary" style="width: 22px; height: 22px;"></i>
                <?= number_format($metrics['new_leads']) ?>
            </div>
            <span class="growth-pill positive">+<?= $metrics['new_leads'] ?> / today</span>
        </div>
    </a>

    <!-- Confirmed Admissions -->
    <a href="<?= View::url('leads?status=Admission+Confirmed') ?>" class="metric-pill-card">
        <div class="metric-pill-top">
            <span>Confirmed Admissions</span>
            <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
        </div>
        <div class="metric-pill-bottom">
            <div class="metric-pill-val">
                <i data-lucide="graduation-cap" class="text-success" style="width: 22px; height: 22px;"></i>
                <?= number_format($metrics['confirmed_admissions']) ?>
            </div>
            <span class="growth-pill positive">+<?= $metrics['confirmed_admissions'] ?> enrolled</span>
        </div>
    </a>

    <!-- Overdue Attention -->
    <a href="<?= View::url('tasks&due_filter=overdue') ?>" class="metric-pill-card">
        <div class="metric-pill-top">
            <span>Overdue Attention</span>
            <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
        </div>
        <div class="metric-pill-bottom">
            <div class="metric-pill-val text-danger">
                <i data-lucide="alert-circle" class="text-danger" style="width: 22px; height: 22px;"></i>
                <?= number_format($metrics['overdue_followups'] + $metrics['overdue_tasks']) ?>
            </div>
            <span class="growth-pill alert"><?= $metrics['overdue_tasks'] ?> tasks</span>
        </div>
    </a>

    <!-- Conversion Rate -->
    <a href="<?= View::url('reports') ?>" class="metric-pill-card">
        <div class="metric-pill-top">
            <span>Conversion Rate</span>
            <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
        </div>
        <div class="metric-pill-bottom">
            <div class="metric-pill-val text-primary">
                <i data-lucide="trending-up" class="text-primary" style="width: 22px; height: 22px;"></i>
                <?= $metrics['conversion_rate'] ?>%
            </div>
            <span class="growth-pill positive">Intake Ratio</span>
        </div>
    </a>
</div>

<!-- 3. BENTO GRID SECTIONS (MATCHING REFERENCE LAYOUT) -->
<div class="row g-3">
    <!-- COLUMN 1: RECENT ACTIVITY -->
    <div class="col-lg-4">
        <div class="bento-card">
            <div class="bento-card-header">
                <h6 class="bento-card-title">
                    <i data-lucide="activity" class="text-primary" style="width: 18px; height: 18px;"></i>
                    Recent Activity
                </h6>
                <a href="<?= View::url('followups') ?>" class="bento-card-link">View All &gt;</a>
            </div>
            <div class="bento-list">
                <?php if (empty($recentLeads)): ?>
                    <div class="text-center text-muted py-4 small">No recent activity.</div>
                <?php else: ?>
                    <?php foreach ($recentLeads as $idx => $rl): 
                        $avatarColor = $avatarColorClasses[$idx % count($avatarColorClasses)];
                        $statusClass = match($rl['status']) {
                            'Admission Confirmed' => 'confirmed',
                            'Contacted' => 'seeking',
                            'New' => 'open',
                            'Lost / Unreachable' => 'lost',
                            default => 'temp'
                        };
                    ?>
                    <div class="bento-item">
                        <div class="d-flex align-items-center gap-2 text-truncate me-2">
                            <div class="bento-avatar <?= $avatarColor ?>">
                                <?= strtoupper(substr($rl['student_name'], 0, 2)) ?>
                            </div>
                            <div class="text-truncate">
                                <a href="<?= View::url('leads/show&id=' . $rl['id']) ?>" class="fw-bold text-dark text-decoration-none small text-truncate d-block">
                                    <?= Sanitizer::e($rl['student_name']) ?>
                                </a>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= Sanitizer::e($rl['class_applied']) ?> &bull; <?= Sanitizer::e($rl['parent_name']) ?></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="status-pill <?= $statusClass ?>">
                                <?= Sanitizer::e($rl['status']) ?>
                            </span>
                            <div class="dropdown">
                                <button class="action-dot-btn" type="button" data-bs-toggle="dropdown">
                                    <i data-lucide="more-vertical" style="width: 15px; height: 15px;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1" style="border-radius: 12px; font-size: 0.8rem;">
                                    <li><a class="dropdown-item" href="<?= View::url('leads/show&id=' . $rl['id']) ?>">View Details</a></li>
                                    <li><a class="dropdown-item" href="<?= View::url('leads/edit&id=' . $rl['id']) ?>">Edit Enquiry</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- COLUMN 2: ENQUIRIES / CANDIDATES -->
    <div class="col-lg-4">
        <div class="bento-card">
            <div class="bento-card-header">
                <h6 class="bento-card-title">
                    <i data-lucide="users" class="text-primary" style="width: 18px; height: 18px;"></i>
                    Student Enquiries
                </h6>
                <a href="<?= View::url('leads') ?>" class="bento-card-link">All Leads &gt;</a>
            </div>
            <div class="bento-list">
                <?php if (empty($recentLeads)): ?>
                    <div class="text-center text-muted py-4 small">No candidates recorded.</div>
                <?php else: ?>
                    <?php foreach ($recentLeads as $idx => $lead): 
                        $initials = strtoupper(substr($lead['student_name'], 0, 1) . substr($lead['parent_name'] ?? 'P', 0, 1));
                    ?>
                    <div class="bento-item">
                        <div class="d-flex align-items-center gap-2 text-truncate me-2">
                            <div class="bento-avatar avatar-blue">
                                <?= $initials ?>
                            </div>
                            <div class="text-truncate">
                                <a href="<?= View::url('leads/show&id=' . $lead['id']) ?>" class="fw-bold text-dark text-decoration-none small text-truncate d-block">
                                    <?= Sanitizer::e($lead['student_name']) ?>
                                </a>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= Sanitizer::e($lead['class_applied']) ?> &bull; <?= Sanitizer::e($lead['mobile']) ?></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="status-pill seeking">
                                <?= Sanitizer::e($lead['enquiry_source']) ?>
                            </span>
                            <div class="dropdown">
                                <button class="action-dot-btn" type="button" data-bs-toggle="dropdown">
                                    <i data-lucide="more-vertical" style="width: 15px; height: 15px;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1" style="border-radius: 12px; font-size: 0.8rem;">
                                    <li><a class="dropdown-item" href="<?= View::url('leads/show&id=' . $lead['id']) ?>">Open Profile</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- COLUMN 3: COUNSELORS & WORKLOAD -->
    <div class="col-lg-4">
        <div class="bento-card">
            <div class="bento-card-header">
                <h6 class="bento-card-title">
                    <i data-lucide="user-check" class="text-primary" style="width: 18px; height: 18px;"></i>
                    Counselors & Staff
                </h6>
                <a href="<?= View::url('users') ?>" class="bento-card-link">Manage &gt;</a>
            </div>
            <div class="bento-list">
                <?php if (empty($workload)): ?>
                    <div class="text-center text-muted py-4 small">No staff assigned.</div>
                <?php else: ?>
                    <?php foreach ($workload as $cw): 
                        $cInitials = strtoupper(substr($cw['name'], 0, 2));
                    ?>
                    <div class="bento-item">
                        <div class="d-flex align-items-center gap-2 text-truncate me-2">
                            <div class="bento-avatar avatar-purple">
                                <?= $cInitials ?>
                            </div>
                            <div class="text-truncate">
                                <div class="fw-bold text-dark small text-truncate"><?= Sanitizer::e($cw['name']) ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= Sanitizer::e($cw['designation'] ?: 'Counselor') ?></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="status-pill temp d-flex align-items-center gap-1">
                                <i data-lucide="briefcase" style="width: 12px; height: 12px;"></i>
                                <?= $cw['active_leads'] ?> active
                            </span>
                            <div class="dropdown">
                                <button class="action-dot-btn" type="button" data-bs-toggle="dropdown">
                                    <i data-lucide="more-vertical" style="width: 15px; height: 15px;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1" style="border-radius: 12px; font-size: 0.8rem;">
                                    <li><a class="dropdown-item" href="<?= View::url('leads?assigned_to=' . $cw['id']) ?>">View Assigned Leads</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
