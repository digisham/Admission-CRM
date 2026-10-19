<?php
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Admission Documents Checklist';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">Admission Documents Management</h3>
        <p class="text-muted small mb-0">Track compulsory & optional admission documents for confirmed student enrollments.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <a href="<?= View::url('leads') ?>" class="btn btn-outline-secondary me-2 shadow-sm">
            &larr; Enquiries List
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold text-navy mb-0">Standard Admission Documents Required:</h6>
    </div>
    <div class="card-body p-3">
        <div class="row g-2">
            <?php foreach ($docTypes as $idx => $dt): ?>
            <div class="col-md-4">
                <div class="p-2 bg-light rounded border small">
                    <strong><?= $idx + 1 ?>. <?= Sanitizer::e($dt['name']) ?></strong>
                    <div class="text-muted" style="font-size: 0.72rem;"><?= Sanitizer::e($dt['description']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold text-navy mb-0">Students with Confirmed / Advanced Admissions</h6>
    </div>
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Ref #</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Parent & Mobile</th>
                    <th>Counselor</th>
                    <th>Enquiry Date</th>
                    <th class="text-end">Document Checklist</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No confirmed admissions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td class="fw-bold text-navy"><?= Sanitizer::e($lead['lead_ref']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= Sanitizer::e($lead['student_name']) ?></div>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= Sanitizer::e($lead['class_applied']) ?></span></td>
                        <td>
                            <div><?= Sanitizer::e($lead['parent_name']) ?></div>
                            <div class="small text-muted"><?= Sanitizer::e($lead['mobile']) ?></div>
                        </td>
                        <td><?= Sanitizer::e($lead['assigned_name'] ?: 'Unassigned') ?></td>
                        <td><?= date('d M Y', strtotime($lead['enquiry_date'])) ?></td>
                        <td class="text-end">
                            <a href="<?= View::url("leads/show&id={$lead['id']}#documents") ?>" class="btn btn-sm btn-navy py-1 px-3 shadow-sm">
                                <i data-lucide="file-check" class="me-1" style="width: 14px; height: 14px;"></i> Open Checklist &rarr;
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
