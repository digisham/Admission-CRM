<?php
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Unassigned Leads Queue';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">Unassigned Leads Queue</h3>
        <p class="text-muted small mb-0">Enquiries awaiting assignment to an admission counselor.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <a href="<?= View::url('leads') ?>" class="btn btn-outline-secondary me-2 shadow-sm">
            &larr; All Leads
        </a>
        <a href="<?= View::url('leads/create') ?>" class="btn btn-navy shadow-sm">
            <i data-lucide="user-plus" class="me-1" style="width: 16px; height: 16px;"></i> Add New Enquiry
        </a>
    </div>
</div>

<form action="<?= View::url('leads/bulk-assign') ?>" method="POST" id="unassignedBulkForm">
    <?= Csrf::field() ?>

    <div class="alert alert-gold-subtle text-dark border-0 shadow-sm d-flex justify-content-between align-items-center mb-3 p-3">
        <div>
            <i data-lucide="info" class="text-gold me-2" style="width: 20px; height: 20px;"></i>
            Select one or more unassigned leads to allocate them to active counselors immediately.
        </div>
        <div>
            <button type="button" class="btn btn-sm btn-navy" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
                <i data-lucide="user-check" class="me-1" style="width: 15px; height: 15px;"></i> Allocate Selected
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table custom-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" class="form-check-input" id="selectAllLeads">
                        </th>
                        <th>Ref #</th>
                        <th>Student Name</th>
                        <th>Parent Name</th>
                        <th>Class</th>
                        <th>Mobile</th>
                        <th>Source</th>
                        <th>Enquiry Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i data-lucide="check-circle" class="text-success mb-2" style="width: 40px; height: 40px;"></i>
                                <div class="fw-semibold">All enquiries have been assigned!</div>
                                <div class="small">The unassigned queue is currently clear.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="lead_ids[]" value="<?= $lead['id'] ?>" class="form-check-input lead-checkbox">
                            </td>
                            <td class="fw-bold text-navy">
                                <a href="<?= View::url('leads/show&id=' . $lead['id']) ?>" class="text-navy text-decoration-none">
                                    <?= Sanitizer::e($lead['lead_ref']) ?>
                                </a>
                            </td>
                            <td class="fw-semibold"><?= Sanitizer::e($lead['student_name']) ?></td>
                            <td><?= Sanitizer::e($lead['parent_name']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= Sanitizer::e($lead['class_applied']) ?></span></td>
                            <td>
                                <a href="tel:<?= Sanitizer::e($lead['mobile']) ?>" class="text-decoration-none text-dark">
                                    <?= Sanitizer::e($lead['mobile']) ?>
                                </a>
                            </td>
                            <td><?= Sanitizer::e($lead['enquiry_source']) ?></td>
                            <td><?= date('d M Y', strtotime($lead['enquiry_date'])) ?></td>
                            <td class="text-end">
                                <a href="<?= View::url('leads/show&id=' . $lead['id']) ?>" class="btn btn-sm btn-outline-navy py-1 px-2">
                                    Assign & Details &rarr;
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bulk Assign Modal -->
    <div class="modal fade" id="bulkAssignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-navy">Allocate Leads to Counselor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Counselor *</label>
                        <select name="assigned_to" class="form-select" required>
                            <option value="">-- Choose Active Counselor --</option>
                            <?php foreach ($counselors as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= Sanitizer::e($c['name']) ?> (<?= Sanitizer::e($c['designation'] ?: 'Counselor') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Assignment Remarks</label>
                        <input type="text" name="reason" class="form-control" value="Queue assignment by admin">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy">Confirm Assignment</button>
                </div>
            </div>
        </div>
    </div>
</form>
