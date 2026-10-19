<?php
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Counselors & Staff Management';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">Staff & Admission Counselors</h3>
        <p class="text-muted small mb-0">Role-based user management with secure password hashing and workload tracking.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <button type="button" class="btn btn-navy shadow-sm" data-bs-toggle="modal" data-bs-target="#newUserModal">
            <i data-lucide="user-plus" class="me-1" style="width: 16px; height: 16px;"></i> Add New Staff Member
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Staff Name & Email</th>
                    <th>Role</th>
                    <th>Designation</th>
                    <th>Phone</th>
                    <th class="text-center">Active Leads</th>
                    <th class="text-center">Pending Tasks</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): 
                    $stats = $userStats[$u['id']] ?? ['active_leads' => 0, 'pending_tasks' => 0];
                ?>
                <tr>
                    <td>
                        <div class="fw-bold text-navy"><?= Sanitizer::e($u['name']) ?></div>
                        <div class="small text-muted"><?= Sanitizer::e($u['email']) ?></div>
                    </td>
                    <td>
                        <span class="badge bg-navy-subtle text-navy"><?= Sanitizer::e($u['role_name']) ?></span>
                    </td>
                    <td><?= Sanitizer::e($u['designation'] ?: 'Counselor') ?></td>
                    <td><?= Sanitizer::e($u['phone'] ?: '—') ?></td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border"><?= $stats['active_leads'] ?></span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border"><?= $stats['pending_tasks'] ?></span>
                    </td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Deactivated</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id'] ?>">
                            Edit
                        </button>

                        <?php if ($u['is_active']): ?>
                            <?php if ($stats['active_leads'] > 0 || $stats['pending_tasks'] > 0): ?>
                                <!-- Trigger Reassign Warning Modal -->
                                <button type="button" class="btn btn-sm btn-outline-warning py-1 px-2 ms-1" data-bs-toggle="modal" data-bs-target="#deactivateWarningModal<?= $u['id'] ?>">
                                    Deactivate
                                </button>
                            <?php else: ?>
                                <a href="<?= View::url('users/toggle-status&id=' . $u['id']) ?>" 
                                   class="btn btn-sm btn-outline-danger py-1 px-2 ms-1"
                                   onclick="return confirm('Deactivate account for <?= Sanitizer::e($u['name']) ?>?');">
                                    Deactivate
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?= View::url('users/toggle-status&id=' . $u['id']) ?>" class="btn btn-sm btn-outline-success py-1 px-2 ms-1">
                                Reactivate
                            </a>
                        <?php endif; ?>

                        <!-- Edit User Modal -->
                        <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1">
                            <div class="modal-dialog text-start">
                                <div class="modal-content">
                                    <form action="<?= View::url('users/update') ?>" method="POST">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">

                                        <div class="modal-header">
                                            <h6 class="modal-title fw-bold text-navy">Edit Staff: <?= Sanitizer::e($u['name']) ?></h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Full Name *</label>
                                                <input type="text" name="name" class="form-control" required value="<?= Sanitizer::e($u['name']) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Email Address *</label>
                                                <input type="email" name="email" class="form-control" required value="<?= Sanitizer::e($u['email']) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Role *</label>
                                                <select name="role_id" class="form-select" required>
                                                    <?php foreach ($roles as $r): ?>
                                                        <option value="<?= $r['id'] ?>" <?= $u['role_id'] == $r['id'] ? 'selected' : '' ?>>
                                                            <?= Sanitizer::e($r['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Designation</label>
                                                    <input type="text" name="designation" class="form-control" value="<?= Sanitizer::e($u['designation']) ?>">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Phone Number</label>
                                                    <input type="tel" name="phone" class="form-control" value="<?= Sanitizer::e($u['phone']) ?>">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">New Password (Leave blank to keep unchanged)</label>
                                                <input type="password" name="password" class="form-control" placeholder="••••••••">
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editActiveCheck<?= $u['id'] ?>" <?= $u['is_active'] ? 'checked' : '' ?>>
                                                <label class="form-check-label small fw-semibold" for="editActiveCheck<?= $u['id'] ?>">Account is Active</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-navy">Save Updates</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Deactivation Reassignment Modal -->
                        <?php if ($stats['active_leads'] > 0 || $stats['pending_tasks'] > 0): ?>
                        <div class="modal fade" id="deactivateWarningModal<?= $u['id'] ?>" tabindex="-1">
                            <div class="modal-dialog text-start">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h6 class="modal-title fw-bold text-danger d-flex align-items-center">
                                            <i data-lucide="alert-triangle" class="me-1" style="width: 18px; height: 18px;"></i>
                                            Reassignment Required Before Deactivation
                                        </h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="small text-muted mb-3">
                                            <strong><?= Sanitizer::e($u['name']) ?></strong> currently has <strong><?= $stats['active_leads'] ?> active lead(s)</strong> and <strong><?= $stats['pending_tasks'] ?> pending task(s)</strong>.
                                            To ensure zero lead abandonment, select another counselor to receive these active records:
                                        </p>
                                        <form action="<?= View::url('users/toggle-status') ?>" method="GET">
                                            <input type="hidden" name="route" value="users/toggle-status">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">

                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Reassign All Active Records To *</label>
                                                <select name="reassign_to" class="form-select" required>
                                                    <option value="">-- Choose Target Counselor --</option>
                                                    <?php foreach ($users as $other): ?>
                                                        <?php if ($other['id'] != $u['id'] && $other['is_active']): ?>
                                                            <option value="<?= $other['id'] ?>"><?= Sanitizer::e($other['name']) ?> (<?= Sanitizer::e($other['designation'] ?: 'Counselor') ?>)</option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="d-flex justify-content-end gap-2">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Reassign & Deactivate</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: New Staff Member -->
<div class="modal fade" id="newUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= View::url('users/store') ?>" method="POST">
                <?= Csrf::field() ?>

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-navy">Add Staff / Counselor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Full Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Shalini Roy">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Official Email *</label>
                        <input type="email" name="email" class="form-control" required placeholder="shalini@gdgoenkapurnea.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">System Role *</label>
                        <select name="role_id" class="form-select" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= $r['slug'] === 'employee' ? 'selected' : '' ?>><?= Sanitizer::e($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Designation</label>
                            <input type="text" name="designation" class="form-control" placeholder="Admission Counselor" value="Admission Counselor">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" placeholder="98XXXXXXXX">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Initial Password *</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••" value="Welcome@123">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="newUserActiveCheck" checked>
                        <label class="form-check-label small fw-semibold" for="newUserActiveCheck">Account active immediately</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
