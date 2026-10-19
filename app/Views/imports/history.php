<?php
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Import Batch Log #' . ($batch['id'] ?? '');
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">Import Batch Result</h3>
        <p class="text-muted small mb-0">Detailed summary of uploaded leads, skipped duplicates, and validation errors.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <a href="<?= View::url('leads') ?>" class="btn btn-navy shadow-sm me-2">
            <i data-lucide="users" class="me-1" style="width: 16px; height: 16px;"></i> View Leads
        </a>
        <a href="<?= View::url('import') ?>" class="btn btn-outline-secondary shadow-sm">
            &larr; Import Another File
        </a>
    </div>
</div>

<?php if ($batch): ?>
<!-- Summary Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small fw-semibold">File Name</div>
            <div class="fw-bold text-navy text-truncate"><?= Sanitizer::e($batch['file_name']) ?></div>
            <div class="small text-muted mt-1"><?= date('d M Y, h:i A', strtotime($batch['created_at'])) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small fw-semibold">Successfully Imported</div>
            <div class="fs-4 fw-bold text-success"><?= $batch['imported_rows'] ?></div>
            <div class="small text-muted mt-1">Committed to database</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small fw-semibold">Duplicates Skipped</div>
            <div class="fs-4 fw-bold text-warning"><?= $batch['skipped_rows'] ?></div>
            <div class="small text-muted mt-1">Existing session phone numbers</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small fw-semibold">Failed / Invalid Rows</div>
            <div class="fs-4 fw-bold text-danger"><?= $batch['failed_rows'] ?></div>
            <div class="small text-muted mt-1">Missing required data</div>
        </div>
    </div>
</div>

<!-- Row Errors Table -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold text-navy mb-0">Row-level Error & Skipped Log (<?= count($errors) ?> items)</h6>
    </div>
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 80px;">Row #</th>
                    <th>Reason / Error Message</th>
                    <th>Raw Row Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($errors)): ?>
                    <tr><td colspan="3" class="text-center py-4 text-success fw-semibold">Zero row errors! All valid records processed cleanly.</td></tr>
                <?php else: ?>
                    <?php foreach ($errors as $err): ?>
                    <tr>
                        <td class="fw-bold text-navy">Row <?= $err['row_number'] ?></td>
                        <td class="text-danger fw-semibold small"><?= Sanitizer::e($err['error_message']) ?></td>
                        <td>
                            <?php if ($err['raw_data']): ?>
                                <pre class="mb-0 bg-light p-1 rounded small" style="max-height: 80px; overflow-y: auto; font-size: 0.72rem;"><?= Sanitizer::e($err['raw_data']) ?></pre>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Previous Batches -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold text-navy mb-0">All Previous Import Batches</h6>
    </div>
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Batch</th>
                    <th>File</th>
                    <th>Imported By</th>
                    <th>Date</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Imported</th>
                    <th class="text-center">Skipped</th>
                    <th class="text-center">Failed</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allBatches as $ab): ?>
                <tr>
                    <td class="fw-bold text-navy">#<?= $ab['id'] ?></td>
                    <td class="fw-semibold"><?= Sanitizer::e($ab['file_name']) ?></td>
                    <td><?= Sanitizer::e($ab['imported_by_name']) ?></td>
                    <td class="small text-muted"><?= date('d M Y, h:i A', strtotime($ab['created_at'])) ?></td>
                    <td class="text-center"><?= $ab['total_rows'] ?></td>
                    <td class="text-center text-success fw-bold"><?= $ab['imported_rows'] ?></td>
                    <td class="text-center text-warning"><?= $ab['skipped_rows'] ?></td>
                    <td class="text-center text-danger"><?= $ab['failed_rows'] ?></td>
                    <td><span class="badge bg-<?= $ab['status'] === 'Completed' ? 'success' : 'danger' ?>"><?= $ab['status'] ?></span></td>
                    <td class="text-end">
                        <a href="<?= View::url('import/history&id=' . $ab['id']) ?>" class="btn btn-sm btn-outline-navy py-0 px-2">
                            View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
