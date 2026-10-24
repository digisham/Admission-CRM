<?php
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Reports & Conversion Analytics';
?>
<!-- Header & Session Control Bar -->
<div class="row align-items-center mb-3.5">
    <div class="col-lg-6">
        <div class="d-flex align-items-center gap-2 mb-1">
            <h3 class="fw-bold text-dark mb-0" style="font-size: 1.35rem; letter-spacing: -0.3px;">Reports & Conversion Analytics</h3>
            <span class="badge rounded-pill bg-light text-secondary border px-2 py-0.5" style="font-size: 0.74rem; font-weight: 600;">
                <?= Sanitizer::e($currentSession['session_label'] ?? 'Session') ?>
            </span>
        </div>
        <p class="text-muted small mb-0" style="font-size: 0.8rem;">Empirical admission intake yield, channel ROI, pipeline velocity, and counselor performance.</p>
    </div>
    <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
        <div class="d-inline-flex align-items-center gap-2 flex-wrap">
            <!-- Academic Session Selector Dropdown -->
            <form action="<?= View::url('reports') ?>" method="GET" class="d-inline-flex m-0 align-items-center">
                <input type="hidden" name="route" value="reports">
                <select name="session_id" class="form-select form-select-sm compact-input" style="width: auto; font-size: 0.8rem; font-weight: 500;" onchange="this.form.submit()">
                    <?php foreach ($allSessions as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (int)$selectedSessionId === (int)$s['id'] ? 'selected' : '' ?>>
                            Session: <?= Sanitizer::e($s['session_label']) ?> <?= $s['is_active'] ? '(Active)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <!-- Export Session Leads Button (Formatted Excel) -->
            <a href="<?= View::url('export/leads?session_id=' . $selectedSessionId . '&export_format=excel') ?>" class="btn btn-sm btn-success rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 0.82rem; font-weight: 600; background: #16A34A; border-color: #16A34A;">
                <i data-lucide="file-spreadsheet" style="width: 14px; height: 14px;"></i>
                <span>Export Report (Excel)</span>
            </a>
        </div>
    </div>
</div>

<!-- 5 Top KPI Cards -->
<div class="row g-2 mb-3 mt-2">
    <!-- Card 1: Total Enquiries -->
    <div class="col-sm-6 col-lg-3 col-xl">
        <div class="analytics-kpi-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-label">Total Inflow</span>
                <span class="kpi-icon-wrap" style="background: #EFF6FF; color: #2563EB;">
                    <i data-lucide="users" style="width: 18px; height: 18px;"></i>
                </span>
            </div>
            <div class="kpi-val"><?= number_format($metrics['total_leads'] ?? 0) ?></div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">All registered enquiries in session</div>
        </div>
    </div>

    <!-- Card 2: Confirmed Admissions -->
    <div class="col-sm-6 col-lg-3 col-xl">
        <div class="analytics-kpi-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-label">Admissions Confirmed</span>
                <span class="kpi-icon-wrap" style="background: #ECFDF5; color: #059669;">
                    <i data-lucide="graduation-cap" style="width: 18px; height: 18px;"></i>
                </span>
            </div>
            <div class="kpi-val text-success"><?= number_format($metrics['confirmed_admissions'] ?? 0) ?></div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Enrolled students with fee/seat</div>
        </div>
    </div>

    <!-- Card 3: Overall Conversion Rate -->
    <div class="col-sm-6 col-lg-3 col-xl">
        <div class="analytics-kpi-card" style="border-left: 3px solid #6366F1 !important;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-label">Conversion Rate</span>
                <span class="kpi-icon-wrap" style="background: #EEF2FF; color: #4F46E5;">
                    <i data-lucide="percent" style="width: 18px; height: 18px;"></i>
                </span>
            </div>
            <div class="d-flex align-items-baseline gap-1.5">
                <div class="kpi-val" style="color: #4F46E5;"><?= $metrics['conversion_rate'] ?>%</div>
            </div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Formula: (Confirmed / Total) &times; 100</div>
        </div>
    </div>

    <!-- Card 4: Active Pipeline -->
    <div class="col-sm-6 col-lg-3 col-xl">
        <div class="analytics-kpi-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-label">Active Pipeline</span>
                <span class="kpi-icon-wrap" style="background: #FFFBEB; color: #D97706;">
                    <i data-lucide="activity" style="width: 18px; height: 18px;"></i>
                </span>
            </div>
            <div class="kpi-val" style="color: #D97706;"><?= number_format($metrics['active_leads'] ?? 0) ?></div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Engaged enquiries in progress</div>
        </div>
    </div>

    <!-- Card 5: Overdue Attention -->
    <div class="col-sm-6 col-lg-3 col-xl">
        <div class="analytics-kpi-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-label">Follow-up Due</span>
                <span class="kpi-icon-wrap" style="background: #FEF2F2; color: #DC2626;">
                    <i data-lucide="clock" style="width: 18px; height: 18px;"></i>
                </span>
            </div>
            <div class="kpi-val <?= ($metrics['overdue_followups'] ?? 0) > 0 ? 'text-danger' : 'text-muted' ?>">
                <?= number_format($metrics['overdue_followups'] ?? 0) ?>
            </div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Overdue interactions requiring call</div>
        </div>
    </div>
</div>

<!-- Charts Row 1: Monthly Intake Trend & Pipeline Stage Funnel -->
<div class="row g-2 mb-3">
    <!-- Chart 1: Monthly Trend Spline Line Chart -->
    <div class="col-lg-8">
        <div class="chart-card">
            <div class="chart-card-header">
                <div>
                    <h6 class="chart-card-title d-flex align-items-center gap-1.5">
                        <i data-lucide="trending-up" style="width: 16px; height: 16px; color: #2563EB;"></i>
                        <span>Enquiry Velocity & Admission Conversion Trend</span>
                    </h6>
                    <div class="text-muted" style="font-size: 0.74rem;">Monthly comparison of registered enquiries versus confirmed admissions.</div>
                </div>
                <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.7rem;">Last 12 Months</span>
            </div>
            <div class="chart-card-body">
                <div style="height: 275px; width: 100%; position: relative;">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 2: Pipeline Stages Doughnut Chart -->
    <div class="col-lg-4">
        <div class="chart-card">
            <div class="chart-card-header">
                <div>
                    <h6 class="chart-card-title d-flex align-items-center gap-1.5">
                        <i data-lucide="pie-chart" style="width: 16px; height: 16px; color: #6366F1;"></i>
                        <span>Pipeline Stage Breakdown</span>
                    </h6>
                    <div class="text-muted" style="font-size: 0.74rem;">Current distribution across admission stages.</div>
                </div>
            </div>
            <div class="chart-card-body">
                <div style="height: 275px; width: 100%; position: relative;">
                    <canvas id="statusDonutChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2: Class Demand & Channel Effectiveness -->
<div class="row g-3 mb-4">
    <!-- Chart 3: Class Demand Bar Chart -->
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-card-header">
                <div>
                    <h6 class="chart-card-title d-flex align-items-center gap-1.5">
                        <i data-lucide="bar-chart-2" style="width: 16px; height: 16px; color: #059669;"></i>
                        <span>Grade / Class Demand & Intake Yield</span>
                    </h6>
                    <div class="text-muted" style="font-size: 0.74rem;">Total enquiries vs confirmed admissions by class.</div>
                </div>
            </div>
            <div class="chart-card-body">
                <div style="height: 270px; width: 100%; position: relative;">
                    <canvas id="classBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 4: Enquiry Source ROI Bar Chart -->
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-card-header">
                <div>
                    <h6 class="chart-card-title d-flex align-items-center gap-1.5">
                        <i data-lucide="share-2" style="width: 16px; height: 16px; color: #D97706;"></i>
                        <span>Enquiry Channel Volume & Conversion</span>
                    </h6>
                    <div class="text-muted" style="font-size: 0.74rem;">Effectiveness of marketing and acquisition channels.</div>
                </div>
            </div>
            <div class="chart-card-body">
                <div style="height: 270px; width: 100%; position: relative;">
                    <canvas id="sourceBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Counselor Performance & Workload Summary Table -->
<div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden bg-white">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <div>
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-1.5" style="font-size: 0.92rem;">
                <i data-lucide="award" style="width: 16px; height: 16px; color: #2563EB;"></i>
                <span>Admission Counselor Performance & Conversion Leaderboard</span>
            </h6>
            <div class="text-muted" style="font-size: 0.74rem;">Empirical conversion ratios, active caseloads, and task discipline per counselor.</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0" style="font-size: 0.83rem;">
            <thead class="bg-light">
                <tr>
                    <th class="ps-3 py-2.5">Counselor Name</th>
                    <th class="text-center py-2.5">Assigned Enquiries</th>
                    <th class="text-center py-2.5">Active Pipeline</th>
                    <th class="text-center py-2.5">Confirmed Admissions</th>
                    <th class="text-center py-2.5">Pending Tasks</th>
                    <th class="text-center py-2.5">Overdue Tasks</th>
                    <th class="text-center py-2.5" style="width: 180px;">Conversion Yield</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($workload)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No counselor records found for this session.</td></tr>
                <?php else: ?>
                    <?php foreach ($workload as $cw): 
                        $rate = $cw['total_leads'] > 0 ? round(($cw['confirmed_leads'] / $cw['total_leads']) * 100, 1) : 0;
                        $rateColor = $rate >= 30 ? '#16A34A' : ($rate >= 15 ? '#2563EB' : '#D97706');
                    ?>
                    <tr>
                        <td class="ps-3 fw-semibold text-dark">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 28px; height: 28px; font-size: 0.75rem; background: #1E3A8A;">
                                    <?= strtoupper(substr($cw['name'], 0, 1)) ?>
                                </div>
                                <span><?= Sanitizer::e($cw['name']) ?></span>
                            </div>
                        </td>
                        <td class="text-center fw-bold"><?= $cw['total_leads'] ?></td>
                        <td class="text-center"><span class="badge bg-light text-secondary border px-2 py-1"><?= $cw['active_leads'] ?></span></td>
                        <td class="text-center"><span class="badge bg-success-subtle text-success px-2 py-1 fw-bold"><?= $cw['confirmed_leads'] ?></span></td>
                        <td class="text-center"><?= $cw['pending_tasks'] ?></td>
                        <td class="text-center">
                            <?php if ($cw['overdue_tasks'] > 0): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><?= $cw['overdue_tasks'] ?> Overdue</span>
                            <?php else: ?>
                                <span class="text-muted small">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center pe-3">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px; background-color: #E2E8F0; border-radius: 4px;">
                                    <div class="progress-bar rounded-pill" style="width: <?= min(100, $rate) ?>%; background-color: <?= $rateColor ?>;"></div>
                                </div>
                                <span class="fw-bold" style="color: <?= $rateColor ?>; font-size: 0.8rem; min-width: 42px;"><?= $rate ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Detailed Breakdown Tables (2 Columns: Class & Source) -->
<div class="row g-3 mb-4">
    <!-- Class Table -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-1.5" style="font-size: 0.9rem;">
                    <i data-lucide="book-open" style="width: 15px; height: 15px; color: #2563EB;"></i>
                    <span>Detailed Demand by Class / Grade</span>
                </h6>
            </div>
            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                <table class="table custom-table table-hover align-middle mb-0" style="font-size: 0.82rem;">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th class="ps-3 py-2">Class Applied</th>
                            <th class="text-center py-2">Enquiries</th>
                            <th class="text-center py-2">Confirmed</th>
                            <th class="text-center py-2">Conversion</th>
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
                                <td class="ps-3 fw-semibold text-dark"><?= Sanitizer::e($cr['class_applied']) ?></td>
                                <td class="text-center fw-bold"><?= $cr['total'] ?></td>
                                <td class="text-center"><span class="badge bg-success-subtle text-success px-2 py-0.5"><?= $cr['confirmed'] ?></span></td>
                                <td class="text-center small fw-semibold text-secondary"><?= $rate ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Source Table -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-1.5" style="font-size: 0.9rem;">
                    <i data-lucide="share-2" style="width: 15px; height: 15px; color: #D97706;"></i>
                    <span>Detailed Acquisition Channel ROI</span>
                </h6>
            </div>
            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                <table class="table custom-table table-hover align-middle mb-0" style="font-size: 0.82rem;">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th class="ps-3 py-2">Enquiry Source</th>
                            <th class="text-center py-2">Total Inflow</th>
                            <th class="text-center py-2">Confirmed</th>
                            <th class="text-center py-2">Conversion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sourceReport)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No source data recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($sourceReport as $sr): 
                                $rate = $sr['total'] > 0 ? round(($sr['confirmed'] / $sr['total']) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-dark"><?= Sanitizer::e($sr['enquiry_source']) ?></td>
                                <td class="text-center fw-bold"><?= $sr['total'] ?></td>
                                <td class="text-center"><span class="badge bg-success-subtle text-success px-2 py-0.5"><?= $sr['confirmed'] ?></span></td>
                                <td class="text-center small fw-semibold text-secondary"><?= $rate ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Document Verification Completion Report -->
<?php if (!empty($docReport)): ?>
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-1.5" style="font-size: 0.9rem;">
            <i data-lucide="file-check" style="width: 15px; height: 15px; color: #059669;"></i>
            <span>Document Checklist Completion Status (Enrolled Admissions)</span>
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="bg-light">
                <tr>
                    <th class="ps-3 py-2">Document Item</th>
                    <th class="text-center py-2">Physical / Available</th>
                    <th class="text-center py-2">Verified Copies</th>
                    <th class="text-center py-2">Pending Collection</th>
                    <th class="text-center py-2">Not Applicable</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($docReport as $dr): ?>
                <tr>
                    <td class="ps-3 fw-semibold text-dark"><?= Sanitizer::e($dr['doc_name']) ?></td>
                    <td class="text-center"><span class="badge bg-success-subtle text-success px-2 py-0.5"><?= $dr['available_count'] ?></span></td>
                    <td class="text-center"><span class="badge bg-primary-subtle text-primary px-2 py-0.5"><?= $dr['verified_count'] ?></span></td>
                    <td class="text-center"><span class="badge bg-warning-subtle text-dark px-2 py-0.5"><?= $dr['pending_count'] ?></span></td>
                    <td class="text-center"><span class="badge bg-light text-muted border px-2 py-0.5"><?= $dr['na_count'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Reports Charts Controller -->
<script>
(function() {
    window.initReportsCharts = function() {
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js is loading...');
            setTimeout(window.initReportsCharts, 80);
            return;
        }

        // Global Chart Registry: Destroy existing instances before recreating to prevent canvas collision
        window._activeCrmCharts = window._activeCrmCharts || {};
        ['monthlyTrendChart', 'statusDonutChart', 'classBarChart', 'sourceBarChart'].forEach(function(canvasId) {
            if (window._activeCrmCharts[canvasId]) {
                try {
                    window._activeCrmCharts[canvasId].destroy();
                } catch(e) {}
                delete window._activeCrmCharts[canvasId];
            }
        });

        // 1. Data Preparation
        const monthlyLabels = <?= json_encode(array_column($monthlyTrend, 'month_label')) ?>;
        const monthlyTotal = <?= json_encode(array_map('intval', array_column($monthlyTrend, 'total'))) ?>;
        const monthlyConfirmed = <?= json_encode(array_map('intval', array_column($monthlyTrend, 'confirmed'))) ?>;

        const statusLabels = <?= json_encode(array_column($statusReport, 'status')) ?>;
        const statusCounts = <?= json_encode(array_map('intval', array_column($statusReport, 'total'))) ?>;

        const classLabels = <?= json_encode(array_column($classReport, 'class_applied')) ?>;
        const classTotal = <?= json_encode(array_map('intval', array_column($classReport, 'total'))) ?>;
        const classConfirmed = <?= json_encode(array_map('intval', array_column($classReport, 'confirmed'))) ?>;

        const sourceLabels = <?= json_encode(array_column($sourceReport, 'enquiry_source')) ?>;
        const sourceTotal = <?= json_encode(array_map('intval', array_column($sourceReport, 'total'))) ?>;
        const sourceConfirmed = <?= json_encode(array_map('intval', array_column($sourceReport, 'confirmed'))) ?>;

        // Default Font Setup for Chart.js
        Chart.defaults.font.family = "'Plus Jakarta Sans', 'Inter', -apple-system, sans-serif";
        Chart.defaults.color = '#64748B';

        // -------------------------------------------------------------
        // CHART 1: Monthly Intake Spline Area Line Chart
        // -------------------------------------------------------------
        const trendCtx = document.getElementById('monthlyTrendChart');
        if (trendCtx) {
            window._activeCrmCharts['monthlyTrendChart'] = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: monthlyLabels.length > 0 ? monthlyLabels : ['No Data'],
                    datasets: [
                        {
                            label: 'Total Enquiries',
                            data: monthlyTotal.length > 0 ? monthlyTotal : [0],
                            borderColor: '#2563EB',
                            backgroundColor: 'rgba(37, 99, 235, 0.08)',
                            fill: true,
                            tension: 0.38,
                            borderWidth: 2.5,
                            pointBackgroundColor: '#2563EB',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Confirmed Admissions',
                            data: monthlyConfirmed.length > 0 ? monthlyConfirmed : [0],
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.12)',
                            fill: true,
                            tension: 0.38,
                            borderWidth: 2.5,
                            pointBackgroundColor: '#10B981',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 12,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: { size: 11, weight: '600' }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0F172A',
                            padding: 10,
                            cornerRadius: 8,
                            titleFont: { size: 12, weight: '700' },
                            bodyFont: { size: 11 }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#F1F5F9' },
                            ticks: { precision: 0, font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // -------------------------------------------------------------
        // CHART 2: Pipeline Stage Breakdown (Doughnut / Pie Chart)
        // -------------------------------------------------------------
        const statusCtx = document.getElementById('statusDonutChart');
        if (statusCtx) {
            const palette = [
                '#2563EB', '#6366F1', '#10B981', '#F59E0B', 
                '#06B6D4', '#EC4899', '#8B5CF6', '#64748B', '#EF4444'
            ];

            window._activeCrmCharts['statusDonutChart'] = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: statusLabels.length > 0 ? statusLabels : ['No Records'],
                    datasets: [{
                        data: statusCounts.length > 0 ? statusCounts : [1],
                        backgroundColor: palette.slice(0, Math.max(1, statusLabels.length)),
                        borderWidth: 2,
                        borderColor: '#FFFFFF',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '66%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: { size: 10.5, weight: '500' },
                                padding: 12
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0F172A',
                            padding: 10,
                            cornerRadius: 8,
                            titleFont: { size: 12, weight: '700' },
                            bodyFont: { size: 11 }
                        }
                    }
                }
            });
        }

        // -------------------------------------------------------------
        // CHART 3: Class Demand vs Conversion (Grouped Bar Chart)
        // -------------------------------------------------------------
        const classCtx = document.getElementById('classBarChart');
        if (classCtx) {
            window._activeCrmCharts['classBarChart'] = new Chart(classCtx, {
                type: 'bar',
                data: {
                    labels: classLabels.length > 0 ? classLabels : ['No Data'],
                    datasets: [
                        {
                            label: 'Total Enquiries',
                            data: classTotal.length > 0 ? classTotal : [0],
                            backgroundColor: '#3B82F6',
                            borderRadius: 5,
                            barPercentage: 0.7,
                            categoryPercentage: 0.75
                        },
                        {
                            label: 'Confirmed Admissions',
                            data: classConfirmed.length > 0 ? classConfirmed : [0],
                            backgroundColor: '#10B981',
                            borderRadius: 5,
                            barPercentage: 0.7,
                            categoryPercentage: 0.75
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: { size: 10.5, weight: '600' }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0F172A',
                            padding: 10,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 0 }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#F1F5F9' },
                            ticks: { precision: 0, font: { size: 10.5 } }
                        }
                    }
                }
            });
        }

        // -------------------------------------------------------------
        // CHART 4: Enquiry Source Volume & Conversion (Bar Chart)
        // -------------------------------------------------------------
        const sourceCtx = document.getElementById('sourceBarChart');
        if (sourceCtx) {
            window._activeCrmCharts['sourceBarChart'] = new Chart(sourceCtx, {
                type: 'bar',
                data: {
                    labels: sourceLabels.length > 0 ? sourceLabels : ['No Data'],
                    datasets: [
                        {
                            label: 'Total Enquiries',
                            data: sourceTotal.length > 0 ? sourceTotal : [0],
                            backgroundColor: '#F59E0B',
                            borderRadius: 5,
                            barPercentage: 0.7,
                            categoryPercentage: 0.75
                        },
                        {
                            label: 'Confirmed Admissions',
                            data: sourceConfirmed.length > 0 ? sourceConfirmed : [0],
                            backgroundColor: '#059669',
                            borderRadius: 5,
                            barPercentage: 0.7,
                            categoryPercentage: 0.75
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: { size: 10.5, weight: '600' }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0F172A',
                            padding: 10,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10.5 } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#F1F5F9' },
                            ticks: { precision: 0, font: { size: 10.5 } }
                        }
                    }
                }
            });
        }

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    };

    // Auto-initialize on DOM ready or immediate if already loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initReportsCharts);
    } else {
        window.initReportsCharts();
    }
})();
</script>
