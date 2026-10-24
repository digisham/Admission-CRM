<?php
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

if (!Auth::isAdmin()) {
    View::redirect(View::url('dashboard'));
    exit;
}

$pageTitle = 'Unassigned Leads Queue';
$totalQueue = !empty($pagination['total']) ? (int)$pagination['total'] : count($leads);
?>

<!-- Breadcrumb & Top Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0" style="font-size: 0.74rem;">
                <li class="breadcrumb-item"><a href="<?= View::url('leads') ?>" class="text-decoration-none text-muted">Admission Leads</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Unassigned Queue</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold text-dark mb-0" style="font-size: 1.35rem; letter-spacing: -0.3px;">
                Unassigned Leads Queue
            </h4>
            <span class="badge rounded-pill bg-warning-subtle text-dark border border-warning-subtle px-2.5 py-1" style="font-size: 0.74rem; font-weight: 600;">
                <i data-lucide="clock" style="width: 12px; height: 12px;" class="me-1 text-warning"></i>
                <span id="queueCountBadge"><?= $totalQueue ?></span> Enquiries Awaiting Staff
            </span>
        </div>
        <p class="text-muted small mb-0 mt-1">Review student admission enquiries that have not yet been allocated to an admission counselor.</p>
    </div>
    
    <div class="d-flex align-items-center gap-2">
        <a href="<?= View::url('leads') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 0.8rem; font-weight: 500;">
            <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i>
            <span>All Leads</span>
        </a>
        <a href="<?= View::url('leads/create') ?>" class="btn btn-navy btn-sm rounded-pill px-3.5 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 0.8rem; font-weight: 500;">
            <i data-lucide="user-plus" style="width: 14px; height: 14px;"></i>
            <span>Add New Enquiry</span>
        </a>
    </div>
</div>

<!-- Search & Class Filter Strip -->
<div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
    <div class="card-body p-2.5">
        <form action="<?= View::url('leads/unassigned') ?>" method="GET" class="row g-2 align-items-center m-0">
            <input type="hidden" name="route" value="leads/unassigned">

            <!-- Search Field -->
            <div class="col-md-5 col-lg-4">
                <div class="position-relative">
                    <i data-lucide="search" class="position-absolute text-muted" style="width: 14px; height: 14px; top: 10px; left: 11px;"></i>
                    <input type="text" 
                           name="search" 
                           value="<?= Sanitizer::e($filters['search'] ?? '') ?>" 
                           class="form-control form-control-sm ps-5 rounded-pill" 
                           placeholder="Search student, parent, phone, or ref..." 
                           style="height: 34px; font-size: 0.8rem;">
                </div>
            </div>

            <!-- Class Filter -->
            <div class="col-md-4 col-lg-3">
                <select name="class_applied" class="form-select form-select-sm rounded-pill" style="height: 34px; font-size: 0.8rem;">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $cl): ?>
                        <option value="<?= $cl ?>" <?= ($filters['class_applied'] ?? '') === $cl ? 'selected' : '' ?>>
                            <?= Sanitizer::e($cl) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Submit & Reset -->
            <div class="col-md-3 col-lg-5 d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-sm btn-navy rounded-pill px-3" style="font-size: 0.78rem; height: 34px;">
                    <i data-lucide="filter" style="width: 12px; height: 12px;" class="me-1"></i> Apply
                </button>
                <?php if (!empty($filters['search']) || !empty($filters['class_applied'])): ?>
                    <a href="<?= View::url('leads/unassigned') ?>" class="btn btn-sm btn-light border rounded-pill px-2.5 text-muted" style="font-size: 0.78rem; height: 34px;">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Selection & Action Bar -->
<div class="card border-0 shadow-sm rounded-3 bg-white mb-3" style="border-left: 4px solid var(--gdg-primary, #1859F2) !important;">
    <div class="card-body py-2.5 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2.5">
        <div class="d-flex align-items-center gap-3">
            <div class="form-check m-0 d-flex align-items-center gap-2">
                <input type="checkbox" class="form-check-input cursor-pointer" id="masterSelectAll" style="width: 17px; height: 17px;">
                <label class="form-check-label small fw-semibold text-dark cursor-pointer user-select-none" for="masterSelectAll">
                    Select All in Queue
                </label>
            </div>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1" id="selectionCountBadge" style="font-size: 0.75rem;">
                <span id="selectedCountNumber">0</span> selected
            </span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" 
                    id="openBulkAssignBtn" 
                    class="btn btn-sm btn-navy rounded-pill px-3.5 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-sm"
                    style="font-size: 0.8rem; font-weight: 600;">
                <i data-lucide="user-check" style="width: 14px; height: 14px;"></i>
                <span>Allocate Selected to Staff</span>
            </button>
        </div>
    </div>
</div>

<!-- Queue Table Card -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="unassignedTable" style="font-size: 0.8rem;">
            <thead class="table-light">
                <tr class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em;">
                    <th style="width: 38px;" class="ps-3 py-3 text-center">
                        <span class="visually-hidden">Select</span>
                    </th>
                    <th class="py-3">Lead Ref #</th>
                    <th class="py-3">Student & Parent</th>
                    <th class="py-3">Class</th>
                    <th class="py-3">Contact Mobile</th>
                    <th class="py-3">Source & Date</th>
                    <th class="pe-3 py-3 text-end" style="min-width: 170px;">Assign to Staff</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="bento-avatar avatar-blue mx-auto mb-2.5" style="width: 48px; height: 48px; border-radius: 14px;">
                                <i data-lucide="check-check" style="width: 24px; height: 24px;"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">Unassigned Queue is Empty</h6>
                            <p class="text-muted small mb-0">All student admission enquiries are currently assigned to counselors.</p>
                            <a href="<?= View::url('leads') ?>" class="btn btn-sm btn-outline-secondary rounded-pill mt-3 px-3">
                                View All Leads
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leads as $lead): ?>
                    <tr class="queue-row" data-lead-id="<?= (int)$lead['id'] ?>" style="cursor: pointer;">
                        <!-- Row Checkbox -->
                        <td class="ps-3 py-2.5 text-center" onclick="event.stopPropagation();">
                            <input type="checkbox" 
                                   class="form-check-input lead-select-checkbox cursor-pointer" 
                                   value="<?= (int)$lead['id'] ?>" 
                                   data-student-name="<?= Sanitizer::e($lead['student_name']) ?>"
                                   data-lead-ref="<?= Sanitizer::e($lead['lead_ref']) ?>"
                                   style="width: 16px; height: 16px;">
                        </td>

                        <!-- Ref # -->
                        <td class="py-2.5" onclick="event.stopPropagation();">
                            <a href="<?= View::url('leads/show&id=' . $lead['id']) ?>" class="fw-bold text-navy text-decoration-none">
                                <?= Sanitizer::e($lead['lead_ref']) ?>
                            </a>
                            <div>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill" style="font-size: 0.65rem;">
                                    Unassigned
                                </span>
                            </div>
                        </td>

                        <!-- Student & Parent -->
                        <td class="py-2.5">
                            <div class="fw-bold text-dark"><?= Sanitizer::e($lead['student_name']) ?></div>
                            <div class="text-muted small" style="font-size: 0.73rem;">
                                Parent: <?= Sanitizer::e($lead['parent_name'] ?? '—') ?>
                            </div>
                        </td>

                        <!-- Class Applied -->
                        <td class="py-2.5">
                            <span class="badge bg-light text-dark border px-2 py-1 font-monospace" style="font-size: 0.72rem;">
                                <?= Sanitizer::e($lead['class_applied'] ?? '—') ?>
                            </span>
                        </td>

                        <!-- Mobile Contact -->
                        <td class="py-2.5" onclick="event.stopPropagation();">
                            <div class="d-flex align-items-center gap-1.5">
                                <i data-lucide="phone" style="width: 12px; height: 12px;" class="text-muted"></i>
                                <a href="tel:<?= Sanitizer::e($lead['mobile']) ?>" class="font-monospace text-dark text-decoration-none fw-semibold">
                                    <?= Sanitizer::e($lead['mobile']) ?>
                                </a>
                            </div>
                            <?php if (!empty($lead['alternate_mobile'])): ?>
                                <div class="text-muted font-monospace small" style="font-size: 0.68rem;">
                                    Alt: <?= Sanitizer::e($lead['alternate_mobile']) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Source & Date -->
                        <td class="py-2.5">
                            <div class="text-dark small fw-medium"><?= Sanitizer::e($lead['enquiry_source'] ?? 'Direct Visit') ?></div>
                            <div class="text-muted small" style="font-size: 0.7rem;">
                                <?= !empty($lead['enquiry_date']) ? date('d M Y', strtotime($lead['enquiry_date'])) : '—' ?>
                            </div>
                        </td>

                        <!-- Action: Quick Single Assign & Details -->
                        <td class="pe-3 py-2.5 text-end" onclick="event.stopPropagation();">
                            <div class="d-inline-flex align-items-center gap-1.5">
                                <button type="button" 
                                        class="btn btn-sm btn-navy rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1 btn-quick-assign"
                                        data-lead-id="<?= (int)$lead['id'] ?>"
                                        data-lead-ref="<?= Sanitizer::e($lead['lead_ref']) ?>"
                                        data-student-name="<?= Sanitizer::e($lead['student_name']) ?>"
                                        style="font-size: 0.75rem; font-weight: 600;"
                                        title="Assign to staff counselor">
                                    <i data-lucide="user-plus" style="width: 13px; height: 13px;"></i>
                                    <span>Assign Staff</span>
                                </button>
                                
                                <a href="<?= View::url('leads/show&id=' . $lead['id']) ?>" 
                                   class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-muted"
                                   style="font-size: 0.75rem;" 
                                   title="View full enquiry details">
                                    <i data-lucide="external-link" style="width: 13px; height: 13px;"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <?php if (!empty($pagination) && ($pagination['pages'] ?? 1) > 1): ?>
        <div class="card-footer bg-white border-top py-2.5 px-3 d-flex align-items-center justify-content-between small text-muted">
            <div>
                Showing page <?= $pagination['page'] ?> of <?= $pagination['pages'] ?> (<?= $pagination['total'] ?> total)
            </div>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($p = 1; $p <= $pagination['pages']; $p++): ?>
                    <li class="page-item <?= $p === (int)$pagination['page'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= View::url('leads/unassigned&page=' . $p . (!empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '') . (!empty($filters['class_applied']) ? '&class_applied=' . urlencode($filters['class_applied']) : '')) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- ==========================================
     MODAL 1: BULK ALLOCATE SELECTED TO COUNSELOR
     ========================================== -->
<div class="modal fade" id="bulkAssignModal" tabindex="-1" aria-labelledby="bulkAssignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form action="<?= View::url('leads/bulk-assign') ?>" method="POST" id="bulkAssignForm">
                <?= Csrf::field() ?>
                <input type="hidden" name="redirect_to" value="<?= View::url('leads/unassigned') ?>">
                <!-- Hidden Container for Selected Lead IDs -->
                <div id="bulkSelectedInputsContainer"></div>

                <div class="modal-header border-bottom py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bento-avatar avatar-blue" style="width: 32px; height: 32px; border-radius: 8px;">
                            <i data-lucide="users" style="width: 16px; height: 16px;"></i>
                        </div>
                        <h5 class="modal-title fw-bold text-dark fs-6" id="bulkAssignModalLabel">Allocate Selected Enquiries</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Selection Summary Alert -->
                    <div class="p-2.5 rounded-3 bg-light border mb-3 d-flex align-items-center justify-content-between text-dark" style="font-size: 0.8rem;">
                        <span class="text-muted">Total Enquiries to Allocate:</span>
                        <span class="badge bg-primary text-white rounded-pill px-2.5 py-1" id="modalBatchCountBadge">0 leads</span>
                    </div>

                    <!-- Staff Counselor Selection Dropdown -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small mb-1" for="bulk_assigned_to">
                            Select Staff Counselor <span class="text-danger">*</span>
                        </label>
                        <select name="assigned_to" id="bulk_assigned_to" class="form-select form-select-sm" required style="height: 38px; font-size: 0.84rem; border-radius: 8px;">
                            <option value="auto" selected>⚡ Auto Distribute (Divide Equally to All Admission Counselors)</option>
                            <option value="">-- Or Select Specific Staff Member --</option>
                            <?php foreach ($counselors as $c): ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= Sanitizer::e($c['name']) ?> &mdash; <?= Sanitizer::e(!empty($c['designation']) ? $c['designation'] : ucfirst($c['role_slug'] ?? 'Staff')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Allocation Remarks -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small mb-1" for="bulk_reason">
                            Assignment Reason / Notes
                        </label>
                        <input type="text" 
                               name="reason" 
                               id="bulk_reason" 
                               class="form-control form-control-sm" 
                               value="Allocated from unassigned queue"
                               placeholder="e.g. Queue assignment by coordinator"
                               style="height: 38px; font-size: 0.84rem; border-radius: 8px;">
                    </div>

                    <!-- Optional Task Generation Checkbox -->
                    <div class="form-check p-2 rounded-2 bg-light border mt-2">
                        <input class="form-check-input ms-1" type="checkbox" name="create_task" value="1" id="bulkCreateTaskCheck" checked>
                        <label class="form-check-label small ms-2 text-dark fw-medium" for="bulkCreateTaskCheck">
                            Auto-create follow-up task for assigned staff
                        </label>
                    </div>
                    <input type="hidden" name="task_title" value="Initial Admission Enquiry Follow-up Call">
                </div>

                <div class="modal-footer border-top py-2.5 px-4 bg-light bg-opacity-50">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm btn-navy rounded-pill px-4 d-inline-flex align-items-center gap-1.5 shadow-sm">
                        <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                        <span>Confirm Allocation</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL 2: QUICK SINGLE LEAD ASSIGN MODAL
     ========================================== -->
<div class="modal fade" id="quickSingleAssignModal" tabindex="-1" aria-labelledby="quickSingleAssignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form action="<?= View::url('leads/assign') ?>" method="POST" id="quickSingleAssignForm">
                <?= Csrf::field() ?>
                <input type="hidden" name="lead_id" id="singleLeadIdInput" value="">
                <input type="hidden" name="return_url" value="<?= View::url('leads/unassigned') ?>">

                <div class="modal-header border-bottom py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bento-avatar avatar-blue" style="width: 32px; height: 32px; border-radius: 8px;">
                            <i data-lucide="user-plus" style="width: 16px; height: 16px;"></i>
                        </div>
                        <h5 class="modal-title fw-bold text-dark fs-6" id="quickSingleAssignModalLabel">Assign Lead to Staff</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Student Summary Card -->
                    <div class="p-3 rounded-3 bg-light border mb-3">
                        <div class="text-muted small" style="font-size: 0.72rem;">Admission Enquiry:</div>
                        <div class="fw-bold text-dark" id="singleStudentNameDisplay" style="font-size: 1rem;">Student Name</div>
                        <div class="text-navy small font-monospace mt-0.5" id="singleLeadRefDisplay">GDG-XXXX</div>
                    </div>

                    <!-- Staff Counselor Selection Dropdown -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small mb-1" for="single_assigned_to">
                            Select Staff Member <span class="text-danger">*</span>
                        </label>
                        <select name="assigned_to" id="single_assigned_to" class="form-select form-select-sm" required style="height: 38px; font-size: 0.84rem; border-radius: 8px;">
                            <option value="">-- Choose Active Counselor / Staff --</option>
                            <?php foreach ($counselors as $c): ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= Sanitizer::e($c['name']) ?> &mdash; <?= Sanitizer::e(!empty($c['designation']) ? $c['designation'] : ucfirst($c['role_slug'] ?? 'Staff')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Reason / Remarks -->
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark small mb-1" for="single_reason">
                            Assignment Note
                        </label>
                        <input type="text" 
                               name="reason" 
                               id="single_reason" 
                               class="form-control form-control-sm" 
                               value="Assigned from unassigned queue"
                               style="height: 38px; font-size: 0.84rem; border-radius: 8px;">
                    </div>
                </div>

                <div class="modal-footer border-top py-2.5 px-4 bg-light bg-opacity-50">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm btn-navy rounded-pill px-4 d-inline-flex align-items-center gap-1.5 shadow-sm">
                        <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                        <span>Assign Now</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
     INTERACTIVE JAVASCRIPT LOGIC
     ========================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const masterCheckbox = document.getElementById('masterSelectAll');
    const rowCheckboxes = document.querySelectorAll('.lead-select-checkbox');
    const selectedCountNumber = document.getElementById('selectedCountNumber');
    const openBulkAssignBtn = document.getElementById('openBulkAssignBtn');
    const bulkModalEl = document.getElementById('bulkAssignModal');
    const modalBatchCountBadge = document.getElementById('modalBatchCountBadge');
    const bulkInputsContainer = document.getElementById('bulkSelectedInputsContainer');

    function updateSelectionState() {
        const checkedList = document.querySelectorAll('.lead-select-checkbox:checked');
        const count = checkedList.length;
        const total = rowCheckboxes.length;

        // Update badge text
        if (selectedCountNumber) {
            selectedCountNumber.textContent = count;
        }

        // Update master checkbox state
        if (masterCheckbox) {
            if (count === 0) {
                masterCheckbox.checked = false;
                masterCheckbox.indeterminate = false;
            } else if (count === total) {
                masterCheckbox.checked = true;
                masterCheckbox.indeterminate = false;
            } else {
                masterCheckbox.checked = false;
                masterCheckbox.indeterminate = true;
            }
        }

        // Highlight selected rows
        rowCheckboxes.forEach(cb => {
            const row = cb.closest('tr');
            if (row) {
                if (cb.checked) {
                    row.classList.add('table-primary');
                } else {
                    row.classList.remove('table-primary');
                }
            }
        });
    }

    // Master select all toggle
    if (masterCheckbox) {
        masterCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(cb => {
                cb.checked = masterCheckbox.checked;
            });
            updateSelectionState();
        });
    }

    // Individual checkbox change
    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectionState);
    });

    // Row click toggle
    document.querySelectorAll('.queue-row').forEach(row => {
        row.addEventListener('click', function(e) {
            // Ignore if clicked on an anchor, button, input or select
            if (e.target.closest('a, button, input, select')) return;
            const cb = row.querySelector('.lead-select-checkbox');
            if (cb) {
                cb.checked = !cb.checked;
                updateSelectionState();
            }
        });
    });

    // Bulk allocate button click handler
    if (openBulkAssignBtn) {
        openBulkAssignBtn.addEventListener('click', function() {
            const checkedList = document.querySelectorAll('.lead-select-checkbox:checked');
            if (checkedList.length === 0) {
                alert('Please select at least one lead from the list first by checking the checkboxes.');
                return;
            }

            // Populate hidden inputs in bulk modal
            if (bulkInputsContainer) {
                bulkInputsContainer.innerHTML = '';
                checkedList.forEach(cb => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'lead_ids[]';
                    hidden.value = cb.value;
                    bulkInputsContainer.appendChild(hidden);
                });
            }

            // Update modal badge
            if (modalBatchCountBadge) {
                modalBatchCountBadge.textContent = checkedList.length + ' leads selected';
            }

            // Open Bootstrap Modal
            const modal = bootstrap.Modal.getOrCreateInstance(bulkModalEl);
            modal.show();
        });
    }

    // Quick single assign button handler
    const quickAssignModalEl = document.getElementById('quickSingleAssignModal');

    document.querySelectorAll('.btn-quick-assign').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const leadId = this.getAttribute('data-lead-id');
            const leadRef = this.getAttribute('data-lead-ref');
            const studentName = this.getAttribute('data-student-name');

            document.getElementById('singleLeadIdInput').value = leadId;
            document.getElementById('singleStudentNameDisplay').textContent = studentName;
            document.getElementById('singleLeadRefDisplay').textContent = leadRef;

            if (quickAssignModalEl && typeof bootstrap !== 'undefined') {
                const quickModal = bootstrap.Modal.getOrCreateInstance(quickAssignModalEl);
                quickModal.show();
            }
        });
    });

    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
