<?php
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Admission Reports & Analytics';
?>
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-navy mb-1">Reports & Conversion Analytics</h3>
        <p class="text-muted small mb-0">Empirical admission metrics, conversion ratios, source analysis, and counselor activity.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <a href="<?= View::url('export/leads?session_id=' . $selectedSessionId) ?>" class="btn btn-navy shadow-sm">
            <i data-lucide="download" class="me-1" style="width: 16px; height: 16px;"></i> Export Session Leads (CSV)
        </a>
    </div>
</div>

<!-- Session Conversion Banner -->
<div class="card border-0 shadow-sm mb-4 bg-navy text-white">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-gold text-dark fw-bold mb-2">OFFICIAL CONVERSION RATE FORMULA</span>
                <h4 class="fw-bold mb-1">Overall Conversion Rate: <?= $metrics['conversion_rate'] ?>%</h4>
                <p class="mb-0 text-white-50 small">
                    Formula: <code>(Confirmed Admissions / Total Enquiries) &times; 100%</code> &bull; 
                    <?= $metrics['confirmed_admissions'] ?> confirmed out of <?= $metrics['total_leads'] ?> active session enquiries.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="fs-1 fw-bold text-gold"><?= $metrics['conversion_rate'] ?>%</div>
                <div class="text-white-50 small">Overall Session Intake</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Leads by Class Applied -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold text-navy mb-0">Demand by Class / Grade</h6>
            </div>
            <div class="table-responsive">
                <table class="table custom-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Class Applied</th>
                            <th class="text-center">Total Enquiries</th>
                            <th class="text-center">Confirmed</th>
                            <th class="text-center">Conversion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($classReport)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No data available for this session.</td></tr>
                        <?php else: ?>
                            <?php foreach ($classReport as $cr): 
                                $rate = $cr['total'] > 0 ? round(($cr['confirmed'] / $cr['total']) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td class="fw-semibold text-navy"><?= Sanitizer::e($cr['class_applied']) ?></td>
                                <td class="text-center fw-bold"><?= $cr['total'] ?></td>
                                <td class="text-center"><span class="badge bg-success-subtle text-success"><?= $cr['confirmed'] ?></span></td>
                                <td class="text-center small fw-semibold"><?= $rate ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Leads by Enquiry Source -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold text-navy mb-0">Effectiveness by Enquiry Channel</h6>
            </div>
            <div class="table-responsive">
                <table class="table custom-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Enquiry Source</th>
                            <th class="text-center">Total Enquiries</th>
                            <th class="text-center">Confirmed</th>
                            <th class="text-center">Conversion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sourceReport)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No source data available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($sourceReport as $sr): 
                                $rate = $sr['total'] > 0 ? round(($sr['confirmed'] / $sr['total']) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td class="fw-semibold text-navy"><?= Sanitizer::e($sr['enquiry_source']) ?></td>
                                <td class="text-center fw-bold"><?= $sr['total'] ?></td>
                                <td class="text-center"><span class="badge bg-success-subtle text-success"><?= $sr['confirmed'] ?></span></td>
                                <td class="text-center small fw-semibold"><?= $rate ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Counselor Performance Report -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold text-navy mb-0">Admission Counselor Workload & Conversion Summary</h6>
    </div>
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Counselor</th>
                    <th class="text-center">Assigned Enquiries</th>
                    <th class="text-center">Active Pipeline</th>
                    <th class="text-center">Confirmed Admissions</th>
                    <th class="text-center">Pending Tasks</th>
                    <th class="text-center">Overdue Attention</th>
                    <th class="text-center">Conversion Ratio</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($workload)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No counselor records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($workload as $cw): 
                        $rate = $cw['total_leads'] > 0 ? round(($cw['confirmed_leads'] / $cw['total_leads']) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td class="fw-semibold text-navy"><?= Sanitizer::e($cw['name']) ?></td>
                        <td class="text-center fw-bold"><?= $cw['total_leads'] ?></td>
                        <td class="text-center"><span class="badge bg-navy-subtle text-navy"><?= $cw['active_leads'] ?></span></td>
                        <td class="text-center"><span class="badge bg-success-subtle text-success"><?= $cw['confirmed_leads'] ?></span></td>
                        <td class="text-center"><?= $cw['pending_tasks'] ?></td>
                        <td class="text-center">
                            <?php if ($cw['overdue_tasks'] > 0): ?>
                                <span class="badge bg-danger"><?= $cw['overdue_tasks'] ?> Overdue</span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center fw-bold text-navy"><?= $rate ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Document Verification Completion Report -->
<?php if (!empty($docReport)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold text-navy mb-0">Document Checklist Completion Across Enrolled Admissions</h6>
    </div>
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Document Checklist Item</th>
                    <th class="text-center">Physical / Available</th>
                    <th class="text-center">Verified Copies</th>
                    <th class="text-center">Pending Collection</th>
                    <th class="text-center">Not Applicable</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($docReport as $dr): ?>
                <tr>
                    <td class="fw-semibold text-navy"><?= Sanitizer::e($dr['doc_name']) ?></td>
                    <td class="text-center"><span class="badge bg-success-subtle text-success"><?= $dr['available_count'] ?></span></td>
                    <td class="text-center"><span class="badge bg-primary-subtle text-primary"><?= $dr['verified_count'] ?></span></td>
                    <td class="text-center"><span class="badge bg-warning-subtle text-dark"><?= $dr['pending_count'] ?></span></td>
                    <td class="text-center"><span class="badge bg-light text-muted border"><?= $dr['na_count'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
