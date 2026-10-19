<?php
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Executive Admission Dashboard';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">Executive Admission Dashboard</h3>
        <p class="text-muted small mb-0">GD Goenka Public School, Purnea &bull; Real-time Session Overview</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <a href="<?= View::url('leads/create') ?>" class="btn btn-navy shadow-sm me-2">
            <i data-lucide="user-plus" class="me-1" style="width: 16px; height: 16px;"></i> Add New Enquiry
        </a>
        <a href="<?= View::url('import') ?>" class="btn btn-outline-secondary shadow-sm">
            <i data-lucide="file-spreadsheet" class="me-1" style="width: 16px; height: 16px;"></i> Import Excel
        </a>
    </div>
</div>

<!-- Primary Metrics -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted fw-semibold small">Total Enquiries</span>
                <div class="metric-card-icon bg-navy-subtle text-navy">
                    <i data-lucide="users" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
            <div class="metric-val"><?= number_format($metrics['total_leads']) ?></div>
            <div class="small text-muted mt-1"><span class="text-navy fw-semibold"><?= $metrics['new_leads'] ?></span> new leads</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted fw-semibold small">Unassigned Leads</span>
                <div class="metric-card-icon bg-gold-subtle text-gold">
                    <i data-lucide="user-x" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
            <div class="metric-val text-gold"><?= number_format($metrics['unassigned_leads']) ?></div>
            <div class="small mt-1">
                <a href="<?= View::url('leads/unassigned') ?>" class="text-decoration-none fw-semibold text-navy">View unassigned queue &rarr;</a>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted fw-semibold small">Confirmed Admissions</span>
                <div class="metric-card-icon bg-success-subtle text-success">
                    <i data-lucide="graduation-cap" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
            <div class="metric-val text-success"><?= number_format($metrics['confirmed_admissions']) ?></div>
            <div class="small text-muted mt-1">Conversion: <strong class="text-dark"><?= $metrics['conversion_rate'] ?>%</strong> (confirmed / total)</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted fw-semibold small">Overdue Attention</span>
                <div class="metric-card-icon bg-danger-subtle text-danger">
                    <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
            <div class="metric-val text-danger"><?= number_format($metrics['overdue_followups'] + $metrics['overdue_tasks']) ?></div>
            <div class="small text-danger mt-1 fw-semibold"><?= $metrics['overdue_followups'] ?> calls overdue, <?= $metrics['overdue_tasks'] ?> tasks overdue</div>
        </div>
    </div>
</div>

<!-- Secondary Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-primary-subtle text-primary p-3 rounded-3 me-3">
                    <i data-lucide="phone-forwarded" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Calls Due Today</div>
                    <h4 class="fw-bold mb-0 text-navy"><?= $metrics['followups_due_today'] ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-info-subtle text-info p-3 rounded-3 me-3">
                    <i data-lucide="calendar-days" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Campus Visits Scheduled</div>
                    <h4 class="fw-bold mb-0 text-navy"><?= $metrics['school_visits'] ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-gold-subtle text-gold p-3 rounded-3 me-3">
                    <i data-lucide="trending-up" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Admission Conversion Rate</div>
                    <h4 class="fw-bold mb-0 text-navy"><?= $metrics['conversion_rate'] ?>%</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Middle Section: Workload & Status Breakdown -->
<div class="row g-4 mb-4">
    <!-- Counselor Workload -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-navy mb-0">Admission Counselors & Workload</h6>
                <a href="<?= View::url('users') ?>" class="small text-decoration-none text-navy fw-semibold">Manage Staff &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table custom-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Counselor</th>
                            <th class="text-center">Assigned</th>
                            <th class="text-center">Active</th>
                            <th class="text-center">Confirmed</th>
                            <th class="text-center">Pending Tasks</th>
                            <th class="text-center">Overdue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($workload)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No active counselors found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($workload as $w): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-navy"><?= Sanitizer::e($w['name']) ?></div>
                                    <div class="small text-muted"><?= Sanitizer::e($w['designation'] ?: $w['role_name']) ?></div>
                                </td>
                                <td class="text-center fw-bold"><?= $w['total_leads'] ?></td>
                                <td class="text-center"><span class="badge bg-navy-subtle text-navy"><?= $w['active_leads'] ?></span></td>
                                <td class="text-center"><span class="badge bg-success-subtle text-success"><?= $w['confirmed_leads'] ?></span></td>
                                <td class="text-center"><?= $w['pending_tasks'] ?></td>
                                <td class="text-center">
                                    <?php if ($w['overdue_tasks'] > 0): ?>
                                        <span class="badge bg-danger"><?= $w['overdue_tasks'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
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

    <!-- Status Breakdown -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold text-navy mb-0">Enquiries by Lead Pipeline Stage</h6>
            </div>
            <div class="card-body p-3">
                <?php if (empty($metrics['leads_by_status'])): ?>
                    <div class="text-center text-muted py-4">No enquiry records for this session yet.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($metrics['leads_by_status'] as $s): 
                            $pct = $metrics['total_leads'] > 0 ? round(($s['count'] / $metrics['total_leads']) * 100) : 0;
                            $statusSlug = str_replace([' ', '/', '–', '-'], '-', $s['status']);
                        ?>
                        <div class="list-group-item px-0 py-2 border-0">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge badge-lead-status badge-status-<?= $statusSlug ?>">
                                    <?= Sanitizer::e($s['status']) ?>
                                </span>
                                <span class="fw-bold small"><?= $s['count'] ?> <span class="text-muted fw-normal">(<?= $pct ?>%)</span></span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-navy" role="progressbar" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Section: Recent Leads & Overdue Tasks -->
<div class="row g-4">
    <!-- Recent Enquiries -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-navy mb-0">Recent Admission Enquiries</h6>
                <a href="<?= View::url('leads') ?>" class="small text-decoration-none text-navy fw-semibold">View All Leads &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table custom-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Student & Parent</th>
                            <th>Class</th>
                            <th>Mobile</th>
                            <th>Counselor</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentLeads)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No enquiries found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentLeads as $lead): 
                                $statusSlug = str_replace([' ', '/', '–', '-'], '-', $lead['status']);
                            ?>
                            <tr>
                                <td class="fw-bold text-navy"><?= Sanitizer::e($lead['lead_ref']) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= Sanitizer::e($lead['student_name']) ?></div>
                                    <div class="small text-muted"><?= Sanitizer::e($lead['parent_name']) ?></div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= Sanitizer::e($lead['class_applied']) ?></span></td>
                                <td><?= Sanitizer::e($lead['mobile']) ?></td>
                                <td>
                                    <?= $lead['assigned_name'] ? Sanitizer::e($lead['assigned_name']) : '<span class="badge bg-gold-subtle text-gold">Unassigned</span>' ?>
                                </td>
                                <td>
                                    <span class="badge badge-lead-status badge-status-<?= $statusSlug ?>">
                                        <?= Sanitizer::e($lead['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= View::url('leads/show&id=' . $lead['id']) ?>" class="btn btn-sm btn-outline-navy py-1 px-2">
                                        View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Overdue Tasks Warning Box -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-danger mb-0 d-flex align-items-center">
                    <i data-lucide="alert-triangle" class="me-1" style="width: 16px; height: 16px;"></i> Overdue Follow-up Tasks
                </h6>
                <a href="<?= View::url('tasks&due_filter=overdue') ?>" class="small text-decoration-none text-danger fw-semibold">View All</a>
            </div>
            <div class="card-body p-3">
                <?php if (empty($overdueTasks)): ?>
                    <div class="text-center text-muted py-4">
                        <i data-lucide="check-circle" class="text-success mb-2" style="width: 32px; height: 32px;"></i>
                        <div class="small">Great job! No pending tasks are currently overdue.</div>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($overdueTasks, 0, 5) as $ot): ?>
                        <div class="list-group-item px-0 py-2 border-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold text-danger small"><?= Sanitizer::e($ot['title']) ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        Lead: <strong><?= Sanitizer::e($ot['student_name']) ?></strong> (<?= Sanitizer::e($ot['assignee_name']) ?>)
                                    </div>
                                    <div class="text-danger fw-semibold" style="font-size: 0.72rem;">
                                        Due: <?= date('d M Y, h:i A', strtotime($ot['due_date'])) ?>
                                    </div>
                                </div>
                                <a href="<?= View::url('leads/show&id=' . $ot['lead_id']) ?>" class="btn btn-sm btn-light border py-0 px-2" style="font-size: 0.75rem;">
                                    Act
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
