<?php
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'My Counselor Workstation';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">Counselor Workstation</h3>
        <p class="text-muted small mb-0">Follow-up schedule, pending calls, and assigned student enquiries.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <a href="<?= View::url('leads/create') ?>" class="btn btn-navy shadow-sm">
            <i data-lucide="user-plus" class="me-1" style="width: 16px; height: 16px;"></i> Log New Walk-in / Call
        </a>
    </div>
</div>

<!-- Counselor Metrics -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted fw-semibold small">My Assigned Leads</span>
                <div class="metric-card-icon bg-navy-subtle text-navy">
                    <i data-lucide="users" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
            <div class="metric-val"><?= number_format($metrics['total_leads']) ?></div>
            <div class="small text-muted mt-1"><span class="text-navy fw-semibold"><?= $metrics['new_leads'] ?></span> new enquiries</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted fw-semibold small">Calls Due Today</span>
                <div class="metric-card-icon bg-primary-subtle text-primary">
                    <i data-lucide="phone-call" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
            <div class="metric-val text-primary"><?= number_format($metrics['followups_due_today']) ?></div>
            <div class="small text-muted mt-1">Scheduled for follow-up today</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted fw-semibold small">Overdue Tasks</span>
                <div class="metric-card-icon bg-danger-subtle text-danger">
                    <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
            <div class="metric-val text-danger"><?= number_format($metrics['overdue_tasks'] + $metrics['overdue_followups']) ?></div>
            <div class="small text-danger mt-1 fw-semibold">Requires immediate attention</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted fw-semibold small">Admissions Confirmed</span>
                <div class="metric-card-icon bg-success-subtle text-success">
                    <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
            <div class="metric-val text-success"><?= number_format($metrics['confirmed_admissions']) ?></div>
            <div class="small text-muted mt-1">Enquiries successfully enrolled</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Today's Action Items -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-navy mb-0 d-flex align-items-center">
                    <i data-lucide="clock" class="me-2 text-primary" style="width: 18px; height: 18px;"></i> Today's Follow-up Tasks
                </h6>
                <span class="badge bg-primary-subtle text-primary"><?= count($todayTasks) ?> tasks</span>
            </div>
            <div class="card-body p-3">
                <?php if (empty($todayTasks)): ?>
                    <div class="text-center text-muted py-4">
                        <i data-lucide="check-circle" class="text-success mb-2" style="width: 32px; height: 32px;"></i>
                        <p class="mb-0">No pending tasks scheduled for today. Great job!</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($todayTasks as $task): ?>
                        <div class="list-group-item px-0 py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold text-navy mb-1"><?= Sanitizer::e($task['title']) ?></h6>
                                    <div class="small text-muted mb-2">
                                        Student: <strong><?= Sanitizer::e($task['student_name']) ?></strong> &bull; 
                                        Parent: <?= Sanitizer::e($task['parent_name']) ?> &bull; 
                                        Mobile: <a href="tel:<?= Sanitizer::e($task['mobile']) ?>" class="text-decoration-none fw-semibold"><?= Sanitizer::e($task['mobile']) ?></a>
                                    </div>
                                    <?php if (!empty($task['description'])): ?>
                                        <div class="small bg-light p-2 rounded mb-2"><?= Sanitizer::e($task['description']) ?></div>
                                    <?php endif; ?>
                                    <span class="badge priority-badge-<?= $task['priority'] ?>"><?= $task['priority'] ?> Priority</span>
                                    <span class="badge bg-light text-secondary border"><?= $task['task_type'] ?></span>
                                </div>
                                <div>
                                    <a href="<?= View::url('leads/show&id=' . $task['lead_id']) ?>" class="btn btn-sm btn-navy">
                                        Open Lead &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- My Recent Leads -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-navy mb-0">My Active Enquiries</h6>
                <a href="<?= View::url('leads') ?>" class="small text-decoration-none text-navy fw-semibold">View All</a>
            </div>
            <div class="card-body p-3">
                <?php if (empty($recentLeads)): ?>
                    <div class="text-center text-muted py-4">No leads assigned yet.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentLeads as $lead): 
                            $statusSlug = str_replace([' ', '/', '–', '-'], '-', $lead['status']);
                        ?>
                        <a href="<?= View::url('leads/show&id=' . $lead['id']) ?>" class="list-group-item list-group-item-action px-0 py-2 border-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold text-navy"><?= Sanitizer::e($lead['student_name']) ?></div>
                                    <div class="small text-muted"><?= Sanitizer::e($lead['class_applied']) ?> &bull; <?= Sanitizer::e($lead['mobile']) ?></div>
                                </div>
                                <span class="badge badge-lead-status badge-status-<?= $statusSlug ?>">
                                    <?= Sanitizer::e($lead['status']) ?>
                                </span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
