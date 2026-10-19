<?php
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Security & Activity Audit Logs';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">Audit Trail & Security Logs</h3>
        <p class="text-muted small mb-0">Immutable tracking of user logins, lead assignments, status updates, and administrative modifications.</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity Type</th>
                    <th>Record ID</th>
                    <th>IP Address</th>
                    <th>Audit Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No audit logs recorded.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $al): ?>
                    <tr>
                        <td class="small text-muted"><?= date('d M Y, h:i:s A', strtotime($al['created_at'])) ?></td>
                        <td>
                            <div class="fw-semibold text-navy"><?= Sanitizer::e($al['user_name'] ?? 'System / Anonymous') ?></div>
                            <div class="small text-muted"><?= Sanitizer::e($al['user_email'] ?? '') ?></div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border font-monospace"><?= Sanitizer::e($al['action']) ?></span>
                        </td>
                        <td><?= Sanitizer::e($al['entity_type']) ?></td>
                        <td class="fw-bold"><?= $al['entity_id'] ?: '—' ?></td>
                        <td class="small font-monospace"><?= Sanitizer::e($al['ip_address'] ?? '127.0.0.1') ?></td>
                        <td>
                            <?php if ($al['new_values']): ?>
                                <pre class="mb-0 bg-light p-1 rounded small" style="max-height: 80px; overflow-y: auto; font-size: 0.72rem;"><?= Sanitizer::e($al['new_values']) ?></pre>
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

    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
        <div class="small text-muted">Page <?= $page ?> of <?= $totalPages ?> (<?= $totalRecords ?> total events)</div>
        <ul class="pagination pagination-sm mb-0">
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= View::url('audit&page=' . ($page - 1)) ?>">&laquo; Prev</a></li>
            <?php endif; ?>
            <li class="page-item active"><span class="page-link"><?= $page ?></span></li>
            <?php if ($page < $totalPages): ?>
                <li class="page-item"><a class="page-link" href="<?= View::url('audit&page=' . ($page + 1)) ?>">Next &raquo;</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
