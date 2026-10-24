<?php
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$statusSlug = trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $lead['status']), '-');
$pageTitle = $lead['lead_ref'] . ' - ' . $lead['student_name'];

// Helper to render timeline items matching the sidebar offcanvas design
$renderTimelineItem = function(array $item) {
    $time = strtotime($item['created_at']);
    $today = strtotime('today');
    $yesterday = strtotime('yesterday');
    if ($time >= $today) {
        $timeStr = 'Today ' . date('h:i A', $time);
    } elseif ($time >= $yesterday) {
        $timeStr = 'Yesterday ' . date('h:i A', $time);
    } else {
        $timeStr = date('d M Y, h:i A', $time);
    }

    $type = $item['type'] ?? '';
    if ($type === 'call_log') {
        $nodeClass = 'timeline-node-lavender';
        $icon = ($item['contact_method'] ?? '') === 'WhatsApp' ? 'message-square' : (($item['contact_method'] ?? '') === 'In-Person' ? 'users' : 'phone-call');
        ?>
        <div class="timeline-v2-item">
            <div class="timeline-v2-node <?= $nodeClass ?>">
                <i data-lucide="<?= $icon ?>" style="width: 15px; height: 15px;"></i>
            </div>
            <div class="timeline-v2-content">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-dark small"><strong><?= Sanitizer::e($item['author']) ?></strong> logged follow-up interaction</span>
                    <span class="text-muted" style="font-size: 0.72rem;"><?= $timeStr ?></span>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                    <span class="badge bg-success text-white border-0 px-2 py-0.5 fw-semibold" style="font-size: 0.72rem; border-radius: 4px;">
                        <i data-lucide="check" style="width: 12px; height: 12px;" class="me-1"></i> <?= Sanitizer::e($item['outcome'] ?? 'Connected') ?>
                    </span>
                    <span class="text-muted small" style="font-size: 0.72rem;">via <?= Sanitizer::e($item['contact_method'] ?? 'Phone') ?></span>
                    <?php if (!empty($item['next_followup_date'])): ?>
                        <span class="text-secondary small ms-auto d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                            <i data-lucide="clock" style="width: 12px; height: 12px;"></i> Next: <?= date('d M Y, h:i A', strtotime($item['next_followup_date'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($item['remarks'])): ?>
                    <div class="notes-card-box mt-2">
                        <div class="notes-card-body">
                            <?= nl2br(Sanitizer::e($item['remarks'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    } elseif ($type === 'assignment') {
        $nodeClass = 'timeline-node-white';
        ?>
        <div class="timeline-v2-item">
            <div class="timeline-v2-node <?= $nodeClass ?>">
                <i data-lucide="loader" style="width: 15px; height: 15px;"></i>
            </div>
            <div class="timeline-v2-content">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-dark small"><strong><?= Sanitizer::e($item['author']) ?></strong> shared deal progress</span>
                    <span class="text-muted" style="font-size: 0.72rem;"><?= $timeStr ?></span>
                </div>
                <div class="d-flex align-items-center gap-1 mt-1">
                    <span class="badge-stage-purple"><?= Sanitizer::e($item['prev_user_name'] ?? 'New') ?></span>
                    <span class="text-muted small mx-1">&rarr;</span>
                    <span class="badge-stage-green"><?= Sanitizer::e($item['new_user_name'] ?? 'Assigned') ?></span>
                </div>
                <?php if (!empty($item['reason']) && $item['reason'] !== 'Initial assignment'): ?>
                    <div class="text-secondary small mt-1.5 p-2 bg-light rounded" style="font-size: 0.78rem;">
                        <i data-lucide="info" style="width: 12px; height: 12px;" class="me-1"></i> <?= Sanitizer::e($item['reason']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    } elseif ($type === 'task_created') {
        $nodeClass = 'timeline-node-lavender';
        ?>
        <div class="timeline-v2-item">
            <div class="timeline-v2-node <?= $nodeClass ?>">
                <i data-lucide="calendar-plus" style="width: 15px; height: 15px;"></i>
            </div>
            <div class="timeline-v2-content">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-dark small"><strong><?= Sanitizer::e($item['author'] ?? 'Staff') ?></strong> created task: <strong><?= Sanitizer::e($item['task_title'] ?? $item['title']) ?></strong></span>
                    <span class="text-muted" style="font-size: 0.72rem;"><?= $timeStr ?></span>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                    <span class="badge bg-primary text-white border-0 px-2 py-0.5 fw-semibold" style="font-size: 0.72rem; border-radius: 4px;">
                        <?= Sanitizer::e($item['task_type'] ?? 'Follow-up') ?>
                    </span>
                    <span class="badge priority-badge-<?= $item['priority'] ?? 'Medium' ?> py-0.5" style="font-size: 0.72rem;">
                        <?= $item['priority'] ?? 'Medium' ?> Priority
                    </span>
                    <?php if (!empty($item['assignee_name'])): ?>
                        <span class="text-muted small" style="font-size: 0.72rem;">Assigned to <?= Sanitizer::e($item['assignee_name']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($item['due_date'])): ?>
                        <span class="text-secondary small ms-auto d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                            <i data-lucide="clock" style="width: 12px; height: 12px;"></i> Due: <?= date('d M Y, h:i A', strtotime($item['due_date'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($item['description'])): ?>
                    <div class="notes-card-box mt-2">
                        <div class="notes-card-body">
                            <?= nl2br(Sanitizer::e($item['description'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    } elseif ($type === 'task_completed') {
        $nodeClass = 'timeline-node-emerald';
        ?>
        <div class="timeline-v2-item">
            <div class="timeline-v2-node <?= $nodeClass ?>">
                <i data-lucide="check-circle-2" style="width: 15px; height: 15px;"></i>
            </div>
            <div class="timeline-v2-content">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-dark small"><strong><?= Sanitizer::e($item['author'] ?? 'Admission Counselor') ?></strong> completed task: <strong><?= Sanitizer::e($item['task_title'] ?? $item['title']) ?></strong></span>
                    <span class="text-muted" style="font-size: 0.72rem;"><?= $timeStr ?></span>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                    <span class="badge bg-success text-white border-0 px-2 py-0.5 fw-semibold" style="font-size: 0.72rem; border-radius: 4px;">
                        <i data-lucide="check-check" style="width: 12px; height: 12px;" class="me-1"></i> Completed
                    </span>
                    <span class="badge bg-light text-secondary border px-2 py-0.5 fw-medium" style="font-size: 0.72rem; border-radius: 4px;">
                        <?= Sanitizer::e($item['task_type'] ?? 'Task') ?>
                    </span>
                    <?php if (!empty($item['assignee_name'])): ?>
                        <span class="text-muted small" style="font-size: 0.72rem;">Assignee: <?= Sanitizer::e($item['assignee_name']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($item['completion_remarks'])): ?>
                    <div class="notes-card-box mt-2" style="border-left: 3px solid #10b981; background: #f0fdf4;">
                        <div class="notes-card-body text-dark" style="font-size: 0.8rem;">
                            <strong class="text-success d-inline-flex align-items-center gap-1 mb-1">
                                <i data-lucide="message-square" style="width: 13px; height: 13px;"></i> Completion Outcome / Remarks:
                            </strong>
                            <div class="text-secondary"><?= nl2br(Sanitizer::e($item['completion_remarks'])) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    } elseif ($type === 'task_cancelled') {
        $nodeClass = 'timeline-node-white';
        ?>
        <div class="timeline-v2-item">
            <div class="timeline-v2-node <?= $nodeClass ?>">
                <i data-lucide="x-circle" style="width: 15px; height: 15px;" class="text-secondary"></i>
            </div>
            <div class="timeline-v2-content">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-dark small"><strong><?= Sanitizer::e($item['author'] ?? 'Staff') ?></strong> cancelled task: <strong><?= Sanitizer::e($item['task_title'] ?? $item['title']) ?></strong></span>
                    <span class="text-muted" style="font-size: 0.72rem;"><?= $timeStr ?></span>
                </div>
                <?php if (!empty($item['description'])): ?>
                    <div class="notes-card-box mt-2">
                        <div class="notes-card-body"><?= nl2br(Sanitizer::e($item['description'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    } elseif ($type === 'lead_created') {
        $nodeClass = 'timeline-node-blue';
        ?>
        <div class="timeline-v2-item">
            <div class="timeline-v2-node <?= $nodeClass ?>">
                <i data-lucide="user-plus" style="width: 15px; height: 15px;"></i>
            </div>
            <div class="timeline-v2-content">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-dark small"><strong><?= Sanitizer::e($item['author'] ?? 'System') ?></strong> registered admission enquiry</span>
                    <span class="text-muted" style="font-size: 0.72rem;"><?= $timeStr ?></span>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                    <span class="badge bg-primary text-white border-0 px-2 py-0.5 fw-semibold" style="font-size: 0.72rem; border-radius: 4px;">
                        Enquiry Created
                    </span>
                    <?php if (!empty($item['class_applied'])): ?>
                        <span class="badge bg-light text-secondary border px-2 py-0.5 fw-medium" style="font-size: 0.72rem; border-radius: 4px;">
                            Class: <?= Sanitizer::e($item['class_applied']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($item['enquiry_source'])): ?>
                        <span class="text-muted small" style="font-size: 0.72rem;">Source: <?= Sanitizer::e($item['enquiry_source']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($item['description'])): ?>
                    <div class="notes-card-box mt-2">
                        <div class="notes-card-body"><?= nl2br(Sanitizer::e($item['description'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    } elseif ($type === 'document') {
        $nodeClass = 'timeline-node-white';
        ?>
        <div class="timeline-v2-item">
            <div class="timeline-v2-node <?= $nodeClass ?>">
                <i data-lucide="file-text" style="width: 15px; height: 15px;"></i>
            </div>
            <div class="timeline-v2-content">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-dark small"><strong><?= Sanitizer::e($item['author'] ?? 'Staff') ?></strong> uploaded document: <strong><?= Sanitizer::e($item['file_name']) ?></strong></span>
                    <span class="text-muted" style="font-size: 0.72rem;"><?= $timeStr ?></span>
                </div>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="badge bg-secondary text-white px-2 py-0.5" style="font-size: 0.72rem; border-radius: 4px;">
                        <?= Sanitizer::e($item['verification_status'] ?? 'Uploaded') ?>
                    </span>
                    <span class="text-muted small" style="font-size: 0.72rem;"><?= Sanitizer::e($item['description']) ?></span>
                </div>
            </div>
        </div>
        <?php
    } elseif ($type === 'task') {
        $nodeClass = ($item['task_status'] ?? '') === 'Completed' ? 'timeline-node-emerald' : 'timeline-node-lavender';
        ?>
        <div class="timeline-v2-item">
            <div class="timeline-v2-node <?= $nodeClass ?>">
                <i data-lucide="check-square" style="width: 15px; height: 15px;"></i>
            </div>
            <div class="timeline-v2-content">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-dark small"><strong><?= Sanitizer::e($item['author']) ?></strong> task: <strong><?= Sanitizer::e($item['task_title'] ?? $item['title']) ?></strong></span>
                    <span class="text-muted" style="font-size: 0.72rem;"><?= $timeStr ?></span>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                    <span class="badge bg-<?= $item['badge'] ?? 'primary' ?> text-white border-0 px-2 py-0.5 fw-semibold" style="font-size: 0.72rem; border-radius: 4px;">
                        <?= Sanitizer::e($item['task_status'] ?? 'Task') ?>
                    </span>
                    <span class="badge priority-badge-<?= $item['priority'] ?? 'Medium' ?> py-0.5" style="font-size: 0.72rem;">
                        <?= $item['priority'] ?? 'Medium' ?> Priority
                    </span>
                    <?php if (!empty($item['assignee_name'])): ?>
                        <span class="text-muted small" style="font-size: 0.72rem;">Assigned to <?= Sanitizer::e($item['assignee_name']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($item['description'])): ?>
                    <div class="notes-card-box mt-2">
                        <div class="notes-card-body">
                            <?= nl2br(Sanitizer::e($item['description'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    } elseif ($type === 'status_change') {
        $nodeClass = 'timeline-node-lavender';
        ?>
        <div class="timeline-v2-item">
            <div class="timeline-v2-node <?= $nodeClass ?>">
                <i data-lucide="tag" style="width: 15px; height: 15px;" class="text-primary"></i>
            </div>
            <div class="timeline-v2-content">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-dark small"><strong><?= Sanitizer::e($item['author'] ?? 'Staff') ?></strong> updated lead status</span>
                    <span class="text-muted" style="font-size: 0.72rem;"><?= $timeStr ?></span>
                </div>
                <div class="notes-card-box mt-2">
                    <div class="notes-card-body d-flex align-items-center gap-2 py-2">
                        <i data-lucide="activity" style="width: 14px; height: 14px;" class="text-primary"></i>
                        <span class="fw-semibold text-dark small"><?= Sanitizer::e($item['remarks'] ?? $item['description']) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } elseif ($type === 'priority_change') {
        $nodeClass = 'timeline-node-lavender';
        ?>
        <div class="timeline-v2-item">
            <div class="timeline-v2-node <?= $nodeClass ?>">
                <i data-lucide="flame" style="width: 15px; height: 15px;" class="text-warning"></i>
            </div>
            <div class="timeline-v2-content">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-dark small"><strong><?= Sanitizer::e($item['author'] ?? 'Staff') ?></strong> updated lead priority</span>
                    <span class="text-muted" style="font-size: 0.72rem;"><?= $timeStr ?></span>
                </div>
                <div class="notes-card-box mt-2">
                    <div class="notes-card-body d-flex align-items-center gap-2 py-2">
                        <i data-lucide="alert-circle" style="width: 14px; height: 14px;" class="text-warning"></i>
                        <span class="fw-semibold text-dark small"><?= Sanitizer::e($item['remarks'] ?? $item['description']) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } else {
        $nodeClass = 'timeline-node-white';
        $icon = !empty($item['icon']) ? $item['icon'] : 'activity';
        ?>
        <div class="timeline-v2-item">
            <div class="timeline-v2-node <?= $nodeClass ?>">
                <i data-lucide="<?= $icon ?>" style="width: 15px; height: 15px;"></i>
            </div>
            <div class="timeline-v2-content">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-dark small"><strong><?= Sanitizer::e($item['author'] ?? 'System') ?></strong> <?= Sanitizer::e($item['title'] ?? 'Activity recorded') ?></span>
                    <span class="text-muted" style="font-size: 0.72rem;"><?= $timeStr ?></span>
                </div>
                <?php if (!empty($item['description'])): ?>
                    <div class="notes-card-box mt-2">
                        <div class="notes-card-body"><?= nl2br(Sanitizer::e($item['description'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
};
?>
<!-- Back link -->
<div class="mb-3">
    <a href="<?= View::url('leads') ?>" class="text-secondary text-decoration-none small d-inline-flex align-items-center gap-1.5 fw-semibold">
        <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i>
        <span>Back to Enquiries</span>
    </a>
</div>

<!-- Lead Header Hero Card -->
<div class="lead-hero-card">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
        <div class="d-flex align-items-start gap-3">
            <?php
            $nameParts = preg_split('/\s+/', trim($lead['student_name']));
            $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
            ?>
            <div class="lead-hero-avatar">
                <?= $initials ?: 'GD' ?>
            </div>
            <div>
                <div class="d-flex align-items-center flex-wrap gap-2 mb-1.5">
                    <span class="badge bg-navy px-2.5 py-1 fs-6 font-monospace" style="letter-spacing: 0.5px;"><?= Sanitizer::e($lead['lead_ref']) ?></span>
                    <span class="badge badge-lead-status badge-status-<?= $statusSlug ?>">
                        <span class="badge-dot"></span>
                        <span><?= Sanitizer::e($lead['status']) ?></span>
                    </span>
                    <span class="badge priority-badge priority-badge-<?= $lead['priority'] ?>">
                        <span class="priority-icon">
                            <?php if ($lead['priority'] === 'Urgent'): ?>
                                <i data-lucide="flame" style="width: 12px; height: 12px;" class="me-1"></i>
                            <?php elseif ($lead['priority'] === 'High'): ?>
                                <i data-lucide="arrow-up" style="width: 12px; height: 12px;" class="me-1"></i>
                            <?php elseif ($lead['priority'] === 'Medium'): ?>
                                <i data-lucide="minus" style="width: 12px; height: 12px;" class="me-1"></i>
                            <?php else: ?>
                                <i data-lucide="arrow-down" style="width: 12px; height: 12px;" class="me-1"></i>
                            <?php endif; ?>
                        </span>
                        <span><?= $lead['priority'] ?> Priority</span>
                    </span>
                </div>
                <h3 class="fw-bold text-navy mb-1" style="font-size: 1.5rem; letter-spacing: -0.02em;"><?= Sanitizer::e($lead['student_name']) ?></h3>
                <div class="text-muted small d-flex flex-wrap align-items-center gap-3">
                    <span class="d-inline-flex align-items-center gap-1">
                        <i data-lucide="graduation-cap" style="width: 14px; height: 14px;" class="text-secondary"></i>
                        Class: <strong><?= Sanitizer::e($lead['class_applied']) ?></strong>
                    </span>
                    <span class="text-muted">&bull;</span>
                    <span class="d-inline-flex align-items-center gap-1">
                        <i data-lucide="calendar" style="width: 14px; height: 14px;" class="text-secondary"></i>
                        Session: <span class="badge bg-light text-dark border px-2 py-0.5"><?= Sanitizer::e($lead['session_label']) ?></span>
                    </span>
                    <span class="text-muted">&bull;</span>
                    <span class="d-inline-flex align-items-center gap-1">
                        <i data-lucide="user" style="width: 14px; height: 14px;" class="text-secondary"></i>
                        Parent: <strong><?= Sanitizer::e($lead['parent_name']) ?></strong>
                    </span>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- 1. Call Button -->
            <a href="tel:<?= Sanitizer::e($lead['mobile']) ?>" class="btn btn-outline-dark d-inline-flex align-items-center gap-1.5 shadow-sm" title="Direct Phone Call">
                <i data-lucide="phone" style="width: 15px; height: 15px;"></i>
                <span>Call</span>
            </a>
            <!-- 2. WhatsApp Button with Templates -->
            <button type="button" class="btn text-white d-inline-flex align-items-center gap-1.5 shadow-sm btn-open-quick-whatsapp" style="background: #25d366; font-weight: 600;" title="Send WhatsApp Message with Status Templates">
                <i data-lucide="message-circle" style="width: 15px; height: 15px;"></i>
                <span>WhatsApp</span>
            </button>
            <!-- 3. Email Button with Templates -->
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm btn-open-quick-email" title="Compose & Send Email with Templates">
                <i data-lucide="mail" style="width: 15px; height: 15px;"></i>
                <span>Email</span>
            </button>

            <button type="button" class="btn btn-navy d-inline-flex align-items-center gap-1.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#logCallModal">
                <i data-lucide="phone-call" style="width: 15px; height: 15px;"></i>
                <span>Log Call</span>
            </button>
            <button type="button" class="btn btn-outline-navy d-inline-flex align-items-center gap-1.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                <i data-lucide="plus-circle" style="width: 15px; height: 15px;"></i>
                <span>Add Task</span>
            </button>
            <?php if (Auth::isAdmin()): ?>
            <button type="button" class="btn btn-outline-warning d-inline-flex align-items-center gap-1.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#assignModal">
                <i data-lucide="user-check" style="width: 15px; height: 15px;"></i>
                <span>Assign</span>
            </button>
            <?php endif; ?>
            <a href="<?= View::url('leads/edit&id=' . $lead['id']) ?>" class="btn btn-light border d-inline-flex align-items-center gap-1.5 shadow-sm text-dark">
                <i data-lucide="edit-3" style="width: 15px; height: 15px;"></i>
                <span>Edit</span>
            </a>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav lead-tabs-nav" id="leadTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" id="overview-tab" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button">
            <i data-lucide="layout-dashboard" class="me-1.5" style="width: 16px; height: 16px;"></i>
            <span>Overview</span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="timeline-tab" data-bs-toggle="pill" data-bs-target="#tab-timeline" type="button">
            <i data-lucide="activity" class="me-1.5" style="width: 16px; height: 16px;"></i>
            <span>Activity Timeline</span>
            <span class="badge-tab-count"><?= count($timeline) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="tasks-tab" data-bs-toggle="pill" data-bs-target="#tab-tasks" type="button">
            <i data-lucide="check-square" class="me-1.5" style="width: 16px; height: 16px;"></i>
            <span>Tasks</span>
            <span class="badge-tab-count"><?= count($tasks) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="documents-tab" data-bs-toggle="pill" data-bs-target="#tab-documents" type="button">
            <i data-lucide="file-check-2" class="me-1.5" style="width: 16px; height: 16px;"></i>
            <span>Admission Documents</span>
            <span class="badge-tab-count"><?= count($checklist) ?></span>
        </button>
    </li>
</ul>

<!-- Tabs Content -->
<div class="tab-content" id="leadTabsContent">
    <!-- TAB 1: OVERVIEW -->
    <div class="tab-pane fade show active" id="tab-overview">
        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Student & Contact Profile Card -->
                <div class="lead-card mb-4">
                    <div class="lead-card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="user-check" style="width: 18px; height: 18px;" class="text-navy"></i>
                            <h6 class="fw-bold text-navy mb-0" style="font-size: 0.98rem;">Student & Admission Profile</h6>
                        </div>
                        <span class="badge bg-light text-secondary border px-2.5 py-1">Enquiry ID #<?= $lead['id'] ?></span>
                    </div>
                    <div class="lead-card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="info-box-tile">
                                    <div class="info-label">Student Full Name</div>
                                    <div class="info-value text-navy"><?= Sanitizer::e($lead['student_name']) ?></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-box-tile">
                                    <div class="info-label">Parent / Guardian</div>
                                    <div class="info-value"><?= Sanitizer::e($lead['parent_name']) ?></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-box-tile">
                                    <div class="info-label">Primary Mobile</div>
                                    <div class="d-flex align-items-center justify-content-between mt-1">
                                        <a href="tel:<?= Sanitizer::e($lead['mobile']) ?>" class="fw-bold text-navy text-decoration-none d-inline-flex align-items-center gap-1.5">
                                            <i data-lucide="phone" style="width: 14px; height: 14px;" class="text-primary"></i>
                                            <span><?= Sanitizer::e($lead['mobile']) ?></span>
                                        </a>
                                        <div class="d-flex gap-1">
                                            <a href="tel:<?= Sanitizer::e($lead['mobile']) ?>" class="btn btn-sm btn-outline-primary py-0.5 px-2" title="Direct Phone Call">
                                                <i data-lucide="phone-call" style="width: 12px; height: 12px;"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-success py-0.5 px-2 btn-open-quick-whatsapp" title="Send WhatsApp with Status Templates">
                                                <i data-lucide="message-circle" style="width: 12px; height: 12px;"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-box-tile">
                                    <div class="info-label">Alternate Contact</div>
                                    <div class="info-value">
                                        <?= $lead['alternate_mobile'] ? '<a href="tel:' . Sanitizer::e($lead['alternate_mobile']) . '" class="text-decoration-none text-dark"><i data-lucide="phone" style="width: 13px; height: 13px;" class="me-1 text-muted"></i>' . Sanitizer::e($lead['alternate_mobile']) . '</a>' : '<span class="text-muted">—</span>' ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-box-tile">
                                    <div class="info-label">Email Address</div>
                                    <div class="d-flex align-items-center justify-content-between mt-1">
                                        <div class="info-value">
                                            <?= $lead['email'] ? '<a href="mailto:' . Sanitizer::e($lead['email']) . '" class="text-decoration-none"><i data-lucide="mail" style="width: 13px; height: 13px;" class="me-1 text-muted"></i>' . Sanitizer::e($lead['email']) . '</a>' : '<span class="text-muted">—</span>' ?>
                                        </div>
                                        <?php if (!empty($lead['email'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2 btn-open-quick-email" title="Compose & Send Email with Templates">
                                            <i data-lucide="mail" style="width: 12px; height: 12px;"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-box-tile">
                                    <div class="info-label">City / Locality</div>
                                    <div class="info-value">
                                        <i data-lucide="map-pin" style="width: 13px; height: 13px;" class="me-1 text-muted"></i>
                                        <?= Sanitizer::e($lead['city'] ?: 'Purnea') ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="info-box-tile">
                                    <div class="info-label">Residential Address</div>
                                    <div class="info-value"><?= $lead['address'] ? Sanitizer::e($lead['address']) : '<span class="text-muted">No address specified</span>' ?></div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($lead['remarks'])): ?>
                        <div class="mt-4 pt-3 border-top">
                            <div class="info-label mb-2">Initial Remarks / Counselor Notes</div>
                            <div class="notes-card-box">
                                <div class="notes-card-header d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <i data-lucide="file-text" style="width: 14px; height: 14px;" class="text-secondary"></i>
                                        <span class="small fw-semibold text-dark">Recorded upon registration</span>
                                    </div>
                                    <span class="text-muted small" style="font-size: 0.72rem;"><?= date('d M Y', strtotime($lead['created_at'])) ?></span>
                                </div>
                                <div class="notes-card-body">
                                    <?= nl2br(Sanitizer::e($lead['remarks'])) ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Admission Documents Quick Summary Card -->
                <?php
                $availableDocsCount = 0;
                $totalDocsCount = count($checklist);
                foreach ($checklist as $c) {
                    if ($c['status'] === 'Available') $availableDocsCount++;
                }
                $docsPct = $totalDocsCount > 0 ? round(($availableDocsCount / $totalDocsCount) * 100) : 0;
                ?>
                <div class="lead-card mb-4">
                    <div class="lead-card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="file-check-2" style="width: 18px; height: 18px;" class="text-success"></i>
                            <h6 class="fw-bold text-navy mb-0" style="font-size: 0.98rem;">Admission Documents Checklist</h6>
                        </div>
                        <button type="button" onclick="const t = document.getElementById('documents-tab'); if(t){ bootstrap.Tab.getOrCreateInstance(t).show(); }" class="btn btn-sm btn-link text-dark p-0 text-decoration-none fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 0.82rem;">
                            <span>Manage Checklist</span>
                            <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
                        </button>
                    </div>
                    <div class="lead-card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small text-muted">Document Verification Progress</span>
                            <span class="fw-bold small text-navy"><?= $availableDocsCount ?> of <?= $totalDocsCount ?> Completed (<?= $docsPct ?>%)</span>
                        </div>
                        <div class="progress mb-3" style="height: 8px; border-radius: 999px;">
                            <div class="progress-bar bg-success" style="width: <?= $docsPct ?>%;"></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($checklist as $doc): ?>
                                <span class="badge border <?= $doc['status'] === 'Available' ? 'bg-success-subtle text-success border-success' : 'bg-light text-secondary' ?> py-1.5 px-2.5 d-inline-flex align-items-center gap-1">
                                    <i data-lucide="<?= $doc['status'] === 'Available' ? 'check' : 'clock' ?>" style="width: 12px; height: 12px;"></i>
                                    <span><?= Sanitizer::e($doc['name']) ?></span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Counselor & Pipeline Card -->
                <div class="lead-card mb-4">
                    <div class="lead-card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-navy mb-0" style="font-size: 0.95rem;">Counselor & Pipeline</h6>
                        <?php if (Auth::isAdmin()): ?>
                        <button type="button" class="btn btn-sm btn-link p-0 text-navy fw-semibold text-decoration-none" data-bs-toggle="modal" data-bs-target="#assignModal" style="font-size: 0.8rem;">
                            <i data-lucide="refresh-cw" style="width: 12px; height: 12px;" class="me-1"></i> Reassign
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="lead-card-body">
                        <div class="counselor-profile-tile mb-3">
                            <div class="counselor-avatar">
                                <?= strtoupper(substr($lead['assigned_name'] ?: 'U', 0, 1)) ?>
                            </div>
                            <div class="overflow-hidden">
                                <div class="info-label" style="font-size: 0.68rem;">Assigned Counselor</div>
                                <div class="fw-bold text-navy text-truncate" style="font-size: 0.95rem;">
                                    <?= $lead['assigned_name'] ? Sanitizer::e($lead['assigned_name']) : '<span class="badge bg-gold-subtle text-gold">Unassigned</span>' ?>
                                </div>
                                <?php if ($lead['assigned_email']): ?>
                                    <div class="small text-muted text-truncate" style="font-size: 0.78rem;"><?= Sanitizer::e($lead['assigned_email']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Next Follow-up Alert Box -->
                        <div class="mb-3 p-3 rounded-3 <?= $lead['next_followup_date'] ? 'bg-light border' : 'bg-light' ?>">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="info-label mb-0">Next Follow-up Due</span>
                                <i data-lucide="clock" style="width: 14px; height: 14px;" class="text-secondary"></i>
                            </div>
                            <?php if ($lead['next_followup_date']): ?>
                                <div class="fw-bold text-navy mt-1" style="font-size: 0.95rem;">
                                    <?= date('d M Y, h:i A', strtotime($lead['next_followup_date'])) ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small mt-1">No scheduled follow-up</div>
                            <?php endif; ?>
                        </div>

                        <!-- Pipeline Info -->
                        <div class="d-flex align-items-center justify-content-between py-2 border-top text-muted small">
                            <?php if (str_starts_with($lead['enquiry_source'] ?? '', 'Admission Manager')): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">
                                    <i data-lucide="user-check" style="width: 12px; height: 12px;" class="me-1"></i><?= Sanitizer::e($lead['enquiry_source']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-navy border fw-semibold"><?= Sanitizer::e($lead['enquiry_source']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center justify-content-between py-2 border-top text-muted small">
                            <span>Enquiry Date:</span>
                            <strong class="text-dark"><?= date('d F Y', strtotime($lead['enquiry_date'])) ?></strong>
                        </div>
                        <div class="d-flex align-items-center justify-content-between py-2 border-top text-muted small">
                            <span>Registered By:</span>
                            <span class="text-dark"><?= Sanitizer::e($lead['created_by_name'] ?? 'System') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Latest Activities (Matching Sidebar Details Design) -->
                <div class="lead-card mb-4">
                    <div class="lead-card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem; letter-spacing: -0.2px;">Latest Activities</h6>
                            <span class="badge-yellow-pill"><?= count($timeline) ?></span>
                        </div>
                        <button type="button" onclick="const t = document.getElementById('timeline-tab'); if(t){ bootstrap.Tab.getOrCreateInstance(t).show(); }" class="btn btn-link text-dark p-0 small text-decoration-none fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 0.8rem;">
                            <i data-lucide="history" style="width: 14px; height: 14px;"></i>
                            <span>View All Activity</span>
                        </button>
                    </div>
                    <div class="lead-card-body">
                        <?php if (empty($timeline)): ?>
                            <div class="text-center py-4 text-muted small">No recent activities recorded yet.</div>
                        <?php else: ?>
                            <div class="timeline-v2-container">
                                <?php 
                                $previewTimeline = array_slice($timeline, 0, 4);
                                foreach ($previewTimeline as $item) {
                                    $renderTimelineItem($item);
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notes Card (Matching Sidebar Notes Design) -->
                <div class="lead-card mb-4">
                    <div class="lead-card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem; letter-spacing: -0.2px;">Notes</h6>
                            <span class="badge-yellow-pill"><?= !empty($lead['remarks']) ? 1 : 0 ?></span>
                        </div>
                        <button type="button" class="btn btn-link text-dark p-0 small text-decoration-none fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 0.8rem;" data-bs-toggle="modal" data-bs-target="#logCallModal">
                            <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
                            <span>Add Note</span>
                        </button>
                    </div>
                    <div class="lead-card-body">
                        <div class="notes-card-box">
                            <div class="notes-card-header d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i data-lucide="file-text" style="width: 15px; height: 15px;" class="text-secondary"></i>
                                    <span class="fw-semibold text-dark small">Note by <strong><?= Sanitizer::e($lead['assigned_name'] ?: ($lead['created_by_name'] ?? 'Counselor')) ?></strong></span>
                                </div>
                                <div class="text-muted small d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                    <i data-lucide="calendar" style="width: 12px; height: 12px;"></i>
                                    <span><?= date('d M Y, h:i A', strtotime($lead['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="notes-card-body">
                                <p class="mb-0 text-secondary" style="white-space: pre-wrap;"><?= !empty($lead['remarks']) ? Sanitizer::e($lead['remarks']) : 'No remarks or special notes entered yet.' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: TIMELINE -->
    <div class="tab-pane fade" id="tab-timeline">
        <div class="lead-card">
            <div class="lead-card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem; letter-spacing: -0.2px;">Recent Follow-up Logs & Activity Timeline</h6>
                    <span class="badge-yellow-pill"><?= count($timeline) ?></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-navy d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#logCallModal">
                        <i data-lucide="phone-call" style="width: 14px; height: 14px;"></i>
                        <span>Log Follow-up Call</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-navy d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                        <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
                        <span>Add Task</span>
                    </button>
                </div>
            </div>
            <div class="lead-card-body p-4 p-md-5">
                <?php if (empty($timeline)): ?>
                    <div class="text-center py-5 text-muted">
                        <i data-lucide="clock" class="mb-2 text-secondary" style="width: 32px; height: 32px;"></i>
                        <p class="mb-0">No timeline activities recorded yet.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline-v2-container">
                        <?php foreach ($timeline as $item): ?>
                            <?php $renderTimelineItem($item); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TAB 3: TASKS -->
    <div class="tab-pane fade" id="tab-tasks">
        <div class="lead-card">
            <div class="lead-card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="check-square" style="width: 18px; height: 18px;" class="text-navy"></i>
                    <h6 class="fw-bold text-navy mb-0" style="font-size: 0.98rem;">Action Items & Tasks</h6>
                </div>
                <button type="button" class="btn btn-sm btn-navy d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                    <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
                    <span>Add Task</span>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table custom-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Task Title</th>
                            <th>Type</th>
                            <th>Assignee</th>
                            <th>Due Date</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Outcome Remarks</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No tasks created for this lead yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $t): ?>
                            <tr>
                                <td class="fw-bold text-navy"><?= Sanitizer::e($t['title']) ?></td>
                                <td><span class="badge bg-light text-secondary border"><?= $t['task_type'] ?></span></td>
                                <td><?= Sanitizer::e($t['assignee_name']) ?></td>
                                <td>
                                    <?php if ($t['is_overdue']): ?>
                                        <span class="text-danger fw-bold"><?= date('d M Y, h:i A', strtotime($t['due_date'])) ?> <i data-lucide="alert-circle" style="width: 14px; height: 14px;"></i></span>
                                    <?php else: ?>
                                        <span><?= date('d M Y, h:i A', strtotime($t['due_date'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge priority-badge-<?= $t['priority'] ?>"><?= $t['priority'] ?></span></td>
                                <td>
                                    <span class="badge bg-<?= $t['status'] === 'Completed' ? 'success' : ($t['status'] === 'Cancelled' ? 'secondary' : 'warning') ?>">
                                        <?= $t['status'] ?>
                                    </span>
                                </td>
                                <td class="small"><?= $t['completion_remarks'] ? Sanitizer::e($t['completion_remarks']) : '—' ?></td>
                                <td class="text-end">
                                    <?php if ($t['status'] !== 'Completed' && $t['status'] !== 'Cancelled'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" 
                                            data-bs-toggle="modal" data-bs-target="#completeTaskModal<?= $t['id'] ?>">
                                        Complete
                                    </button>

                                    <!-- Complete Task Modal -->
                                    <div class="modal fade" id="completeTaskModal<?= $t['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content text-start">
                                                <form action="<?= View::url('tasks/complete') ?>" method="POST">
                                                    <?= Csrf::field() ?>
                                                    <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                                                    <input type="hidden" name="return_url" value="<?= View::url("leads/show&id={$lead['id']}#tasks") ?>">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title fw-bold">Complete Task: <?= Sanitizer::e($t['title']) ?></h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Outcome / Completion Remarks *</label>
                                                            <textarea name="completion_remarks" class="form-control" rows="3" required placeholder="Describe task outcome, discussion notes, or next steps..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success">Save & Mark Completed</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 4: ADMISSION DOCUMENTS CHECKLIST -->
    <?php
    $uploadedCount = 0;
    foreach ($checklist as $c) {
        if (!empty($c['file_path'])) $uploadedCount++;
    }
    ?>
    <div class="tab-pane fade" id="tab-documents">
        <div class="lead-card">
            <div class="lead-card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold text-navy mb-0 d-inline-flex align-items-center gap-2" style="font-size: 0.98rem;">
                        <i data-lucide="file-check-2" style="width: 18px; height: 18px;" class="text-success"></i>
                        <span>Admission Documents Checklist</span>
                    </h6>
                    <div class="small text-muted mt-0.5">Physical and digital document verification checklist for admission enrollment.</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($uploadedCount > 0): ?>
                        <a href="<?= View::url("documents/download-all&lead_id={$lead['id']}") ?>" class="btn btn-sm btn-navy rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-sm" title="Download all <?= $uploadedCount ?> uploaded document(s) in a single ZIP file">
                            <i data-lucide="archive" style="width: 14px; height: 14px;"></i>
                            <span>Download All (ZIP)</span>
                            <span class="badge bg-white text-navy ms-1" style="font-size: 0.72rem;"><?= $uploadedCount ?> <?= $uploadedCount === 1 ? 'file' : 'files' ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table custom-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Document Checklist Item</th>
                            <th>Requirement</th>
                            <th>Status</th>
                            <th>Physical/Uploaded File</th>
                            <th>Verification Status</th>
                            <th>Remarks</th>
                            <th class="text-end">Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checklist as $doc): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-navy"><?= Sanitizer::e($doc['name']) ?></div>
                                <div class="small text-muted"><?= Sanitizer::e($doc['description']) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $doc['is_required'] ? 'bg-navy-subtle text-navy' : 'bg-light text-secondary border' ?>">
                                    <?= $doc['is_required'] ? 'Compulsory' : 'Optional' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $doc['status'] === 'Available' ? 'success' : ($doc['status'] === 'Not Applicable' ? 'secondary' : 'warning') ?>">
                                    <?= $doc['status'] ?>
                                </span>
                                                        <td>
                                <?php if (!empty($doc['file_path'])): 
                                    $ext = strtolower(pathinfo($doc['file_name'] ?: $doc['file_path'], PATHINFO_EXTENSION));
                                    $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                    $previewUrl = View::url("documents/download&lead_id={$lead['id']}&doc_type_id={$doc['document_type_id']}");
                                    $downloadUrl = View::url("documents/download&lead_id={$lead['id']}&doc_type_id={$doc['document_type_id']}&mode=download");
                                    $fileSizeStr = !empty($doc['file_size']) ? round($doc['file_size'] / 1024, 1) . ' KB' : '';
                                ?>
                                    <div class="d-flex align-items-center gap-1.5 flex-nowrap">
                                        <!-- View / Zoom (Icon only) -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary btn-doc-action-icon btn-preview-doc" 
                                                data-doc-title="<?= Sanitizer::e($doc['name']) ?>" 
                                                data-doc-url="<?= $previewUrl ?>" 
                                                data-download-url="<?= $downloadUrl ?>" 
                                                data-is-img="<?= $isImg ? '1' : '0' ?>" 
                                                data-ext="<?= $ext ?>"
                                                title="View & Zoom Document">
                                            <i data-lucide="zoom-in" style="width: 15px; height: 15px;"></i>
                                        </button>

                                        <!-- Download (Icon only) -->
                                        <a href="<?= $downloadUrl ?>" 
                                           class="btn btn-sm btn-outline-success btn-doc-action-icon" 
                                           title="Download Document (<?= Sanitizer::e($doc['name']) ?>)">
                                            <i data-lucide="download" style="width: 15px; height: 15px;"></i>
                                        </a>

                                        <!-- Re-upload (Icon only) -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-warning btn-doc-action-icon" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#uploadDocModal<?= $doc['document_type_id'] ?>"
                                                title="Re-upload / Replace File">
                                            <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                                        </button>

                                        <!-- Delete Uploaded Document (Icon only) -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger btn-doc-action-icon" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteDocModal<?= $doc['document_type_id'] ?>"
                                                title="Delete Uploaded File">
                                            <i data-lucide="trash-2" style="width: 15px; height: 15px;"></i>
                                        </button>
                                    </div>
                                    <div class="text-muted mt-1 text-truncate d-flex align-items-center gap-1" style="font-size: 0.72rem; max-width: 210px;" title="<?= Sanitizer::e($doc['file_name'] ?: 'File') ?>">
                                        <i data-lucide="<?= $isImg ? 'image' : 'file-text' ?>" style="width: 12px; height: 12px;" class="text-secondary flex-shrink-0"></i>
                                        <span class="text-truncate"><?= Sanitizer::e($doc['file_name'] ?: 'Document') ?></span>
                                        <?= $fileSizeStr ? "<span class='text-muted flex-shrink-0'>({$fileSizeStr})</span>" : '' ?>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex align-items-center gap-1.5">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary btn-doc-action-icon" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#uploadDocModal<?= $doc['document_type_id'] ?>" 
                                                title="Upload Digital Document">
                                            <i data-lucide="upload" style="width: 14px; height: 14px;"></i>
                                        </button>
                                        <span class="badge bg-light text-muted border py-1 px-2" style="font-size: 0.74rem;">
                                            No digital upload
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $doc['verification_status'] === 'Verified' ? 'success' : ($doc['verification_status'] === 'Rejected' ? 'danger' : 'light text-dark border') ?>">
                                    <?= $doc['verification_status'] ?>
                                </span>
                            </td>
                            <td class="small"><?= $doc['remarks'] ? Sanitizer::e($doc['remarks']) : '—' ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-navy py-1 px-2" data-bs-toggle="modal" data-bs-target="#editDocModal<?= $doc['document_type_id'] ?>">
                                    Update
                                </button>

                                <!-- Edit Document Status Modal -->
                                <div class="modal fade" id="editDocModal<?= $doc['document_type_id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content text-start">
                                            <form action="<?= View::url('documents/update-status') ?>" method="POST">
                                                 <?= Csrf::field() ?>
                                                 <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                                                 <input type="hidden" name="document_type_id" value="<?= $doc['document_type_id'] ?>">

                                                 <div class="modal-header">
                                                     <h6 class="modal-title fw-bold text-navy"><?= Sanitizer::e($doc['name']) ?></h6>
                                                     <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                 </div>
                                                 <div class="modal-body">
                                                     <div class="mb-3">
                                                         <label class="form-label small fw-semibold">Status *</label>
                                                         <select name="status" class="form-select">
                                                             <option value="Pending" <?= $doc['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                             <option value="Available" <?= $doc['status'] === 'Available' ? 'selected' : '' ?>>Available (Physical or Digital copy received)</option>
                                                             <option value="Not Available" <?= $doc['status'] === 'Not Available' ? 'selected' : '' ?>>Not Available</option>
                                                             <option value="Not Applicable" <?= $doc['status'] === 'Not Applicable' ? 'selected' : '' ?>>Not Applicable (e.g. TC for Nursery)</option>
                                                         </select>
                                                     </div>

                                                     <div class="mb-3">
                                                         <label class="form-label small fw-semibold">Verification Status</label>
                                                         <select name="verification_status" class="form-select">
                                                             <option value="Not Reviewed" <?= $doc['verification_status'] === 'Not Reviewed' ? 'selected' : '' ?>>Not Reviewed</option>
                                                             <option value="Verified" <?= $doc['verification_status'] === 'Verified' ? 'selected' : '' ?>>Verified</option>
                                                             <option value="Rejected" <?= $doc['verification_status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                                         </select>
                                                     </div>

                                                     <div class="mb-3">
                                                         <label class="form-label small fw-semibold">Document Remarks</label>
                                                         <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Attested photocopy submitted during counselling"><?= Sanitizer::e($doc['remarks']) ?></textarea>
                                                     </div>
                                                 </div>
                                                 <div class="modal-footer">
                                                     <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                     <button type="submit" class="btn btn-navy">Save Status</button>
                                                 </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Upload / Re-upload Modal -->
                                <div class="modal fade" id="uploadDocModal<?= $doc['document_type_id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                                            <form action="<?= View::url('documents/upload') ?>" method="POST" enctype="multipart/form-data">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                                                <input type="hidden" name="document_type_id" value="<?= $doc['document_type_id'] ?>">

                                                <div class="modal-header bg-light border-bottom px-3.5 py-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="rounded-circle d-flex align-items-center justify-content-center text-primary" style="width: 34px; height: 34px; background: #EEF2FF;">
                                                            <i data-lucide="<?= !empty($doc['file_path']) ? 'refresh-cw' : 'upload' ?>" style="width: 16px; height: 16px;"></i>
                                                        </span>
                                                        <div class="text-start">
                                                            <h6 class="modal-title fw-bold text-navy mb-0" style="font-size: 0.95rem;">
                                                                <?= !empty($doc['file_path']) ? 'Re-upload / Replace File' : 'Upload Digital Document' ?>
                                                            </h6>
                                                            <div class="text-muted" style="font-size: 0.72rem;"><?= Sanitizer::e($doc['name']) ?></div>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body p-3.5 text-start">
                                                    <?php if (!empty($doc['file_path'])): ?>
                                                        <div class="alert alert-warning d-flex align-items-start gap-2 py-2 px-3 mb-3" style="font-size: 0.8rem; border-radius: 8px;">
                                                            <i data-lucide="alert-triangle" style="width: 16px; height: 16px;" class="flex-shrink-0 text-warning mt-0.5"></i>
                                                            <div>
                                                                <strong>Current file will be replaced:</strong>
                                                                <div class="text-truncate text-dark fw-semibold" style="max-width: 360px;">
                                                                    <?= Sanitizer::e($doc['file_name']) ?> <?= $fileSizeStr ? "({$fileSizeStr})" : '' ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold text-dark">Select File to Upload *</label>
                                                        <input type="file" name="document_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                                                        <div class="form-text mt-1 text-muted" style="font-size: 0.72rem;">
                                                            Supported formats: <strong>PDF, JPG, PNG, WEBP</strong> (Max size: <strong>10MB</strong>).
                                                        </div>
                                                    </div>

                                                    <div class="p-2.5 rounded-3 bg-light border text-muted" style="font-size: 0.74rem;">
                                                        <i data-lucide="shield-check" style="width: 13px; height: 13px;" class="text-success me-1"></i>
                                                        Uploaded files are securely stored in private storage and strictly restricted to authorized staff.
                                                    </div>
                                                </div>

                                                <div class="modal-footer bg-white border-top px-3.5 py-2.5 d-flex justify-content-end gap-2">
                                                    <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-sm btn-navy px-3.5 d-inline-flex align-items-center gap-1.5 shadow-sm">
                                                        <i data-lucide="<?= !empty($doc['file_path']) ? 'refresh-cw' : 'upload' ?>" style="width: 14px; height: 14px;"></i>
                                                        <span><?= !empty($doc['file_path']) ? 'Replace Document' : 'Upload File' ?></span>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Uploaded File Confirmation Modal -->
                                <?php if (!empty($doc['file_path'])): ?>
                                <div class="modal fade" id="deleteDocModal<?= $doc['document_type_id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width: 420px;">
                                        <div class="modal-content border-0 shadow-lg text-start" style="border-radius: 12px; overflow: hidden;">
                                            <form action="<?= View::url('documents/delete') ?>" method="POST">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                                                <input type="hidden" name="document_type_id" value="<?= $doc['document_type_id'] ?>">

                                                <div class="modal-header bg-danger-subtle border-bottom px-3.5 py-2.5 d-flex align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-2 text-danger">
                                                        <i data-lucide="trash-2" style="width: 17px; height: 17px;"></i>
                                                        <h6 class="modal-title fw-bold text-danger mb-0" style="font-size: 0.92rem;">Delete Uploaded File</h6>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body p-3.5">
                                                    <p class="text-dark small mb-2" style="font-size: 0.86rem;">
                                                        Are you sure you want to delete the uploaded file for <strong><?= Sanitizer::e($doc['name']) ?></strong>?
                                                    </p>
                                                    <div class="p-2.5 rounded-3 bg-light border text-muted small mb-2" style="font-size: 0.78rem;">
                                                        <div class="fw-semibold text-dark text-truncate" title="<?= Sanitizer::e($doc['file_name']) ?>">
                                                            <i data-lucide="file" style="width: 12px; height: 12px;" class="me-1"></i>
                                                            <?= Sanitizer::e($doc['file_name']) ?>
                                                        </div>
                                                        <?php if ($fileSizeStr): ?>
                                                            <div class="mt-0.5 text-muted" style="font-size: 0.72rem;">File size: <?= $fileSizeStr ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-danger small" style="font-size: 0.74rem;">
                                                        <i data-lucide="info" style="width: 12px; height: 12px;" class="me-0.5"></i>
                                                        This will permanently delete the file from disk.
                                                    </div>
                                                </div>

                                                <div class="modal-footer bg-white border-top px-3.5 py-2.5 d-flex justify-content-end gap-2">
                                                    <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-sm btn-danger px-3.5 d-inline-flex align-items-center gap-1.5 shadow-sm">
                                                        <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                                                        <span>Delete File</span>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Log Call Modal -->
<div class="modal fade" id="logCallModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= View::url('followups/store') ?>" method="POST">
                <?= Csrf::field() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                <input type="hidden" name="return_url" value="<?= View::url("leads/show&id={$lead['id']}#timeline") ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-navy">Record Call / Interaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Contact Method</label>
                            <select name="contact_method" class="form-select">
                                <option value="Phone" selected>Phone Call</option>
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="Email">Email</option>
                                <option value="In Person">Campus Walk-in / In Person</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Call Outcome *</label>
                            <select name="outcome" class="form-select" required>
                                <option value="Interested" selected>Interested</option>
                                <option value="Call Back Requested">Call Back Requested</option>
                                <option value="Visit Scheduled">Visit Scheduled</option>
                                <option value="Documents Requested">Documents Requested</option>
                                <option value="Busy">Busy</option>
                                <option value="No Answer">No Answer</option>
                                <option value="Not Interested">Not Interested</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Discussion Remarks *</label>
                        <textarea name="remarks" class="form-control" rows="3" required placeholder="Parent questions asked, campus tour scheduling, fee clarification..."></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Next Follow-up Date & Time</label>
                            <input type="datetime-local" name="next_followup_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime('+2 days 11:00')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Update Lead Status</label>
                            <select name="lead_status" class="form-select">
                                <option value="">-- Keep Current Status (<?= Sanitizer::e($lead['status']) ?>) --</option>
                                <?php foreach ($statuses as $st): ?>
                                    <option value="<?= Sanitizer::e($st) ?>"><?= Sanitizer::e($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy">Save Interaction Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= View::url('tasks/store') ?>" method="POST">
                <?= Csrf::field() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                <input type="hidden" name="return_url" value="<?= View::url("leads/show&id={$lead['id']}#tasks") ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-navy">Schedule Task for Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Task Title *</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. WhatsApp Admission Brochure">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Task Type</label>
                            <select name="task_type" class="form-select">
                                <option value="Follow-up Call">Follow-up Call</option>
                                <option value="WhatsApp Follow-up">WhatsApp Follow-up</option>
                                <option value="Schedule School Visit">Schedule School Visit</option>
                                <option value="Conduct School Visit">Conduct School Visit</option>
                                <option value="Collect Documents">Collect Documents</option>
                                <option value="Application Follow-up">Application Follow-up</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Assignee *</label>
                            <select name="assigned_to" class="form-select" required>
                                <?php foreach ($counselors as $cn): ?>
                                    <option value="<?= $cn['id'] ?>" <?= (int)$lead['assigned_to'] === (int)$cn['id'] ? 'selected' : '' ?>>
                                        <?= Sanitizer::e($cn['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Due Date & Time *</label>
                            <input type="datetime-local" name="due_date" class="form-control" required value="<?= date('Y-m-d\TH:i', strtotime('+1 day 10:00')) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Instructions / Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Details about what to discuss or verify..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Reassign Counselor Modal -->
<?php if (Auth::isAdmin()): ?>
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= View::url('leads/assign') ?>" method="POST">
                <?= Csrf::field() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-navy">Assign / Reassign Counselor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Counselor *</label>
                        <select name="assigned_to" class="form-select" required>
                            <option value="">-- Choose Active Counselor --</option>
                            <?php foreach ($counselors as $cn): ?>
                                <option value="<?= $cn['id'] ?>" <?= (int)$lead['assigned_to'] === (int)$cn['id'] ? 'selected' : '' ?>>
                                    <?= Sanitizer::e($cn['name']) ?> (<?= Sanitizer::e($cn['designation'] ?: 'Counselor') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Reason for Assignment *</label>
                        <input type="text" name="reason" class="form-control" required value="Workload balancing">
                        <div class="form-text" style="font-size: 0.72rem;">Recorded in assignment history and visible in lead timeline.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy">Save Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Interactive Document Preview & Zoom-in Modal -->
<div class="modal fade" id="documentPreviewModal" tabindex="-1" aria-labelledby="documentPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <!-- Modal Header with Zoom & Action Toolbar -->
            <div class="modal-header bg-white border-bottom px-3.5 py-2.5 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="rounded-circle d-flex align-items-center justify-content-center text-primary" style="width: 34px; height: 34px; background: #EEF2FF;">
                        <i data-lucide="file-text" style="width: 17px; height: 17px;"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="docPreviewTitle" style="font-size: 0.95rem;">Document Preview</h6>
                        <div class="text-muted" style="font-size: 0.74rem;">
                            <?= Sanitizer::e($lead['student_name']) ?> &bull; <span class="badge bg-light text-dark border px-1.5 py-0"><?= Sanitizer::e($lead['lead_ref']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Zoom Controls & Action Buttons -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Zoom Controls (for images) -->
                    <div class="d-flex align-items-center gap-1 bg-light p-1 rounded-3 border" id="docZoomControlsGroup">
                        <button type="button" class="doc-preview-toolbar-btn" id="btnZoomOut" title="Zoom Out (-)">
                            <i data-lucide="zoom-out" style="width: 14px; height: 14px;"></i>
                        </button>
                        <span class="px-2 small fw-bold text-dark" id="zoomLevelIndicator" style="font-size: 0.78rem; min-width: 48px; text-align: center;">100%</span>
                        <button type="button" class="doc-preview-toolbar-btn" id="btnZoomIn" title="Zoom In (+)">
                            <i data-lucide="zoom-in" style="width: 14px; height: 14px;"></i>
                        </button>
                        <button type="button" class="doc-preview-toolbar-btn" id="btnResetZoom" title="Reset Zoom (100%)">
                            <i data-lucide="maximize-2" style="width: 14px; height: 14px;"></i>
                        </button>
                        <button type="button" class="doc-preview-toolbar-btn" id="btnRotateDoc" title="Rotate 90 Degrees">
                            <i data-lucide="rotate-cw" style="width: 14px; height: 14px;"></i>
                        </button>
                    </div>

                    <!-- Direct Download Button with Formatted Name -->
                    <a href="#" id="docModalDownloadBtn" class="btn btn-sm btn-success rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 0.8rem; font-weight: 600; background: #16A34A; border-color: #16A34A;">
                        <i data-lucide="download" style="width: 14px; height: 14px;"></i>
                        <span>Download</span>
                    </a>

                    <!-- Open in New Window Tab -->
                    <a href="#" id="docModalNewTabBtn" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1.5 d-inline-flex align-items-center gap-1" title="Open in new window">
                        <i data-lucide="external-link" style="width: 14px; height: 14px;"></i>
                    </a>

                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <!-- Modal Body / Zoom Viewport -->
            <div class="modal-body p-0 doc-preview-viewport position-relative" id="docPreviewContainer">
                <!-- For Images (JPG, PNG, WEBP) with Zoom & Pan -->
                <div id="imagePreviewContainer" class="w-100 h-100 d-flex align-items-center justify-content-center p-3" style="min-height: 480px; overflow: auto;">
                    <img src="" id="docPreviewImage" class="doc-preview-img" alt="Document Preview">
                </div>

                <!-- For PDF / Document Iframe -->
                <div id="pdfPreviewContainer" class="w-100 d-none" style="height: 75vh;">
                    <iframe src="" id="docPreviewIframe" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- QUICK WHATSAPP MODAL (With Status Pre-templates & Manual Editing)          -->
<!-- ========================================================================= -->
<div class="modal fade" id="leadQuickWhatsAppModal" tabindex="-1" aria-labelledby="leadQuickWhatsAppModalLabel" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-2xl" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header border-bottom py-3 px-4" style="background: linear-gradient(135deg, #128c7e 0%, #25d366 100%); color: white;">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-circle bg-white text-success d-flex align-items-center justify-content-center shadow-xs" style="width: 36px; height: 36px;">
                        <i data-lucide="message-circle" style="width: 20px; height: 20px; color: #25d366;"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="leadQuickWhatsAppModalLabel" style="font-size: 1rem; letter-spacing: -0.2px;">Send WhatsApp Message</h6>
                        <span class="text-white text-opacity-80 small" style="font-size: 0.72rem;">Pre-made status templates & manual composer</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light bg-opacity-50">
                <!-- Recipient Info Card -->
                <div class="p-3 bg-white border rounded-3 mb-3 shadow-xs">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Candidate:</div>
                            <strong class="text-dark" id="quickWaStudentName"><?= Sanitizer::e($lead['student_name']) ?></strong>
                            <span class="badge bg-light text-secondary border ms-1" id="quickWaClassBadge" style="font-size: 0.7rem;"><?= Sanitizer::e($lead['class_applied']) ?></span>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small" style="font-size: 0.72rem;">Recipient Mobile:</div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold" id="quickWaMobileBadge" style="font-size: 0.8rem;">+91 <?= Sanitizer::e($lead['mobile']) ?></span>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
                        <span class="text-muted small" style="font-size: 0.72rem;">Current Status:</span>
                        <span class="badge badge-lead-status px-2 py-0.5 badge-status-<?= $statusSlug ?>" id="quickWaStatusBadge">
                            <span class="badge-dot"></span>
                            <span class="badge-text" id="quickWaStatusText"><?= Sanitizer::e($lead['status']) ?></span>
                        </span>
                    </div>
                </div>

                <!-- Template Selector -->
                <div class="mb-3">
                    <label class="form-label text-dark fw-semibold small mb-1.5 d-flex align-items-center justify-content-between">
                        <span><i data-lucide="sparkles" style="width: 14px; height: 14px;" class="text-warning me-1"></i> Choose Template</span>
                        <span class="text-muted fw-normal" style="font-size: 0.72rem;">Auto-tailored to status</span>
                    </label>
                    <select class="form-select form-select-sm shadow-none" id="quickWaTemplateSelect" style="border-radius: 8px;">
                        <option value="auto">⚡ Status-Matched Template (Auto)</option>
                        <option value="greeting">👋 1. New Enquiry Greeting & Introduction</option>
                        <option value="campus_visit">🏫 2. Campus Visit Invitation & Directions</option>
                        <option value="fee_details">💰 3. Fee Structure & Admission Process</option>
                        <option value="doc_pending">📄 4. Required Documents Checklist</option>
                        <option value="followup">📞 5. Follow-up & Counseling Discussion</option>
                        <option value="admission_confirmed">🎉 6. Congratulations - Admission Confirmed</option>
                        <option value="custom">✍️ Custom Message (Manual Compose)</option>
                    </select>
                </div>

                <!-- Message Compose Textarea with WhatsApp Formatting Toolbar -->
                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                        <label class="form-label text-dark fw-semibold small mb-0">Message Content</label>
                        <span class="text-muted small" id="quickWaCharCount" style="font-size: 0.72rem;">0 characters</span>
                    </div>
                    <!-- Quick Formatting Toolbar for WhatsApp -->
                    <div class="wa-format-toolbar">
                        <button type="button" class="wa-format-btn" data-format="bold" title="Wrap selection in *bold*"><b>*B*</b> Bold</button>
                        <button type="button" class="wa-format-btn" data-format="italic" title="Wrap selection in _italic_"><i>_I_</i> Italic</button>
                        <button type="button" class="wa-format-btn" data-format="bullet" title="Insert Bullet list item">• Bullet</button>
                        <button type="button" class="wa-format-btn" data-insert="📞 Toll-Free: 1800 891 1934" title="Insert Toll-Free number">📞 Toll-Free</button>
                        <button type="button" class="wa-format-btn" data-insert="✉️ info@gdgoenkapurnea.com" title="Insert Email">✉️ Email</button>
                        <div class="ms-auto d-flex gap-1">
                            <button type="button" class="wa-format-btn px-1.5" data-insert="🎓" title="Graduate cap">🎓</button>
                            <button type="button" class="wa-format-btn px-1.5" data-insert="🏫" title="School">🏫</button>
                            <button type="button" class="wa-format-btn px-1.5" data-insert="📋" title="Checklist">📋</button>
                            <button type="button" class="wa-format-btn px-1.5" data-insert="🎉" title="Celebration">🎉</button>
                        </div>
                    </div>
                    <textarea class="form-control wa-textarea-attached" id="quickWaMessage" rows="7" style="font-size: 0.85rem; line-height: 1.55;" placeholder="Type your WhatsApp message or select a template above..."></textarea>
                    <div class="form-text text-muted" style="font-size: 0.72rem;">Formatted for WhatsApp: supports <code>*bold*</code>, <code>_italic_</code>, bullet points, and clean paragraphs.</div>
                </div>

                <!-- Log Activity Option -->
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="quickWaLogActivity" checked>
                    <label class="form-check-label text-dark small" for="quickWaLogActivity">
                        Record this WhatsApp interaction on the activity timeline
                    </label>
                </div>
            </div>
            <div class="modal-footer py-2.5 px-4 bg-white border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-none" id="btnCopyWhatsAppMsg" title="Copy to clipboard">
                    <i data-lucide="copy" style="width: 14px; height: 14px;"></i>
                    <span>Copy Text</span>
                </button>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 shadow-sm" id="btnSendWhatsAppWeb" title="Open directly in WhatsApp Web in browser">
                        <i data-lucide="globe" style="width: 14px; height: 14px;"></i>
                        <span>WhatsApp Web</span>
                    </button>
                    <button type="button" class="btn btn-sm text-white px-3.5 d-inline-flex align-items-center gap-1.5 shadow-sm" id="btnConfirmSendWhatsApp" style="background: #25d366; font-weight: 600;">
                        <i data-lucide="send" style="width: 14px; height: 14px;"></i>
                        <span>Send on WhatsApp</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- QUICK EMAIL MODAL (With Templates & Compose)                              -->
<!-- ========================================================================= -->
<div class="modal fade" id="leadQuickEmailModal" tabindex="-1" aria-labelledby="leadQuickEmailModalLabel" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-2xl" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header border-bottom py-3 px-4" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color: white;">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-xs" style="width: 36px; height: 36px;">
                        <i data-lucide="mail" style="width: 20px; height: 20px; color: #4f46e5;"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="leadQuickEmailModalLabel" style="font-size: 1rem; letter-spacing: -0.2px;">Compose & Send Email</h6>
                        <span class="text-white text-opacity-80 small" style="font-size: 0.72rem;">Pre-made templates with subject and customizable body</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light bg-opacity-50">
                <!-- Recipient Info Card -->
                <div class="row g-2 mb-3">
                    <div class="col-md-7">
                        <label class="form-label text-dark fw-semibold small mb-1">To Email Address</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i data-lucide="at-sign" style="width: 14px; height: 14px;"></i></span>
                            <input type="email" class="form-control border-start-0 ps-0 shadow-none" id="quickEmailTo" value="<?= Sanitizer::e($lead['email']) ?>" placeholder="student@example.com">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label text-dark fw-semibold small mb-1">Candidate</label>
                        <input type="text" class="form-control form-control-sm bg-white shadow-none" id="quickEmailStudentName" readonly value="<?= Sanitizer::e($lead['student_name']) ?>">
                    </div>
                </div>

                <!-- Template Selector -->
                <div class="mb-3">
                    <label class="form-label text-dark fw-semibold small mb-1.5 d-flex align-items-center justify-content-between">
                        <span><i data-lucide="sparkles" style="width: 14px; height: 14px;" class="text-warning me-1"></i> Email Template</span>
                        <span class="text-muted fw-normal" style="font-size: 0.72rem;">Auto fills subject & body</span>
                    </label>
                    <select class="form-select form-select-sm shadow-none" id="quickEmailTemplateSelect" style="border-radius: 8px;">
                        <option value="auto">⚡ Status-Matched Email Template (Auto)</option>
                        <option value="brochure">📘 1. Admission Prospectus & School Overview</option>
                        <option value="campus_tour">🏫 2. Invitation for Campus Tour & Interaction</option>
                        <option value="fee_structure">📄 3. Fee Structure & Payment Guidelines</option>
                        <option value="docs_checklist">📋 4. Required Admission Documents Checklist</option>
                        <option value="welcome_letter">🎓 5. Congratulations - Welcome to GD Goenka</option>
                        <option value="custom">✍️ Custom Blank Email</option>
                    </select>
                </div>

                <!-- Subject -->
                <div class="mb-3">
                    <label class="form-label text-dark fw-semibold small mb-1">Subject</label>
                    <input type="text" class="form-control form-control-sm shadow-none" id="quickEmailSubject" style="border-radius: 8px;" placeholder="Admission Enquiry Follow-up...">
                </div>

                <!-- Message Body -->
                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label text-dark fw-semibold small mb-0">Email Message Body</label>
                        <span class="text-muted small" id="quickEmailCharCount" style="font-size: 0.72rem;">0 characters</span>
                    </div>
                    <textarea class="form-control" id="quickEmailBody" rows="7" style="border-radius: 10px; font-size: 0.85rem; line-height: 1.5;" placeholder="Compose your email message..."></textarea>
                </div>

                <!-- Log Activity Option -->
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="quickEmailLogActivity" checked>
                    <label class="form-check-label text-dark small" for="quickEmailLogActivity">
                        Record this Email in candidate activity timeline
                    </label>
                </div>
            </div>
            <div class="modal-footer py-2.5 px-4 bg-white border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-none" id="btnCopyEmailBody" title="Copy formatted text to clipboard">
                    <i data-lucide="copy" style="width: 14px; height: 14px;"></i>
                    <span>Copy Formatted</span>
                </button>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm text-white d-inline-flex align-items-center gap-1.5 shadow-sm" id="btnSendViaGmail" style="background: #ea4335; font-weight: 600;" title="Open and compose directly in Gmail Web in new tab">
                        <i data-lucide="mail" style="width: 14px; height: 14px;"></i>
                        <span>Open in Gmail</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary px-3 d-inline-flex align-items-center gap-1.5 shadow-sm" id="btnConfirmSendEmail" style="font-weight: 600;" title="Open in Outlook / Default Mail App">
                        <i data-lucide="external-link" style="width: 14px; height: 14px;"></i>
                        <span>Outlook / App</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize current lead data for quick WhatsApp & Email modals
window.currentOffcanvasLead = {
    id: <?= (int)$lead['id'] ?>,
    student_name: <?= json_encode($lead['student_name']) ?>,
    parent_name: <?= json_encode($lead['parent_name']) ?>,
    mobile: <?= json_encode($lead['mobile']) ?>,
    clean_mobile: <?= json_encode(preg_replace('/\D/', '', $lead['mobile'] ?? '')) ?>,
    email: <?= json_encode($lead['email']) ?>,
    class_applied: <?= json_encode($lead['class_applied']) ?>,
    status: <?= json_encode($lead['status']) ?>,
    status_slug: <?= json_encode($statusSlug) ?>,
    priority: <?= json_encode($lead['priority']) ?>,
    lead_ref: <?= json_encode($lead['lead_ref']) ?>,
    city: <?= json_encode($lead['city'] ?? 'Purnea') ?>
};

document.addEventListener('DOMContentLoaded', function() {
    const tabEls = document.querySelectorAll('button[data-bs-toggle="pill"]');
    tabEls.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    });

    document.querySelectorAll('.modal').forEach(modalEl => {
        modalEl.addEventListener('shown.bs.modal', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    });

    // --- Document Preview & Zoom Handler ---
    const previewModalEl = document.getElementById('documentPreviewModal');
    if (!previewModalEl) return;

    let bsModal = null;
    function getPreviewModal() {
        if (!bsModal && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bsModal = bootstrap.Modal.getOrCreateInstance(previewModalEl);
        }
        return bsModal;
    }

    const titleEl = document.getElementById('docPreviewTitle');
    const imgEl = document.getElementById('docPreviewImage');
    const iframeEl = document.getElementById('docPreviewIframe');
    const imgContainer = document.getElementById('imagePreviewContainer');
    const pdfContainer = document.getElementById('pdfPreviewContainer');
    const downloadBtn = document.getElementById('docModalDownloadBtn');
    const newTabBtn = document.getElementById('docModalNewTabBtn');
    const zoomControls = document.getElementById('docZoomControlsGroup');
    const zoomIndicator = document.getElementById('zoomLevelIndicator');
    const btnZoomIn = document.getElementById('btnZoomIn');
    const btnZoomOut = document.getElementById('btnZoomOut');
    const btnReset = document.getElementById('btnResetZoom');
    const btnRotate = document.getElementById('btnRotateDoc');

    let currentZoom = 100;
    let currentRotation = 0;

    function applyTransform() {
        if (!imgEl) return;
        imgEl.style.transform = `scale(${currentZoom / 100}) rotate(${currentRotation}deg)`;
        if (zoomIndicator) zoomIndicator.textContent = `${currentZoom}%`;
    }

    btnZoomIn?.addEventListener('click', () => {
        if (currentZoom < 350) {
            currentZoom += 25;
            applyTransform();
        }
    });

    btnZoomOut?.addEventListener('click', () => {
        if (currentZoom > 50) {
            currentZoom -= 25;
            applyTransform();
        }
    });

    btnReset?.addEventListener('click', () => {
        currentZoom = 100;
        currentRotation = 0;
        applyTransform();
    });

    btnRotate?.addEventListener('click', () => {
        currentRotation = (currentRotation + 90) % 360;
        applyTransform();
    });

    // Mousewheel Zoom on Image
    imgContainer?.addEventListener('wheel', (e) => {
        e.preventDefault();
        if (e.deltaY < 0 && currentZoom < 350) {
            currentZoom += 15;
            applyTransform();
        } else if (e.deltaY > 0 && currentZoom > 50) {
            currentZoom -= 15;
            applyTransform();
        }
    }, { passive: false });

    // Open Modal on click of View & Zoom
    document.querySelectorAll('.btn-preview-doc').forEach(btn => {
        btn.addEventListener('click', () => {
            const docTitle = btn.getAttribute('data-doc-title') || 'Document Preview';
            const docUrl = btn.getAttribute('data-doc-url');
            const downloadUrl = btn.getAttribute('data-download-url');
            const isImg = btn.getAttribute('data-is-img') === '1';

            if (titleEl) titleEl.textContent = docTitle;
            if (downloadBtn) downloadBtn.setAttribute('href', downloadUrl);
            if (newTabBtn) newTabBtn.setAttribute('href', docUrl);

            // Reset zoom level
            currentZoom = 100;
            currentRotation = 0;

            if (isImg) {
                if (imgContainer) imgContainer.classList.remove('d-none');
                if (pdfContainer) pdfContainer.classList.add('d-none');
                if (zoomControls) zoomControls.classList.remove('d-none');
                if (imgEl) {
                    imgEl.src = docUrl;
                    applyTransform();
                }
            } else {
                // PDF or other documents
                if (imgContainer) imgContainer.classList.add('d-none');
                if (pdfContainer) pdfContainer.classList.remove('d-none');
                if (zoomControls) zoomControls.classList.add('d-none');
                if (iframeEl) iframeEl.src = docUrl;
            }

            const m = getPreviewModal();
            if (m) m.show();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    });

    // Cleanup resources when modal closes
    previewModalEl.addEventListener('hidden.bs.modal', () => {
        if (iframeEl) iframeEl.src = '';
        if (imgEl) imgEl.src = '';
    });
});
</script>
