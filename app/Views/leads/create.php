<?php
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Add New Admission Enquiry';
?>
<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <!-- Compact Top Action Bar -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <nav aria-label="breadcrumb" class="mb-1">
                    <ol class="breadcrumb mb-0" style="font-size: 0.74rem;">
                        <li class="breadcrumb-item"><a href="<?= View::url('leads') ?>" class="text-decoration-none text-muted">Admission Enquiries</a></li>
                        <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Add New</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="fw-bold text-dark mb-0" style="font-size: 1.25rem; letter-spacing: -0.3px;">Add New Admission Enquiry</h4>
                    <span class="badge rounded-pill bg-light text-secondary border px-2 py-0.5" style="font-size: 0.72rem; font-weight: 600;">
                        Session <?= Sanitizer::e($activeSession['session_label']) ?>
                    </span>
                </div>
            </div>
            <a href="<?= View::url('leads') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 0.8rem; font-weight: 500;">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i>
                <span>Back to Leads</span>
            </a>
        </div>

        <!-- Real-time Duplicate Alert Box -->
        <div class="alert alert-warning d-none mb-3 shadow-sm border-0 rounded-3 p-3" id="duplicateWarningAlert" role="alert" style="background: #FEF3C7; color: #92400E; border-left: 4px solid #F59E0B !important;"></div>

        <!-- Main Form Card (Compact, Clean & Elegant) -->
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white">
            <form action="<?= View::url('leads/store') ?>" method="POST" id="createLeadForm">
                <?= Csrf::field() ?>

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
                            <!-- Academic Session -->
                            <div class="col-md-4">
                                <label class="form-field-label">Academic Session <span class="text-danger">*</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="calendar" class="input-icon"></i>
                                    <input type="text" class="form-control form-control-sm compact-input ps-5 bg-light" value="<?= Sanitizer::e($activeSession['session_label']) ?>" readonly>
                                </div>
                                <input type="hidden" name="session_id" id="leadSessionSelect" value="<?= $activeSession['id'] ?>">
                            </div>

                            <!-- Student Full Name -->
                            <div class="col-md-4">
                                <label class="form-field-label">Student Full Name <span class="text-danger">*</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="user" class="input-icon"></i>
                                    <input type="text" name="student_name" class="form-control form-control-sm compact-input ps-5" required placeholder="e.g. Aarav Sharma">
                                </div>
                            </div>

                            <!-- Class Applied -->
                            <div class="col-md-4">
                                <label class="form-field-label">Class / Grade Applied <span class="text-danger">*</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="book-open" class="input-icon"></i>
                                    <select name="class_applied" class="form-select form-select-sm compact-input ps-5" required>
                                        <?php foreach ($classes as $cl): ?>
                                            <option value="<?= Sanitizer::e($cl) ?>"><?= Sanitizer::e($cl) ?></option>
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
                                    <input type="text" name="parent_name" class="form-control form-control-sm compact-input ps-5" required placeholder="e.g. Mr. Rajesh Sharma">
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
                                    <input type="tel" name="mobile" id="leadMobileInput" class="form-control form-control-sm compact-input ps-5" required placeholder="10-digit mobile" maxlength="15">
                                </div>
                            </div>

                            <!-- Alternate Mobile -->
                            <div class="col-md-4">
                                <label class="form-field-label">Alternate Mobile <span class="text-muted fw-normal">(Optional)</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="phone-forwarded" class="input-icon"></i>
                                    <input type="tel" name="alternate_mobile" class="form-control form-control-sm compact-input ps-5" placeholder="Secondary mobile" maxlength="15">
                                </div>
                            </div>

                            <!-- Email Address -->
                            <div class="col-md-4">
                                <label class="form-field-label">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="mail" class="input-icon"></i>
                                    <input type="email" name="email" class="form-control form-control-sm compact-input ps-5" placeholder="parent@example.com">
                                </div>
                            </div>

                            <!-- City / Locality -->
                            <div class="col-md-4">
                                <label class="form-field-label">City / Locality</label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="map-pin" class="input-icon"></i>
                                    <input type="text" name="city" class="form-control form-control-sm compact-input ps-5" value="Purnea" placeholder="Purnea, Katihar...">
                                </div>
                            </div>

                            <!-- Residential Address -->
                            <div class="col-md-4">
                                <label class="form-field-label">Residential Address <span class="text-muted fw-normal">(Optional)</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="home" class="input-icon"></i>
                                    <input type="text" name="address" class="form-control form-control-sm compact-input ps-5" placeholder="Street, area, town...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: Enquiry Management & Assignment -->
                    <div class="mb-2">
                        <div class="d-flex align-items-center gap-2 mb-2.5">
                            <span class="section-badge-icon section-badge-emerald">
                                <i data-lucide="clipboard-check" style="width: 14px; height: 14px;"></i>
                            </span>
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.88rem; letter-spacing: -0.1px;">3. Enquiry Management & Assignment</h6>
                        </div>

                        <div class="row g-2.5">
                            <!-- Enquiry Source -->
                            <div class="col-md-4" id="enquirySourceWrapper">
                                <label class="form-field-label">Enquiry Source <span class="text-danger">*</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="share-2" class="input-icon"></i>
                                    <select name="enquiry_source" id="enquirySourceSelect" class="form-select form-select-sm compact-input ps-5" required onchange="handleEnquirySourceChange(this.value)">
                                        <?php foreach ($sources as $src): ?>
                                            <?php if (!str_starts_with($src, 'Admission Manager -')): ?>
                                                <option value="<?= Sanitizer::e($src) ?>"><?= Sanitizer::e($src) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Dynamic Admission Manager Name Field (Hidden unless "Admission Manager" is selected) -->
                            <div class="col-md-4" id="admissionManagerNameWrapper" style="display: none;">
                                <label class="form-field-label text-primary fw-semibold">
                                    <i data-lucide="user-check" style="width: 13px; height: 13px;" class="me-1"></i>Select Admission Manager <span class="text-danger">*</span>
                                </label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="user" class="input-icon text-primary"></i>
                                    <select name="admission_manager_name" id="admissionManagerNameSelect" class="form-select form-select-sm compact-input ps-5 border-primary shadow-sm" style="background-color: #f8faff;">
                                        <option value="">-- Choose Admission Manager Name --</option>
                                        <?php if (!empty($admissionManagers)): ?>
                                            <?php foreach ($admissionManagers as $am): ?>
                                                <option value="<?= Sanitizer::e($am['name']) ?>">
                                                    <?= Sanitizer::e($am['name']) ?> (<?= Sanitizer::e($am['designation'] ?: $am['role_name']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="form-text text-muted" style="font-size: 0.68rem;">Select the manager who referred this enquiry.</div>
                            </div>

                            <!-- Enquiry Date -->
                            <div class="col-md-4">
                                <label class="form-field-label">Enquiry Date <span class="text-danger">*</span></label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="calendar-check" class="input-icon"></i>
                                    <input type="date" name="enquiry_date" class="form-control form-control-sm compact-input ps-5" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <!-- Initial Lead Status -->
                            <div class="col-md-4">
                                <label class="form-field-label">Initial Status</label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="tag" class="input-icon"></i>
                                    <select name="status" class="form-select form-select-sm compact-input ps-5">
                                        <?php foreach ($statuses as $st): ?>
                                            <option value="<?= Sanitizer::e($st) ?>" <?= $st === 'New' ? 'selected' : '' ?>><?= Sanitizer::e($st) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Lead Priority -->
                            <div class="col-md-4">
                                <label class="form-field-label">Lead Priority</label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="flag" class="input-icon"></i>
                                    <select name="priority" class="form-select form-select-sm compact-input ps-5" id="leadPrioritySelect">
                                        <option value="Low">Low</option>
                                        <option value="Medium" selected>Medium</option>
                                        <option value="High">High</option>
                                        <option value="Urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Counselor Assignment -->
                            <div class="col-md-4">
                                <label class="form-field-label">Assign to Counselor</label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="user-plus" class="input-icon"></i>
                                    <?php if (Auth::isEmployee()): ?>
                                        <input type="hidden" name="assigned_to" value="<?= Auth::id() ?>">
                                        <input type="text" class="form-control form-control-sm compact-input ps-5 bg-light" value="<?= Sanitizer::e(Auth::user()['name']) ?> (Assigned to You)" readonly>
                                    <?php else: ?>
                                        <select name="assigned_to" class="form-select form-select-sm compact-input ps-5">
                                            <option value="auto" selected>⚡ Auto Distribute (Balanced to Counselors)</option>
                                            <option value="">-- Keep in Unassigned Queue --</option>
                                            <optgroup label="Specific Staff Member">
                                                <?php foreach ($counselors as $cn): ?>
                                                    <option value="<?= $cn['id'] ?>"><?= Sanitizer::e($cn['name']) ?> (<?= Sanitizer::e($cn['designation'] ?: 'Counselor') ?>)</option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        </select>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Next Scheduled Follow-up -->
                            <div class="col-md-4">
                                <label class="form-field-label">Next Scheduled Follow-up</label>
                                <div class="input-icon-wrap">
                                    <i data-lucide="clock" class="input-icon"></i>
                                    <input type="datetime-local" name="next_followup_date" class="form-control form-control-sm compact-input ps-5" value="<?= date('Y-m-d\TH:i', strtotime('+1 day 10:30')) ?>">
                                </div>
                            </div>

                            <!-- Remarks / Requirements -->
                            <div class="col-12 mt-1">
                                <label class="form-field-label">Initial Remarks / Parent Requirements</label>
                                <textarea name="remarks" class="form-control compact-input" rows="2" placeholder="Parent queries regarding fee structure, bus routes, CBSE curriculum, day boarding, sports facilities..." style="font-size: 0.82rem; height: auto; border-radius: 8px;"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Duplicate Checkbox if Duplicate Warning Appears -->
                    <div class="form-check mb-2 d-none p-2.5 bg-warning-subtle rounded-3 border border-warning" id="confirmDuplicateBox">
                        <input class="form-check-input mt-0.5" type="checkbox" name="confirm_duplicate" value="1" id="confirmDupCheck" style="cursor: pointer;">
                        <label class="form-check-label fw-semibold text-danger small ms-1" for="confirmDupCheck" style="font-size: 0.78rem; cursor: pointer;">
                            I confirm this is a valid separate enquiry (e.g. sibling or new session enquiry) sharing this mobile number.
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
                        <a href="<?= View::url('leads') ?>" class="btn btn-sm btn-outline-secondary px-3.5 py-1.5 rounded-pill" style="font-size: 0.82rem;">Cancel</a>
                        <button type="submit" class="btn btn-sm btn-navy px-4 py-1.5 rounded-pill d-inline-flex align-items-center gap-1.5 shadow-sm fw-semibold" style="font-size: 0.84rem; background: #0F172A; border-color: #0F172A;">
                            <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                            <span>Save Enquiry</span>
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

    const leadForm = document.getElementById('createLeadForm') || document.querySelector('form');
    if (leadForm) {
        leadForm.addEventListener('submit', function(e) {
            const sourceSelect = document.getElementById('enquirySourceSelect');
            const mgrSelect = document.getElementById('admissionManagerNameSelect');
            if (sourceSelect && sourceSelect.value === 'Admission Manager' && mgrSelect && !mgrSelect.value) {
                e.preventDefault();
                mgrSelect.focus();
                alert('Please select the Admission Manager Name from the dropdown.');
            }
        });
    }
});
</script>
