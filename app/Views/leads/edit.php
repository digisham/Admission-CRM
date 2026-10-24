<?php
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Edit Enquiry ' . $lead['lead_ref'];
?>
<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <!-- Compact Top Action Bar -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <nav aria-label="breadcrumb" class="mb-1">
                    <ol class="breadcrumb mb-0" style="font-size: 0.74rem;">
                        <li class="breadcrumb-item"><a href="<?= View::url('leads') ?>" class="text-decoration-none text-muted">Admission Enquiries</a></li>
                        <li class="breadcrumb-item"><a href="<?= View::url('leads/show&id=' . $lead['id']) ?>" class="text-decoration-none text-muted"><?= Sanitizer::e($lead['lead_ref']) ?></a></li>
                        <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Edit Enquiry</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="fw-bold text-dark mb-0" style="font-size: 1.25rem; letter-spacing: -0.3px;">Edit Enquiry <?= Sanitizer::e($lead['lead_ref']) ?></h4>
                    <span class="badge rounded-pill bg-light text-secondary border px-2 py-0.5" style="font-size: 0.72rem; font-weight: 600;">
                        Session <?= Sanitizer::e($lead['session_label']) ?>
                    </span>
                    <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5" style="font-size: 0.72rem; font-weight: 600;">
                        <?= Sanitizer::e($lead['status']) ?>
                    </span>
                </div>
            </div>
            <a href="<?= View::url('leads/show&id=' . $lead['id']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 0.8rem; font-weight: 500;">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i>
                <span>View Lead Details</span>
            </a>
        </div>

        <!-- Real-time Duplicate Alert Box -->
        <div class="alert alert-warning d-none mb-3 shadow-sm border-0 rounded-3 p-3" id="duplicateWarningAlert" role="alert" style="background: #FEF3C7; color: #92400E; border-left: 4px solid #F59E0B !important;"></div>

        <!-- Main Form Card (Compact, Clean & Elegant) -->
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white">
            <form action="<?= View::url('leads/update') ?>" method="POST" id="editLeadForm">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= $lead['id'] ?>">
                <input type="hidden" id="leadExcludeId" value="<?= $lead['id'] ?>">
                <input type="hidden" id="leadSessionSelect" value="<?= $lead['session_id'] ?>">

                <div class="p-3 p-md-4">
                    <!-- SECTION 1: Student & Academic Details -->
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex align-items-center gap-2 mb-2.5">
                            <span class="section-badge-icon section-badge-blue">
                                <i data-lucide="graduation-cap" style="width: 14px; height: 14px;"></i>
                            </span>
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.88rem; letter-spacing: -0.1px;">1. Student & Academic Details</h6>
                        </div>

                        <div class="row g-2.5">
                            <!-- Academic Session (Locked) -->
                            <div class="col-md-4">
                                <label class="form-field-label d-flex justify-content-between align-items-center">
                                    <span>Academic Session</span>
                                    <span class="text-muted" style="font-size: 0.68rem;"><i data-lucide="lock" style="width: 11px; height: 11px;"></i> Locked</span>
                                </label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="calendar" class="input-icon"></i>
                                    <input type="text" class="form-control form-control-sm compact-input ps-5 bg-light" value="<?= Sanitizer::e($lead['session_label']) ?>" readonly title="Session is locked to preserve historical record">
                                </div>
                            </div>

                            <!-- Student Full Name -->
                            <div class="col-md-4">
                                <label class="form-field-label">Student Full Name <span class="text-danger">*</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="user" class="input-icon"></i>
                                    <input type="text" name="student_name" class="form-control form-control-sm compact-input ps-5" required value="<?= Sanitizer::e($lead['student_name']) ?>" placeholder="e.g. Aarav Sharma">
                                </div>
                            </div>

                            <!-- Class Applied -->
                            <div class="col-md-4">
                                <label class="form-field-label">Class / Grade Applied <span class="text-danger">*</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="book-open" class="input-icon"></i>
                                    <select name="class_applied" class="form-select form-select-sm compact-input ps-5" required>
                                        <?php foreach ($classes as $cl): ?>
                                            <option value="<?= Sanitizer::e($cl) ?>" <?= $lead['class_applied'] === $cl ? 'selected' : '' ?>><?= Sanitizer::e($cl) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: Parent & Contact Details -->
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex align-items-center gap-2 mb-2.5">
                            <span class="section-badge-icon section-badge-indigo">
                                <i data-lucide="phone-call" style="width: 14px; height: 14px;"></i>
                            </span>
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.88rem; letter-spacing: -0.1px;">2. Parent & Contact Details</h6>
                        </div>

                        <div class="row g-2.5">
                            <!-- Parent Name -->
                            <div class="col-md-4">
                                <label class="form-field-label">Parent / Guardian Name <span class="text-danger">*</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="user-check" class="input-icon"></i>
                                    <input type="text" name="parent_name" class="form-control form-control-sm compact-input ps-5" required value="<?= Sanitizer::e($lead['parent_name']) ?>" placeholder="e.g. Mr. Rajesh Sharma">
                                </div>
                            </div>

                            <!-- Primary Mobile -->
                            <div class="col-md-4">
                                <label class="form-field-label d-flex justify-content-between align-items-center">
                                    <span>Primary Mobile <span class="text-danger">*</span></span>
                                    <span class="text-muted" style="font-size: 0.68rem; font-weight: normal;">Checked for duplicates</span>
                                </label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="phone" class="input-icon"></i>
                                    <input type="tel" name="mobile" id="leadMobileInput" class="form-control form-control-sm compact-input ps-5" required value="<?= Sanitizer::e($lead['mobile']) ?>" placeholder="10-digit mobile" maxlength="15">
                                </div>
                            </div>

                            <!-- Alternate Mobile -->
                            <div class="col-md-4">
                                <label class="form-field-label">Alternate Mobile <span class="text-muted fw-normal">(Optional)</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="phone-forwarded" class="input-icon"></i>
                                    <input type="tel" name="alternate_mobile" class="form-control form-control-sm compact-input ps-5" value="<?= Sanitizer::e($lead['alternate_mobile']) ?>" placeholder="Secondary mobile" maxlength="15">
                                </div>
                            </div>

                            <!-- Email Address -->
                            <div class="col-md-4">
                                <label class="form-field-label">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="mail" class="input-icon"></i>
                                    <input type="email" name="email" class="form-control form-control-sm compact-input ps-5" value="<?= Sanitizer::e($lead['email']) ?>" placeholder="parent@example.com">
                                </div>
                            </div>

                            <!-- City / Locality -->
                            <div class="col-md-4">
                                <label class="form-field-label">City / Locality</label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="map-pin" class="input-icon"></i>
                                    <input type="text" name="city" class="form-control form-control-sm compact-input ps-5" value="<?= Sanitizer::e($lead['city'] ?: 'Purnea') ?>" placeholder="Purnea, Katihar...">
                                </div>
                            </div>

                            <!-- Residential Address -->
                            <div class="col-md-4">
                                <label class="form-field-label">Residential Address <span class="text-muted fw-normal">(Optional)</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="home" class="input-icon"></i>
                                    <input type="text" name="address" class="form-control form-control-sm compact-input ps-5" value="<?= Sanitizer::e($lead['address']) ?>" placeholder="Street, area, town...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: Pipeline Status & Priority -->
                    <div class="mb-2">
                        <div class="d-flex align-items-center gap-2 mb-2.5">
                            <span class="section-badge-icon section-badge-emerald">
                                <i data-lucide="clipboard-check" style="width: 14px; height: 14px;"></i>
                            </span>
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.88rem; letter-spacing: -0.1px;">3. Pipeline Status & Priority</h6>
                        </div>

                        <div class="row g-2.5">
                            <?php
                            $isMgrSource = str_starts_with($lead['enquiry_source'] ?? '', 'Admission Manager');
                            $currentMgrName = $isMgrSource ? trim(str_replace('Admission Manager -', '', str_replace('Admission Manager:', '', $lead['enquiry_source']))) : '';
                            ?>
                            <!-- Enquiry Source -->
                            <div class="col-md-4" id="enquirySourceWrapper">
                                <label class="form-field-label">Enquiry Source <span class="text-danger">*</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="share-2" class="input-icon"></i>
                                    <select name="enquiry_source" id="enquirySourceSelect" class="form-select form-select-sm compact-input ps-5" required onchange="handleEnquirySourceChange(this.value)">
                                        <?php foreach ($sources as $src): ?>
                                            <?php if (!str_starts_with($src, 'Admission Manager -')): ?>
                                                <option value="<?= Sanitizer::e($src) ?>" <?= ($lead['enquiry_source'] === $src || ($src === 'Admission Manager' && $isMgrSource)) ? 'selected' : '' ?>>
                                                    <?= Sanitizer::e($src) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Dynamic Admission Manager Name Field (Hidden unless "Admission Manager" is selected) -->
                            <div class="col-md-4" id="admissionManagerNameWrapper" style="display: <?= $isMgrSource ? 'block' : 'none' ?>;">
                                <label class="form-field-label text-primary fw-semibold">
                                    <i data-lucide="user-check" style="width: 13px; height: 13px;" class="me-1"></i>Select Admission Manager <span class="text-danger">*</span>
                                </label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="user" class="input-icon text-primary"></i>
                                    <select name="admission_manager_name" id="admissionManagerNameSelect" class="form-select form-select-sm compact-input ps-5 border-primary shadow-sm" style="background-color: #f8faff;">
                                        <option value="">-- Choose Admission Manager Name --</option>
                                        <?php if (!empty($admissionManagers)): ?>
                                            <?php foreach ($admissionManagers as $am): ?>
                                                <option value="<?= Sanitizer::e($am['name']) ?>" <?= $currentMgrName === $am['name'] ? 'selected' : '' ?>>
                                                    <?= Sanitizer::e($am['name']) ?> (<?= Sanitizer::e($am['designation'] ?: $am['role_name']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="form-text text-muted" style="font-size: 0.68rem;">Select the manager who referred this enquiry.</div>
                            </div>

                            <!-- Lead Status -->
                            <div class="col-md-4">
                                <label class="form-field-label">Lead Status</label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="tag" class="input-icon"></i>
                                    <select name="status" class="form-select form-select-sm compact-input ps-5">
                                        <?php foreach ($statuses as $st): ?>
                                            <option value="<?= Sanitizer::e($st) ?>" <?= $lead['status'] === $st ? 'selected' : '' ?>><?= Sanitizer::e($st) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Priority -->
                            <div class="col-md-4">
                                <label class="form-field-label">Priority</label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="flag" class="input-icon"></i>
                                    <select name="priority" class="form-select form-select-sm compact-input ps-5">
                                        <option value="Low" <?= $lead['priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
                                        <option value="Medium" <?= $lead['priority'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                                        <option value="High" <?= $lead['priority'] === 'High' ? 'selected' : '' ?>>High</option>
                                        <option value="Urgent" <?= $lead['priority'] === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Next Scheduled Follow-up -->
                            <div class="col-md-6">
                                <label class="form-field-label">Next Scheduled Follow-up</label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="clock" class="input-icon"></i>
                                    <input type="datetime-local" name="next_followup_date" class="form-control form-control-sm compact-input ps-5" 
                                           value="<?= $lead['next_followup_date'] ? date('Y-m-d\TH:i', strtotime($lead['next_followup_date'])) : '' ?>">
                                </div>
                            </div>

                            <!-- Remarks / Requirements -->
                            <div class="col-12 mt-1">
                                <label class="form-field-label">General Remarks / Notes</label>
                                <textarea name="remarks" class="form-control compact-input" rows="2" placeholder="Parent requirements, follow-up comments, special notes..." style="font-size: 0.82rem; height: auto; border-radius: 8px;"><?= Sanitizer::e($lead['remarks']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Duplicate Checkbox if Duplicate Warning Appears -->
                    <div class="form-check mb-2 d-none p-2.5 bg-warning-subtle rounded-3 border border-warning" id="confirmDuplicateBox">
                        <input class="form-check-input mt-0.5" type="checkbox" name="confirm_duplicate" value="1" id="confirmDupCheck" style="cursor: pointer;">
                        <label class="form-check-label fw-semibold text-danger small ms-1" for="confirmDupCheck" style="font-size: 0.78rem; cursor: pointer;">
                            I confirm this is a valid separate enquiry sharing this mobile number.
                        </label>
                    </div>
                </div>

                <!-- Footer Action Bar -->
                <div class="p-3 bg-light border-top d-flex align-items-center justify-content-between">
                    <span class="text-muted small" style="font-size: 0.75rem;">
                        <i data-lucide="info" style="width: 13px; height: 13px;" class="me-1"></i>
                        Fields marked with <span class="text-danger">*</span> are mandatory.
                    </span>
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= View::url('leads/show&id=' . $lead['id']) ?>" class="btn btn-sm btn-outline-secondary px-3.5 py-1.5 rounded-pill" style="font-size: 0.82rem;">Cancel</a>
                        <button type="submit" class="btn btn-sm btn-navy px-4 py-1.5 rounded-pill d-inline-flex align-items-center gap-1.5 shadow-sm fw-semibold" style="font-size: 0.84rem; background: #0F172A; border-color: #0F172A;">
                            <i data-lucide="save" style="width: 14px; height: 14px;"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function handleEnquirySourceChange(val) {
    const mgrWrapper = document.getElementById('admissionManagerNameWrapper');
    const mgrSelect = document.getElementById('admissionManagerNameSelect');
    if (!mgrWrapper || !mgrSelect) return;

    if (val === 'Admission Manager') {
        mgrWrapper.style.display = 'block';
        mgrSelect.setAttribute('required', 'required');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } else {
        mgrWrapper.style.display = 'none';
        mgrSelect.removeAttribute('required');
        mgrSelect.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const sourceSelect = document.getElementById('enquirySourceSelect');
    if (sourceSelect) {
        handleEnquirySourceChange(sourceSelect.value);
    }
});
</script>
