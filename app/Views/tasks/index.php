<?php
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Tasks & Follow-up Actions';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">Tasks & Action Items</h3>
        <p class="text-muted small mb-0">Track follow-up calls, campus visits, document pickups, and deadlines.</p>
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

            <?php if (Auth::isAdmin()): ?>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Assignee</label>
                <select name="assigned_to" class="form-select form-select-sm">
                    <option value="">All Staff</option>
                    <?php foreach ($counselors as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($filters['assigned_to'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= Sanitizer::e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?= ($filters['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="In Progress" <?= ($filters['status'] ?? '') === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Completed" <?= ($filters['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Cancelled" <?= ($filters['status'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Priority</label>
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All Priorities</option>
                    <option value="Low" <?= ($filters['priority'] ?? '') === 'Low' ? 'selected' : '' ?>>Low</option>
                    <option value="Medium" <?= ($filters['priority'] ?? '') === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="High" <?= ($filters['priority'] ?? '') === 'High' ? 'selected' : '' ?>>High</option>
                    <option value="Urgent" <?= ($filters['priority'] ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                </select>
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-navy w-100">Filter Tasks</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Task Title</th>
                    <th>Related Enquiry</th>
                    <th>Contact</th>
                    <th>Type</th>
                    <th>Assignee</th>
                    <th>Due Date</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">No tasks found matching your filter criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-navy"><?= Sanitizer::e($task['title']) ?></div>
                            <?php if (!empty($task['description'])): ?>
                                <div class="small text-muted text-truncate" style="max-width: 200px;"><?= Sanitizer::e($task['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= View::url('leads/show&id=' . $task['lead_id']) ?>" class="fw-semibold text-navy text-decoration-none">
                                <?= Sanitizer::e($task['student_name']) ?>
                            </a>
                            <div class="small text-muted"><?= Sanitizer::e($task['lead_ref']) ?></div>
                        </td>
                        <td>
                            <a href="tel:<?= Sanitizer::e($task['mobile']) ?>" class="small text-decoration-none">
                                <?= Sanitizer::e($task['mobile']) ?>
                            </a>
                        </td>
                        <td><span class="badge bg-light text-secondary border"><?= $task['task_type'] ?></span></td>
                        <td><?= Sanitizer::e($task['assignee_name']) ?></td>
                        <td>
                            <?php if ($task['is_overdue']): ?>
                                <span class="badge bg-danger">Overdue: <?= date('d M, h:i A', strtotime($task['due_date'])) ?></span>
                            <?php else: ?>
                                <span class="small"><?= date('d M, h:i A', strtotime($task['due_date'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge priority-badge-<?= $task['priority'] ?>"><?= $task['priority'] ?></span></td>
                        <td>
                            <span class="badge bg-<?= $task['status'] === 'Completed' ? 'success' : ($task['status'] === 'Cancelled' ? 'secondary' : 'warning') ?>">
                                <?= $task['status'] ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= View::url('leads/show&id=' . $task['lead_id']) ?>" class="btn btn-sm btn-outline-navy py-1 px-2">
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
