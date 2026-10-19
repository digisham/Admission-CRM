<?php
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Step 2: Column Mapping & Validation Preview';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">Preview & Column Mapping</h3>
        <p class="text-muted small mb-0">File: <strong><?= Sanitizer::e($originalName) ?></strong> &bull; Total rows: <strong><?= $totalRows ?></strong> &bull; Session: <strong><?= Sanitizer::e($session['session_label']) ?></strong></p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <a href="<?= View::url('import') ?>" class="btn btn-outline-secondary shadow-sm">
            &larr; Cancel / Choose Another File
        </a>
    </div>
</div>

<?php if ($duplicateBatch): ?>
<div class="alert alert-warning shadow-sm mb-4">
    <i data-lucide="alert-triangle" class="me-1" style="width: 18px; height: 18px;"></i>
    <strong>Potential Duplicate File Detected:</strong> An identical file was previously imported on 
    <strong><?= date('d M Y, h:i A', strtotime($duplicateBatch['created_at'])) ?></strong> (Batch #<?= $duplicateBatch['id'] ?>).
    Please verify you are not accidentally importing the same dataset twice.
</div>
<?php endif; ?>

<form action="<?= View::url('import/commit') ?>" method="POST" id="importCommitForm">
    <?= Csrf::field() ?>
    <input type="hidden" name="temp_file" value="<?= Sanitizer::e($tempFile) ?>">
    <input type="hidden" name="original_name" value="<?= Sanitizer::e($originalName) ?>">
    <input type="hidden" name="session_id" value="<?= $session['id'] ?>">

    <!-- Column Mapping Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="fw-bold text-navy mb-0">1. Map Spreadsheet Headers to CRM Fields</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <?php
                $targetFields = [
                    'student_name' => ['label' => 'Student Name *', 'required' => true, 'guess' => ['student name', 'student', 'child name', 'name']],
                    'parent_name' => ['label' => 'Parent Name *', 'required' => true, 'guess' => ['parent name', 'father name', 'mother name', 'parent', 'guardian']],
                    'mobile' => ['label' => 'Mobile Number *', 'required' => true, 'guess' => ['mobile number', 'mobile', 'phone', 'contact']],
                    'alternate_mobile' => ['label' => 'Alternate Mobile', 'required' => false, 'guess' => ['alternate mobile', 'alt mobile', 'secondary phone']],
                    'email' => ['label' => 'Email Address', 'required' => false, 'guess' => ['email address', 'email', 'e-mail']],
                    'class_applied' => ['label' => 'Class Applied', 'required' => false, 'guess' => ['class applied', 'class', 'grade']],
                    'enquiry_source' => ['label' => 'Enquiry Source', 'required' => false, 'guess' => ['enquiry source', 'source', 'channel']],
                    'assigned_to' => ['label' => 'Assigned Employee / Counselor', 'required' => false, 'guess' => ['assigned employee', 'counselor', 'assigned to']],
                    'priority' => ['label' => 'Priority', 'required' => false, 'guess' => ['priority', 'urgency']],
                    'address' => ['label' => 'Residential Address', 'required' => false, 'guess' => ['address', 'residence']],
                    'city' => ['label' => 'City', 'required' => false, 'guess' => ['city', 'location']],
                    'remarks' => ['label' => 'Remarks / Notes', 'required' => false, 'guess' => ['remarks', 'notes', 'comments']],
                ];

                foreach ($targetFields as $fieldName => $info):
                    // Auto-guess match
                    $bestMatch = '';
                    foreach ($headers as $h) {
                        $lowerH = strtolower(trim($h));
                        foreach ($info['guess'] as $g) {
                            if ($lowerH === $g || str_contains($lowerH, $g)) {
                                $bestMatch = $h;
                                break 2;
                            }
                        }
                    }
                ?>
                <div class="col-md-4 col-sm-6">
                    <label class="form-label small fw-semibold text-muted">
                        <?= $info['label'] ?>
                    </label>
                    <select name="mapping[<?= $fieldName ?>]" class="form-select form-select-sm" <?= $info['required'] ? 'required' : '' ?>>
                        <option value="">-- Do Not Import --</option>
                        <?php foreach ($headers as $h): ?>
                            <option value="<?= Sanitizer::e($h) ?>" <?= $h === $bestMatch ? 'selected' : '' ?>>
                                <?= Sanitizer::e($h) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Duplicate Strategy Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="fw-bold text-navy mb-0">2. Duplicate Mobile Strategy (Session <?= Sanitizer::e($session['session_label']) ?>)</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-check p-3 bg-light rounded border h-100">
                        <input class="form-check-input" type="radio" name="duplicate_action" id="dupSkip" value="skip" checked>
                        <label class="form-check-label fw-bold small text-navy" for="dupSkip">
                            Skip Duplicate Records (Recommended)
                        </label>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            Rows matching existing phone numbers in this session are skipped and logged in the error report.
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-check p-3 bg-light rounded border h-100">
                        <input class="form-check-input" type="radio" name="duplicate_action" id="dupUpdate" value="update">
                        <label class="form-check-label fw-bold small text-navy" for="dupUpdate">
                            Update Existing Lead Records
                        </label>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            Updates student info and appends remarks to the existing lead timeline.
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-check p-3 bg-light rounded border h-100">
                        <input class="form-check-input" type="radio" name="duplicate_action" id="dupAllow" value="allow">
                        <label class="form-check-label fw-bold small text-navy" for="dupAllow">
                            Allow Multiple Enquiries
                        </label>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            Creates a separate enquiry record even if the phone number already exists (e.g. for siblings).
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Table Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="fw-bold text-navy mb-0">3. Raw Spreadsheet Preview (First <?= count($previewRows) ?> Rows)</h6>
        </div>
        <div class="table-responsive" style="max-height: 340px;">
            <table class="table custom-table table-bordered table-sm mb-0">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <?php foreach ($headers as $h): ?>
                            <th><?= Sanitizer::e($h) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($previewRows as $idx => $row): ?>
                    <tr>
                        <td class="text-muted fw-bold"><?= $idx + 1 ?></td>
                        <?php foreach ($headers as $h): ?>
                            <td><?= Sanitizer::e($row[$h] ?? '') ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Confirmation Banner & Submit Button -->
    <div class="d-flex justify-content-between align-items-center p-3 bg-white border rounded shadow-sm">
        <div class="small text-muted">
            Ready to process <strong><?= $totalRows ?></strong> rows into <strong><?= Sanitizer::e($session['session_label']) ?></strong>.
        </div>
        <div>
            <a href="<?= View::url('import') ?>" class="btn btn-light border me-2">Cancel</a>
            <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm" onclick="return confirm('Commit lead import for <?= $totalRows ?> records?');">
                <i data-lucide="check" class="me-1" style="width: 16px; height: 16px;"></i> Commit & Import Leads
            </button>
        </div>
    </div>
</form>
