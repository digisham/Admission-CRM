document.addEventListener('DOMContentLoaded', () => {
    // 1. Live Clock Ticker
    function updateClock() {
        const el = document.getElementById('liveClockDisplay');
        if (el) {
            const now = new Date();
            el.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    }
    setInterval(updateClock, 1000);
    updateClock();

    // 2. Sidebar Toggle on Mobile
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const closeBtn = document.getElementById('sidebarCloseBtn');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }

    if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', () => {
            sidebar.classList.remove('show');
        });
    }

    // 2. Select All Checkboxes for Bulk Lead Actions
    const selectAllCheckbox = document.getElementById('selectAllLeads');
    const leadCheckboxes = document.querySelectorAll('.lead-checkbox');
    const bulkActionContainer = document.getElementById('bulkActionsBar');
    const selectedCountSpan = document.getElementById('selectedLeadsCount');

    function updateBulkActionsState() {
        const checkedCount = document.querySelectorAll('.lead-checkbox:checked').length;
        if (selectedCountSpan) {
            selectedCountSpan.textContent = checkedCount;
        }
        if (bulkActionContainer) {
            if (checkedCount > 0) {
                bulkActionContainer.classList.remove('d-none');
            } else {
                bulkActionContainer.classList.add('d-none');
            }
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', (e) => {
            leadCheckboxes.forEach(cb => {
                cb.checked = e.target.checked;
            });
            updateBulkActionsState();
        });
    }

    leadCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            if (!cb.checked && selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            updateBulkActionsState();
        });
    });

    // 3. Real-time Duplicate Mobile Detection on Lead Creation Form
    const mobileInput = document.getElementById('leadMobileInput');
    const duplicateAlert = document.getElementById('duplicateWarningAlert');
    const duplicateConfirmBox = document.getElementById('confirmDuplicateBox');

    if (mobileInput && duplicateAlert) {
        let debounceTimer = null;

        mobileInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const val = mobileInput.value.trim().replace(/[^\d+]/g, '');

            if (val.length < 10) {
                duplicateAlert.classList.add('d-none');
                if (duplicateConfirmBox) duplicateConfirmBox.classList.add('d-none');
                return;
            }

            debounceTimer = setTimeout(async () => {
                const sessionSelect = document.getElementById('leadSessionSelect');
                const sessionId = sessionSelect ? sessionSelect.value : '';
                const excludeInput = document.getElementById('leadExcludeId');
                const excludeId = excludeInput ? excludeInput.value : '';

                try {
                    const response = await fetch(`index.php?route=leads/check-duplicate&mobile=${encodeURIComponent(val)}&session_id=${sessionId}&exclude_id=${excludeId}`);
                    const data = await response.json();

                    if (data.is_duplicate && data.duplicates.length > 0) {
                        const dup = data.duplicates[0];
                        duplicateAlert.innerHTML = `
                            <strong><i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i> Existing Enquiry Found:</strong>
                            A lead with mobile <strong>${dup.mobile}</strong> already exists in this session:
                            <a href="index.php?route=leads/show&id=${dup.id}" target="_blank" class="fw-bold">${dup.lead_ref} (${dup.student_name} - ${dup.class_applied})</a>.
                            Status: <span class="badge bg-secondary">${dup.status}</span>, Counselor: <strong>${dup.assigned_name || 'Unassigned'}</strong>.
                        `;
                        duplicateAlert.classList.remove('d-none');
                        if (duplicateConfirmBox) duplicateConfirmBox.classList.remove('d-none');
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    } else {
                        duplicateAlert.classList.add('d-none');
                        if (duplicateConfirmBox) duplicateConfirmBox.classList.add('d-none');
                    }
                } catch (e) {
                    console.error("Duplicate check error:", e);
                }
            }, 400);
        });
    }

    // 4. Initialize Lucide icons if available
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
