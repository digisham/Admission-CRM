<?php
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Follow-up & Interaction Logs';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">Follow-ups & Call History</h3>
        <p class="text-muted small mb-0">Complete log of parent phone calls, WhatsApp messages, and campus counselling visits.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <a href="<?= View::url('leads') ?>" class="btn btn-navy shadow-sm">
            <i data-lucide="users" class="me-1" style="width: 16px; height: 16px;"></i> Browse Leads
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Enquiry / Student</th>
                    <th>Contact Method</th>
                    <th>Call Outcome</th>
                    <th>Discussion Remarks</th>
                    <th>Next Scheduled Follow-up</th>
                    <th>Counselor</th>
                    <th class="text-end">Lead</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">No follow-up interactions recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="small text-muted"><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></td>
                        <td>
                            <a href="<?= View::url('leads/show&id=' . $log['lead_id']) ?>" class="fw-bold text-navy text-decoration-none">
                                <?= Sanitizer::e($log['student_name']) ?>
                            </a>
                            <div class="small text-muted"><?= Sanitizer::e($log['parent_name']) ?> &bull; <?= Sanitizer::e($log['mobile']) ?></div>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= $log['contact_method'] ?></span></td>
                        <td>
                            <span class="badge bg-<?= in_array($log['outcome'], ['Interested', 'Visit Scheduled']) ? 'success-subtle text-success' : 'light text-dark border' ?>">
                                <?= Sanitizer::e($log['outcome']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="small text-dark" style="max-width: 300px;"><?= nl2br(Sanitizer::e($log['remarks'])) ?></div>
                        </td>
                        <td>
                            <?php if ($log['next_followup_date']): ?>
                                <span class="small fw-semibold text-navy">
                                    <?= date('d M Y, h:i A', strtotime($log['next_followup_date'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small fw-semibold"><?= Sanitizer::e($log['author_name']) ?></td>
                        <td class="text-end">
                            <a href="<?= View::url('leads/show&id=' . $log['lead_id']) ?>" class="btn btn-sm btn-outline-navy py-1 px-2">
                                Details &rarr;
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
