<?php
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Add New Admission Enquiry';
?>
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-navy mb-1">Add New Admission Enquiry</h3>
                <p class="text-muted small mb-0">Record an admission enquiry from a walk-in, phone call, website, or campaign.</p>
            </div>
            <a href="<?= View::url('leads') ?>" class="btn btn-outline-secondary shadow-sm">
                &larr; Back to Leads List
            </a>
        </div>

        <!-- Real-time Duplicate Alert Box -->
        <div class="alert alert-warning d-none mb-3 shadow-sm" id="duplicateWarningAlert" role="alert"></div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <form action="<?= View::url('leads/store') ?>" method="POST" id="createLeadForm">
                    <?= Csrf::field() ?>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="fw-bold text-navy border-bottom pb-2">1. Student & Academic Details</h6>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Academic Session *</label>
                            <input type="text" class="form-control" value="<?= Sanitizer::e($activeSession['session_label']) ?>" readonly>
                            <input type="hidden" name="session_id" id="leadSessionSelect" value="<?= $activeSession['id'] ?>">
                            <div class="form-text" style="font-size: 0.72rem;">Defaulted to the active admission session.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Student Full Name *</label>
                            <input type="text" name="student_name" class="form-control" required placeholder="e.g. Aarav Sharma">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Class / Grade Applied *</label>
                            <select name="class_applied" class="form-select" required>
                                <?php foreach ($classes as $cl): ?>
                                    <option value="<?= Sanitizer::e($cl) ?>"><?= Sanitizer::e($cl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="fw-bold text-navy border-bottom pb-2">2. Parent & Contact Details</h6>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Parent / Guardian Name *</label>
                            <input type="text" name="parent_name" class="form-control" required placeholder="e.g. Mr. Rajesh Sharma">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Primary Mobile Number *</label>
                            <input type="tel" name="mobile" id="leadMobileInput" class="form-control" required placeholder="e.g. 9811223344">
                            <div class="form-text" style="font-size: 0.72rem;">10-digit mobile number. Checked for duplicates.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Alternate Mobile (Optional)</label>
                            <input type="tel" name="alternate_mobile" class="form-control" placeholder="e.g. 9811223345">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Email Address (Optional)</label>
                            <input type="email" name="email" class="form-control" placeholder="rajesh@example.com">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">City / Locality</label>
                            <input type="text" name="city" class="form-control" value="Purnea" placeholder="Purnea, Katihar, Araria...">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Residential Address (Optional)</label>
                            <input type="text" name="address" class="form-control" placeholder="Street, landmark, town...">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="fw-bold text-navy border-bottom pb-2">3. Enquiry Management & Assignment</h6>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Enquiry Source *</label>
                            <select name="enquiry_source" class="form-select" required>
                                <?php foreach ($sources as $src): ?>
                                    <option value="<?= Sanitizer::e($src) ?>"><?= Sanitizer::e($src) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Enquiry Date *</label>
                            <input type="date" name="enquiry_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Initial Lead Status</label>
                            <select name="status" class="form-select">
                                <?php foreach ($statuses as $st): ?>
                                    <option value="<?= Sanitizer::e($st) ?>" <?= $st === 'New' ? 'selected' : '' ?>><?= Sanitizer::e($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Lead Priority</label>
                            <select name="priority" class="form-select">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Assign to Counselor</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">-- Leave in Unassigned Queue --</option>
                                <?php foreach ($counselors as $cn): ?>
                                    <option value="<?= $cn['id'] ?>"><?= Sanitizer::e($cn['name']) ?> (<?= Sanitizer::e($cn['designation'] ?: 'Counselor') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Next Scheduled Follow-up</label>
                            <input type="datetime-local" name="next_followup_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime('+1 day 10:30')) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">Initial Remarks / Parent Requirements</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Parent queries regarding fee structure, bus routes, CBSE curriculum, sports..."></textarea>
                        </div>
                    </div>

                    <!-- Confirm Duplicate Checkbox if Duplicate Warning Appears -->
                    <div class="form-check mb-4 d-none p-3 bg-light rounded border" id="confirmDuplicateBox">
                        <input class="form-check-input" type="checkbox" name="confirm_duplicate" value="1" id="confirmDupCheck">
                        <label class="form-check-label fw-semibold text-danger small" for="confirmDupCheck">
                            I confirm this is a valid separate enquiry (e.g. sibling or new session enquiry) sharing this mobile number.
                        </label>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= View::url('leads') ?>" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-navy px-4 fw-semibold shadow-sm">
                            <i data-lucide="check" class="me-1" style="width: 16px; height: 16px;"></i> Save Enquiry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
