<?php
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$statusSlug = str_replace([' ', '/', '–', '-'], '-', $lead['status']);
$pageTitle = $lead['lead_ref'] . ' - ' . $lead['student_name'];
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <div class="d-flex align-items-center mb-1">
            <span class="badge bg-navy me-2 fs-6"><?= Sanitizer::e($lead['lead_ref']) ?></span>
            <span class="badge badge-lead-status badge-status-<?= $statusSlug ?> me-2">
                <?= Sanitizer::e($lead['status']) ?>
            </span>
            <span class="badge priority-badge-<?= $lead['priority'] ?>">
                <?= $lead['priority'] ?> Priority
            </span>
        </div>
        <h3 class="fw-bold text-navy mb-0"><?= Sanitizer::e($lead['student_name']) ?></h3>
        <div class="small text-muted">
            Applied for <strong><?= Sanitizer::e($lead['class_applied']) ?></strong> &bull; 
            Session: <span class="badge bg-light text-dark border"><?= Sanitizer::e($lead['session_label']) ?></span> &bull; 
            Parent: <strong><?= Sanitizer::e($lead['parent_name']) ?></strong>
        </div>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <button type="button" class="btn btn-outline-primary me-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#logCallModal">
            <i data-lucide="phone-call" class="me-1" style="width: 15px; height: 15px;"></i> Log Call
        </button>
        <button type="button" class="btn btn-outline-success me-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#createTaskModal">
            <i data-lucide="plus-circle" class="me-1" style="width: 15px; height: 15px;"></i> Add Task
        </button>
        <?php if (Auth::isAdmin()): ?>
        <button type="button" class="btn btn-outline-warning me-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#assignModal">
            <i data-lucide="user-check" class="me-1" style="width: 15px; height: 15px;"></i> Assign
        </button>
        <?php endif; ?>
        <a href="<?= View::url('leads/edit&id=' . $lead['id']) ?>" class="btn btn-navy shadow-sm">
            <i data-lucide="edit-3" class="me-1" style="width: 15px; height: 15px;"></i> Edit
        </a>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4 bg-white p-2 rounded-3 shadow-sm" id="leadTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active fw-semibold" id="overview-tab" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button">
            <i data-lucide="info" class="me-1" style="width: 16px; height: 16px;"></i> Overview
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="timeline-tab" data-bs-toggle="pill" data-bs-target="#tab-timeline" type="button">
            <i data-lucide="activity" class="me-1" style="width: 16px; height: 16px;"></i> Timeline (<?= count($timeline) ?>)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="tasks-tab" data-bs-toggle="pill" data-bs-target="#tab-tasks" type="button">
            <i data-lucide="check-square" class="me-1" style="width: 16px; height: 16px;"></i> Tasks (<?= count($tasks) ?>)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="documents-tab" data-bs-toggle="pill" data-bs-target="#tab-documents" type="button">
            <i data-lucide="file-check-2" class="me-1" style="width: 16px; height: 16px;"></i> Admission Documents (<?= count($checklist) ?>)
        </button>
    </li>
</ul>

<!-- Tabs Content -->
<div class="tab-content" id="leadTabsContent">
    <!-- TAB 1: OVERVIEW -->
    <div class="tab-pane fade show active" id="tab-overview">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold text-navy mb-0">Enquiry Information</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <span class="text-muted small">Student Full Name:</span>
                                <div class="fw-bold text-navy fs-6"><?= Sanitizer::e($lead['student_name']) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small">Parent / Guardian:</span>
                                <div class="fw-semibold"><?= Sanitizer::e($lead['parent_name']) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small">Primary Mobile:</span>
                                <div>
                                    <a href="tel:<?= Sanitizer::e($lead['mobile']) ?>" class="fw-bold text-decoration-none">
                                        <i data-lucide="phone" style="width: 14px; height: 14px;"></i> <?= Sanitizer::e($lead['mobile']) ?>
                                    </a>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small">Alternate Mobile:</span>
                                <div><?= $lead['alternate_mobile'] ? Sanitizer::e($lead['alternate_mobile']) : '<span class="text-muted">—</span>' ?></div>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small">Email Address:</span>
                                <div><?= $lead['email'] ? '<a href="mailto:' . Sanitizer::e($lead['email']) . '">' . Sanitizer::e($lead['email']) . '</a>' : '<span class="text-muted">—</span>' ?></div>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small">City / Locality:</span>
                                <div><?= Sanitizer::e($lead['city'] ?: 'Purnea') ?></div>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small">Residential Address:</span>
                                <div><?= $lead['address'] ? Sanitizer::e($lead['address']) : '<span class="text-muted">—</span>' ?></div>
                            </div>
                            <?php if (!empty($lead['remarks'])): ?>
                            <div class="col-12">
                                <span class="text-muted small">General Remarks / Notes:</span>
                                <div class="p-3 bg-light rounded mt-1"><?= nl2br(Sanitizer::e($lead['remarks'])) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold text-navy mb-0">Counselor & Pipeline</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <span class="text-muted small">Assigned Counselor:</span>
                            <div class="fw-bold fs-6 mt-1">
                                <?= $lead['assigned_name'] ? Sanitizer::e($lead['assigned_name']) : '<span class="badge bg-gold-subtle text-gold">Unassigned</span>' ?>
                            </div>
                            <?php if ($lead['assigned_email']): ?>
                                <div class="small text-muted"><?= Sanitizer::e($lead['assigned_email']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <span class="text-muted small">Enquiry Source:</span>
                            <div class="fw-semibold mt-1"><?= Sanitizer::e($lead['enquiry_source']) ?></div>
                        </div>

                        <div class="mb-3">
                            <span class="text-muted small">Enquiry Date:</span>
                            <div class="fw-semibold mt-1"><?= date('d F Y', strtotime($lead['enquiry_date'])) ?></div>
                        </div>

                        <div class="mb-3">
                            <span class="text-muted small">Next Follow-up Due:</span>
                            <div class="fw-semibold mt-1">
                                <?php if ($lead['next_followup_date']): ?>
                                    <span class="badge bg-light text-dark border p-2">
                                        <i data-lucide="clock" style="width: 14px; height: 14px;"></i> <?= date('d M Y, h:i A', strtotime($lead['next_followup_date'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">No scheduled follow-up</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr>
                        <div class="small text-muted">
                            Created: <?= date('d M Y, h:i A', strtotime($lead['created_at'])) ?><br>
                            Created By: <?= Sanitizer::e($lead['created_by_name'] ?? 'System') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: TIMELINE -->
    <div class="tab-pane fade" id="tab-timeline">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-navy mb-0">Chronological Activity History</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#logCallModal">
                    <i data-lucide="phone-call" class="me-1" style="width: 14px; height: 14px;"></i> Log New Interaction
                </button>
            </div>
            <div class="card-body p-4">
                <?php if (empty($timeline)): ?>
                    <div class="text-center py-5 text-muted">No timeline activities recorded yet.</div>
                <?php else: ?>
                    <div class="timeline-list">
                        <?php foreach ($timeline as $item): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon text-<?= $item['badge'] ?>">
                                <i data-lucide="<?= $item['icon'] ?>" style="width: 16px; height: 16px;"></i>
                            </div>
                            <div class="card border shadow-none bg-light p-3">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="fw-bold text-navy mb-0"><?= Sanitizer::e($item['title']) ?></h6>
                                    <span class="small text-muted"><?= date('d M Y, h:i A', strtotime($item['created_at'])) ?></span>
                                </div>
                                <div class="small text-dark mb-2"><?= nl2br(Sanitizer::e($item['description'])) ?></div>
                                <div class="small text-muted" style="font-size: 0.75rem;">
                                    Recorded by <strong><?= Sanitizer::e($item['author']) ?></strong>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TAB 3: TASKS -->
    <div class="tab-pane fade" id="tab-tasks">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-navy mb-0">Action Items & Tasks</h6>
                <button type="button" class="btn btn-sm btn-navy" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                    <i data-lucide="plus" class="me-1" style="width: 14px; height: 14px;"></i> Add Task
                </button>
            </div>
            <div class="table-responsive">
                <table class="table custom-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Task Title</th>
                            <th>Type</th>
                            <th>Assignee</th>
                            <th>Due Date</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Outcome Remarks</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No tasks created for this lead yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $t): ?>
                            <tr>
                                <td class="fw-bold text-navy"><?= Sanitizer::e($t['title']) ?></td>
                                <td><span class="badge bg-light text-secondary border"><?= $t['task_type'] ?></span></td>
                                <td><?= Sanitizer::e($t['assignee_name']) ?></td>
                                <td>
                                    <?php if ($t['is_overdue']): ?>
                                        <span class="text-danger fw-bold"><?= date('d M Y, h:i A', strtotime($t['due_date'])) ?> <i data-lucide="alert-circle" style="width: 14px; height: 14px;"></i></span>
                                    <?php else: ?>
                                        <span><?= date('d M Y, h:i A', strtotime($t['due_date'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge priority-badge-<?= $t['priority'] ?>"><?= $t['priority'] ?></span></td>
                                <td>
                                    <span class="badge bg-<?= $t['status'] === 'Completed' ? 'success' : ($t['status'] === 'Cancelled' ? 'secondary' : 'warning') ?>">
                                        <?= $t['status'] ?>
                                    </span>
                                </td>
                                <td class="small"><?= $t['completion_remarks'] ? Sanitizer::e($t['completion_remarks']) : '—' ?></td>
                                <td class="text-end">
                                    <?php if ($t['status'] !== 'Completed' && $t['status'] !== 'Cancelled'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" 
                                            data-bs-toggle="modal" data-bs-target="#completeTaskModal<?= $t['id'] ?>">
                                        Complete
                                    </button>

                                    <!-- Complete Task Modal -->
                                    <div class="modal fade" id="completeTaskModal<?= $t['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content text-start">
                                                <form action="<?= View::url('tasks/complete') ?>" method="POST">
                                                    <?= Csrf::field() ?>
                                                    <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                                                    <input type="hidden" name="return_url" value="<?= View::url("leads/show&id={$lead['id']}#tasks") ?>">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title fw-bold">Complete Task: <?= Sanitizer::e($t['title']) ?></h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Outcome / Completion Remarks *</label>
                                                            <textarea name="completion_remarks" class="form-control" rows="3" required placeholder="Describe task outcome, discussion notes, or next steps..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success">Save & Mark Completed</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 4: ADMISSION DOCUMENTS CHECKLIST -->
    <div class="tab-pane fade" id="tab-documents">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-navy mb-0">Admission Documents Checklist</h6>
                        <div class="small text-muted">Optional checklist for verified/confirmed admissions. Documents may be marked Available with or without an uploaded copy.</div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table custom-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Document Checklist Item</th>
                            <th>Requirement</th>
                            <th>Status</th>
                            <th>Physical/Uploaded File</th>
                            <th>Verification Status</th>
                            <th>Remarks</th>
                            <th class="text-end">Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checklist as $doc): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-navy"><?= Sanitizer::e($doc['name']) ?></div>
                                <div class="small text-muted"><?= Sanitizer::e($doc['description']) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $doc['is_required'] ? 'bg-navy-subtle text-navy' : 'bg-light text-secondary border' ?>">
                                    <?= $doc['is_required'] ? 'Compulsory' : 'Optional' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $doc['status'] === 'Available' ? 'success' : ($doc['status'] === 'Not Applicable' ? 'secondary' : 'warning') ?>">
                                    <?= $doc['status'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($doc['file_path'])): ?>
                                    <a href="<?= View::url("documents/download&lead_id={$lead['id']}&doc_type_id={$doc['document_type_id']}") ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2">
                                        <i data-lucide="file" style="width: 14px; height: 14px;"></i> View File
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">No file uploaded</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $doc['verification_status'] === 'Verified' ? 'success' : ($doc['verification_status'] === 'Rejected' ? 'danger' : 'light text-dark border') ?>">
                                    <?= $doc['verification_status'] ?>
                                </span>
                            </td>
                            <td class="small"><?= $doc['remarks'] ? Sanitizer::e($doc['remarks']) : '—' ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-navy py-1 px-2" data-bs-toggle="modal" data-bs-target="#editDocModal<?= $doc['document_type_id'] ?>">
                                    Update
                                </button>

                                <!-- Edit Document Modal -->
                                <div class="modal fade" id="editDocModal<?= $doc['document_type_id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content text-start">
                                            <form action="<?= View::url('documents/update-status') ?>" method="POST">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                                                <input type="hidden" name="document_type_id" value="<?= $doc['document_type_id'] ?>">

                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold text-navy"><?= Sanitizer::e($doc['name']) ?></h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Status *</label>
                                                        <select name="status" class="form-select">
                                                            <option value="Pending" <?= $doc['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                            <option value="Available" <?= $doc['status'] === 'Available' ? 'selected' : '' ?>>Available (Physical or Digital copy received)</option>
                                                            <option value="Not Available" <?= $doc['status'] === 'Not Available' ? 'selected' : '' ?>>Not Available</option>
                                                            <option value="Not Applicable" <?= $doc['status'] === 'Not Applicable' ? 'selected' : '' ?>>Not Applicable (e.g. TC for Nursery)</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Verification Status</label>
                                                        <select name="verification_status" class="form-select">
                                                            <option value="Not Reviewed" <?= $doc['verification_status'] === 'Not Reviewed' ? 'selected' : '' ?>>Not Reviewed</option>
                                                            <option value="Verified" <?= $doc['verification_status'] === 'Verified' ? 'selected' : '' ?>>Verified</option>
                                                            <option value="Rejected" <?= $doc['verification_status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Document Remarks</label>
                                                        <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Attested photocopy submitted during counselling"><?= Sanitizer::e($doc['remarks']) ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-navy">Save Status</button>
                                                </div>
                                            </form>

                                            <!-- File Upload Form Section -->
                                            <div class="border-top p-3 bg-light">
                                                <form action="<?= View::url('documents/upload') ?>" method="POST" enctype="multipart/form-data">
                                                    <?= Csrf::field() ?>
                                                    <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                                                    <input type="hidden" name="document_type_id" value="<?= $doc['document_type_id'] ?>">
                                                    <label class="form-label small fw-semibold">Upload Digital Document Copy (PDF/JPG/PNG, Max 10MB)</label>
                                                    <div class="input-group">
                                                        <input type="file" name="document_file" class="form-control form-control-sm" required>
                                                        <button type="submit" class="btn btn-sm btn-navy">Upload</button>
                                                    </div>
                                                    <div class="form-text" style="font-size: 0.72rem;">Uploaded files are stored privately with access-controlled authorization.</div>
                                                </form>
                                            </div>
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
    </div>
</div>

<!-- Modal 1: Log Call Modal -->
<div class="modal fade" id="logCallModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= View::url('followups/store') ?>" method="POST">
                <?= Csrf::field() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                <input type="hidden" name="return_url" value="<?= View::url("leads/show&id={$lead['id']}#timeline") ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-navy">Record Call / Interaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Contact Method</label>
                            <select name="contact_method" class="form-select">
                                <option value="Phone" selected>Phone Call</option>
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="Email">Email</option>
                                <option value="In Person">Campus Walk-in / In Person</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Call Outcome *</label>
                            <select name="outcome" class="form-select" required>
                                <option value="Interested" selected>Interested</option>
                                <option value="Call Back Requested">Call Back Requested</option>
                                <option value="Visit Scheduled">Visit Scheduled</option>
                                <option value="Documents Requested">Documents Requested</option>
                                <option value="Busy">Busy</option>
                                <option value="No Answer">No Answer</option>
                                <option value="Not Interested">Not Interested</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Discussion Remarks *</label>
                        <textarea name="remarks" class="form-control" rows="3" required placeholder="Parent questions asked, campus tour scheduling, fee clarification..."></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Next Follow-up Date & Time</label>
                            <input type="datetime-local" name="next_followup_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime('+2 days 11:00')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Update Lead Status</label>
                            <select name="lead_status" class="form-select">
                                <option value="">-- Keep Current Status (<?= Sanitizer::e($lead['status']) ?>) --</option>
                                <?php foreach ($statuses as $st): ?>
                                    <option value="<?= Sanitizer::e($st) ?>"><?= Sanitizer::e($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy">Save Interaction Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= View::url('tasks/store') ?>" method="POST">
                <?= Csrf::field() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                <input type="hidden" name="return_url" value="<?= View::url("leads/show&id={$lead['id']}#tasks") ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-navy">Schedule Task for Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Task Title *</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. WhatsApp Admission Brochure">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Task Type</label>
                            <select name="task_type" class="form-select">
                                <option value="Follow-up Call">Follow-up Call</option>
                                <option value="WhatsApp Follow-up">WhatsApp Follow-up</option>
                                <option value="Schedule School Visit">Schedule School Visit</option>
                                <option value="Conduct School Visit">Conduct School Visit</option>
                                <option value="Collect Documents">Collect Documents</option>
                                <option value="Application Follow-up">Application Follow-up</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Assignee *</label>
                            <select name="assigned_to" class="form-select" required>
                                <?php foreach ($counselors as $cn): ?>
                                    <option value="<?= $cn['id'] ?>" <?= (int)$lead['assigned_to'] === (int)$cn['id'] ? 'selected' : '' ?>>
                                        <?= Sanitizer::e($cn['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Due Date & Time *</label>
                            <input type="datetime-local" name="due_date" class="form-control" required value="<?= date('Y-m-d\TH:i', strtotime('+1 day 10:00')) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Instructions / Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Details about what to discuss or verify..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Reassign Counselor Modal -->
<?php if (Auth::isAdmin()): ?>
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= View::url('leads/assign') ?>" method="POST">
                <?= Csrf::field() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-navy">Assign / Reassign Counselor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Counselor *</label>
                        <select name="assigned_to" class="form-select" required>
                            <option value="">-- Choose Active Counselor --</option>
                            <?php foreach ($counselors as $cn): ?>
                                <option value="<?= $cn['id'] ?>" <?= (int)$lead['assigned_to'] === (int)$cn['id'] ? 'selected' : '' ?>>
                                    <?= Sanitizer::e($cn['name']) ?> (<?= Sanitizer::e($cn['designation'] ?: 'Counselor') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Reason for Assignment *</label>
                        <input type="text" name="reason" class="form-control" required value="Workload balancing">
                        <div class="form-text" style="font-size: 0.72rem;">Recorded in assignment history and visible in lead timeline.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy">Save Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
