<?php
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Edit Lead #' . $lead['lead_ref'];
?>
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-navy mb-1">Edit Enquiry <?= Sanitizer::e($lead['lead_ref']) ?></h3>
                <p class="text-muted small mb-0">Update contact or admission details for <strong><?= Sanitizer::e($lead['student_name']) ?></strong>.</p>
            </div>
            <a href="<?= View::url('leads/show&id=' . $lead['id']) ?>" class="btn btn-outline-secondary shadow-sm">
                &larr; View Lead Details
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <form action="<?= View::url('leads/update') ?>" method="POST">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $lead['id'] ?>">

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="fw-bold text-navy border-bottom pb-2">1. Student & Academic Details</h6>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Academic Session</label>
                            <input type="text" class="form-control" value="<?= Sanitizer::e($lead['session_label']) ?>" readonly>
                            <div class="form-text" style="font-size: 0.72rem;">Academic session is locked to preserve historical record.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Student Name *</label>
                            <input type="text" name="student_name" class="form-control" required value="<?= Sanitizer::e($lead['student_name']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Class Applied *</label>
                            <select name="class_applied" class="form-select" required>
                                <?php foreach ($classes as $cl): ?>
                                    <option value="<?= Sanitizer::e($cl) ?>" <?= $lead['class_applied'] === $cl ? 'selected' : '' ?>><?= Sanitizer::e($cl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="fw-bold text-navy border-bottom pb-2">2. Parent & Contact Details</h6>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Parent Name *</label>
                            <input type="text" name="parent_name" class="form-control" required value="<?= Sanitizer::e($lead['parent_name']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Mobile Number *</label>
                            <input type="tel" name="mobile" class="form-control" required value="<?= Sanitizer::e($lead['mobile']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Alternate Mobile</label>
                            <input type="tel" name="alternate_mobile" class="form-control" value="<?= Sanitizer::e($lead['alternate_mobile']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= Sanitizer::e($lead['email']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">City</label>
                            <input type="text" name="city" class="form-control" value="<?= Sanitizer::e($lead['city']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Address</label>
                            <input type="text" name="address" class="form-control" value="<?= Sanitizer::e($lead['address']) ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="fw-bold text-navy border-bottom pb-2">3. Pipeline Status & Priority</h6>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Enquiry Source</label>
                            <select name="enquiry_source" class="form-select">
                                <?php foreach ($sources as $src): ?>
                                    <option value="<?= Sanitizer::e($src) ?>" <?= $lead['enquiry_source'] === $src ? 'selected' : '' ?>><?= Sanitizer::e($src) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Lead Status</label>
                            <select name="status" class="form-select">
                                <?php foreach ($statuses as $st): ?>
                                    <option value="<?= Sanitizer::e($st) ?>" <?= $lead['status'] === $st ? 'selected' : '' ?>><?= Sanitizer::e($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="Low" <?= $lead['priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
                                <option value="Medium" <?= $lead['priority'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="High" <?= $lead['priority'] === 'High' ? 'selected' : '' ?>>High</option>
                                <option value="Urgent" <?= $lead['priority'] === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Next Scheduled Follow-up</label>
                            <input type="datetime-local" name="next_followup_date" class="form-control" 
                                   value="<?= $lead['next_followup_date'] ? date('Y-m-d\TH:i', strtotime($lead['next_followup_date'])) : '' ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">General Remarks / Notes</label>
                            <textarea name="remarks" class="form-control" rows="3"><?= Sanitizer::e($lead['remarks']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= View::url('leads/show&id=' . $lead['id']) ?>" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-navy px-4 fw-semibold shadow-sm">
                            <i data-lucide="save" class="me-1" style="width: 16px; height: 16px;"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
