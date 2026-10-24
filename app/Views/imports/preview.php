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
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h6 class="fw-bold text-navy mb-0">1. Map Spreadsheet Headers to CRM Fields</h6>
                <div class="text-muted small" style="font-size: 0.76rem;">Primary required column: <strong>Student Name</strong>. If <strong>Mobile Number</strong> or other columns are empty, the system automatically fills safe defaults (e.g. Mobile as 'N/A', Status as 'New') so all leads import cleanly without being skipped.</div>
            </div>
            <span class="badge bg-light text-secondary border px-2.5 py-1">Auto-detected Columns</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <?php
                $targetFields = [
                    'student_name' => ['label' => 'Student Name *', 'required' => true, 'default' => 'Required', 'guess' => ['student name', 'student', 'child name', 'name', 'applicant', 'candidate', 'student_name']],
                    'parent_name' => ['label' => 'Parent Name', 'required' => false, 'default' => 'Defaults to "Parent / Guardian"', 'guess' => ['parent name', 'father name', 'mother name', 'parent', 'guardian', 'father', 'mother', 'parent_name']],
                    'mobile' => ['label' => 'Mobile Number', 'required' => false, 'default' => 'Defaults to "N/A" if empty', 'guess' => ['mobile number', 'mobile', 'phone', 'contact', 'contact no', 'mobile no', 'phone number', 'cell', 'mobile_number']],
                    'alternate_mobile' => ['label' => 'Alternate Mobile', 'required' => false, 'default' => 'Optional', 'guess' => ['alternate mobile', 'alt mobile', 'secondary phone', 'alternate phone', 'alt phone']],
                    'email' => ['label' => 'Email Address', 'required' => false, 'default' => 'Optional', 'guess' => ['email address', 'email', 'e-mail', 'mail']],
                    'class_applied' => ['label' => 'Class Applied', 'required' => false, 'default' => 'Defaults to "Grade 1"', 'guess' => ['class applied', 'class', 'grade', 'standard', 'admission class']],
                    'enquiry_source' => ['label' => 'Enquiry Source', 'required' => false, 'default' => 'Defaults to "Direct Walk-in"', 'guess' => ['enquiry source', 'source', 'channel', 'lead source']],
                    'assigned_to' => ['label' => 'Assigned Employee / Counselor', 'required' => false, 'default' => 'Uses Allocation Rule below', 'guess' => ['assigned employee', 'counselor', 'assigned to', 'counsellor', 'staff']],
                    'priority' => ['label' => 'Priority', 'required' => false, 'default' => 'Defaults to "Medium"', 'guess' => ['priority', 'urgency', 'level']],
                    'address' => ['label' => 'Residential Address', 'required' => false, 'default' => 'Optional', 'guess' => ['address', 'residence', 'street', 'locality']],
                    'city' => ['label' => 'City', 'required' => false, 'default' => 'Defaults to "Purnea"', 'guess' => ['city', 'location', 'town', 'district']],
                    'remarks' => ['label' => 'Remarks / Notes', 'required' => false, 'default' => 'Defaults to import note', 'guess' => ['remarks', 'notes', 'comments', 'description']],
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
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label small fw-semibold text-muted mb-0">
                            <?= $info['label'] ?>
                        </label>
                        <?php if (!empty($info['default'])): ?>
                            <span class="text-muted" style="font-size: 0.68rem;"><?= $info['default'] ?></span>
                        <?php endif; ?>
                    </div>
                    <select name="mapping[<?= $fieldName ?>]" class="form-select form-select-sm" <?= $info['required'] ? 'required' : '' ?>>
                        <option value="">-- Do Not Import / Empty --</option>
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

    <!-- 3. Lead Distribution to Counselors Strategy -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <h6 class="fw-bold text-navy mb-0">3. Lead Allocation to Admission Counselors</h6>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">⚡ Auto Equal Divide</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-check p-3 bg-light rounded border h-100">
                        <input class="form-check-input" type="radio" name="assign_strategy" id="assignRoundRobin" value="round_robin" checked>
                        <label class="form-check-label fw-bold small text-navy" for="assignRoundRobin">
                            ⚡ Divide & Distribute Equally to All Admission Counselors (Automatic)
                        </label>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            Every active counselor receives an equal portion of the imported leads in a balanced round-robin cycle. Tasks and notifications are generated automatically.
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-check p-3 bg-light rounded border h-100">
                        <input class="form-check-input" type="radio" name="assign_strategy" id="assignUnassigned" value="unassigned">
                        <label class="form-check-label fw-bold small text-navy" for="assignUnassigned">
                            Keep in Unassigned Queue
                        </label>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            Import leads without counselor assignment. They will remain in the Unassigned Leads queue for subsequent manual allocation.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Table Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="fw-bold text-navy mb-0">4. Raw Spreadsheet Preview (First <?= count($previewRows) ?> Rows)</h6>
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
