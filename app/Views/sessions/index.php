<?php
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Academic Sessions Management';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">Academic Sessions</h3>
        <p class="text-muted small mb-0">Dynamic session administration. Switching active sessions never changes historical lead records.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <button type="button" class="btn btn-navy shadow-sm" data-bs-toggle="modal" data-bs-target="#newSessionModal">
            <i data-lucide="calendar-plus" class="me-1" style="width: 16px; height: 16px;"></i> Add Academic Session
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Session Label</th>
                    <th>Term Dates</th>
                    <th>Admission Window</th>
                    <th>Current Status</th>
                    <th>Notes</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $s): ?>
                <tr>
                    <td>
                        <div class="fw-bold text-navy fs-6"><?= Sanitizer::e($s['session_label']) ?></div>
                        <?php if (!empty($s['is_active'])): ?>
                            <span class="badge bg-success" style="font-size: 0.72rem;">Default Active Admission Session</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="small fw-semibold"><?= date('d M Y', strtotime($s['start_date'])) ?> &rarr; <?= date('d M Y', strtotime($s['end_date'])) ?></div>
                    </td>
                    <td>
                        <?php if ($s['admission_open_date'] && $s['admission_close_date']): ?>
                            <div class="small text-muted"><?= date('d M Y', strtotime($s['admission_open_date'])) ?> to <?= date('d M Y', strtotime($s['admission_close_date'])) ?></div>
                        <?php else: ?>
                            <span class="text-muted small">Open</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($s['is_archived'])): ?>
                            <span class="badge bg-secondary">Archived</span>
                        <?php elseif (!empty($s['is_active'])): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark border">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= Sanitizer::e($s['notes'] ?? '—') ?></td>
                    <td class="text-end">
                        <?php if (empty($s['is_active']) && empty($s['is_archived'])): ?>
                            <a href="<?= View::url('sessions/activate&id=' . $s['id']) ?>" 
                               class="btn btn-sm btn-outline-success py-1 px-2 me-1"
                               onclick="return confirm('Set <?= Sanitizer::e($s['session_label']) ?> as the default active admission session? Existing lead sessions will NOT be modified.');">
                                Set Active
                            </a>
                        <?php endif; ?>

                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" data-bs-toggle="modal" data-bs-target="#editSessionModal<?= $s['id'] ?>">
                            Edit
                        </button>

                        <?php if (empty($s['is_active']) && empty($s['is_archived'])): ?>
                            <a href="<?= View::url('sessions/archive&id=' . $s['id']) ?>" 
                               class="btn btn-sm btn-outline-danger py-1 px-2 ms-1"
                               onclick="return confirm('Archive this older session?');">
                                Archive
                            </a>
                        <?php endif; ?>

                        <!-- Edit Session Modal -->
                        <div class="modal fade" id="editSessionModal<?= $s['id'] ?>" tabindex="-1">
                            <div class="modal-dialog text-start">
                                <div class="modal-content">
                                    <form action="<?= View::url('sessions/update') ?>" method="POST">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">

                                        <div class="modal-header">
                                            <h6 class="modal-title fw-bold text-navy">Edit Session: <?= Sanitizer::e($s['session_label']) ?></h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Session Label *</label>
                                                <input type="text" name="session_label" class="form-control" required value="<?= Sanitizer::e($s['session_label']) ?>">
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Start Date *</label>
                                                    <input type="date" name="start_date" class="form-control" required value="<?= $s['start_date'] ?>">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">End Date *</label>
                                                    <input type="date" name="end_date" class="form-control" required value="<?= $s['end_date'] ?>">
                                                </div>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Admission Open Date</label>
                                                    <input type="date" name="admission_open_date" class="form-control" value="<?= $s['admission_open_date'] ?>">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Admission Close Date</label>
                                                    <input type="date" name="admission_close_date" class="form-control" value="<?= $s['admission_close_date'] ?>">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Notes</label>
                                                <textarea name="notes" class="form-control" rows="2"><?= Sanitizer::e($s['notes']) ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-navy">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: New Session -->
<div class="modal fade" id="newSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= View::url('sessions/store') ?>" method="POST">
                <?= Csrf::field() ?>

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-navy">Add Academic Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Session Label *</label>
                        <input type="text" name="session_label" class="form-control" required placeholder="e.g. 2028–29">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Term Start Date *</label>
                            <input type="date" name="start_date" class="form-control" required value="<?= date('Y') + 1 ?>-04-01">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Term End Date *</label>
                            <input type="date" name="end_date" class="form-control" required value="<?= date('Y') + 2 ?>-03-31">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Admission Open Date</label>
                            <input type="date" name="admission_open_date" class="form-control" value="<?= date('Y') ?>-10-01">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Admission Close Date</label>
                            <input type="date" name="admission_close_date" class="form-control" value="<?= date('Y') + 1 ?>-07-31">
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="newSessionActiveCheck">
                        <label class="form-check-label small fw-semibold" for="newSessionActiveCheck">
                            Set as default active admission session immediately
                        </label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Session comments or intake targets..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy">Create Session</button>
                </div>
            </form>
        </div>
    </div>
</div>
