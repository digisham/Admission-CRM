<?php
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'School & CRM Settings';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">School & CRM Settings</h3>
        <p class="text-muted small mb-0">Configure GD Goenka Public School details, admissions contact, and customizable enquiry parameters.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <form action="<?= View::url('settings/update') ?>" method="POST">
                    <?= Csrf::field() ?>

                    <h6 class="fw-bold text-navy border-bottom pb-2 mb-3">1. School Identity & Campus Address</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">School Name *</label>
                            <input type="text" name="school_name" class="form-control" required value="<?= Sanitizer::e($settings['school_name'] ?? 'GD Goenka Public School, Purnea') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Admissions Hotline Phone</label>
                            <input type="text" name="school_phone" class="form-control" value="<?= Sanitizer::e($settings['school_phone'] ?? '+91 99999 99999') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Admissions Contact Email</label>
                            <input type="email" name="school_email" class="form-control" value="<?= Sanitizer::e($settings['school_email'] ?? 'admissions@gdgoenkapurnea.com') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Campus Address</label>
                            <input type="text" name="school_address" class="form-control" value="<?= Sanitizer::e($settings['school_address'] ?? 'NH-31, Near Chunapur Road, Purnea, Bihar 854301') ?>">
                        </div>
                    </div>

                    <h6 class="fw-bold text-navy border-bottom pb-2 mb-3">2. Configurable Enquiry Sources</h6>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Lead Enquiry Sources (One per line)</label>
                        <textarea name="lead_sources" class="form-control font-monospace small" rows="5"><?= implode("\n", $sources) ?></textarea>
                        <div class="form-text" style="font-size: 0.72rem;">Available as choices when adding or importing enquiries.</div>
                    </div>

                    <h6 class="fw-bold text-navy border-bottom pb-2 mb-3">3. Configurable Classes / Grades Offered</h6>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Classes Offered for Admission (One per line)</label>
                        <textarea name="classes_offered" class="form-control font-monospace small" rows="6"><?= implode("\n", $classes) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-navy px-4 shadow-sm fw-semibold">
                            <i data-lucide="save" class="me-1" style="width: 16px; height: 16px;"></i> Save All Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
