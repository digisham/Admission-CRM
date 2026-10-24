<?php
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Tasks & Follow-up Actions';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">
            Tasks &amp; Action Items
            <span class="badge bg-navy-subtle text-navy ms-2 fs-6 fw-semibold rounded-pill px-3"><?= count($tasks) ?> <?= count($tasks) === 1 ? 'Task' : 'Tasks' ?></span>
        </h3>
        <p class="text-muted small mb-0">Track follow-up calls, campus visits, document pickups, and deadlines for active leads.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <div class="btn-group shadow-sm">
            <a href="<?= View::url('tasks') ?>" class="btn btn-sm <?= empty($filters['due_filter']) ? 'btn-navy' : 'btn-light border' ?>">All Tasks</a>
            <a href="<?= View::url('tasks&due_filter=today') ?>" class="btn btn-sm <?= ($filters['due_filter'] ?? '') === 'today' ? 'btn-navy' : 'btn-light border' ?>">Due Today</a>
            <a href="<?= View::url('tasks&due_filter=overdue') ?>" class="btn btn-sm <?= ($filters['due_filter'] ?? '') === 'overdue' ? 'btn-danger' : 'btn-light border' ?>">Overdue</a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?= View::url('tasks') ?>" method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="tasks">
            <?php if (!empty($filters['due_filter'])): ?>
                <input type="hidden" name="due_filter" value="<?= Sanitizer::e($filters['due_filter']) ?>">
            <?php endif; ?>

            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Search Task / Lead</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i data-lucide="search" style="width: 14px; height: 14px;"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Title, student, phone..." value="<?= Sanitizer::e($filters['search'] ?? '') ?>">
                </div>
            </div>

            <?php if (Auth::isAdmin()): ?>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Assignee</label>
                <select name="assigned_to" class="form-select form-select-sm">
                    <option value="">All Staff</option>
                    <?php foreach ($counselors as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($filters['assigned_to'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= Sanitizer::e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="<?= Auth::isAdmin() ? 'col-md-2' : 'col-md-3' ?>">
                <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?= ($filters['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="In Progress" <?= ($filters['status'] ?? '') === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Completed" <?= ($filters['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Cancelled" <?= ($filters['status'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <div class="<?= Auth::isAdmin() ? 'col-md-2' : 'col-md-3' ?>">
                <label class="form-label small fw-semibold text-muted mb-1">Priority</label>
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All Priorities</option>
                    <option value="Low" <?= ($filters['priority'] ?? '') === 'Low' ? 'selected' : '' ?>>Low</option>
                    <option value="Medium" <?= ($filters['priority'] ?? '') === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="High" <?= ($filters['priority'] ?? '') === 'High' ? 'selected' : '' ?>>High</option>
                    <option value="Urgent" <?= ($filters['priority'] ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                </select>
            </div>

            <div class="<?= Auth::isAdmin() ? 'col-md-3' : 'col-md-3' ?> d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-navy flex-fill">
                    <i data-lucide="filter" style="width: 14px; height: 14px;" class="me-1"></i> Filter
                </button>
                <?php if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['priority']) || !empty($filters['assigned_to']) || !empty($filters['due_filter'])): ?>
                    <a href="<?= View::url('tasks') ?>" class="btn btn-sm btn-light border" title="Reset Filters">
                        <i data-lucide="rotate-ccw" style="width: 14px; height: 14px;"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="min-width: 220px;">Task Title</th>
                    <th style="min-width: 190px;">Related Enquiry</th>
                    <th style="min-width: 130px;">Contact</th>
                    <th style="min-width: 120px;">Type</th>
                    <th style="min-width: 130px;">Assignee</th>
                    <th style="min-width: 150px;">Due Date</th>
                    <th style="min-width: 90px;">Priority</th>
                    <th style="min-width: 100px;">Status</th>
                    <th class="text-end" style="min-width: 90px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <div class="mb-2"><i data-lucide="check-circle-2" style="width: 40px; height: 40px;" class="text-secondary opacity-50"></i></div>
                            <div class="fw-semibold">No action tasks found matching your filter criteria.</div>
                            <div class="small text-muted mt-1">All action items and scheduled calls for active leads appear here.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-navy"><?= Sanitizer::e($task['title']) ?></div>
                            <?php if (!empty($task['description'])): ?>
                                <div class="small text-muted text-truncate" style="max-width: 220px;"><?= Sanitizer::e($task['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= View::url('leads/show&id=' . $task['lead_id']) ?>" class="fw-semibold text-navy text-decoration-none">
                                <?= Sanitizer::e($task['student_name']) ?>
                            </a>
                            <div class="small text-muted"><span class="badge bg-light text-muted border" style="font-size: 0.7rem;"><?= Sanitizer::e($task['lead_ref']) ?></span></div>
                        </td>
                        <td>
                            <a href="tel:<?= Sanitizer::e($task['mobile']) ?>" class="small text-decoration-none text-navy fw-medium">
                                <i data-lucide="phone" style="width: 12px; height: 12px;" class="me-1"></i><?= Sanitizer::e($task['mobile']) ?>
                            </a>
                        </td>
                        <td><span class="badge bg-light text-secondary border"><?= $task['task_type'] ?></span></td>
                        <td>
                            <div class="fw-medium text-navy"><?= Sanitizer::e($task['assignee_name']) ?></div>
                            <?php if (!empty($task['creator_name']) && $task['creator_name'] !== $task['assignee_name']): ?>
                                <div class="text-muted" style="font-size: 0.72rem;">Created by: <?= Sanitizer::e($task['creator_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($task['is_overdue']): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger">
                                    <i data-lucide="alert-circle" style="width: 12px; height: 12px;" class="me-1"></i>Overdue: <?= date('d M, h:i A', strtotime($task['due_date'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="small fw-medium text-dark"><i data-lucide="clock" style="width: 12px; height: 12px;" class="me-1 text-muted"></i><?= date('d M, h:i A', strtotime($task['due_date'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge priority-badge-<?= $task['priority'] ?>"><?= $task['priority'] ?></span></td>
                        <td>
                            <span class="badge bg-<?= $task['status'] === 'Completed' ? 'success' : ($task['status'] === 'Cancelled' ? 'secondary' : 'warning') ?>">
                                <?= $task['status'] ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= View::url('leads/show&id=' . $task['lead_id']) ?>" class="btn btn-sm btn-outline-navy py-1 px-2 shadow-sm" title="View Lead Details">
                                Lead &rarr;
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
