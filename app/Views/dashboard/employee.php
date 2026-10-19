<?php
use App\Helpers\Auth;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Counselor Workstation';
$userName = Auth::user()['name'] ?? 'Counselor';
$firstName = explode(' ', $userName)[0];
$avatarColorClasses = ['avatar-purple', 'avatar-orange', 'avatar-blue', 'avatar-red', 'avatar-green', 'avatar-gold'];
?>

<!-- 1. VIBRANT HERO BANNER -->
<div class="hero-dashboard-banner">
    <!-- Top Row: Avatar + Date Pill, Search Pill, Clock Pill -->
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

        <!-- Search Bar -->
        <div class="hero-search-wrapper position-relative">
            <form action="<?= View::url('leads') ?>" method="GET" class="m-0">
                <input type="hidden" name="route" value="leads">
                <i data-lucide="search" class="hero-search-icon"></i>
                <input type="text" name="search" class="hero-search-input" placeholder="Search my assigned students or contacts...">
            </form>
        </div>

        <div class="d-flex align-items-center gap-2">
            <div class="hero-pill">
                <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
                <span><?= date('g:i a') ?></span>
            </div>
            <a href="<?= View::url('leads/create') ?>" class="hero-pill" title="Add Walk-in Enquiry">
                <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
            </a>
        </div>
    </div>

    <!-- Greeting Headline -->
    <div class="hero-greeting-title">
        Welcome, <?= Sanitizer::e($firstName) ?>. Today's an admission day.
    </div>
    <div class="hero-greeting-subtitle">
        You have <strong><?= $metrics['followups_due_today'] ?> follow-up calls</strong> and <strong><?= count($todayTasks) ?> tasks</strong> scheduled for today.
    </div>

    <!-- Quick Remind Box -->
    <div class="hero-remind-box">
        <i data-lucide="bell" style="width: 15px; height: 15px; color: #FBBF24;"></i>
        <span>
            <?php if (!empty($todayTasks)): ?>
                <strong>Scheduled:</strong> <?= Sanitizer::e($todayTasks[0]['title']) ?> with <strong><?= Sanitizer::e($todayTasks[0]['student_name']) ?></strong>
            <?php else: ?>
                <strong>Great job:</strong> All scheduled tasks for today are up to date.
            <?php endif; ?>
        </span>
    </div>

    <!-- Action Buttons Row -->
    <div class="hero-action-row">
        <a href="<?= View::url('leads/create') ?>" class="hero-action-item">
            <div class="hero-action-btn">
                <i data-lucide="user-plus" style="width: 22px; height: 22px;"></i>
            </div>
            <span class="hero-action-label">Log Walk-in</span>
        </a>

        <a href="<?= View::url('tasks') ?>" class="hero-action-item">
            <div class="hero-action-btn">
                <i data-lucide="phone-call" style="width: 22px; height: 22px;"></i>
            </div>
            <span class="hero-action-label">Quick Call</span>
        </a>

        <a href="<?= View::url('tasks') ?>" class="hero-action-item">
            <div class="hero-action-btn">
                <i data-lucide="calendar" style="width: 22px; height: 22px;"></i>
            </div>
            <span class="hero-action-label">Campus Tour</span>
        </a>

        <a href="<?= View::url('followups') ?>" class="hero-action-item">
            <div class="hero-action-btn">
                <i data-lucide="message-square" style="width: 22px; height: 22px;"></i>
            </div>
            <span class="hero-action-label">Log Remarks</span>
        </a>

        <a href="<?= View::url('documents') ?>" class="hero-action-item">
            <div class="hero-action-btn">
                <i data-lucide="file-check-2" style="width: 22px; height: 22px;"></i>
            </div>
            <span class="hero-action-label">Check Documents</span>
        </a>
    </div>
</div>

<!-- 2. METRIC PILL CARDS -->
<div class="metrics-pill-row">
    <a href="<?= View::url('leads') ?>" class="metric-pill-card">
        <div class="metric-pill-top">
            <span>My Assigned Leads</span>
            <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
        </div>
        <div class="metric-pill-bottom">
            <div class="metric-pill-val">
                <i data-lucide="users" class="text-primary" style="width: 22px; height: 22px;"></i>
                <?= number_format($metrics['total_leads']) ?>
            </div>
            <span class="growth-pill positive">+<?= $metrics['new_leads'] ?> new</span>
        </div>
    </a>

    <a href="<?= View::url('tasks&due_filter=today') ?>" class="metric-pill-card">
        <div class="metric-pill-top">
            <span>Calls Due Today</span>
            <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
        </div>
        <div class="metric-pill-bottom">
            <div class="metric-pill-val text-primary">
                <i data-lucide="phone-forwarded" class="text-primary" style="width: 22px; height: 22px;"></i>
                <?= number_format($metrics['followups_due_today']) ?>
            </div>
            <span class="growth-pill positive">Today</span>
        </div>
    </a>

    <a href="<?= View::url('tasks&due_filter=overdue') ?>" class="metric-pill-card">
        <div class="metric-pill-top">
            <span>Overdue Tasks</span>
            <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
        </div>
        <div class="metric-pill-bottom">
            <div class="metric-pill-val text-danger">
                <i data-lucide="alert-circle" class="text-danger" style="width: 22px; height: 22px;"></i>
                <?= number_format($metrics['overdue_tasks']) ?>
            </div>
            <span class="growth-pill alert">Attention</span>
        </div>
    </a>

    <a href="<?= View::url('leads?status=Admission+Confirmed') ?>" class="metric-pill-card">
        <div class="metric-pill-top">
            <span>Enrolled Students</span>
            <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
        </div>
        <div class="metric-pill-bottom">
            <div class="metric-pill-val text-success">
                <i data-lucide="check-circle" class="text-success" style="width: 22px; height: 22px;"></i>
                <?= number_format($metrics['confirmed_admissions']) ?>
            </div>
            <span class="growth-pill positive">Confirmed</span>
        </div>
    </a>
</div>

<!-- 3. BENTO CARDS ROW -->
<div class="row g-3">
    <!-- Today's Tasks -->
    <div class="col-lg-6">
        <div class="bento-card">
            <div class="bento-card-header">
                <h6 class="bento-card-title">
                    <i data-lucide="clock" class="text-primary" style="width: 18px; height: 18px;"></i>
                    Today's Action Items
                </h6>
                <a href="<?= View::url('tasks') ?>" class="bento-card-link">All Tasks &gt;</a>
            </div>
            <div class="bento-list">
                <?php if (empty($todayTasks)): ?>
                    <div class="text-center text-muted py-5 small">No pending tasks for today. You're all caught up!</div>
                <?php else: ?>
                    <?php foreach ($todayTasks as $tt): ?>
                    <div class="bento-item">
                        <div class="d-flex align-items-center gap-2 text-truncate me-2">
                            <div class="bento-avatar avatar-blue">
                                <i data-lucide="phone-call" style="width: 16px; height: 16px;"></i>
                            </div>
                            <div class="text-truncate">
                                <div class="fw-bold text-dark small text-truncate"><?= Sanitizer::e($tt['title']) ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;">Student: <strong><?= Sanitizer::e($tt['student_name']) ?></strong> &bull; <?= Sanitizer::e($tt['mobile']) ?></div>
                            </div>
                        </div>
                        <a href="<?= View::url('leads/show&id=' . $tt['lead_id']) ?>" class="btn btn-sm btn-navy btn-pill py-1 px-3" style="font-size: 0.75rem;">
                            Action &rarr;
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- My Recent Leads -->
    <div class="col-lg-6">
        <div class="bento-card">
            <div class="bento-card-header">
                <h6 class="bento-card-title">
                    <i data-lucide="users" class="text-primary" style="width: 18px; height: 18px;"></i>
                    My Active Enquiries
                </h6>
                <a href="<?= View::url('leads') ?>" class="bento-card-link">View All &gt;</a>
            </div>
            <div class="bento-list">
                <?php if (empty($recentLeads)): ?>
                    <div class="text-center text-muted py-5 small">No assigned enquiries.</div>
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
                                <div class="text-muted" style="font-size: 0.72rem;"><?= Sanitizer::e($rl['class_applied']) ?> &bull; <?= Sanitizer::e($rl['mobile']) ?></div>
                            </div>
                        </div>
                        <span class="status-pill <?= $statusClass ?>">
                            <?= Sanitizer::e($rl['status']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
