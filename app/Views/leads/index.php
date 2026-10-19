<?php
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Admission Enquiries & Leads';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h3 class="fw-bold text-navy mb-1">Admission Enquiries</h3>
        <p class="text-muted small mb-0">Total matching enquiries: <strong><?= number_format($pagination['total']) ?></strong></p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <a href="<?= View::url('export/leads?' . http_build_query($filters)) ?>" class="btn btn-outline-secondary me-2 shadow-sm">
            <i data-lucide="download" class="me-1" style="width: 16px; height: 16px;"></i> Export CSV
        </a>
        <a href="<?= View::url('leads/create') ?>" class="btn btn-navy shadow-sm">
            <i data-lucide="user-plus" class="me-1" style="width: 16px; height: 16px;"></i> Add New Enquiry
        </a>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?= View::url('leads') ?>" method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="leads">

            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Search Keywords</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Student, parent, phone, ref..." value="<?= Sanitizer::e($filters['search'] ?? '') ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $st): ?>
                        <option value="<?= Sanitizer::e($st) ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>><?= Sanitizer::e($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Class / Grade</label>
                <select name="class_applied" class="form-select form-select-sm">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $cl): ?>
                        <option value="<?= Sanitizer::e($cl) ?>" <?= ($filters['class_applied'] ?? '') === $cl ? 'selected' : '' ?>><?= Sanitizer::e($cl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (Auth::isAdmin()): ?>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Assigned Counselor</label>
                <select name="assigned_to" class="form-select form-select-sm">
                    <option value="">All Counselors</option>
                    <?php foreach ($counselors as $cn): ?>
                        <option value="<?= $cn['id'] ?>" <?= ($filters['assigned_to'] ?? '') == $cn['id'] ? 'selected' : '' ?>><?= Sanitizer::e($cn['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Follow-up Due</label>
                <select name="followup_due" class="form-select form-select-sm">
                    <option value="">Any Time</option>
                    <option value="today" <?= ($filters['followup_due'] ?? '') === 'today' ? 'selected' : '' ?>>Due Today</option>
                    <option value="overdue" <?= ($filters['followup_due'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                    <option value="upcoming" <?= ($filters['followup_due'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                </select>
            </div>

            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-navy w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Action Bar -->
<form action="<?= View::url('leads/bulk-assign') ?>" method="POST" id="bulkAssignForm">
    <?= Csrf::field() ?>

    <div class="alert alert-secondary d-none d-flex justify-content-between align-items-center mb-3 shadow-sm py-2" id="bulkActionsBar">
        <div>
            <i data-lucide="check-square" class="me-1 text-navy" style="width: 18px; height: 18px;"></i>
            <strong><span id="selectedLeadsCount">0</span></strong> enquiry row(s) selected
        </div>
        <div class="d-flex align-items-center">
            <?php if (Auth::isAdmin()): ?>
            <button type="button" class="btn btn-sm btn-navy me-2" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
                <i data-lucide="user-check" class="me-1" style="width: 15px; height: 15px;"></i> Bulk Assign Selected
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Leads Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table custom-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" class="form-check-input" id="selectAllLeads">
                        </th>
                        <th>Ref #</th>
                        <th>Student & Parent</th>
                        <th>Class</th>
                        <th>Mobile</th>
                        <th>Source</th>
                        <th>Counselor</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Next Follow-up</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)): ?>
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i data-lucide="inbox" class="mb-2 text-muted" style="width: 40px; height: 40px;"></i>
                                <div class="fw-semibold">No admission enquiries found</div>
                                <div class="small">Try adjusting search filters or add a new enquiry.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leads as $lead): 
                            $statusSlug = str_replace([' ', '/', '–', '-'], '-', $lead['status']);
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="lead_ids[]" value="<?= $lead['id'] ?>" class="form-check-input lead-checkbox">
                            </td>
                            <td class="fw-bold text-navy">
                                <a href="<?= View::url('leads/show&id=' . $lead['id']) ?>" class="text-navy text-decoration-none">
                                    <?= Sanitizer::e($lead['lead_ref']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= Sanitizer::e($lead['student_name']) ?></div>
                                <div class="small text-muted"><?= Sanitizer::e($lead['parent_name']) ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= Sanitizer::e($lead['class_applied']) ?></span></td>
                            <td>
                                <a href="tel:<?= Sanitizer::e($lead['mobile']) ?>" class="text-decoration-none text-dark">
                                    <?= Sanitizer::e($lead['mobile']) ?>
                                </a>
                            </td>
                            <td><span class="small text-muted"><?= Sanitizer::e($lead['enquiry_source']) ?></span></td>
                            <td>
                                <?= $lead['assigned_name'] ? Sanitizer::e($lead['assigned_name']) : '<span class="badge bg-gold-subtle text-gold">Unassigned</span>' ?>
                            </td>
                            <td>
                                <span class="badge badge-lead-status badge-status-<?= $statusSlug ?>">
                                    <?= Sanitizer::e($lead['status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge priority-badge-<?= $lead['priority'] ?>">
                                    <?= $lead['priority'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($lead['next_followup_date']): ?>
                                    <?php 
                                        $isOverdue = strtotime($lead['next_followup_date']) < time() && !in_array($lead['status'], ['Admission Confirmed', 'Lost / Unreachable']);
                                    ?>
                                    <span class="small <?= $isOverdue ? 'text-danger fw-bold' : 'text-muted' ?>">
                                        <?= date('d M Y, h:i A', strtotime($lead['next_followup_date'])) ?>
                                        <?php if ($isOverdue): ?>
                                            <i data-lucide="alert-circle" style="width: 14px; height: 14px;"></i>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= View::url('leads/show&id=' . $lead['id']) ?>" class="btn btn-sm btn-outline-navy py-1 px-2" title="View Details">
                                    View
                                </a>
                                <a href="<?= View::url('leads/edit&id=' . $lead['id']) ?>" class="btn btn-sm btn-light border py-1 px-2" title="Edit Lead">
                                    Edit
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Server-side Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                Showing page <strong><?= $pagination['page'] ?></strong> of <strong><?= $pagination['total_pages'] ?></strong>
            </div>
            <nav aria-label="Leads pagination">
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($pagination['page'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= View::url('leads?' . http_build_query(array_merge($filters, ['page' => $pagination['page'] - 1]))) ?>">&laquo; Prev</a>
                    </li>
                    <?php endif; ?>

                    <?php for ($p = max(1, $pagination['page'] - 2); $p <= min($pagination['total_pages'], $pagination['page'] + 2); $p++): ?>
                    <li class="page-item <?= $p === $pagination['page'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= View::url('leads?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= View::url('leads?' . http_build_query(array_merge($filters, ['page' => $pagination['page'] + 1]))) ?>">Next &raquo;</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bulk Assign Modal -->
    <div class="modal fade" id="bulkAssignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-navy">Bulk Assign Enquiries</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Assign to Counselor *</label>
                        <select name="assigned_to" class="form-select" required>
                            <option value="">-- Choose Active Counselor --</option>
                            <?php foreach ($counselors as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= Sanitizer::e($c['name']) ?> (<?= Sanitizer::e($c['designation'] ?: 'Counselor') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Assignment Reason / Remarks</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Session 2027-28 walk-in batch allocation" value="Bulk allocation by admin">
                    </div>

                    <hr>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="create_task" value="1" id="bulkCreateTaskCheck">
                        <label class="form-check-label small fw-semibold" for="bulkCreateTaskCheck">
                            Automatically create initial follow-up task for each lead
                        </label>
                    </div>

                    <div id="bulkTaskFields" class="d-none">
                        <div class="mb-2">
                            <label class="form-label small">Task Title</label>
                            <input type="text" name="task_title" class="form-control form-control-sm" value="Initial Admission Enquiry Call">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Due Date & Time</label>
                            <input type="datetime-local" name="task_due_date" class="form-control form-control-sm" value="<?= date('Y-m-d\TH:i', strtotime('+1 day 11:00')) ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy">Confirm Bulk Assignment</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('bulkCreateTaskCheck')?.addEventListener('change', function() {
    const f = document.getElementById('bulkTaskFields');
    if (this.checked) f.classList.remove('d-none');
    else f.classList.add('d-none');
});
</script>
