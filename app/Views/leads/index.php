<?php
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$pageTitle = 'Admission Enquiries & Leads';
$exportColumns = \App\Services\CsvExportService::getExportableColumns();
?>
<?php
$hasActiveFilters = !empty($filters['status']) || !empty($filters['class_applied']) || !empty($filters['assigned_to']) || !empty($filters['followup_due']);
?>

<!-- Bulk Action Bar -->
<div class="alert alert-secondary d-none d-flex justify-content-between align-items-center mb-3 shadow-sm py-2" id="bulkActionsBar">
    <div class="d-flex align-items-center gap-2">
        <i data-lucide="check-square" class="text-navy" style="width: 18px; height: 18px;"></i>
        <strong><span id="selectedLeadsCount">0</span></strong> enquiry row(s) selected
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if (Auth::isAdmin() || Auth::hasPermission('leads.assign')): ?>
        <button type="button" class="btn btn-sm btn-navy rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-sm" id="btnOpenBulkAssignModal">
            <i data-lucide="user-check" style="width: 14px; height: 14px;"></i>
            <span>Bulk Assign Selected</span>
        </button>
        <?php endif; ?>

        <?php if (Auth::isSuperAdmin()): ?>
        <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-sm" id="btnOpenBulkDeleteModal" title="Permanently delete selected enquiries (Super Admin only)">
            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
            <span>Bulk Delete Selected</span>
        </button>
        <?php endif; ?>
    </div>
</div>

    <!-- Main Sample Table Card -->
    <div class="sample-table-card">
        <!-- Top Header Bar -->
        <div class="sample-table-header-bar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h4 class="sample-table-title mb-0">Admission Enquiries</h4>
                <span class="badge bg-light text-muted border px-2 py-1 small" style="font-size: 0.75rem; font-weight: 600;">
                    <?= (int)($pagination['total'] ?? count($leads)) ?> Total
                </span>
                <?php if (!empty($filters['status'])): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1.5 px-2.5 py-1" style="font-size: 0.75rem; font-weight: 600;">
                        <i data-lucide="<?= $filters['status'] === 'public_enquiry' ? 'globe' : 'filter' ?>" style="width: 12px; height: 12px;"></i>
                        <span>Status: <?= $filters['status'] === 'public_enquiry' ? 'Public Online Form' : Sanitizer::e($filters['status']) ?></span>
                        <a href="<?= View::url('leads?' . http_build_query(array_merge($filters, ['status' => '']))) ?>" class="text-primary text-decoration-none ms-1 fw-bold" title="Clear status filter">&times;</a>
                    </span>
                <?php endif; ?>
                <?php if (!empty($filters['class_applied'])): ?>
                    <span class="badge d-inline-flex align-items-center gap-1.5 px-2.5 py-1" style="font-size: 0.75rem; font-weight: 600; background-color: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe;">
                        <i data-lucide="book-open" style="width: 12px; height: 12px;"></i>
                        <span>Class: <?= Sanitizer::e($filters['class_applied']) ?></span>
                        <a href="<?= View::url('leads?' . http_build_query(array_merge($filters, ['class_applied' => '']))) ?>" class="text-indigo text-decoration-none ms-1 fw-bold" title="Clear class filter" style="color: #5b21b6 !important;">&times;</a>
                    </span>
                <?php endif; ?>
            </div>

            <div class="sample-table-actions d-flex align-items-center gap-2">
                <!-- Search Form -->
                <form action="<?= View::url('leads') ?>" method="GET" class="d-inline-flex m-0">
                    <input type="hidden" name="route" value="leads">
                    <?php if (!empty($filters['status'])): ?>
                        <input type="hidden" name="status" value="<?= Sanitizer::e($filters['status']) ?>">
                    <?php endif; ?>
                    <?php if (!empty($filters['class_applied'])): ?>
                        <input type="hidden" name="class_applied" value="<?= Sanitizer::e($filters['class_applied']) ?>">
                    <?php endif; ?>
                    <?php if (!empty($filters['assigned_to'])): ?>
                        <input type="hidden" name="assigned_to" value="<?= Sanitizer::e($filters['assigned_to']) ?>">
                    <?php endif; ?>
                    <?php if (!empty($filters['followup_due'])): ?>
                        <input type="hidden" name="followup_due" value="<?= Sanitizer::e($filters['followup_due']) ?>">
                    <?php endif; ?>
                    <?php if (!empty($filters['per_page'])): ?>
                        <input type="hidden" name="per_page" value="<?= Sanitizer::e($filters['per_page']) ?>">
                    <?php endif; ?>

                    <div class="sample-search-wrap">
                        <i data-lucide="search" class="sample-search-icon"></i>
                        <input type="text" name="search" class="sample-search-input" placeholder="Search..." value="<?= Sanitizer::e($filters['search'] ?? '') ?>">
                    </div>
                </form>

                <!-- Qty / Limit Dropdown Selector -->
                <?php
                $curPerPage = ($filters['per_page'] ?? '') === 'all' ? 'all' : (int)($filters['per_page'] ?? 15);
                ?>
                <div class="dropdown d-inline-block">
                    <button type="button" class="sample-page-btn text-dark d-inline-flex align-items-center gap-1.5 px-2.5" data-bs-toggle="dropdown" aria-expanded="false" title="Show quantity (number of leads per page)" style="font-size: 0.8rem; height: 38px;">
                        <i data-lucide="rows-3" style="width: 14px; height: 14px;" class="text-primary"></i>
                        <span class="fw-semibold">Qty: <?= $curPerPage === 'all' ? 'All' : $curPerPage ?></span>
                        <i data-lucide="chevron-down" style="width: 11px; height: 11px;" class="text-muted"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-2" style="font-size: 0.82rem; min-width: 195px; border-radius: 8px;">
                        <li class="dropdown-header text-uppercase text-muted py-1" style="font-size: 0.68rem;">Show Enquiries Qty</li>
                        <?php foreach ([15, 50, 100, 200] as $q): ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between py-1.5 <?= $curPerPage == $q ? 'active bg-primary' : '' ?>" 
                               href="<?= View::url('leads?' . http_build_query(array_merge($filters, ['per_page' => $q, 'page' => 1]))) ?>">
                                <span><?= $q ?> per page</span>
                                <?php if ($curPerPage == $q): ?><i data-lucide="check" style="width: 13px; height: 13px;"></i><?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between py-1.5 <?= $curPerPage === 'all' ? 'active bg-primary' : '' ?>" 
                               href="<?= View::url('leads?' . http_build_query(array_merge($filters, ['per_page' => 'all', 'page' => 1]))) ?>">
                                <span class="fw-bold">Show All (<?= (int)($pagination['total'] ?? 0) ?> leads)</span>
                                <?php if ($curPerPage === 'all'): ?><i data-lucide="check" style="width: 13px; height: 13px;"></i><?php endif; ?>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li class="px-3 py-1">
                            <form action="<?= View::url('leads') ?>" method="GET" class="m-0">
                                <input type="hidden" name="route" value="leads">
                                <?php foreach ($filters as $k => $v): if ($k !== 'per_page' && $k !== 'page' && !empty($v)): ?>
                                    <input type="hidden" name="<?= Sanitizer::e($k) ?>" value="<?= Sanitizer::e($v) ?>">
                                <?php endif; endforeach; ?>
                                <label class="form-label text-muted small mb-1" style="font-size: 0.72rem;">Enter Manually:</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="per_page" class="form-control form-control-sm text-center" placeholder="e.g. 75" min="1" max="5000" style="font-size: 0.8rem;">
                                    <button class="btn btn-navy btn-sm" type="submit">Go</button>
                                </div>
                            </form>
                        </li>
                    </ul>
                </div>

                <!-- Sticky Header Scroll Toggle Button -->
                <button type="button" class="sample-page-btn text-muted" id="btnToggleScrollMode" title="Toggle sticky scroll / full-height view">
                    <i data-lucide="pin" style="width: 15px; height: 15px;"></i>
                </button>

                <!-- Filter Toggle Button -->
                <button type="button" class="sample-page-btn text-muted" data-bs-toggle="collapse" data-bs-target="#advancedFiltersPanel" title="Filter options" style="<?= $hasActiveFilters ? 'border-color: #6366f1; color: #6366f1;' : '' ?>">
                    <i data-lucide="sliders-horizontal" style="width: 15px; height: 15px;"></i>
                </button>

                <!-- Download Excel / Export with Columns Modal Button -->
                <button type="button" class="sample-page-btn text-muted" data-bs-toggle="modal" data-bs-target="#exportColumnsModal" title="Download Excel / Export CSV">
                    <i data-lucide="download" style="width: 15px; height: 15px;"></i>
                </button>

                <!-- Add New Button -->
                <a href="<?= View::url('leads/create') ?>" class="sample-btn-add">
                    <i data-lucide="plus" style="width: 15px; height: 15px;"></i> Add New
                </a>
            </div>
        </div>

        <!-- Collapsible Filters Toolbar -->
        <div class="collapse <?= $hasActiveFilters ? 'show' : '' ?> border-bottom p-3 bg-light" id="advancedFiltersPanel">
            <form action="<?= View::url('leads') ?>" method="GET" class="row g-2 align-items-end" id="leadsAdvancedFilterForm">
                <input type="hidden" name="route" value="leads">
                <?php if (!empty($filters['search'])): ?>
                    <input type="hidden" name="search" value="<?= Sanitizer::e($filters['search']) ?>">
                <?php endif; ?>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm" id="leadsStatusFilterSelect">
                        <option value="">All Statuses</option>
                        <option value="public_enquiry" <?= ($filters['status'] ?? '') === 'public_enquiry' ? 'selected' : '' ?> style="font-weight: 600; color: #1e40af; background-color: #eff6ff;">🌐 Public Online Form</option>
                        <optgroup label="Standard Statuses">
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= Sanitizer::e($st) ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>><?= Sanitizer::e($st) ?></option>
                        <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Class / Grade</label>
                    <select name="class_applied" class="form-select form-select-sm" id="leadClassFilterSelect">
                        <option value="">All Classes / Grades</option>
                        <optgroup label="Kindergarten / Early Years">
                            <option value="Pre-Nursery" <?= ($filters['class_applied'] ?? '') === 'Pre-Nursery' ? 'selected' : '' ?>>Pre-Nursery</option>
                            <option value="Nursery" <?= ($filters['class_applied'] ?? '') === 'Nursery' ? 'selected' : '' ?>>Nursery</option>
                            <option value="LKG" <?= in_array($filters['class_applied'] ?? '', ['LKG', 'L.K.G.']) ? 'selected' : '' ?>>LKG</option>
                            <option value="UKG" <?= in_array($filters['class_applied'] ?? '', ['UKG', 'U.K.G.']) ? 'selected' : '' ?>>UKG</option>
                        </optgroup>
                        <optgroup label="Primary & Middle School (Grade 1 - 8)">
                            <option value="Grade 1" <?= in_array($filters['class_applied'] ?? '', ['Grade 1', 'I', 'Class 1', 'Class I', '1']) ? 'selected' : '' ?>>Grade 1 (Class I)</option>
                            <option value="Grade 2" <?= in_array($filters['class_applied'] ?? '', ['Grade 2', 'II', 'Class 2', 'Class II', '2']) ? 'selected' : '' ?>>Grade 2 (Class II)</option>
                            <option value="Grade 3" <?= in_array($filters['class_applied'] ?? '', ['Grade 3', 'III', 'Class 3', 'Class III', '3']) ? 'selected' : '' ?>>Grade 3 (Class III)</option>
                            <option value="Grade 4" <?= in_array($filters['class_applied'] ?? '', ['Grade 4', 'IV', 'Class 4', 'Class IV', '4']) ? 'selected' : '' ?>>Grade 4 (Class IV)</option>
                            <option value="Grade 5" <?= in_array($filters['class_applied'] ?? '', ['Grade 5', 'V', 'Class 5', 'Class V', '5']) ? 'selected' : '' ?>>Grade 5 (Class V)</option>
                            <option value="Grade 6" <?= in_array($filters['class_applied'] ?? '', ['Grade 6', 'VI', 'Class 6', 'Class VI', '6']) ? 'selected' : '' ?>>Grade 6 (Class VI)</option>
                            <option value="Grade 7" <?= in_array($filters['class_applied'] ?? '', ['Grade 7', 'VII', 'Class 7', 'Class VII', '7']) ? 'selected' : '' ?>>Grade 7 (Class VII)</option>
                            <option value="Grade 8" <?= in_array($filters['class_applied'] ?? '', ['Grade 8', 'VIII', 'Class 8', 'Class VIII', '8']) ? 'selected' : '' ?>>Grade 8 (Class VIII)</option>
                        </optgroup>
                        <optgroup label="Secondary School (Grade 9 - 10)">
                            <option value="Grade 9" <?= in_array($filters['class_applied'] ?? '', ['Grade 9', 'IX', 'Class 9', 'Class IX', '9']) ? 'selected' : '' ?>>Grade 9 (Class IX)</option>
                            <option value="Grade 10" <?= in_array($filters['class_applied'] ?? '', ['Grade 10', 'X', 'Class 10', 'Class X', '10']) ? 'selected' : '' ?>>Grade 10 (Class X)</option>
                        </optgroup>
                        <optgroup label="Senior Secondary (Grade 11 - 12)">
                            <option value="Grade 11 - Science" <?= in_array($filters['class_applied'] ?? '', ['Grade 11 - Science', 'XI(SCIENCE)', 'XI - Science']) ? 'selected' : '' ?>>Grade 11 - Science (XI Science)</option>
                            <option value="Grade 11 - Commerce" <?= in_array($filters['class_applied'] ?? '', ['Grade 11 - Commerce', 'XI(COMMERCE)', 'XI - Commerce']) ? 'selected' : '' ?>>Grade 11 - Commerce (XI Commerce)</option>
                            <option value="Grade 11 - Humanities" <?= in_array($filters['class_applied'] ?? '', ['Grade 11 - Humanities', 'XI(HUMANITIES)', 'XI - Humanities', 'XI - Arts']) ? 'selected' : '' ?>>Grade 11 - Humanities (XI Arts)</option>
                            <option value="Grade 12 - Science" <?= in_array($filters['class_applied'] ?? '', ['Grade 12 - Science', 'XII(SCIENCE)', 'XII - Science']) ? 'selected' : '' ?>>Grade 12 - Science (XII Science)</option>
                            <option value="Grade 12 - Commerce" <?= in_array($filters['class_applied'] ?? '', ['Grade 12 - Commerce', 'XII(COMMERCE)', 'XII - Commerce']) ? 'selected' : '' ?>>Grade 12 - Commerce (XII Commerce)</option>
                            <option value="Grade 12 - Humanities" <?= in_array($filters['class_applied'] ?? '', ['Grade 12 - Humanities', 'XII(HUMANITIES)', 'XII - Humanities', 'XII - Arts']) ? 'selected' : '' ?>>Grade 12 - Humanities (XII Arts)</option>
                        </optgroup>
                    </select>
                </div>

                <?php if (Auth::isAdmin()): ?>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Assigned Counselor</label>
                    <select name="assigned_to" class="form-select form-select-sm">
                        <option value="">All Counselors</option>
                        <?php foreach ($counselors as $cn): ?>
                            <option value="<?= $cn['id'] ?>" <?= ($filters['assigned_to'] ?? '') == $cn['id'] ? 'selected' : '' ?>><?= Sanitizer::e($cn['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Follow-up Due</label>
                    <select name="followup_due" class="form-select form-select-sm">
                        <option value="">Any Time</option>
                        <option value="today" <?= ($filters['followup_due'] ?? '') === 'today' ? 'selected' : '' ?>>Due Today</option>
                        <option value="overdue" <?= ($filters['followup_due'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                        <option value="upcoming" <?= ($filters['followup_due'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Show Quantity</label>
                    <select name="per_page" class="form-select form-select-sm">
                        <option value="15" <?= ($curPerPage == 15) ? 'selected' : '' ?>>15 Leads</option>
                        <option value="50" <?= ($curPerPage == 50) ? 'selected' : '' ?>>50 Leads</option>
                        <option value="100" <?= ($curPerPage == 100) ? 'selected' : '' ?>>100 Leads</option>
                        <option value="200" <?= ($curPerPage == 200) ? 'selected' : '' ?>>200 Leads</option>
                        <option value="all" <?= ($curPerPage === 'all') ? 'selected' : '' ?>>Show All Leads</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-1.5">
                    <button type="submit" class="btn btn-sm btn-navy flex-grow-1">Apply</button>
                    <?php if ($hasActiveFilters): ?>
                        <a href="<?= View::url('leads') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                            <i data-lucide="rotate-ccw" style="width: 13px; height: 13px;"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (Auth::isSuperAdmin() && $hasActiveFilters): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btnOpenFilterDeleteModal" title="Delete all leads matching current filters (Super Admin only)">
                            <i data-lucide="filter-x" style="width: 13px; height: 13px;"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Sample Table Scroll Container with Sticky Header -->
        <div class="table-responsive leads-table-scroll-wrap" id="leadsTableScrollWrap">
            <table class="sample-table leads-sticky-table">
                <thead>
                    <tr>
                        <th style="width: 45px;">
                            <input type="checkbox" class="sample-checkbox" id="selectAllLeads">
                        </th>
                        <th>Name <span class="sample-sort-arrow">&#8597;</span></th>
                        <th>Phone <span class="sample-sort-arrow">&#8597;</span></th>
                        <th>Email <span class="sample-sort-arrow">&#8597;</span></th>
                        <th>Days <span class="sample-sort-arrow">&#8597;</span></th>
                        <th>Priority <span class="sample-sort-arrow">&#8597;</span></th>
                        <th>Status <span class="sample-sort-arrow">&#8597;</span></th>
                        <th class="text-end" style="min-width: 120px;">Action <span class="sample-sort-arrow">&#8597;</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i data-lucide="inbox" class="mb-2 text-muted" style="width: 40px; height: 40px;"></i>
                                <div class="fw-semibold">No admission enquiries found</div>
                                <div class="small">Try adjusting search filters or add a new enquiry.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leads as $lead): 
                            $statusSlug = trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $lead['status']), '-');

                            // Compute initials and avatar color
                            $cleanStudent = trim($lead['student_name'] ?? '');
                            $nameParts = preg_split('/\s+/', $cleanStudent);
                            $initials = '';
                            if (!empty($nameParts[0])) $initials .= mb_strtoupper(mb_substr($nameParts[0], 0, 1));
                            if (count($nameParts) > 1 && !empty($nameParts[count($nameParts) - 1])) {
                                $initials .= mb_strtoupper(mb_substr($nameParts[count($nameParts) - 1], 0, 1));
                            }
                            if (empty($initials)) $initials = 'S';

                            $bgPalette = [
                                ['#ede9fe', '#6366f1'],
                                ['#dcfce7', '#16a34a'],
                                ['#e0f2fe', '#0284c7'],
                                ['#fef3c7', '#d97706'],
                                ['#fce7f3', '#db2777'],
                                ['#e2e8f0', '#475569'],
                            ];
                            $cIdx = abs(crc32($cleanStudent ?: 'student')) % count($bgPalette);
                            $aBg = $bgPalette[$cIdx][0];
                            $aColor = $bgPalette[$cIdx][1];

                            // Status pill style matching sample: Active = purple, Pending = mint green
                            $stPillClass = 'sample-status-active';
                            if (stripos($lead['status'], 'Pending') !== false || stripos($lead['status'], 'New') !== false) {
                                $stPillClass = 'sample-status-pending';
                            } elseif (stripos($lead['status'], 'Lost') !== false || stripos($lead['status'], 'Cancel') !== false) {
                                $stPillClass = 'sample-status-secondary';
                            } elseif (stripos($lead['status'], 'Follow') !== false || stripos($lead['status'], 'Visit') !== false) {
                                $stPillClass = 'sample-status-active';
                            }

                            // Days calculation matching sample format
                            $createdTime = strtotime($lead['created_at']);
                            $diffDays = floor((time() - $createdTime) / 86400);
                            $daysText = $diffDays == 0 ? 'Today' : ($diffDays == 1 ? '1 Days' : $diffDays . ' Days');
                            if (!empty($lead['class_applied'])) {
                                $daysText = Sanitizer::e($lead['class_applied']) . ' &bull; ' . $daysText;
                            }
                        ?>
                        <tr id="lead-row-<?= $lead['id'] ?>">
                            <td>
                                <input type="checkbox" name="lead_ids[]" value="<?= $lead['id'] ?>" class="sample-checkbox lead-checkbox">
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <!-- <span class="sample-row-avatar" style="background-color: <?= $aBg ?>; color: <?= $aColor ?>;">
                                        <?= Sanitizer::e($initials) ?>
                                    </span> -->
                                    <div>
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                            <a href="javascript:void(0)" class="text-decoration-none fw-semibold text-dark btn-trigger-offcanvas" data-lead-id="<?= $lead['id'] ?>">
                                                <?= Sanitizer::e($lead['student_name']) ?>
                                            </a>
                                            <?php 
                                            $isPublicLead = (stripos($lead['enquiry_source'] ?? '', 'Online') !== false || stripos($lead['enquiry_source'] ?? '', 'Public') !== false || ($lead['enquiry_source'] ?? '') === 'Website / Online Enquiry');
                                            if ($isPublicLead): 
                                            ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-0.5" style="font-size: 0.64rem; padding: 1.5px 5px; border-radius: 4px;" title="Online Enquiry via Public Admission Form (Source: <?= Sanitizer::e($lead['enquiry_source']) ?>)">
                                                    <i data-lucide="globe" style="width: 10px; height: 10px;"></i> Public
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($lead['parent_name'])): ?>
                                            <div class="small text-muted" style="font-size: 0.78rem;"><?= Sanitizer::e($lead['parent_name']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="tel:<?= Sanitizer::e($lead['mobile']) ?>" class="text-decoration-none text-dark">
                                    <?= Sanitizer::e($lead['mobile']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="text-muted"><?= !empty($lead['email']) ? Sanitizer::e($lead['email']) : Sanitizer::e($lead['lead_ref']) ?></span>
                            </td>
                            <td>
                                <span class="text-dark"><?= $daysText ?></span>
                            </td>
                            <td>
                                <!-- Interactive 1-Click Priority Dropdown -->
                                <div class="dropdown d-inline-block">
                                    <button type="button" 
                                            class="btn btn-sm p-0 border-0 bg-transparent text-decoration-none shadow-none dropdown-toggle priority-dropdown-btn"
                                            data-bs-toggle="dropdown" 
                                            aria-expanded="false" 
                                            title="Click to update priority">
                                        <span class="badge priority-badge priority-badge-<?= $lead['priority'] ?? 'Medium' ?> px-2.5 py-1" id="priority-badge-<?= $lead['id'] ?>">
                                            <span class="priority-icon"></span>
                                            <span class="badge-text"><?= Sanitizer::e($lead['priority'] ?? 'Medium') ?></span>
                                            <i data-lucide="chevron-down" style="width: 11px; height: 11px;" class="ms-1 opacity-75"></i>
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu shadow-lg border-0 py-2 quick-priority-menu" style="font-size: 0.82rem; z-index: 1050; border-radius: 8px;">
                                        <li class="dropdown-header text-uppercase text-muted py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Change Priority</li>
                                        <?php foreach (['Urgent', 'High', 'Medium', 'Low'] as $pr): 
                                            $isCurPr = (($lead['priority'] ?? 'Medium') === $pr);
                                        ?>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3 quick-change-priority-item <?= $isCurPr ? 'active' : '' ?>" 
                                               href="javascript:void(0)" 
                                               data-lead-id="<?= $lead['id'] ?>" 
                                               data-priority="<?= $pr ?>">
                                                <span class="d-flex align-items-center gap-2">
                                                    <span class="priority-dot priority-dot-<?= $pr ?>"></span>
                                                    <span class="fw-medium"><?= $pr ?></span>
                                                </span>
                                                <?php if ($isCurPr): ?>
                                                    <i data-lucide="check" style="width: 13px; height: 13px;" class="text-success ms-2"></i>
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <!-- Interactive 1-Click Status Dropdown with Modal Option -->
                                <div class="dropdown d-inline-block">
                                    <button type="button" 
                                            class="btn btn-sm p-0 border-0 bg-transparent text-decoration-none shadow-none dropdown-toggle status-dropdown-btn"
                                            data-bs-toggle="dropdown" 
                                            aria-expanded="false" 
                                            title="Click to change status">
                                        <span class="sample-status-pill <?= $stPillClass ?> badge-lead-status badge-status-<?= $statusSlug ?>" id="status-badge-<?= $lead['id'] ?>">
                                            <span class="badge-text"><?= Sanitizer::e($lead['status']) ?></span>
                                            <i data-lucide="chevron-down" style="width: 12px; height: 12px;" class="ms-1 status-chevron opacity-75"></i>
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu shadow-lg border-0 py-2 quick-status-menu" style="font-size: 0.82rem; z-index: 1050; max-height: 320px; overflow-y: auto; border-radius: 8px;">
                                        <li class="dropdown-header text-uppercase text-muted py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Update Status</li>
                                        <?php foreach ($statuses as $st): 
                                            $stSlug = trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $st), '-');
                                            $isActiveSt = ($lead['status'] === $st);
                                        ?>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3 quick-change-status-item <?= $isActiveSt ? 'active' : '' ?>" 
                                               href="javascript:void(0)" 
                                               data-lead-id="<?= $lead['id'] ?>" 
                                               data-status="<?= Sanitizer::e($st) ?>" 
                                               data-slug="<?= $stSlug ?>">
                                                <span class="d-flex align-items-center gap-2">
                                                    <span class="badge-dot badge-dot-<?= $stSlug ?>"></span>
                                                    <span class="fw-medium"><?= Sanitizer::e($st) ?></span>
                                                </span>
                                                <?php if ($isActiveSt): ?>
                                                    <i data-lucide="check" style="width: 13px; height: 13px;" class="text-success ms-2"></i>
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                        <?php endforeach; ?>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 text-primary btn-open-status-modal" 
                                               href="javascript:void(0)"
                                               data-lead-id="<?= $lead['id'] ?>"
                                               data-lead-name="<?= Sanitizer::e($lead['student_name']) ?>"
                                               data-lead-ref="<?= Sanitizer::e($lead['lead_ref']) ?>"
                                               data-current-status="<?= Sanitizer::e($lead['status']) ?>">
                                                <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                                                <span class="fw-semibold">Add Remarks & Custom Note...</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                                    <!-- View Button (opens right sidebar offcanvas as before) -->
                                    <button type="button" class="btn btn-sm btn-navy btn-pill px-2 py-1 btn-trigger-offcanvas d-inline-flex align-items-center gap-1 shadow-sm" 
                                            data-lead-id="<?= $lead['id'] ?>" title="View details in side drawer">
                                        <i data-lucide="eye" style="width: 13px; height: 13px;"></i>
                                        <span style="font-size: 0.78rem; font-weight: 600;">View</span>
                                    </button>

                                    <!-- Quick actions dropdown (vertical ⋮) -->
                                    <div class="dropdown d-inline-block">
                                        <button type="button" class="sample-action-btn" data-bs-toggle="dropdown" aria-expanded="false" title="More Actions">
                                            <i data-lucide="more-vertical" style="width: 15px; height: 15px;"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-2" style="font-size: 0.85rem; border-radius: 8px;">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 btn-open-status-modal" href="javascript:void(0)"
                                                   data-lead-id="<?= $lead['id'] ?>"
                                                   data-lead-name="<?= Sanitizer::e($lead['student_name']) ?>"
                                                   data-lead-ref="<?= Sanitizer::e($lead['lead_ref']) ?>"
                                                   data-current-status="<?= Sanitizer::e($lead['status']) ?>">
                                                    <i data-lucide="tag" style="width: 14px; height: 14px;" class="text-primary"></i> Change Status
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 btn-trigger-offcanvas" href="javascript:void(0)" data-lead-id="<?= $lead['id'] ?>">
                                                    <i data-lucide="eye" style="width: 14px; height: 14px;" class="text-primary"></i> View in Side Drawer
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= View::url('leads/edit&id=' . $lead['id']) ?>">
                                                    <i data-lucide="edit-2" style="width: 14px; height: 14px;" class="text-muted"></i> Edit Enquiry
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= View::url('leads/show&id=' . $lead['id']) ?>">
                                                    <i data-lucide="external-link" style="width: 14px; height: 14px;" class="text-muted"></i> Full Page Details
                                                </a>
                                            </li>
                                            <?php if (Auth::isSuperAdmin()): ?>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <form action="<?= View::url('leads/delete') ?>" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete enquiry for <?= Sanitizer::e(addslashes($lead['student_name'])) ?> (<?= Sanitizer::e($lead['lead_ref']) ?>)? This action cannot be undone.');" class="m-0">
                                                    <?= Csrf::field() ?>
                                                    <input type="hidden" name="id" value="<?= $lead['id'] ?>">
                                                    <input type="hidden" name="redirect_to" value="<?= Sanitizer::e($_SERVER['REQUEST_URI'] ?? View::url('leads')) ?>">
                                                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger w-100 border-0 bg-transparent text-start">
                                                        <i data-lucide="trash-2" style="width: 14px; height: 14px;" class="text-danger"></i> Delete Enquiry
                                                    </button>
                                                </form>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Sample Table Footer / Pagination -->
        <?php
        $perPageCount = (int)($pagination['per_page'] ?? 15);
        $totalEntries = (int)($pagination['total'] ?? 0);
        $currentPage = (int)($pagination['page'] ?? 1);
        $totalPages = (int)($pagination['total_pages'] ?? 1);
        $isAllMode = (($filters['per_page'] ?? '') === 'all' || $perPageCount <= 0);

        if ($isAllMode) {
            $startEntry = $totalEntries > 0 ? 1 : 0;
            $endEntry = $totalEntries;
        } else {
            $startEntry = $totalEntries > 0 ? (($currentPage - 1) * $perPageCount + 1) : 0;
            $endEntry = min($currentPage * $perPageCount, $totalEntries);
        }
        ?>
        <div class="sample-table-footer d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <p class="sample-entries-info m-0">
                    Showing <?= $startEntry ?> to <?= $endEntry ?> of <?= $totalEntries ?> entries
                </p>

                <!-- Qty Selector in footer -->
                <div class="d-flex align-items-center gap-1.5 small text-muted">
                    <span style="font-size: 0.78rem;">Show:</span>
                    <?php 
                    $activeQty = ($filters['per_page'] ?? '') === 'all' ? 'all' : (int)($filters['per_page'] ?? 15);
                    $qtyOptions = [15, 50, 100, 200];
                    ?>
                    <div class="btn-group btn-group-sm" role="group">
                        <?php foreach ($qtyOptions as $opt): ?>
                            <a href="<?= View::url('leads?' . http_build_query(array_merge($filters, ['per_page' => $opt, 'page' => 1]))) ?>" 
                               class="btn btn-xs <?= ($activeQty == $opt) ? 'btn-navy text-white fw-bold' : 'btn-outline-secondary' ?>"
                               style="font-size: 0.74rem; padding: 0.15rem 0.5rem;">
                                <?= $opt ?>
                            </a>
                        <?php endforeach; ?>
                        <a href="<?= View::url('leads?' . http_build_query(array_merge($filters, ['per_page' => 'all', 'page' => 1]))) ?>" 
                           class="btn btn-xs <?= ($activeQty === 'all') ? 'btn-navy text-white fw-bold' : 'btn-outline-secondary' ?>"
                           style="font-size: 0.74rem; padding: 0.15rem 0.5rem;" title="Show all leads without pagination">
                            All
                        </a>
                    </div>

                    <!-- Custom manual Qty input in footer -->
                    <form action="<?= View::url('leads') ?>" method="GET" class="d-inline-flex align-items-center gap-1 ms-1 m-0">
                        <input type="hidden" name="route" value="leads">
                        <?php foreach ($filters as $k => $v): if ($k !== 'per_page' && $k !== 'page' && !empty($v)): ?>
                            <input type="hidden" name="<?= Sanitizer::e($k) ?>" value="<?= Sanitizer::e($v) ?>">
                        <?php endif; endforeach; ?>
                        <input type="number" name="per_page" class="form-control form-control-sm text-center" 
                               placeholder="Qty" min="1" max="5000" 
                               value="<?= is_numeric($activeQty) && !in_array($activeQty, $qtyOptions) ? $activeQty : '' ?>" 
                               style="width: 58px; height: 26px; font-size: 0.75rem; padding: 2px 4px;" title="Enter custom quantity">
                        <button type="submit" class="btn btn-xs btn-outline-secondary" style="font-size: 0.72rem; padding: 2px 6px; height: 26px;">Go</button>
                    </form>
                </div>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="sample-pagination">
                <!-- First Page « -->
                <a href="<?= $currentPage > 1 ? View::url('leads?' . http_build_query(array_merge($filters, ['page' => 1]))) : 'javascript:void(0)' ?>" 
                   class="sample-page-btn <?= $currentPage <= 1 ? 'disabled' : '' ?>" title="First Page">
                    &laquo;
                </a>
                <!-- Previous Page ‹ -->
                <a href="<?= $currentPage > 1 ? View::url('leads?' . http_build_query(array_merge($filters, ['page' => $currentPage - 1]))) : 'javascript:void(0)' ?>" 
                   class="sample-page-btn <?= $currentPage <= 1 ? 'disabled' : '' ?>" title="Previous Page">
                    &lsaquo;
                </a>

                <!-- Numbered Pages -->
                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                if ($endPage - $startPage < 4 && $totalPages >= 5) {
                    if ($startPage === 1) {
                        $endPage = min($totalPages, 5);
                    } else {
                        $startPage = max(1, $totalPages - 4);
                    }
                }
                for ($p = $startPage; $p <= $endPage; $p++):
                ?>
                    <a href="<?= View::url('leads?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>" 
                       class="sample-page-btn page-num <?= $p === $currentPage ? 'active' : '' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <!-- Next Page › -->
                <a href="<?= $currentPage < $totalPages ? View::url('leads?' . http_build_query(array_merge($filters, ['page' => $currentPage + 1]))) : 'javascript:void(0)' ?>" 
                   class="sample-page-btn <?= $currentPage >= $totalPages ? 'disabled' : '' ?>" title="Next Page">
                    &rsaquo;
                </a>
                <!-- Last Page » -->
                <a href="<?= $currentPage < $totalPages ? View::url('leads?' . http_build_query(array_merge($filters, ['page' => $totalPages]))) : 'javascript:void(0)' ?>" 
                   class="sample-page-btn <?= $currentPage >= $totalPages ? 'disabled' : '' ?>" title="Last Page">
                    &raquo;
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bulk Assign Modal -->
    <div class="modal fade" id="bulkAssignModal" tabindex="-1" aria-labelledby="bulkAssignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <form action="<?= View::url('leads/bulk-assign') ?>" method="POST" id="bulkAssignModalForm">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="redirect_to" value="<?= View::url('leads') ?>">
                    <div id="bulkAssignHiddenContainer"></div>

                    <div class="modal-header border-bottom py-3 px-4">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bento-avatar avatar-blue" style="width: 32px; height: 32px; border-radius: 8px;">
                                <i data-lucide="users" style="width: 16px; height: 16px;"></i>
                            </div>
                            <h5 class="modal-title fw-bold text-dark fs-6" id="bulkAssignModalLabel">Bulk Allocate Enquiries</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="p-2.5 rounded-3 bg-light border mb-3 d-flex align-items-center justify-content-between text-dark" style="font-size: 0.8rem;">
                            <span class="text-muted">Selected Enquiries:</span>
                            <span class="badge bg-primary text-white rounded-pill px-2.5 py-1" id="bulkModalCountBadge">0 leads</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark mb-1">Assign to Staff Counselor *</label>
                            <select name="assigned_to" class="form-select form-select-sm" required style="height: 38px; font-size: 0.84rem; border-radius: 8px;">
                                <option value="auto" selected>⚡ Auto Distribute (Divide Equally to All Admission Counselors)</option>
                                <option value="">-- Or Select Specific Staff Member --</option>
                                <?php foreach ($counselors as $c): ?>
                                    <option value="<?= $c['id'] ?>">
                                        <?= Sanitizer::e($c['name']) ?> &mdash; <?= Sanitizer::e(!empty($c['designation']) ? $c['designation'] : ucfirst($c['role_slug'] ?? 'Staff')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark mb-1">Assignment Reason / Remarks</label>
                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Session 2027-28 batch allocation" value="Bulk allocation by admin" style="height: 38px; font-size: 0.84rem; border-radius: 8px;">
                        </div>

                        <div class="form-check p-2.5 rounded-2 bg-light border mb-2">
                            <input class="form-check-input ms-1" type="checkbox" name="create_task" value="1" id="bulkCreateTaskCheck" checked>
                            <label class="form-check-label small ms-2 text-dark fw-medium" for="bulkCreateTaskCheck">
                                Schedule initial follow-up call task in counselor's task list
                            </label>
                        </div>

                        <div id="bulkTaskFields">
                            <div class="mb-2">
                                <label class="form-label small text-muted mb-1">Task Title</label>
                                <input type="text" name="task_title" class="form-control form-control-sm" value="Initial Admission Enquiry Call" style="font-size: 0.82rem;">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-muted mb-1">Follow-up Due Date & Time</label>
                                <input type="datetime-local" name="task_due_date" class="form-control form-control-sm" value="<?= date('Y-m-d\TH:i', strtotime('+1 day 11:00')) ?>" style="font-size: 0.82rem;">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-top py-2.5 px-4 bg-light bg-opacity-50">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-navy rounded-pill px-4 d-inline-flex align-items-center gap-1.5 shadow-sm">
                            <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                            <span>Confirm Bulk Assignment</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (Auth::isSuperAdmin()): ?>
    <!-- Bulk Delete Selected Enquiries Modal (Super Admin Only) -->
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <form action="<?= View::url('leads/bulk-delete') ?>" method="POST" id="bulkDeleteModalForm">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="delete_mode" value="selected">
                    <input type="hidden" name="redirect_to" value="<?= Sanitizer::e($_SERVER['REQUEST_URI'] ?? View::url('leads')) ?>">
                    <div id="bulkDeleteHiddenContainer"></div>

                    <div class="modal-header border-bottom py-3 px-4 bg-danger text-white">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
                            <h5 class="modal-title fw-bold text-white fs-6 mb-0" id="bulkDeleteModalLabel">Confirm Bulk Deletion</h5>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
                            <i data-lucide="alert-circle" class="flex-shrink-0 mt-0.5" style="width: 18px; height: 18px;"></i>
                            <div class="small">
                                <strong>Warning:</strong> You are about to permanently delete <strong><span id="bulkDeleteCountSpan">0</span> selected enquiry(ies)</strong>.
                                This will also permanently remove all associated follow-up call logs, tasks, and documents.
                            </div>
                        </div>

                        <p class="text-muted small mb-3">
                            This action is strictly restricted to Super Administrators and <strong>CANNOT</strong> be undone.
                        </p>

                        <div class="form-check p-2.5 rounded bg-light border">
                            <input class="form-check-input ms-1" type="checkbox" id="confirmBulkDeleteCheck" required>
                            <label class="form-check-label small ms-2 fw-semibold text-danger" for="confirmBulkDeleteCheck">
                                I confirm permanent deletion of the selected enquiries
                            </label>
                        </div>
                    </div>

                    <div class="modal-footer border-top py-2.5 px-4 bg-light">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 d-inline-flex align-items-center gap-1.5 shadow-sm" id="btnSubmitBulkDelete">
                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                            <span>Permanently Delete Selected</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Filter-Wise Delete Enquiries Modal (Super Admin Only) -->
    <div class="modal fade" id="filterDeleteModal" tabindex="-1" aria-labelledby="filterDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <form action="<?= View::url('leads/bulk-delete') ?>" method="POST" id="filterDeleteModalForm">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="delete_mode" value="filtered">
                    <input type="hidden" name="redirect_to" value="<?= View::url('leads') ?>">
                    <?php foreach ($filters as $fk => $fv): ?>
                        <?php if (!empty($fv) && $fk !== 'page' && $fk !== 'per_page'): ?>
                            <input type="hidden" name="<?= Sanitizer::e($fk) ?>" value="<?= Sanitizer::e($fv) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <div class="modal-header border-bottom py-3 px-4 bg-danger text-white">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="filter-x" style="width: 20px; height: 20px;"></i>
                            <h5 class="modal-title fw-bold text-white fs-6 mb-0" id="filterDeleteModalLabel">Delete All Filtered Enquiries</h5>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
                            <i data-lucide="alert-triangle" class="flex-shrink-0 mt-0.5 text-danger" style="width: 20px; height: 20px;"></i>
                            <div class="small">
                                <strong>High Risk Action:</strong> You are about to permanently delete <strong>ALL <?= (int)($pagination['total'] ?? 0) ?> enquiries</strong> matching your current active filter!
                            </div>
                        </div>

                        <!-- Active filter summary list -->
                        <div class="bg-light p-3 rounded border mb-3 small">
                            <div class="fw-bold text-dark mb-1.5">Applied Filters:</div>
                            <ul class="mb-0 ps-3 text-muted">
                                <?php if (!empty($filters['status'])): ?>
                                    <li>Status: <strong class="text-dark"><?= $filters['status'] === 'public_enquiry' ? '🌐 Public Online Form' : Sanitizer::e($filters['status']) ?></strong></li>
                                <?php endif; ?>
                                <?php if (!empty($filters['class_applied'])): ?>
                                    <li>Class: <strong class="text-dark"><?= Sanitizer::e($filters['class_applied']) ?></strong></li>
                                <?php endif; ?>
                                <?php if (!empty($filters['assigned_to'])): ?>
                                    <li>Assigned Staff ID: <strong class="text-dark"><?= Sanitizer::e($filters['assigned_to']) ?></strong></li>
                                <?php endif; ?>
                                <?php if (!empty($filters['followup_due'])): ?>
                                    <li>Follow-up Due: <strong class="text-dark"><?= Sanitizer::e($filters['followup_due']) ?></strong></li>
                                <?php endif; ?>
                                <?php if (!empty($filters['search'])): ?>
                                    <li>Search Query: <strong class="text-dark"><?= Sanitizer::e($filters['search']) ?></strong></li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <p class="text-danger small mb-3">
                            <strong>Note:</strong> All follow-up logs, tasks, and student documents linked to these <?= (int)($pagination['total'] ?? 0) ?> enquiries will be permanently wiped out.
                        </p>

                        <div class="form-check p-2.5 rounded bg-light border">
                            <input class="form-check-input ms-1" type="checkbox" id="confirmFilterDeleteCheck" required>
                            <label class="form-check-label small ms-2 fw-semibold text-danger" for="confirmFilterDeleteCheck">
                                I understand that deleting <?= (int)($pagination['total'] ?? 0) ?> filtered records is permanent and cannot be undone.
                            </label>
                        </div>
                    </div>

                    <div class="modal-footer border-top py-2.5 px-4 bg-light">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 d-inline-flex align-items-center gap-1.5 shadow-sm" id="btnSubmitFilterDelete">
                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                            <span>Permanently Delete All <?= (int)($pagination['total'] ?? 0) ?> Filtered</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

<!-- Compact Change Status Modal -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header bg-white border-bottom px-4 py-3 align-items-center">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 36px; height: 36px; background: #EEF2FF; color: #4F46E5;">
                        <i data-lucide="tag" style="width: 18px; height: 18px;"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="changeStatusModalLabel" style="font-size: 1rem; letter-spacing: -0.2px;">Change Status</h6>
                        <div class="text-muted d-flex align-items-center gap-1.5 mt-0.5" style="font-size: 0.76rem;">
                            <span class="badge bg-light text-dark border px-1.5 py-0.5 fw-semibold" id="csmLeadRef">GDG-XXXX</span>
                            <span class="fw-semibold text-dark text-truncate" style="max-width: 200px;" id="csmStudentName">Student Name</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.78rem;"></button>
            </div>

            <!-- Modal Form -->
            <form id="changeStatusForm">
                <?= Csrf::field() ?>
                <input type="hidden" id="csmLeadId" value="">

                <div class="modal-body px-4 py-3 bg-white">
                    <!-- Current Status Row -->
                    <div class="d-flex align-items-center justify-content-between p-2.5 rounded-3 mb-3 p-2" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                        <span class="text-secondary small fw-medium" style="font-size: 0.8rem;">Current Status:</span>
                        <span class="sample-status-pill sample-status-active" id="csmCurrentStatusBadge" style="font-size: 0.78rem; padding: 0.25rem 0.75rem;">
                            <span class="badge-text" id="csmCurrentStatusText">—</span>
                        </span>
                    </div>

                    <!-- New Status Dropdown Selection -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1.5" style="font-size: 0.82rem;">Select New Status *</label>
                        <select id="csmNewStatusSelect" class="form-select form-select-sm py-2 px-3 fw-semibold text-dark shadow-none" style="font-size: 0.88rem; border-radius: 8px; border-color: #CBD5E1;" required>
                            <?php foreach ($statuses as $st): ?>
                                <option value="<?= Sanitizer::e($st) ?>"><?= Sanitizer::e($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Quick Pick Status Pills Grid -->
                    <div class="mb-3">
                        <div class="text-muted small fw-medium mb-1.5" style="font-size: 0.74rem;">Quick Pick:</div>
                        <div class="d-flex flex-wrap gap-1" id="csmQuickPickPills">
                            <?php foreach ($statuses as $st): 
                                $slug = trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $st), '-');
                            ?>
                            <button type="button" class="btn btn-xs rounded-pill px-2.5 py-1 csm-quick-pill" 
                                    data-status="<?= Sanitizer::e($st) ?>" 
                                    data-slug="<?= $slug ?>"
                                    style="font-size: 0.74rem; background: #F1F5F9; color: #334155;">
                                <?= Sanitizer::e($st) ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Optional Remarks -->
                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.8rem;">
                            Remarks / Follow-up Note <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <textarea id="csmRemarks" class="form-control form-control-sm" rows="2" 
                                  placeholder="e.g. Visited school campus today, parent requested fee structure..." 
                                  style="font-size: 0.82rem; border-radius: 8px; resize: none;"></textarea>
                        <div class="form-text text-muted" style="font-size: 0.72rem;">Automatically logs to the enquiry's timeline activity if provided.</div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-light px-4 py-2.5 border-top d-flex align-items-center justify-content-between">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3 rounded-pill" data-bs-dismiss="modal" style="font-size: 0.82rem;">Cancel</button>
                    <button type="submit" id="csmSubmitBtn" class="btn btn-sm btn-primary px-3.5 py-1.5 rounded-pill d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 0.82rem; font-weight: 600; background: #1859F2; border-color: #1859F2;">
                        <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                        <span>Update Status</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Export Columns Selection Modal -->
<div class="modal fade" id="exportColumnsModal" tabindex="-1" aria-labelledby="exportColumnsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 620px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header bg-white border-bottom px-4 py-3 align-items-center">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width: 36px; height: 36px; background: #ECFDF5; color: #059669;">
                        <i data-lucide="file-spreadsheet" style="width: 18px; height: 18px;"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="exportColumnsModalLabel" style="font-size: 0.98rem; letter-spacing: -0.2px;">Export to Excel / CSV</h6>
                        <div class="text-muted mt-0.5" style="font-size: 0.76rem;">Select columns to include in your downloaded spreadsheet.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.78rem;"></button>
            </div>

            <!-- Modal Form -->
            <form action="<?= View::url('export/leads') ?>" method="POST" id="exportColumnsForm">
                <?= Csrf::field() ?>
                
                <!-- Carry forward active filters -->
                <?php foreach ($filters as $fk => $fv): ?>
                    <?php if (!empty($fv)): ?>
                        <input type="hidden" name="<?= Sanitizer::e($fk) ?>" value="<?= Sanitizer::e($fv) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>

                <div class="modal-body px-4 py-3 bg-white">
                    <!-- Scope & Format Selection -->
                    <div class="p-2.5 rounded-3 mb-3 border d-flex align-items-center justify-content-between flex-wrap gap-2" style="background: #F8FAFC; border-color: #E2E8F0 !important;">
                        <!-- Format: Excel vs CSV -->
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-secondary small fw-medium" style="font-size: 0.8rem;">Format:</span>
                            <div class="form-check m-0 d-flex align-items-center gap-1.5">
                                <input class="form-check-input mt-0" type="radio" name="export_format" id="formatExcel" value="excel" checked style="cursor: pointer;">
                                <label class="form-check-label small fw-bold text-dark" for="formatExcel" style="font-size: 0.78rem; cursor: pointer;">
                                    Excel (.xls) <span class="badge bg-success-subtle text-success py-0.5 px-1 ms-0.5" style="font-size: 0.68rem; font-weight: 600;">Formatted</span>
                                </label>
                            </div>
                            <div class="form-check m-0 d-flex align-items-center gap-1.5 ms-1">
                                <input class="form-check-input mt-0" type="radio" name="export_format" id="formatCsv" value="csv" style="cursor: pointer;">
                                <label class="form-check-label small fw-semibold text-muted" for="formatCsv" style="font-size: 0.78rem; cursor: pointer;">CSV (.csv)</label>
                            </div>
                        </div>

                        <!-- Scope: Filtered vs All -->
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-secondary small fw-medium" style="font-size: 0.8rem;">Scope:</span>
                            <div class="form-check m-0 d-flex align-items-center gap-1.5">
                                <input class="form-check-input mt-0" type="radio" name="export_scope" id="scopeFiltered" value="filtered" checked style="cursor: pointer;">
                                <label class="form-check-label small fw-semibold text-dark" for="scopeFiltered" style="font-size: 0.78rem; cursor: pointer;">Filtered</label>
                            </div>
                            <div class="form-check m-0 d-flex align-items-center gap-1.5">
                                <input class="form-check-input mt-0" type="radio" name="export_scope" id="scopeAll" value="all_session" style="cursor: pointer;">
                                <label class="form-check-label small fw-semibold text-dark" for="scopeAll" style="font-size: 0.78rem; cursor: pointer;">All in Session</label>
                            </div>
                        </div>
                    </div>

                    <!-- Selection Preset Controls Bar -->
                    <div class="d-flex align-items-center justify-content-between pb-2 mb-2.5 border-bottom">
                        <div class="d-flex align-items-center gap-1.5">
                            <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2.5 py-1" id="exportBtnSelectAll" style="font-size: 0.74rem;">
                                Select All
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2.5 py-1" id="exportBtnDeselectAll" style="font-size: 0.74rem;">
                                Deselect All
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-1" id="exportBtnDefault" style="font-size: 0.74rem;">
                                Default
                            </button>
                        </div>
                        <div>
                            <span class="badge rounded-pill px-2.5 py-1" id="exportColumnCounter" style="font-size: 0.74rem; background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0;">
                                <strong id="exportCountNum" class="text-primary">0</strong> of <?= count($exportColumns) ?> selected
                            </span>
                        </div>
                    </div>

                    <!-- Column Checkboxes Grid (Clean: only checkbox and bold heading) -->
                    <div class="row g-2" id="exportCheckboxGrid">
                        <?php foreach ($exportColumns as $colKey => $colMeta): 
                            $isDef = !empty($colMeta['default']);
                        ?>
                        <div class="col-sm-6 col-md-4">
                            <label class="export-col-card d-flex align-items-center gap-2 px-2.5 py-2 user-select-none" 
                                   for="col_chk_<?= $colKey ?>">
                                <input type="checkbox" 
                                       name="columns[]" 
                                       value="<?= $colKey ?>" 
                                       id="col_chk_<?= $colKey ?>" 
                                       class="form-check-input export-column-chk flex-shrink-0" 
                                       data-default="<?= $isDef ? '1' : '0' ?>"
                                       <?= $isDef ? 'checked' : '' ?>>
                                <span class="export-col-label text-truncate"><?= Sanitizer::e($colMeta['label']) ?></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-light px-4 py-2.5 border-top d-flex align-items-center justify-content-between">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3.5 py-1.5 rounded-pill" data-bs-dismiss="modal" style="font-size: 0.82rem;">Cancel</button>
                    <button type="submit" id="exportSubmitBtn" class="btn btn-sm btn-success px-4 py-1.5 rounded-pill d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 0.84rem; font-weight: 600; background: #16A34A; border-color: #16A34A;">
                        <i data-lucide="file-spreadsheet" style="width: 14px; height: 14px;"></i>
                        <span id="exportSubmitBtnText">Download Excel</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Lead Quick Details Offcanvas Drawer (Matching Modern Sample Design) -->
<div class="offcanvas offcanvas-end border-0 shadow-lg lead-detail-drawer" tabindex="-1" id="leadDetailsOffcanvas" aria-labelledby="leadDetailsOffcanvasLabel">
    <!-- Top Header Bar -->
    <div class="offcanvas-header bg-white px-4 py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn p-0 border-0 text-dark d-flex align-items-center justify-content-center" data-bs-dismiss="offcanvas" aria-label="Close" style="width: 28px; height: 28px;">
                <i data-lucide="x" style="width: 20px; height: 20px;" class="text-dark"></i>
            </button>
            <h5 class="offcanvas-title fw-bold mb-0 text-dark" style="font-size: 1.15rem; letter-spacing: -0.3px;">Lead Detail</h5>
        </div>
        <a href="#" id="offcanvasFullProfileLink" class="btn btn-black-pill px-3 py-1.5 d-inline-flex align-items-center gap-1 shadow-sm" style="font-size: 0.8rem;" title="View Complete Lead Profile">
            <span>View Lead Details</span>
        </a>
    </div>

    <!-- Offcanvas Scrollable Body -->
    <div class="offcanvas-body p-0" style="overflow-y: auto;">
        <!-- Lead Hero Profile Row -->
        <div class="px-4 pt-4 pb-3 bg-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <!-- Avatar + Student Name + Email / Phone -->
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" id="offcanvasHeroAvatar" style="width: 52px; height: 52px; background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); font-size: 1.3rem;">
                        S
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-1 text-truncate" id="offcanvasStudentName" style="font-size: 1.2rem; letter-spacing: -0.2px;">Student Name</h5>
                        
                        <!-- Status and Priority Badges Just Below Name -->
                        <div class="d-flex align-items-center gap-2 mb-1.5 flex-wrap">
                            <!-- Status Dropdown Trigger -->
                            <div class="dropdown">
                                <button class="btn btn-sm p-0 border-0 bg-transparent dropdown-toggle status-dropdown-btn text-decoration-none shadow-none" 
                                        type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Click to update status">
                                    <span class="badge badge-lead-status px-2.5 py-1" id="offcanvasStatusBadge">
                                        <span class="badge-dot"></span>
                                        <span class="badge-text" id="offcanvasStatusText">New</span>
                                        <i data-lucide="chevron-down" class="dropdown-arrow-icon ms-1" style="width: 12px; height: 12px;"></i>
                                    </span>
                                </button>
                                <ul class="dropdown-menu shadow-lg border-0 py-2 quick-status-menu" style="max-height: 280px; overflow-y: auto; font-size: 0.82rem; z-index: 1060;">
                                    <li class="dropdown-header text-uppercase text-muted py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Update Status</li>
                                    <?php foreach ($statuses as $st): 
                                        $stSlug = trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $st), '-');
                                    ?>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3 quick-change-status-item offcanvas-status-option" 
                                           href="#" data-lead-id="" data-status="<?= Sanitizer::e($st) ?>" data-slug="<?= $stSlug ?>">
                                            <span class="d-flex align-items-center gap-2">
                                                <span class="badge-dot badge-dot-<?= $stSlug ?>"></span>
                                                <span><?= Sanitizer::e($st) ?></span>
                                            </span>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Priority Dropdown Trigger -->
                            <div class="dropdown">
                                <button class="btn btn-sm p-0 border-0 bg-transparent dropdown-toggle priority-dropdown-btn text-decoration-none shadow-none" 
                                        type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Click to update priority">
                                    <span class="badge priority-badge px-2.5 py-1" id="offcanvasPriorityBadge">
                                        <span class="priority-icon" id="offcanvasPriorityIcon"></span>
                                        <span class="badge-text" id="offcanvasPriorityText">Medium</span>
                                        <i data-lucide="chevron-down" class="dropdown-arrow-icon ms-1" style="width: 12px; height: 12px;"></i>
                                    </span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2 quick-priority-menu" style="font-size: 0.82rem; z-index: 1060;">
                                    <li class="dropdown-header text-uppercase text-muted py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Update Priority</li>
                                    <?php foreach (['Urgent', 'High', 'Medium', 'Low'] as $pr): ?>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3 quick-change-priority-item offcanvas-priority-option" 
                                           href="#" data-lead-id="" data-priority="<?= $pr ?>">
                                            <span class="d-flex align-items-center gap-2">
                                                <span class="priority-dot priority-dot-<?= $pr ?>"></span>
                                                <span><?= $pr ?></span>
                                            </span>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Email line -->
                        <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.78rem;">
                            <i data-lucide="mail" style="width: 13px; height: 13px;" class="text-muted"></i>
                            <span id="offcanvasEmail" class="text-muted text-truncate" style="max-width: 220px;">student@example.com</span>
                        </div>
                    </div>
                </div>

                <!-- Contact Action Icons (Order: 1. Call, 2. WhatsApp, 3. Email, 4. More Options) -->
                <div class="d-flex align-items-center gap-2">
                    <!-- 1. Call Button -->
                    <a href="#" id="offcanvasCallBtn" class="action-circle-btn text-dark border shadow-xs" title="Call Mobile">
                        <i data-lucide="phone" style="width: 16px; height: 16px;"></i>
                    </a>
                    <!-- 2. WhatsApp Button (Opens Template Modal) -->
                    <button type="button" id="offcanvasWhatsAppBtn" class="action-circle-btn text-success border border-success-subtle bg-success-subtle shadow-xs" title="Send WhatsApp Message">
                        <i data-lucide="message-circle" style="width: 17px; height: 17px;"></i>
                    </button>
                    <!-- 3. Email Button (Opens Template Modal) -->
                    <button type="button" id="offcanvasEmailBtn" class="action-circle-btn text-primary border border-primary-subtle bg-primary-subtle shadow-xs" title="Send Email Message">
                        <i data-lucide="mail" style="width: 16px; height: 16px;"></i>
                    </button>
                    <!-- 4. More Options -->
                    <div class="dropdown">
                        <button class="action-circle-btn dropdown-toggle shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More options">
                            <i data-lucide="more-horizontal" style="width: 16px; height: 16px;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2" style="border-radius: 12px; font-size: 0.82rem; z-index: 1060;">
                            <li><a class="dropdown-item py-2 px-3" href="#" id="offcanvasEditBtn"><i data-lucide="edit-3" style="width: 14px; height: 14px;" class="me-2 text-secondary"></i> Edit Enquiry</a></li>
                            <li><a class="dropdown-item py-2 px-3" href="#" id="offcanvasFooterFullBtn"><i data-lucide="external-link" style="width: 14px; height: 14px;" class="me-2 text-secondary"></i> Full Lead Page</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 4-Column Metric Box (Lead owner | Location | Referral Partner/Class | Annual Income/Ref #) -->
            <div class="metric-grid-box mt-3 overflow-hidden d-flex flex-wrap">
                <div class="col-6 col-md-3 metric-grid-cell border-end border-bottom border-md-bottom-0">
                    <div class="text-muted small" style="font-size: 0.72rem;">Lead owner</div>
                    <div class="fw-bold text-dark mt-0.5 text-truncate" id="offcanvasCounselor" style="font-size: 0.88rem;">—</div>
                </div>
                <div class="col-6 col-md-3 metric-grid-cell border-end border-bottom border-md-bottom-0">
                    <div class="text-muted small" style="font-size: 0.72rem;">Location</div>
                    <div class="fw-bold text-dark mt-0.5 text-truncate" id="offcanvasCity" style="font-size: 0.88rem;">Purnea</div>
                </div>
                <div class="col-6 col-md-3 metric-grid-cell border-end">
                    <div class="text-muted small" style="font-size: 0.72rem;">Class Applied</div>
                    <div class="fw-bold text-dark mt-0.5 text-truncate" id="offcanvasClass" style="font-size: 0.88rem;">—</div>
                </div>
                <div class="col-6 col-md-3 metric-grid-cell">
                    <div class="text-muted small" style="font-size: 0.72rem;">Ref #</div>
                    <div class="fw-bold text-dark mt-0.5 text-truncate" id="offcanvasLeadRef" style="font-size: 0.88rem;">GDG-REF</div>
                </div>
            </div>

            <!-- Progress Bar Box (matching sample screenshot) -->
            <div class="progress-card-box p-3 mt-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="loader" style="width: 15px; height: 15px;" class="text-dark"></i>
                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">Progress</span>
                    </div>
                    <span class="text-muted small fw-medium" id="offcanvasProgressLabel" style="font-size: 0.82rem;">76% completed</span>
                </div>
                <div class="progress-track-wrapper position-relative">
                    <div id="offcanvasProgressBar" class="progress-fill-bar" style="width: 76%;"></div>
                    <div id="offcanvasProgressThumb" class="progress-thumb-knob" style="left: 76%;"></div>
                </div>
            </div>
        </div>

        <!-- Latest Activities / Recent Follow-up Logs Section (Matching Sample Timeline) -->
        <div class="px-4 py-4 bg-white border-bottom">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem; letter-spacing: -0.2px;">Latest Activities</h6>
                    <span class="badge-yellow-pill" id="offcanvasActivityCount">0</span>
                </div>
                <a href="#" id="offcanvasViewLogsLink" class="text-dark small text-decoration-none fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 0.8rem;">
                    <i data-lucide="history" style="width: 14px; height: 14px;"></i>
                    <span>View All Activity</span>
                </a>
            </div>

            <!-- Vertical Timeline matching sample -->
            <div class="timeline-v2-container" id="offcanvasFollowupList">
                <div class="text-center py-3 text-muted">
                    <span class="spinner-border spinner-border-sm text-secondary" role="status"></span>
                    <span class="ms-2 small">Loading activities...</span>
                </div>
            </div>
        </div>

        <!-- Notes Section (Matching Sample Notes Card) -->
        <div class="px-4 py-4 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem; letter-spacing: -0.2px;">Notes</h6>
                    <span class="badge-yellow-pill" id="offcanvasNotesCount">1</span>
                </div>
                <a href="#" id="offcanvasAddNoteBtn" class="text-dark small text-decoration-none fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 0.8rem;">
                    <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
                    <span>Add Note</span>
                </a>
            </div>

            <!-- Notes Card Box -->
            <div class="notes-card-box">
                <div class="notes-card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="file-text" style="width: 15px; height: 15px;" class="text-secondary"></i>
                        <span class="fw-semibold text-dark small">Note by <strong id="offcanvasNoteAuthor">Counselor</strong></span>
                    </div>
                    <div class="text-muted small d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                        <i data-lucide="calendar" style="width: 12px; height: 12px;"></i>
                        <span id="offcanvasNoteDate">Today 12:00 PM</span>
                    </div>
                </div>
                <div class="notes-card-body">
                    <p class="mb-0 text-secondary" id="offcanvasRemarks" style="white-space: pre-wrap;">No remarks or special notes entered yet.</p>
                </div>
            </div>

            <!-- Additional Contact & Admission Summary Accordion -->
            <div class="mt-4 pt-3 border-top">
                <div class="d-flex align-items-center justify-content-between text-muted small py-1">
                    <span>Parent:</span>
                    <strong class="text-dark" id="offcanvasParentName">—</strong>
                </div>
                <div class="d-flex align-items-center justify-content-between text-muted small py-1">
                    <span>Mobile:</span>
                    <strong class="text-dark" id="offcanvasMobile">—</strong>
                </div>
                <div class="d-flex align-items-center justify-content-between text-muted small py-1">
                    <span>Enquiry Source:</span>
                    <span class="text-dark" id="offcanvasSource">—</span>
                </div>
                <div class="d-flex align-items-center justify-content-between text-muted small py-1">
                    <span>Next Follow-up Date:</span>
                    <span class="text-dark" id="offcanvasFollowupDate">—</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- QUICK WHATSAPP MODAL (With Status Pre-templates & Manual Editing)          -->
<!-- ========================================================================= -->
<div class="modal fade" id="leadQuickWhatsAppModal" tabindex="-1" aria-labelledby="leadQuickWhatsAppModalLabel" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-2xl" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header border-bottom py-3 px-4" style="background: linear-gradient(135deg, #128c7e 0%, #25d366 100%); color: white;">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-circle bg-white text-success d-flex align-items-center justify-content-center shadow-xs" style="width: 36px; height: 36px;">
                        <i data-lucide="message-circle" style="width: 20px; height: 20px; color: #25d366;"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="leadQuickWhatsAppModalLabel" style="font-size: 1rem; letter-spacing: -0.2px;">Send WhatsApp Message</h6>
                        <span class="text-white text-opacity-80 small" style="font-size: 0.72rem;">Pre-made status templates & manual composer</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light bg-opacity-50">
                <!-- Recipient Info Card -->
                <div class="p-3 bg-white border rounded-3 mb-3 shadow-xs">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Candidate:</div>
                            <strong class="text-dark" id="quickWaStudentName">Student Name</strong>
                            <span class="badge bg-light text-secondary border ms-1" id="quickWaClassBadge" style="font-size: 0.7rem;">Grade</span>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small" style="font-size: 0.72rem;">Recipient Mobile:</div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold" id="quickWaMobileBadge" style="font-size: 0.8rem;">+91 —</span>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
                        <span class="text-muted small" style="font-size: 0.72rem;">Current Status:</span>
                        <span class="badge badge-lead-status px-2 py-0.5" id="quickWaStatusBadge">
                            <span class="badge-dot"></span>
                            <span class="badge-text" id="quickWaStatusText">New</span>
                        </span>
                    </div>
                </div>

                <!-- Template Selector -->
                <div class="mb-3">
                    <label class="form-label text-dark fw-semibold small mb-1.5 d-flex align-items-center justify-content-between">
                        <span><i data-lucide="sparkles" style="width: 14px; height: 14px;" class="text-warning me-1"></i> Choose Template</span>
                        <span class="text-muted fw-normal" style="font-size: 0.72rem;">Auto-tailored to status</span>
                    </label>
                    <select class="form-select form-select-sm shadow-none" id="quickWaTemplateSelect" style="border-radius: 8px;">
                        <option value="auto">⚡ Status-Matched Template (Auto)</option>
                        <option value="greeting">👋 1. New Enquiry Greeting & Introduction</option>
                        <option value="campus_visit">🏫 2. Campus Visit Invitation & Directions</option>
                        <option value="fee_details">💰 3. Fee Structure & Admission Process</option>
                        <option value="doc_pending">📄 4. Required Documents Checklist</option>
                        <option value="followup">📞 5. Follow-up & Counseling Discussion</option>
                        <option value="admission_confirmed">🎉 6. Congratulations - Admission Confirmed</option>
                        <option value="custom">✍️ Custom Message (Manual Compose)</option>
                    </select>
                </div>

                <!-- Message Compose Textarea with WhatsApp Formatting Toolbar -->
                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                        <label class="form-label text-dark fw-semibold small mb-0">Message Content</label>
                        <span class="text-muted small" id="quickWaCharCount" style="font-size: 0.72rem;">0 characters</span>
                    </div>
                    <!-- Quick Formatting Toolbar for WhatsApp -->
                    <div class="wa-format-toolbar">
                        <button type="button" class="wa-format-btn" data-format="bold" title="Wrap selection in *bold*"><b>*B*</b> Bold</button>
                        <button type="button" class="wa-format-btn" data-format="italic" title="Wrap selection in _italic_"><i>_I_</i> Italic</button>
                        <button type="button" class="wa-format-btn" data-format="bullet" title="Insert Bullet list item">• Bullet</button>
                        <button type="button" class="wa-format-btn" data-insert="📞 Toll-Free: 1800 891 1934" title="Insert Toll-Free number">📞 Toll-Free</button>
                        <button type="button" class="wa-format-btn" data-insert="✉️ info@gdgoenkapurnea.com" title="Insert Email">✉️ Email</button>
                        <div class="ms-auto d-flex gap-1">
                            <button type="button" class="wa-format-btn px-1.5" data-insert="🎓" title="Graduate cap">🎓</button>
                            <button type="button" class="wa-format-btn px-1.5" data-insert="🏫" title="School">🏫</button>
                            <button type="button" class="wa-format-btn px-1.5" data-insert="📋" title="Checklist">📋</button>
                            <button type="button" class="wa-format-btn px-1.5" data-insert="🎉" title="Celebration">🎉</button>
                        </div>
                    </div>
                    <textarea class="form-control wa-textarea-attached" id="quickWaMessage" rows="7" style="font-size: 0.85rem; line-height: 1.55;" placeholder="Type your WhatsApp message or select a template above..."></textarea>
                    <div class="form-text text-muted" style="font-size: 0.72rem;">Formatted for WhatsApp: supports <code>*bold*</code>, <code>_italic_</code>, bullet points, and clean paragraphs.</div>
                </div>

                <!-- Log Activity Option -->
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="quickWaLogActivity" checked>
                    <label class="form-check-label text-dark small" for="quickWaLogActivity">
                        Record this WhatsApp interaction on the activity timeline
                    </label>
                </div>
            </div>
            <div class="modal-footer py-2.5 px-4 bg-white border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-none" id="btnCopyWhatsAppMsg" title="Copy to clipboard">
                    <i data-lucide="copy" style="width: 14px; height: 14px;"></i>
                    <span>Copy Text</span>
                </button>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 shadow-sm" id="btnSendWhatsAppWeb" title="Open directly in WhatsApp Web in browser">
                        <i data-lucide="globe" style="width: 14px; height: 14px;"></i>
                        <span>WhatsApp Web</span>
                    </button>
                    <button type="button" class="btn btn-sm text-white px-3.5 d-inline-flex align-items-center gap-1.5 shadow-sm" id="btnConfirmSendWhatsApp" style="background: #25d366; font-weight: 600;">
                        <i data-lucide="send" style="width: 14px; height: 14px;"></i>
                        <span>Send on WhatsApp</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- QUICK EMAIL MODAL (With Templates & Compose)                              -->
<!-- ========================================================================= -->
<div class="modal fade" id="leadQuickEmailModal" tabindex="-1" aria-labelledby="leadQuickEmailModalLabel" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-2xl" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header border-bottom py-3 px-4" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color: white;">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-xs" style="width: 36px; height: 36px;">
                        <i data-lucide="mail" style="width: 20px; height: 20px; color: #4f46e5;"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="leadQuickEmailModalLabel" style="font-size: 1rem; letter-spacing: -0.2px;">Compose & Send Email</h6>
                        <span class="text-white text-opacity-80 small" style="font-size: 0.72rem;">Pre-made templates with subject and customizable body</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light bg-opacity-50">
                <!-- Recipient Info Card -->
                <div class="row g-2 mb-3">
                    <div class="col-md-7">
                        <label class="form-label text-dark fw-semibold small mb-1">To Email Address</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i data-lucide="at-sign" style="width: 14px; height: 14px;"></i></span>
                            <input type="email" class="form-control border-start-0 ps-0 shadow-none" id="quickEmailTo" placeholder="student@example.com">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label text-dark fw-semibold small mb-1">Candidate</label>
                        <input type="text" class="form-control form-control-sm bg-white shadow-none" id="quickEmailStudentName" readonly value="Student">
                    </div>
                </div>

                <!-- Template Selector -->
                <div class="mb-3">
                    <label class="form-label text-dark fw-semibold small mb-1.5 d-flex align-items-center justify-content-between">
                        <span><i data-lucide="sparkles" style="width: 14px; height: 14px;" class="text-warning me-1"></i> Email Template</span>
                        <span class="text-muted fw-normal" style="font-size: 0.72rem;">Auto fills subject & body</span>
                    </label>
                    <select class="form-select form-select-sm shadow-none" id="quickEmailTemplateSelect" style="border-radius: 8px;">
                        <option value="auto">⚡ Status-Matched Email Template (Auto)</option>
                        <option value="brochure">📘 1. Admission Prospectus & School Overview</option>
                        <option value="campus_tour">🏫 2. Invitation for Campus Tour & Interaction</option>
                        <option value="fee_structure">📄 3. Fee Structure & Payment Guidelines</option>
                        <option value="docs_checklist">📋 4. Required Admission Documents Checklist</option>
                        <option value="welcome_letter">🎓 5. Congratulations - Welcome to GD Goenka</option>
                        <option value="custom">✍️ Custom Blank Email</option>
                    </select>
                </div>

                <!-- Subject -->
                <div class="mb-3">
                    <label class="form-label text-dark fw-semibold small mb-1">Subject</label>
                    <input type="text" class="form-control form-control-sm shadow-none" id="quickEmailSubject" style="border-radius: 8px;" placeholder="Admission Enquiry Follow-up...">
                </div>

                <!-- Message Body -->
                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label text-dark fw-semibold small mb-0">Email Message Body</label>
                        <span class="text-muted small" id="quickEmailCharCount" style="font-size: 0.72rem;">0 characters</span>
                    </div>
                    <textarea class="form-control" id="quickEmailBody" rows="7" style="border-radius: 10px; font-size: 0.85rem; line-height: 1.5;" placeholder="Compose your email message..."></textarea>
                </div>

                <!-- Log Activity Option -->
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="quickEmailLogActivity" checked>
                    <label class="form-check-label text-dark small" for="quickEmailLogActivity">
                        Record this Email in candidate activity timeline
                    </label>
                </div>
            </div>
            <div class="modal-footer py-2.5 px-4 bg-white border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-none" id="btnCopyEmailBody" title="Copy formatted text to clipboard">
                    <i data-lucide="copy" style="width: 14px; height: 14px;"></i>
                    <span>Copy Formatted</span>
                </button>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm text-white d-inline-flex align-items-center gap-1.5 shadow-sm" id="btnSendViaGmail" style="background: #ea4335; font-weight: 600;" title="Open and compose directly in Gmail Web in new tab">
                        <i data-lucide="mail" style="width: 14px; height: 14px;"></i>
                        <span>Open in Gmail</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary px-3 d-inline-flex align-items-center gap-1.5 shadow-sm" id="btnConfirmSendEmail" style="font-weight: 600;" title="Open in Outlook / Default Mail App">
                        <i data-lucide="external-link" style="width: 14px; height: 14px;"></i>
                        <span>Outlook / App</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Bulk Assign Modal opener with checked IDs injection
document.getElementById('btnOpenBulkAssignModal')?.addEventListener('click', function() {
    const checkedBoxes = document.querySelectorAll('.lead-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Please select at least one enquiry from the list first.');
        return;
    }
    const container = document.getElementById('bulkAssignHiddenContainer');
    if (container) {
        container.innerHTML = '';
        checkedBoxes.forEach(cb => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'lead_ids[]';
            hidden.value = cb.value;
            container.appendChild(hidden);
        });
    }
    const badge = document.getElementById('bulkModalCountBadge');
    if (badge) {
        badge.textContent = checkedBoxes.length + ' leads selected';
    }
    const modalEl = document.getElementById('bulkAssignModal');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
});

document.getElementById('bulkCreateTaskCheck')?.addEventListener('change', function() {
    const f = document.getElementById('bulkTaskFields');
    if (this.checked) f.classList.remove('d-none');
    else f.classList.add('d-none');
});

// Bulk Delete Modal opener with checked IDs injection (Super Admin Only)
document.getElementById('btnOpenBulkDeleteModal')?.addEventListener('click', function() {
    const checkedBoxes = document.querySelectorAll('.lead-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Please select at least one enquiry from the list first.');
        return;
    }
    const container = document.getElementById('bulkDeleteHiddenContainer');
    if (container) {
        container.innerHTML = '';
        checkedBoxes.forEach(cb => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'lead_ids[]';
            hidden.value = cb.value;
            container.appendChild(hidden);
        });
    }
    const countSpan = document.getElementById('bulkDeleteCountSpan');
    if (countSpan) {
        countSpan.textContent = checkedBoxes.length;
    }
    const modalEl = document.getElementById('bulkDeleteModal');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
});

// Filter-wise Delete Modal opener (Super Admin Only)
document.getElementById('btnOpenFilterDeleteModal')?.addEventListener('click', function() {
    const modalEl = document.getElementById('filterDeleteModal');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
});

// Sticky Header Scroll Mode Toggle
(function() {
    const toggleBtn = document.getElementById('btnToggleScrollMode');
    const scrollWrap = document.getElementById('leadsTableScrollWrap');
    if (!toggleBtn || !scrollWrap) return;

    // Check localStorage preference
    const savedMode = localStorage.getItem('crm_leads_scroll_mode');
    if (savedMode === 'full') {
        scrollWrap.classList.add('is-full-height');
        toggleBtn.classList.add('text-primary');
        toggleBtn.title = "Sticky scroll active: Click for compact fixed table scroll";
    }

    toggleBtn.addEventListener('click', function() {
        const isFull = scrollWrap.classList.toggle('is-full-height');
        if (isFull) {
            localStorage.setItem('crm_leads_scroll_mode', 'full');
            toggleBtn.classList.add('text-primary');
            toggleBtn.title = "Sticky scroll active: Click for compact fixed table scroll";
        } else {
            localStorage.setItem('crm_leads_scroll_mode', 'compact');
            toggleBtn.classList.remove('text-primary');
            toggleBtn.title = "Compact scroll active: Click for full-height scroll";
        }
    });
})();

// --- Compact Change Status Modal Handler ---
(function() {
    const statusModalEl = document.getElementById('changeStatusModal');
    if (!statusModalEl) return;

    let bsStatusModal = null;
    function getModalInstance() {
        if (!bsStatusModal && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bsStatusModal = bootstrap.Modal.getOrCreateInstance(statusModalEl);
        }
        return bsStatusModal;
    }

    const csmLeadIdInput = document.getElementById('csmLeadId');
    const csmStudentNameSpan = document.getElementById('csmStudentName');
    const csmLeadRefSpan = document.getElementById('csmLeadRef');
    const csmCurrentStatusText = document.getElementById('csmCurrentStatusText');
    const csmCurrentStatusBadge = document.getElementById('csmCurrentStatusBadge');
    const csmNewStatusSelect = document.getElementById('csmNewStatusSelect');
    const csmRemarks = document.getElementById('csmRemarks');
    const csmSubmitBtn = document.getElementById('csmSubmitBtn');
    const csmQuickPills = document.querySelectorAll('.csm-quick-pill');

    function getPillClass(status) {
        const s = (status || '').toLowerCase();
        if (s.includes('pending') || s.includes('new')) return 'sample-status-pending';
        if (s.includes('lost') || s.includes('cancel') || s.includes('not') || s.includes('cold') || s.includes('reject')) return 'sample-status-secondary';
        return 'sample-status-active';
    }

    function syncPillsWithStatus(selectedStatus) {
        csmQuickPills.forEach(pill => {
            if (pill.getAttribute('data-status') === selectedStatus) {
                pill.classList.add('active');
            } else {
                pill.classList.remove('active');
            }
        });
    }

    function showStatusToast(message) {
        if (typeof showQuickToast === 'function') {
            showQuickToast(message);
            return;
        }
        let toast = document.getElementById('quickStatusToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'quickStatusToast';
            toast.className = 'quick-toast-notice shadow-lg';
            document.body.appendChild(toast);
        }
        toast.innerHTML = `<i data-lucide="check-circle" style="width: 16px; height: 16px;" class="text-success"></i> <span>${message}</span>`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        toast.style.display = 'flex';
        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    // Open Modal Event Delegation
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-open-status-modal');
        if (!btn) return;
        e.preventDefault();

        const leadId = btn.getAttribute('data-lead-id');
        const leadName = btn.getAttribute('data-lead-name') || 'Student';
        const leadRef = btn.getAttribute('data-lead-ref') || '';
        const currentStatus = btn.getAttribute('data-current-status') || 'New';

        if (csmLeadIdInput) csmLeadIdInput.value = leadId;
        if (csmStudentNameSpan) csmStudentNameSpan.textContent = leadName;
        if (csmLeadRefSpan) csmLeadRefSpan.textContent = leadRef;
        if (csmCurrentStatusText) csmCurrentStatusText.textContent = currentStatus;
        if (csmCurrentStatusBadge) {
            csmCurrentStatusBadge.className = `sample-status-pill ${getPillClass(currentStatus)}`;
        }
        if (csmNewStatusSelect) {
            csmNewStatusSelect.value = currentStatus;
        }
        syncPillsWithStatus(currentStatus);

        if (csmRemarks) csmRemarks.value = '';
        if (csmSubmitBtn) {
            csmSubmitBtn.disabled = false;
            csmSubmitBtn.innerHTML = `<i data-lucide="check" style="width: 14px; height: 14px;"></i> <span>Update Status</span>`;
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();
        const m = getModalInstance();
        if (m) m.show();
    });

    // Quick pick pill click
    csmQuickPills.forEach(pill => {
        pill.addEventListener('click', function() {
            const status = this.getAttribute('data-status');
            if (csmNewStatusSelect) {
                csmNewStatusSelect.value = status;
            }
            syncPillsWithStatus(status);
        });
    });

    // Dropdown change syncs pills
    csmNewStatusSelect?.addEventListener('change', function() {
        syncPillsWithStatus(this.value);
    });

    // Form submission
    const form = document.getElementById('changeStatusForm');
    form?.addEventListener('submit', async function(e) {
        e.preventDefault();

        const leadId = csmLeadIdInput?.value;
        const newStatus = csmNewStatusSelect?.value;
        const remarks = csmRemarks?.value?.trim() || '';

        if (!leadId || !newStatus) return;

        if (csmSubmitBtn) {
            csmSubmitBtn.disabled = true;
            csmSubmitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Updating...`;
        }

        try {
            const formData = new FormData();
            formData.append('lead_id', leadId);
            formData.append('field', 'status');
            formData.append('value', newStatus);
            if (remarks) {
                formData.append('remarks', remarks);
            }

            const updateUrl = (typeof window.APP_URL === 'function') 
                ? window.APP_URL('leads/quick-update') 
                : 'index.php?route=leads/quick-update';
            const res = await fetch(updateUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();

            if (data.status === 'success') {
                // 1. Update Table Badge
                const tableBadgeEl = document.getElementById(`status-badge-${leadId}`);
                if (tableBadgeEl) {
                    const pillClass = getPillClass(data.value);
                    tableBadgeEl.className = `sample-status-pill ${pillClass} badge-lead-status badge-status-${data.slug}`;
                    tableBadgeEl.innerHTML = `
                        <span class="badge-text">${data.value}</span>
                        <i data-lucide="chevron-down" style="width: 12px; height: 12px;" class="ms-1 status-chevron opacity-75"></i>
                    `;
                    tableBadgeEl.closest('tr')?.classList.add('row-updated-flash');
                    setTimeout(() => tableBadgeEl.closest('tr')?.classList.remove('row-updated-flash'), 1500);

                    // Update checkmarks in status dropdown if present
                    const statusDropdown = tableBadgeEl.closest('.dropdown');
                    if (statusDropdown) {
                        statusDropdown.querySelectorAll('.quick-change-status-item').forEach(it => {
                            const isCur = it.getAttribute('data-status') === data.value;
                            it.classList.toggle('active', isCur);
                            const checkIcon = it.querySelector('.text-success');
                            if (isCur && !checkIcon) {
                                it.insertAdjacentHTML('beforeend', '<i data-lucide="check" style="width: 13px; height: 13px;" class="text-success ms-2"></i>');
                            } else if (!isCur && checkIcon) {
                                checkIcon.remove();
                            }
                        });
                    }
                }

                // 2. Update all modal trigger buttons for this lead with new data-current-status
                document.querySelectorAll(`.btn-open-status-modal[data-lead-id="${leadId}"]`).forEach(b => {
                    b.setAttribute('data-current-status', data.value);
                });

                // 3. Update Offcanvas Badge if open for this lead
                const offcanvasBadgeEl = document.getElementById('offcanvasStatusBadge');
                if (offcanvasBadgeEl) {
                    offcanvasBadgeEl.className = `badge badge-lead-status w-100 justify-content-between py-2 px-3 badge-status-${data.slug}`;
                    const offText = document.getElementById('offcanvasStatusText');
                    if (offText) offText.textContent = data.value;
                }

                // Append activity to offcanvas if open
                if (typeof appendActivityToOffcanvas === 'function') {
                    appendActivityToOffcanvas(leadId, {
                        type: 'status_change',
                        author: data.author || 'Staff',
                        time: data.formatted_time || 'Today',
                        remarks: data.remarks || `Status updated to ${data.value}`
                    });
                }

                const m = getModalInstance();
                if (m) m.hide();
                if (typeof lucide !== 'undefined') lucide.createIcons();
                showStatusToast(data.message || `Status updated to ${data.value}`);
            } else {
                alert(data.message || 'Failed to update status');
            }
        } catch (err) {
            console.error('Status update error:', err);
            alert('An error occurred while updating status. Please try again.');
        } finally {
            if (csmSubmitBtn) {
                csmSubmitBtn.disabled = false;
                csmSubmitBtn.innerHTML = `<i data-lucide="check" style="width: 14px; height: 14px;"></i> <span>Update Status</span>`;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }
    });

    // Ensure direct click on submit button triggers form submission
    csmSubmitBtn?.addEventListener('click', function(e) {
        if (form && !csmSubmitBtn.disabled) {
            form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        }
    });
})();

// --- Export Columns Modal Handler ---
(function() {
    const modalEl = document.getElementById('exportColumnsModal');
    if (!modalEl) return;

    const chks = modalEl.querySelectorAll('.export-column-chk');
    const counterEl = document.getElementById('exportCountNum');
    const btnSelectAll = document.getElementById('exportBtnSelectAll');
    const btnDeselectAll = document.getElementById('exportBtnDeselectAll');
    const btnDefault = document.getElementById('exportBtnDefault');
    const submitBtn = document.getElementById('exportSubmitBtn');
    const form = document.getElementById('exportColumnsForm');

    function updateCounter() {
        let count = 0;
        chks.forEach(c => {
            const card = c.closest('.export-col-card');
            if (c.checked) {
                count++;
                if (card) card.classList.add('is-checked');
            } else {
                if (card) card.classList.remove('is-checked');
            }
        });
        if (counterEl) counterEl.textContent = count;
        if (submitBtn) {
            submitBtn.disabled = count === 0;
            if (count === 0) {
                submitBtn.title = 'Select at least 1 column to export';
            } else {
                submitBtn.title = '';
            }
        }
    }

    chks.forEach(c => c.addEventListener('change', updateCounter));

    btnSelectAll?.addEventListener('click', () => {
        chks.forEach(c => c.checked = true);
        updateCounter();
    });

    btnDeselectAll?.addEventListener('click', () => {
        chks.forEach(c => c.checked = false);
        updateCounter();
    });

    btnDefault?.addEventListener('click', () => {
        chks.forEach(c => {
            c.checked = c.getAttribute('data-default') === '1';
        });
        updateCounter();
    });

    form?.addEventListener('submit', (e) => {
        let count = 0;
        chks.forEach(c => { if (c.checked) count++; });
        if (count === 0) {
            e.preventDefault();
            alert('Please select at least one column to export.');
            return false;
        }

        // Close modal shortly after triggering download
        setTimeout(() => {
            const m = bootstrap.Modal.getInstance(modalEl);
            if (m) m.hide();
        }, 1200);
    });

    const formatRadios = modalEl.querySelectorAll('input[name="export_format"]');
    const submitBtnText = document.getElementById('exportSubmitBtnText');
    formatRadios.forEach(r => {
        r.addEventListener('change', () => {
            if (submitBtnText) {
                submitBtnText.textContent = r.value === 'csv' ? 'Download CSV' : 'Download Excel';
            }
        });
    });

    // Run on initial page load to set count
    updateCounter();
})();
</script>
