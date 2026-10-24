<?php
use App\Helpers\Auth;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Follow-up & Interaction Logs';

$outcomesList = [
    'Interested' => ['bg' => 'success-subtle text-success', 'border' => 'border-success'],
    'Visit Scheduled' => ['bg' => 'info-subtle text-info', 'border' => 'border-info'],
    'Callback Requested' => ['bg' => 'warning-subtle text-warning', 'border' => 'border-warning'],
    'Documents Requested' => ['bg' => 'primary-subtle text-primary', 'border' => 'border-primary'],
    'Admission Confirmed' => ['bg' => 'success text-white', 'border' => ''],
    'Not Interested' => ['bg' => 'danger-subtle text-danger', 'border' => 'border-danger'],
    'Lost / Unreachable' => ['bg' => 'secondary-subtle text-secondary', 'border' => 'border-secondary'],
    'Other' => ['bg' => 'light text-dark', 'border' => 'border']
];
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">
            Follow-ups &amp; Call Logs
            <span class="badge bg-navy-subtle text-navy ms-2 fs-6 fw-semibold rounded-pill px-3"><?= count($logs) ?> <?= count($logs) === 1 ? 'Log' : 'Logs' ?></span>
        </h3>
        <p class="text-muted small mb-0">Verified interaction history for all active admission enquiries and leads.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <a href="<?= View::url('leads') ?>" class="btn btn-navy shadow-sm">
            <i data-lucide="users" class="me-1" style="width: 16px; height: 16px;"></i> Browse Leads
        </a>
    </div>
</div>

<!-- FILTER CARD -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?= View::url('followups') ?>" method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="followups">

            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Search Lead / Parent</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i data-lucide="search" style="width: 14px; height: 14px;"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Name, mobile, remarks..." value="<?= Sanitizer::e($filters['search'] ?? '') ?>">
                </div>
            </div>

            <?php if (Auth::isAdmin()): ?>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Counselor</label>
                <select name="assigned_to" class="form-select form-select-sm">
                    <option value="">All Counselors</option>
                    <?php foreach ($counselors as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($filters['assigned_to'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= Sanitizer::e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Call Outcome</label>
                <select name="outcome" class="form-select form-select-sm">
                    <option value="">All Outcomes</option>
                    <?php foreach (array_keys($outcomesList) as $outcomeName): ?>
                        <option value="<?= $outcomeName ?>" <?= ($filters['outcome'] ?? '') === $outcomeName ? 'selected' : '' ?>><?= $outcomeName ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-navy flex-fill">
                    <i data-lucide="filter" style="width: 14px; height: 14px;" class="me-1"></i> Filter
                </button>
                <?php if (!empty($filters['search']) || !empty($filters['outcome']) || !empty($filters['assigned_to'])): ?>
                    <a href="<?= View::url('followups') ?>" class="btn btn-sm btn-light border" title="Reset Filters">
                        <i data-lucide="rotate-ccw" style="width: 14px; height: 14px;"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- CALL LOGS TABLE -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="min-width: 130px;">Date &amp; Time</th>
                    <th style="min-width: 220px;">Enquiry / Student</th>
                    <th style="min-width: 120px;">Method</th>
                    <th style="min-width: 150px;">Call Outcome</th>
                    <th style="min-width: 260px;">Discussion Remarks</th>
                    <th style="min-width: 160px;">Next Follow-up</th>
                    <th style="min-width: 130px;">Counselor</th>
                    <th class="text-end" style="min-width: 90px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="mb-2"><i data-lucide="phone-off" style="width: 40px; height: 40px;" class="text-secondary opacity-50"></i></div>
                            <div class="fw-semibold">No follow-up interactions found.</div>
                            <div class="small text-muted mt-1">Logs appear here as counselors record parent interactions on leads.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): 
                        $outcomeConfig = $outcomesList[$log['outcome']] ?? ['bg' => 'light text-dark', 'border' => 'border'];
                        $method = !empty($log['contact_method']) ? $log['contact_method'] : 'Phone';
                    ?>
                    <tr>
                        <td class="small text-muted">
                            <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($log['created_at'])) ?></div>
                            <div class="text-muted" style="font-size: 0.76rem;"><?= date('h:i A', strtotime($log['created_at'])) ?></div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div>
                                    <a href="<?= View::url('leads/show&id=' . $log['lead_id']) ?>" class="fw-bold text-navy text-decoration-none">
                                        <?= Sanitizer::e($log['student_name']) ?>
                                    </a>
                                    <div class="small text-muted">
                                        <span class="badge bg-light text-muted border me-1" style="font-size: 0.7rem;"><?= Sanitizer::e($log['lead_ref']) ?></span>
                                        <?= Sanitizer::e($log['parent_name']) ?>
                                    </div>
                                    <div class="mt-1">
                                        <a href="tel:<?= Sanitizer::e($log['mobile']) ?>" class="small text-decoration-none text-navy fw-medium">
                                            <i data-lucide="phone" style="width: 12px; height: 12px;" class="me-1"></i><?= Sanitizer::e($log['mobile']) ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1">
                                <?php if ($method === 'Phone'): ?>
                                    <i data-lucide="phone" style="width: 12px; height: 12px;" class="me-1 text-primary"></i> Phone
                                <?php elseif ($method === 'WhatsApp'): ?>
                                    <i data-lucide="message-square" style="width: 12px; height: 12px;" class="me-1 text-success"></i> WhatsApp
                                <?php elseif ($method === 'In Person'): ?>
                                    <i data-lucide="user-check" style="width: 12px; height: 12px;" class="me-1 text-info"></i> Campus Visit
                                <?php elseif ($method === 'Email'): ?>
                                    <i data-lucide="mail" style="width: 12px; height: 12px;" class="me-1 text-warning"></i> Email
                                <?php else: ?>
                                    <?= Sanitizer::e($method) ?>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $outcomeConfig['bg'] ?> <?= $outcomeConfig['border'] ?> px-2 py-1">
                                <?= Sanitizer::e(!empty($log['outcome']) ? $log['outcome'] : 'Logged') ?>
                            </span>
                        </td>
                        <td>
                            <div class="small text-dark text-break" style="max-width: 340px; line-height: 1.45;">
                                <?= nl2br(Sanitizer::e($log['remarks'] ?: '—')) ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($log['next_followup_date']): 
                                $isOverdue = strtotime($log['next_followup_date']) < time();
                            ?>
                                <span class="small fw-semibold <?= $isOverdue ? 'text-danger' : 'text-navy' ?>">
                                    <i data-lucide="calendar" style="width: 12px; height: 12px;" class="me-1"></i>
                                    <?= date('d M Y, h:i A', strtotime($log['next_followup_date'])) ?>
                                </span>
                                <?php if ($isOverdue): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger ms-1" style="font-size: 0.65rem;">Due</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <div class="fw-semibold text-navy"><?= Sanitizer::e($log['author_name']) ?></div>
                            <?php if (!empty($log['assigned_to_name']) && $log['assigned_to_name'] !== $log['author_name']): ?>
                                <div class="text-muted" style="font-size: 0.72rem;">Assignee: <?= Sanitizer::e($log['assigned_to_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= View::url('leads/show&id=' . $log['lead_id']) ?>" class="btn btn-sm btn-outline-navy py-1 px-2 shadow-sm" title="View Lead Details">
                                View &rarr;
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
