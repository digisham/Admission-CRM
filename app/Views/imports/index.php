<?php
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Excel / CSV Leads Import Wizard';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">Leads Import Wizard</h3>
        <p class="text-muted small mb-0">Multi-step import with preview, column mapping, duplicate detection, and audit history.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <a href="<?= View::url('import/template') ?>" class="btn btn-outline-success shadow-sm">
            <i data-lucide="download" class="me-1" style="width: 16px; height: 16px;"></i> Download CSV Template
        </a>
    </div>
</div>

<!-- Upload Wizard Step 1 Card -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold text-navy mb-0 d-flex align-items-center">
                    <span class="badge bg-navy me-2">Step 1</span> Select & Upload Leads Spreadsheet
                </h6>
            </div>
            <div class="card-body p-4">
                <form action="<?= View::url('import/preview') ?>" method="POST" enctype="multipart/form-data">
                    <?= Csrf::field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Target Academic Session *</label>
                        <select name="session_id" class="form-select" required>
                            <option value="<?= $activeSession['id'] ?>" selected>
                                <?= Sanitizer::e($activeSession['session_label']) ?> (Active Admission Session)
                            </option>
                        </select>
                        <div class="form-text" style="font-size: 0.72rem;">New leads will be attached to this academic intake session.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Select CSV File (.csv) *</label>
                        <input type="file" name="csv_file" class="form-control" required accept=".csv">
                        <div class="form-text" style="font-size: 0.72rem;">Max file size: 10MB. UTF-8 encoded CSV.</div>
                    </div>

                    <div class="p-3 bg-light rounded border mb-4 small text-muted">
                        <div class="fw-semibold text-dark mb-1">Import Guidelines:</div>
                        <ul class="mb-0 ps-3">
                            <li>Required columns: <strong>Student Name</strong> and <strong>Mobile Number</strong> (10 digits).</li>
                            <li>Phone numbers are preserved with leading zeros.</li>
                            <li>Existing phone numbers in the active session will be flagged as duplicates.</li>
                            <li>You will preview rows and map columns before any data is committed.</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-navy px-4 fw-semibold shadow-sm">
                        Continue to Preview & Column Mapping &rarr;
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold text-navy mb-0">Download Template</h6>
            </div>
            <div class="card-body p-4 text-center">
                <i data-lucide="file-spreadsheet" class="text-success mb-3" style="width: 48px; height: 48px;"></i>
                <h6 class="fw-bold text-navy">Ready-to-use Import Template</h6>
                <p class="text-muted small mb-4">
                    Download our structured CSV template with verified sample rows and standard columns for GD Goenka Public School.
                </p>
                <a href="<?= View::url('import/template') ?>" class="btn btn-outline-navy w-100 py-2">
                    <i data-lucide="download" class="me-1" style="width: 16px; height: 16px;"></i> Download Sample CSV
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Past Imports History -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold text-navy mb-0">Recent Import Batches</h6>
    </div>
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Batch ID</th>
                    <th>File Name</th>
                    <th>Imported By</th>
                    <th>Date & Time</th>
                    <th class="text-center">Total Rows</th>
                    <th class="text-center">Imported</th>
                    <th class="text-center">Duplicates Skipped</th>
                    <th class="text-center">Failed</th>
                    <th>Status</th>
                    <th class="text-end">Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($batches)): ?>
                    <tr><td colspan="10" class="text-center py-4 text-muted">No spreadsheet imports performed yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($batches as $b): ?>
                    <tr>
                        <td class="fw-bold text-navy">#<?= $b['id'] ?></td>
                        <td class="fw-semibold"><?= Sanitizer::e($b['file_name']) ?></td>
                        <td><?= Sanitizer::e($b['imported_by_name']) ?></td>
                        <td class="small text-muted"><?= date('d M Y, h:i A', strtotime($b['created_at'])) ?></td>
                        <td class="text-center fw-bold"><?= $b['total_rows'] ?></td>
                        <td class="text-center"><span class="badge bg-success-subtle text-success"><?= $b['imported_rows'] ?></span></td>
                        <td class="text-center"><span class="badge bg-warning-subtle text-dark"><?= $b['skipped_rows'] ?></span></td>
                        <td class="text-center"><span class="badge bg-danger-subtle text-danger"><?= $b['failed_rows'] ?></span></td>
                        <td>
                            <span class="badge bg-<?= $b['status'] === 'Completed' ? 'success' : 'danger' ?>">
                                <?= $b['status'] ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= View::url('import/history&id=' . $b['id']) ?>" class="btn btn-sm btn-light border py-1 px-2">
                                View Log &rarr;
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
