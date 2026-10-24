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
            <div class="fs-4 fw-bold text-danger" id="batchFailedRowsCount"><?= $batch['failed_rows'] ?></div>
            <div class="small text-muted mt-1">Missing required data</div>
        </div>
    </div>
</div>

<!-- Row Errors Table with Select & Delete Actions -->
<div class="card border-0 shadow-sm mb-4" id="errorLogCard">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h6 class="fw-bold text-navy mb-0">
                Row-level Error & Skipped Log (<span id="totalErrorsCount"><?= count($errors) ?></span> items)
            </h6>
            <span id="selectedErrorsBadge" class="badge bg-primary rounded-pill d-none px-2.5 py-1" style="font-size: 0.72rem;">
                <span id="selectedErrorsNum">0</span> selected
            </span>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Search Filter Input -->
            <div class="position-relative" style="width: 220px;">
                <input type="text" id="filterErrorsInput" class="form-control form-control-sm ps-4" placeholder="Search error log..." style="font-size: 0.78rem;">
                <i data-lucide="search" class="position-absolute text-muted" style="width: 14px; height: 14px; left: 10px; top: 50%; transform: translateY(-50%);"></i>
            </div>

            <!-- Delete Selected Button -->
            <button type="button" id="btnDeleteSelectedErrors" class="btn btn-sm btn-danger rounded-pill px-3 py-1 shadow-sm d-none d-inline-flex align-items-center gap-1.5" style="font-size: 0.8rem; font-weight: 600;">
                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                <span>Delete Selected</span>
            </button>

            <!-- Clear All Errors Button -->
            <button type="button" id="btnClearAllErrors" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1.5 <?= empty($errors) ? 'd-none' : '' ?>" style="font-size: 0.8rem; font-weight: 600;">
                <i data-lucide="trash" style="width: 14px; height: 14px;"></i>
                <span>Clear All Log</span>
            </button>
        </div>
    </div>

    <!-- Scrollable container for fast browsing across all items -->
    <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
        <table class="table custom-table table-hover align-middle mb-0" id="errorsTable">
            <thead class="sticky-top bg-white border-bottom shadow-sm" style="z-index: 5;">
                <tr>
                    <th style="width: 44px;" class="text-center">
                        <input type="checkbox" id="selectAllErrors" class="form-check-input" title="Select All Rows">
                    </th>
                    <th style="width: 90px;">Row #</th>
                    <th>Reason / Error Message</th>
                    <th>Raw Row Data</th>
                    <th style="width: 70px;" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody id="errorsTableBody">
                <?php if (empty($errors)): ?>
                    <tr id="noErrorsRow"><td colspan="5" class="text-center py-4 text-success fw-semibold">Zero row errors! All valid records processed cleanly.</td></tr>
                <?php else: ?>
                    <?php foreach ($errors as $err): ?>
                    <tr id="error-row-<?= $err['id'] ?>" data-id="<?= $err['id'] ?>">
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input error-row-cb" value="<?= $err['id'] ?>">
                        </td>
                        <td class="fw-bold text-navy">Row <?= $err['row_number'] ?></td>
                        <td class="text-danger fw-semibold small error-message-cell"><?= Sanitizer::e($err['error_message']) ?></td>
                        <td class="raw-data-cell">
                            <?php if ($err['raw_data']): ?>
                                <pre class="mb-0 bg-light p-1 rounded small" style="max-height: 80px; overflow-y: auto; font-size: 0.72rem;"><?= Sanitizer::e($err['raw_data']) ?></pre>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-single-error p-1" data-id="<?= $err['id'] ?>" title="Delete this error record" style="width: 28px; height: 28px; border-radius: 8px;">
                                <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function() {
    const batchId = <?= (int)($batch['id'] ?? 0) ?>;
    const csrfToken = window.CSRF_TOKEN || '';

    const selectAllCb = document.getElementById('selectAllErrors');
    const deleteSelectedBtn = document.getElementById('btnDeleteSelectedErrors');
    const selectedBadge = document.getElementById('selectedErrorsBadge');
    const selectedNum = document.getElementById('selectedErrorsNum');
    const clearAllBtn = document.getElementById('btnClearAllErrors');
    const totalCountEl = document.getElementById('totalErrorsCount');
    const batchFailedCountEl = document.getElementById('batchFailedRowsCount');
    const tableBody = document.getElementById('errorsTableBody');
    const searchInput = document.getElementById('filterErrorsInput');

    function getSelectedCheckboxes() {
        return Array.from(document.querySelectorAll('.error-row-cb:checked'));
    }

    function updateSelectionState() {
        const selected = getSelectedCheckboxes();
        const totalCbs = document.querySelectorAll('.error-row-cb');
        const count = selected.length;

        if (selectedNum) selectedNum.textContent = count;

        if (count > 0) {
            if (deleteSelectedBtn) deleteSelectedBtn.classList.remove('d-none');
            if (selectedBadge) selectedBadge.classList.remove('d-none');
        } else {
            if (deleteSelectedBtn) deleteSelectedBtn.classList.add('d-none');
            if (selectedBadge) selectedBadge.classList.add('d-none');
        }

        if (selectAllCb) {
            selectAllCb.checked = (totalCbs.length > 0 && count === totalCbs.length);
            selectAllCb.indeterminate = (count > 0 && count < totalCbs.length);
        }
    }

    function updateTotalCounts(newTotal) {
        if (totalCountEl) totalCountEl.textContent = newTotal;
        if (batchFailedCountEl) batchFailedCountEl.textContent = newTotal;

        if (newTotal === 0) {
            if (clearAllBtn) clearAllBtn.classList.add('d-none');
            if (deleteSelectedBtn) deleteSelectedBtn.classList.add('d-none');
            if (selectedBadge) selectedBadge.classList.add('d-none');
            if (selectAllCb) selectAllCb.checked = false;
            if (tableBody) {
                tableBody.innerHTML = '<tr id="noErrorsRow"><td colspan="5" class="text-center py-4 text-success fw-semibold">Zero row errors! All valid records processed cleanly.</td></tr>';
            }
        }
    }

    // 1. Select All Checkbox Handler
    if (selectAllCb) {
        selectAllCb.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.error-row-cb').forEach(cb => {
                const tr = cb.closest('tr');
                if (tr && tr.style.display !== 'none') {
                    cb.checked = isChecked;
                }
            });
            updateSelectionState();
        });
    }

    // 2. Individual Checkbox Delegation
    if (tableBody) {
        tableBody.addEventListener('change', function(e) {
            if (e.target.classList.contains('error-row-cb')) {
                updateSelectionState();
            }
        });
    }

    // 3. Search Filter through items
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#errorsTableBody tr:not(#noErrorsRow)');
            rows.forEach(r => {
                const text = r.textContent.toLowerCase();
                r.style.display = text.includes(q) ? '' : 'none';
            });
            updateSelectionState();
        });
    }

    // 4. Delete Single Error Handler
    if (tableBody) {
        tableBody.addEventListener('click', async function(e) {
            const btn = e.target.closest('.btn-delete-single-error');
            if (!btn) return;

            const errorId = btn.getAttribute('data-id');
            const row = document.getElementById('error-row-' + errorId);
            if (!errorId || !row) return;

            if (!confirm('Are you sure you want to delete this error log entry?')) {
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm text-danger" style="width: 12px; height: 12px;"></span>';

            try {
                const formData = new FormData();
                formData.append('id', errorId);
                formData.append('batch_id', batchId);
                formData.append('_csrf_token', csrfToken);

                const res = await fetch(getAppUrl('import/delete-error'), {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    row.style.transition = 'opacity 0.25s, transform 0.25s';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(10px)';
                    setTimeout(() => {
                        row.remove();
                        const remaining = document.querySelectorAll('.error-row-cb').length;
                        updateTotalCounts(remaining);
                        updateSelectionState();
                    }, 260);

                    if (typeof crmToast === 'function') {
                        crmToast('Error log record deleted.', 'success');
                    }
                } else {
                    alert(data.error || 'Failed to delete record.');
                    btn.disabled = false;
                    btn.innerHTML = '<i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            } catch (err) {
                console.error(err);
                alert('Network error while deleting error log.');
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        });
    }

    // 5. Delete Selected Errors Handler
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', async function() {
            const selected = getSelectedCheckboxes();
            if (selected.length === 0) return;

            const ids = selected.map(cb => cb.value);
            if (!confirm(`Are you sure you want to delete ${ids.length} selected error log entries?`)) {
                return;
            }

            deleteSelectedBtn.disabled = true;
            deleteSelectedBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width: 14px; height: 14px;"></span> Deleting...';

            try {
                const formData = new FormData();
                formData.append('ids', ids.join(','));
                formData.append('batch_id', batchId);
                formData.append('_csrf_token', csrfToken);

                const res = await fetch(getAppUrl('import/delete-errors'), {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    ids.forEach(id => {
                        const row = document.getElementById('error-row-' + id);
                        if (row) {
                            row.remove();
                        }
                    });

                    const remaining = document.querySelectorAll('.error-row-cb').length;
                    updateTotalCounts(remaining);
                    updateSelectionState();

                    if (typeof crmToast === 'function') {
                        crmToast(`${data.deleted_count || ids.length} error logs deleted successfully.`, 'success');
                    }
                } else {
                    alert(data.error || 'Failed to delete selected error logs.');
                }
            } catch (err) {
                console.error(err);
                alert('Network error while deleting error logs.');
            } finally {
                deleteSelectedBtn.disabled = false;
                deleteSelectedBtn.innerHTML = '<i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> <span>Delete Selected</span>';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        });
    }

    // 6. Clear All Errors Handler
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', async function() {
            const total = parseInt(totalCountEl ? totalCountEl.textContent : '0', 10);
            if (total === 0) return;

            if (!confirm(`Warning: Are you sure you want to delete ALL ${total} error log entries for this batch? This action cannot be undone.`)) {
                return;
            }

            clearAllBtn.disabled = true;
            clearAllBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width: 14px; height: 14px;"></span> Clearing...';

            try {
                const formData = new FormData();
                formData.append('batch_id', batchId);
                formData.append('_csrf_token', csrfToken);

                const res = await fetch(getAppUrl('import/clear-errors'), {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    updateTotalCounts(0);
                    updateSelectionState();

                    if (typeof crmToast === 'function') {
                        crmToast('All error logs cleared successfully.', 'success');
                    }
                } else {
                    alert(data.error || 'Failed to clear error logs.');
                }
            } catch (err) {
                console.error(err);
                alert('Network error while clearing error logs.');
            } finally {
                clearAllBtn.disabled = false;
                clearAllBtn.innerHTML = '<i data-lucide="trash" style="width: 14px; height: 14px;"></i> <span>Clear All Log</span>';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        });
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
})();
</script>
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
