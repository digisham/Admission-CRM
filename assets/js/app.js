/**
 * GD Goenka Public School, Purnea — Admission CRM & Lead Management System
 * Production Application Core JS
 * Features:
 *  - Instant Navigation & Click Intent (PJAX / Turbo / Zero Page Reloads)
 *  - Universal AJAX Form Submissions (Instant Updates without Browser Refresh)
 *  - Real-time Notifications & Desktop Alerts
 *  - Quick Status & Priority Updating with Instant Feedback
 *  - Sliding Offcanvas Lead Inspector & Followup Timeline
 *  - Robust Multi-Subdirectory URL Resolver
 */

// 1. Multi-environment URL Resolver
function getAppUrl(route = '', params = '') {
    if (typeof window.APP_URL === 'function') {
        return window.APP_URL(route, params);
    }
    const base = window.APP_BASE_URL || '';
    let url = (base ? base : '') + '/index.php';
    if (route) {
        url += '?route=' + encodeURIComponent(route).replace(/%2F/g, '/');
        if (params) {
            url += (params.startsWith('&') ? '' : '&') + params;
        }
    } else if (params) {
        url += '?' + (params.startsWith('?') ? params.substring(1) : params);
    }
    return url;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// 2. Modern Universal Toast Notification System
function crmToast(message, type = 'success') {
    let container = document.getElementById('crmGlobalToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'crmGlobalToastContainer';
        document.body.appendChild(container);
    }

    const item = document.createElement('div');
    item.className = `crm-toast-item toast-${type}`;
    let icon = type === 'success' ? 'check-circle' : (type === 'error' ? 'alert-triangle' : 'info');
    item.innerHTML = `<i data-lucide="${icon}" style="width: 17px; height: 17px;"></i> <span>${escapeHtml(message)}</span>`;
    container.appendChild(item);

    if (typeof lucide !== 'undefined') lucide.createIcons();

    requestAnimationFrame(() => {
        item.classList.add('show');
    });

    setTimeout(() => {
        item.classList.remove('show');
        setTimeout(() => item.remove(), 300);
    }, 3500);
}

// Universal Viewport & Scrolling Watchdog (Prevents Body Scrolling Locks Across All Pages)
function ensurePageScrollable() {
    const activeModals = document.querySelectorAll('.modal.show, .offcanvas.show');
    if (!activeModals || activeModals.length === 0) {
        document.querySelectorAll('.modal-backdrop, .offcanvas-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('overflow-y');
        document.body.style.removeProperty('padding-right');
        document.documentElement.style.removeProperty('overflow');
        document.documentElement.style.removeProperty('overflow-y');
    }
}
window.ensurePageScrollable = ensurePageScrollable;

// Clean up modals and offcanvas whenever a link is clicked inside them
document.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    if (link) {
        const offcanvasParent = link.closest('.offcanvas');
        if (offcanvasParent && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
            const inst = bootstrap.Offcanvas.getInstance(offcanvasParent);
            if (inst) {
                try { inst.hide(); } catch(err) {}
            }
        }
        const modalParent = link.closest('.modal');
        if (modalParent && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const inst = bootstrap.Modal.getInstance(modalParent);
            if (inst) {
                try { inst.hide(); } catch(err) {}
            }
        }
    }
}, true);

// Listen to hidden events on all modals & offcanvas
['hidden.bs.modal', 'hidden.bs.offcanvas'].forEach(evtName => {
    document.addEventListener(evtName, () => {
        setTimeout(ensurePageScrollable, 40);
    });
});

window.addEventListener('load', ensurePageScrollable);

// 3. Instant Navigation Engine (Fast Click Intent & PJAX)
const crmNavigator = {
    cache: new Map(),
    prefetchTimers: new Map(),
    loaderEl: null,

    init() {
        this.loaderEl = document.getElementById('crm-instant-loader');

        // Click Intent: Hover or Touchstart prefetch
        document.addEventListener('mouseover', (e) => this.handleHover(e));
        document.addEventListener('touchstart', (e) => this.handleHover(e), { passive: true });
        document.addEventListener('mouseout', (e) => this.cancelHover(e));

        // Intercept internal link clicks
        document.addEventListener('click', (e) => this.handleClick(e));

        // Handle Browser Back & Forward buttons
        window.addEventListener('popstate', (e) => {
            this.loadUrl(window.location.href, false);
        });

        this.updateActiveNav(window.location.href);
    },

    isEligibleLink(anchor) {
        if (!anchor || !anchor.href) return false;
        if (anchor.target && anchor.target !== '_self') return false;
        if (anchor.hasAttribute('download')) return false;
        if (anchor.getAttribute('data-no-pjax') !== null) return false;
        if (anchor.getAttribute('data-bs-toggle') !== null) return false;

        // Skip in-page hash anchors
        if (anchor.href.includes('#') && anchor.pathname === window.location.pathname && anchor.search === window.location.search) {
            return false;
        }

        try {
            const url = new URL(anchor.href, window.location.origin);
            // Must match same origin
            if (url.origin !== window.location.origin) return false;

            const pathAndQuery = url.pathname + url.search;
            // Exclude logout, file downloads, exports
            if (/route=(logout|export|documents\/download)/i.test(pathAndQuery)) return false;
            if (/\.(pdf|xlsx|xls|csv|zip|png|jpg|jpeg)$/i.test(url.pathname)) return false;

            return true;
        } catch (e) {
            return false;
        }
    },

    handleHover(e) {
        const a = e.target.closest('a');
        if (!this.isEligibleLink(a)) return;

        const href = a.href;
        if (this.cache.has(href)) return;

        const timer = setTimeout(() => {
            this.prefetch(href);
        }, 65); // 65ms hover intent threshold

        this.prefetchTimers.set(a, timer);
    },

    cancelHover(e) {
        const a = e.target.closest('a');
        if (a && this.prefetchTimers.has(a)) {
            clearTimeout(this.prefetchTimers.get(a));
            this.prefetchTimers.delete(a);
        }
    },

    async prefetch(url) {
        if (this.cache.has(url)) return;
        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-PJAX': 'true' }
            });
            if (res.ok) {
                const html = await res.text();
                this.cache.set(url, { html, time: Date.now() });
                if (this.cache.size > 25) {
                    const oldest = this.cache.keys().next().value;
                    this.cache.delete(oldest);
                }
            }
        } catch (e) {}
    },

    async handleClick(e) {
        const a = e.target.closest('a');
        if (!this.isEligibleLink(a)) return;

        // Allow opening in new tab via Cmd/Ctrl/Shift
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        e.preventDefault();
        const url = a.href;
        if (url === window.location.href) return;

        await this.loadUrl(url, true);
    },

    startLoading() {
        if (!this.loaderEl) this.loaderEl = document.getElementById('crm-instant-loader');
        if (this.loaderEl) {
            this.loaderEl.className = 'crm-instant-loader loading';
        }
        const pageContainer = document.getElementById('app-page-container');
        if (pageContainer) {
            pageContainer.classList.add('page-nav-loading');
        }
    },

    doneLoading() {
        if (!this.loaderEl) this.loaderEl = document.getElementById('crm-instant-loader');
        if (this.loaderEl) {
            this.loaderEl.className = 'crm-instant-loader done';
            setTimeout(() => {
                if (this.loaderEl) this.loaderEl.className = 'crm-instant-loader';
            }, 300);
        }
        const pageContainer = document.getElementById('app-page-container');
        if (pageContainer) {
            pageContainer.classList.remove('page-nav-loading');
        }
    },

    async loadUrl(url, pushState = true) {
        this.startLoading();

        // 1. Immediately dismiss any open modals or offcanvas before page swap
        document.querySelectorAll('.modal.show, .offcanvas.show').forEach(el => {
            try {
                if (typeof bootstrap !== 'undefined') {
                    const inst = (bootstrap.Offcanvas && bootstrap.Offcanvas.getInstance(el)) || 
                                 (bootstrap.Modal && bootstrap.Modal.getInstance(el));
                    if (inst) inst.hide();
                }
            } catch(e) {}
            el.classList.remove('show');
            el.setAttribute('aria-hidden', 'true');
            el.removeAttribute('aria-modal');
            el.style.display = 'none';
        });

        // 2. Remove all leftover backdrops & release body scrolling immediately
        document.querySelectorAll('.modal-backdrop, .offcanvas-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('overflow-y');
        document.body.style.removeProperty('padding-right');
        document.documentElement.style.removeProperty('overflow');
        document.documentElement.style.removeProperty('overflow-y');

        let html = null;
        const cached = this.cache.get(url);
        if (cached && (Date.now() - cached.time < 20000)) {
            html = cached.html;
        } else {
            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-PJAX': 'true' }
                });
                if (!res.ok) {
                    window.location.href = url;
                    return;
                }
                html = await res.text();
                this.cache.set(url, { html, time: Date.now() });
            } catch (err) {
                window.location.href = url;
                return;
            }
        }

        try {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newContent = doc.getElementById('app-page-container') || doc.getElementById('app-main-content');
            const targetContainer = document.getElementById('app-page-container') || document.getElementById('app-main-content');

            if (!newContent || !targetContainer) {
                window.location.href = url;
                return;
            }

            if (doc.title) {
                document.title = doc.title;
            }

            // Smooth content swap
            targetContainer.innerHTML = newContent.innerHTML;

            // Execute any embedded scripts in the newly swapped page content
            targetContainer.querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.textContent = oldScript.textContent;
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            // Instant scroll to top
            window.scrollTo({ top: 0, behavior: 'instant' });

            if (pushState) {
                window.history.pushState({ pjax: true }, '', url);
            }

            this.updateActiveNav(url);

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Ensure scrolling is unlocked across entire window
            ensurePageScrollable();

            // Dispatch event for page-specific component rebinds
            document.dispatchEvent(new CustomEvent('crm:page-loaded', { detail: { url } }));
        } catch (e) {
            console.error("Instant navigation swap error:", e);
            window.location.href = url;
        } finally {
            this.doneLoading();
            ensurePageScrollable();
        }
    },

    updateActiveNav(currentUrl) {
        try {
            const parsed = new URL(currentUrl, window.location.origin);
            const routeParam = parsed.searchParams.get('route') || 'dashboard';

            document.querySelectorAll('#sidebar .nav-link').forEach(link => {
                const linkUrl = new URL(link.href, window.location.origin);
                const linkRoute = linkUrl.searchParams.get('route') || 'dashboard';

                if (linkRoute === routeParam) {
                    link.classList.add('active');
                } else if (routeParam.startsWith('leads') && linkRoute === 'leads' && routeParam !== 'leads/unassigned') {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        } catch (e) {}
    }
};

// 4. Universal AJAX Form Submissions (Instant Updates without Refresh)
function initUniversalAjaxForms() {
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!form || form.tagName !== 'FORM') return;

        const action = form.getAttribute('action') || '';
        const isAjaxForm = form.hasAttribute('data-ajax')
            || /tasks\/(complete|store)/i.test(action)
            || /followups\/store/i.test(action)
            || /leads\/(assign|bulk-assign)/i.test(action);

        if (!isAjaxForm) return;

        // Skip multipart file upload forms (allow standard progress upload)
        if (form.enctype === 'multipart/form-data') return;

        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...`;
        }

        try {
            const formData = new FormData(form);
            if (window.CSRF_TOKEN && !formData.has('_csrf_token')) {
                formData.append('_csrf_token', window.CSRF_TOKEN);
            }

            const res = await fetch(action, {
                method: (form.method || 'POST').toUpperCase(),
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const data = await res.json();

            if (data.status === 'success') {
                // 1. Close modal containing form
                const modalEl = form.closest('.modal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modalInstance.hide();
                }

                // 2. Clear cache so subsequent visits reflect updated state
                crmNavigator.cache.clear();

                // 3. Show instant feedback toast
                crmToast(data.message || 'Updated successfully!', 'success');

                // 4. Specific DOM element updates:
                // A. Task Completed
                if (/tasks\/complete/i.test(action)) {
                    const taskId = data.task_id || formData.get('task_id');
                    document.querySelectorAll(`tr:has(input[value="${taskId}"]), [data-task-id="${taskId}"]`).forEach(el => {
                        const statusCell = el.querySelector('.badge');
                        if (statusCell) {
                            statusCell.className = 'badge bg-success';
                            statusCell.textContent = 'Completed';
                        }
                        const completeBtn = el.querySelector('button[data-bs-target*="completeTaskModal"]');
                        if (completeBtn) completeBtn.remove();
                        el.classList.add('row-updated-flash');
                    });
                }
                // B. Lead Single Assign
                else if (/leads\/assign/i.test(action)) {
                    const leadId = data.lead_id || formData.get('lead_id');
                    const row = document.querySelector(`tr[data-lead-id="${leadId}"]`);
                    if (row) {
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(20px)';
                        setTimeout(() => {
                            row.remove();
                            const queueBadge = document.getElementById('queueCountBadge');
                            if (queueBadge) {
                                const currentCount = Math.max(0, parseInt(queueBadge.textContent, 10) - 1);
                                queueBadge.textContent = currentCount;
                            }
                        }, 300);
                    }
                    // Lead details page assignee badge
                    document.querySelectorAll('.assigned-counselor-name, #leadAssignedNameDisplay').forEach(el => {
                        el.textContent = data.user_name || 'Staff';
                        el.classList.add('row-updated-flash');
                    });
                }
                // C. Lead Bulk Assign
                else if (/leads\/bulk-assign/i.test(action)) {
                    const leadIds = data.lead_ids || [];
                    leadIds.forEach(id => {
                        const row = document.querySelector(`tr[data-lead-id="${id}"]`);
                        if (row) {
                            row.style.transition = 'all 0.3s ease';
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 300);
                        }
                    });
                    const queueBadge = document.getElementById('queueCountBadge');
                    if (queueBadge && data.assigned_count) {
                        const current = Math.max(0, parseInt(queueBadge.textContent, 10) - data.assigned_count);
                        queueBadge.textContent = current;
                    }
                    const masterCb = document.getElementById('masterSelectAll');
                    if (masterCb) masterCb.checked = false;
                    const selectedCountEl = document.getElementById('selectedCountNumber');
                    if (selectedCountEl) selectedCountEl.textContent = '0';
                }
                // D. Follow-up Call / Interaction Log
                else if (/followups\/store/i.test(action)) {
                    const timelineContainer = document.querySelector('.timeline-v2-container');
                    if (timelineContainer) {
                        const newLog = document.createElement('div');
                        newLog.className = 'timeline-v2-item row-updated-flash';
                        newLog.innerHTML = `
                            <div class="timeline-v2-node timeline-node-lavender">
                                <i data-lucide="phone-call" style="width: 15px; height: 15px;"></i>
                            </div>
                            <div class="timeline-v2-content">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="text-dark small"><strong>${escapeHtml(data.author || 'You')}</strong> logged follow-up interaction</span>
                                    <span class="text-muted" style="font-size: 0.72rem;">${data.formatted_time || 'Just now'}</span>
                                </div>
                                <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                                    <span class="badge bg-success text-white border-0 px-2 py-0.5 fw-semibold" style="font-size: 0.72rem; border-radius: 4px;">
                                        <i data-lucide="check" style="width: 12px; height: 12px;" class="me-1"></i> ${escapeHtml(data.outcome || 'Connected')}
                                    </span>
                                    <span class="text-muted small" style="font-size: 0.72rem;">via ${escapeHtml(data.contact_method || 'Phone')}</span>
                                </div>
                                ${data.remarks ? `<div class="notes-card-box mt-2"><div class="notes-card-body">${escapeHtml(data.remarks)}</div></div>` : ''}
                            </div>
                        `;
                        timelineContainer.prepend(newLog);
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }
                    form.reset();
                }
                // E. Task Store
                else if (/tasks\/store/i.test(action)) {
                    form.reset();
                    crmNavigator.loadUrl(window.location.href, false);
                }
            } else {
                crmToast(data.message || 'Operation failed. Please try again.', 'error');
            }
        } catch (err) {
            console.error('Universal AJAX form error:', err);
            crmToast('An unexpected error occurred. Please try again.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }
    });
}

// 5. Initialize Core Application Components
document.addEventListener('DOMContentLoaded', () => {
    // Start Instant Click Intent Navigation
    crmNavigator.init();

    // Start Universal AJAX Form Interceptor
    initUniversalAjaxForms();

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
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('show');
        });
    }

    if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', () => {
            sidebar.classList.remove('show');
        });
    }

    // Auto-close mobile sidebar when clicking outside or clicking any nav link
    document.addEventListener('click', (e) => {
        if (window.innerWidth < 992 && sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && (!toggleBtn || !toggleBtn.contains(e.target))) {
                sidebar.classList.remove('show');
            }
        }
    });

    if (sidebar) {
        sidebar.addEventListener('click', (e) => {
            if (window.innerWidth < 992 && e.target.closest('a')) {
                sidebar.classList.remove('show');
            }
        });
    }

    // 3. Lead Selection Checkboxes for Bulk Actions
    function initLeadCheckboxes() {
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
            selectAllCheckbox.onchange = (e) => {
                leadCheckboxes.forEach(cb => {
                    cb.checked = e.target.checked;
                });
                updateBulkActionsState();
            };
        }

        leadCheckboxes.forEach(cb => {
            cb.onchange = () => {
                if (!cb.checked && selectAllCheckbox) {
                    selectAllCheckbox.checked = false;
                }
                updateBulkActionsState();
            };
        });
    }
    initLeadCheckboxes();

    // 4. Real-time Duplicate Mobile Detection on Lead Creation Form
    function initDuplicateDetection() {
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
                        const checkUrl = getAppUrl('leads/check-duplicate', `mobile=${encodeURIComponent(val)}&session_id=${sessionId}&exclude_id=${excludeId}`);
                        const response = await fetch(checkUrl);
                        const data = await response.json();

                        if (data.is_duplicate && data.duplicates.length > 0) {
                            const dup = data.duplicates[0];
                            const dupUrl = getAppUrl('leads/show', 'id=' + dup.id);
                            duplicateAlert.innerHTML = `
                                <strong><i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i> Existing Enquiry Found:</strong>
                                A lead with mobile <strong>${dup.mobile}</strong> already exists in this session:
                                <a href="${dupUrl}" target="_blank" class="fw-bold">${dup.lead_ref} (${dup.student_name} - ${dup.class_applied})</a>.
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
    }
    initDuplicateDetection();

    // 5. Real-time Notifications & Desktop Alerts
    const notifBellBtn = document.getElementById('notificationBellBtn');
    const notifBellIcon = document.getElementById('notificationBellIcon');
    const notifBadge = document.getElementById('notifBadge');
    const notifOffcanvasCount = document.getElementById('notifOffcanvasCount');
    const notifList = document.getElementById('notificationList');
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    const enableDesktopBtn = document.getElementById('enableDesktopAlertsBtn');
    const desktopEnabledLabel = document.getElementById('desktopAlertsEnabledLabel');
    const filterCountAll = document.getElementById('filterCountAll');
    const filterCountUnread = document.getElementById('filterCountUnread');

    let currentFilter = 'all';
    let lastKnownLatestId = 0;

    if (notifList) {
        const existingItems = notifList.querySelectorAll('.notif-card');
        existingItems.forEach(el => {
            const id = parseInt(el.getAttribute('data-id'), 10);
            if (id > lastKnownLatestId) lastKnownLatestId = id;
        });
    }

    const notifPopupModalEl = document.getElementById('notifPermissionPopupModal');
    const btnAllowNotifPopup = document.getElementById('btnAllowNotifPopup');
    const btnDenyNotifPopup = document.getElementById('btnDenyNotifPopup');

    function playNotificationChime() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const audioCtx = new AudioCtx();
            const now = audioCtx.currentTime;

            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(659.25, now); // E5
            osc.frequency.setValueAtTime(880.00, now + 0.08); // A5

            gain.gain.setValueAtTime(0.12, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);

            osc.connect(gain);
            gain.connect(audioCtx.destination);

            osc.start(now);
            osc.stop(now + 0.35);
        } catch (e) {}
    }

    async function requestDesktopNotificationPermission(userTriggered = false) {
        if (!('Notification' in window)) {
            crmToast('Browser does not support notifications.', 'warning');
            return;
        }

        try {
            let permission = Notification.permission;
            if (permission === 'default' || userTriggered) {
                if (typeof Notification.requestPermission === 'function') {
                    permission = await Notification.requestPermission();
                }
            }

            // Close popup modal if open
            if (notifPopupModalEl && typeof bootstrap !== 'undefined') {
                const modalInst = bootstrap.Modal.getInstance(notifPopupModalEl);
                if (modalInst) modalInst.hide();
            }

            if (permission === 'granted') {
                if (enableDesktopBtn) enableDesktopBtn.classList.add('d-none');
                if (desktopEnabledLabel) desktopEnabledLabel.classList.remove('d-none');

                playNotificationChime();

                if (userTriggered || !sessionStorage.getItem('crm_notif_welcomed')) {
                    sessionStorage.setItem('crm_notif_welcomed', '1');
                    try {
                        new Notification("GD Goenka CRM Alerts Active", {
                            body: "Notifications enabled! You will now receive instant alerts for new leads, assignments, and follow-up tasks.",
                            icon: "assets/images/logo.png"
                        });
                    } catch (e) {}
                }
                crmToast('Real-time notifications enabled successfully!', 'success');
            } else if (permission === 'denied') {
                if (enableDesktopBtn) enableDesktopBtn.classList.remove('d-none');
                if (desktopEnabledLabel) desktopEnabledLabel.classList.add('d-none');
                if (userTriggered) {
                    crmToast('Notifications are blocked in browser site settings.', 'warning');
                }
            }
        } catch (err) {
            console.log("Desktop notification request error:", err);
        }
    }

    function initNotificationPermissions() {
        if (!('Notification' in window)) {
            if (enableDesktopBtn) enableDesktopBtn.classList.add('d-none');
            return;
        }

        const perm = Notification.permission;

        if (perm === 'granted') {
            if (enableDesktopBtn) enableDesktopBtn.classList.add('d-none');
            if (desktopEnabledLabel) desktopEnabledLabel.classList.remove('d-none');
        } else {
            if (enableDesktopBtn) enableDesktopBtn.classList.remove('d-none');
            if (desktopEnabledLabel) desktopEnabledLabel.classList.add('d-none');

            // Show Popup Modal asking Yes / No on site open across all browsers (Chrome, Firefox, Edge, Safari, Mobile)
            const isPopupDismissed = sessionStorage.getItem('crm_notif_popup_dismissed');
            if (!isPopupDismissed && notifPopupModalEl && typeof bootstrap !== 'undefined') {
                setTimeout(() => {
                    const modalInst = new bootstrap.Modal(notifPopupModalEl);
                    modalInst.show();
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }, 400);
            }
        }
    }
    initNotificationPermissions();

    if (btnAllowNotifPopup) {
        btnAllowNotifPopup.addEventListener('click', async (e) => {
            e.preventDefault();
            await requestDesktopNotificationPermission(true);
        });
    }

    if (btnDenyNotifPopup) {
        btnDenyNotifPopup.addEventListener('click', () => {
            sessionStorage.setItem('crm_notif_popup_dismissed', '1');
            if (notifPopupModalEl && typeof bootstrap !== 'undefined') {
                const modalInst = bootstrap.Modal.getInstance(notifPopupModalEl);
                if (modalInst) modalInst.hide();
            }
        });
    }

    if (enableDesktopBtn) {
        enableDesktopBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (notifPopupModalEl && typeof bootstrap !== 'undefined') {
                const modalInst = new bootstrap.Modal(notifPopupModalEl);
                modalInst.show();
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } else {
                requestDesktopNotificationPermission(true);
            }
        });
    }

    function applyNotificationFilter() {
        if (!notifList) return;
        const cards = notifList.querySelectorAll('.notif-card');
        let visibleCount = 0;
        cards.forEach(card => {
            const isUnread = card.getAttribute('data-unread') === '1';
            const type = card.getAttribute('data-type') || '';

            let match = false;
            if (currentFilter === 'all') {
                match = true;
            } else if (currentFilter === 'unread') {
                match = isUnread;
            } else if (currentFilter === 'status') {
                match = type.includes('status');
            } else if (currentFilter === 'assigned') {
                match = type.includes('assign');
            }

            if (match) {
                card.classList.remove('d-none');
                visibleCount++;
            } else {
                card.classList.add('d-none');
            }
        });

        const emptyEl = document.getElementById('notifEmptyState');
        if (emptyEl) {
            emptyEl.style.display = (visibleCount === 0 && cards.length === 0) ? 'block' : 'none';
        }
    }

    document.querySelectorAll('.filter-notif-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-notif-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.getAttribute('data-filter') || 'all';
            applyNotificationFilter();
        });
    });

    function updateNotificationBadges(unreadCount, totalCount) {
        if (notifBadge) {
            notifBadge.textContent = unreadCount;
            if (unreadCount > 0) notifBadge.classList.remove('d-none');
            else notifBadge.classList.add('d-none');
        }
        if (notifOffcanvasCount) {
            notifOffcanvasCount.textContent = `${unreadCount} unread`;
            if (unreadCount > 0) notifOffcanvasCount.classList.remove('d-none');
            else notifOffcanvasCount.classList.add('d-none');
        }
        if (filterCountAll && totalCount !== undefined) filterCountAll.textContent = totalCount;
        if (filterCountUnread) filterCountUnread.textContent = unreadCount;
    }

    function renderNotifications(notifications, unreadCount) {
        if (!notifList) return;

        updateNotificationBadges(unreadCount, notifications ? notifications.length : 0);

        if (!notifications || notifications.length === 0) {
            notifList.innerHTML = `
                <div class="p-4 text-center text-muted" id="notifEmptyState">
                    <div class="d-inline-flex p-3 rounded-circle bg-light border mb-3">
                        <i data-lucide="check-circle" class="text-success" style="width: 32px; height: 32px; opacity: 0.8;"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">All Caught Up!</h6>
                    <p class="small text-muted mb-0">No unread notifications. New lead activities and tasks will appear here instantly.</p>
                </div>
            `;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            return;
        }

        let html = '';
        notifications.forEach(n => {
            const isUnread = !n.is_read || parseInt(n.is_read, 10) === 0;
            const isAssign = (n.type === 'lead_assigned');
            const isStatus = (n.type === 'lead_status_changed');
            const cardClass = isUnread ? 'notif-card notif-unread' : 'notif-card';
            const iconClass = isAssign ? 'icon-assign' : (isStatus ? 'icon-status' : 'icon-general');
            const iconName = isAssign ? 'user-check' : (isStatus ? 'activity' : 'bell');
            const typeLabel = isAssign ? 'Assignment' : (isStatus ? 'Status Update' : 'Update');
            const link = getAppUrl('notifications/read', 'id=' + n.id);

            let leadBoxHtml = '';
            if (n.lead_id || n.lead_ref) {
                const leadRef = escapeHtml(n.lead_ref || `#ID-${n.lead_id}`);
                const studentName = escapeHtml(n.student_name || 'Student');
                const parentName = n.parent_name ? `<span class="text-muted small">P/o ${escapeHtml(n.parent_name)}</span>` : '';
                const classBadge = n.class_applying_for ? `<span class="badge bg-white text-dark border px-2 py-0.5" style="font-size: 0.68rem;">${escapeHtml(n.class_applying_for)}</span>` : '';

                leadBoxHtml = `
                    <div class="notif-lead-preview">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1">
                            <div class="d-flex align-items-center gap-1">
                                <span class="badge bg-navy text-white fw-bold px-2 py-0.5" style="font-size: 0.7rem;">${leadRef}</span>
                                ${classBadge}
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between text-dark fw-medium small mt-1">
                            <span>
                                <i data-lucide="graduation-cap" style="width: 13px; height: 13px;" class="text-primary me-1"></i>
                                ${studentName}
                            </span>
                            ${parentName}
                        </div>
                    </div>
                `;
            }

            const senderInfo = n.sender_name ? `by ${escapeHtml(n.sender_name)}` : '';
            const markReadBtnHtml = isUnread ? `
                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0.5 btn-mark-single-read" data-id="${n.id}" style="font-size: 0.7rem;" title="Mark as Read & Remove">
                    <i data-lucide="check" style="width: 11px; height: 11px;"></i> Mark Read
                </button>
            ` : '';

            html += `
                <div class="${cardClass}" data-id="${n.id}" data-type="${escapeHtml(n.type || 'general')}" data-unread="${isUnread ? '1' : '0'}">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="notif-icon-circle ${iconClass}">
                                <i data-lucide="${iconName}" style="width: 16px; height: 16px;"></i>
                            </div>
                            <span class="badge bg-light text-secondary border fw-medium" style="font-size: 0.68rem;">${typeLabel}</span>
                        </div>
                        <span class="text-muted" style="font-size: 0.72rem;">
                            <i data-lucide="clock" style="width: 11px; height: 11px;" class="me-1"></i>${escapeHtml(n.time_ago || '')}
                        </span>
                    </div>

                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.88rem;">${escapeHtml(n.title)}</h6>
                    <p class="text-secondary small mb-2" style="font-size: 0.81rem; line-height: 1.45;">${escapeHtml(n.message)}</p>

                    ${leadBoxHtml}

                    <div class="d-flex align-items-center justify-content-between pt-1 mt-1">
                        <span class="text-muted" style="font-size: 0.72rem;">${senderInfo}</span>
                        <div class="d-flex align-items-center gap-1">
                            ${markReadBtnHtml}
                            <a href="${link}" class="btn btn-xs btn-primary rounded-pill px-2.5 py-0.5 d-flex align-items-center gap-1 btn-view-notif-link" data-id="${n.id}" style="font-size: 0.72rem;">
                                <span>View Details</span>
                                <i data-lucide="arrow-right" style="width: 11px; height: 11px;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            `;
        });

        notifList.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        applyNotificationFilter();
    }

    // Single Notification Read -> Remove from list immediately!
    if (notifList) {
        notifList.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-mark-single-read');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();

            const notifId = btn.getAttribute('data-id');
            const card = btn.closest('.notif-card');

            // Animate card removal immediately
            if (card) {
                card.classList.add('card-removing');
            }

            try {
                const markUrl = getAppUrl('notifications/read', `id=${notifId}&format=json`);
                const res = await fetch(markUrl);
                const data = await res.json();
                if (data.status === 'success') {
                    setTimeout(() => {
                        if (card) card.remove();
                        const remainingCards = notifList.querySelectorAll('.notif-card');
                        const remainingUnread = notifList.querySelectorAll('.notif-card[data-unread="1"]');
                        updateNotificationBadges(remainingUnread.length, remainingCards.length);

                        if (remainingCards.length === 0) {
                            notifList.innerHTML = `
                                <div class="p-4 text-center text-muted" id="notifEmptyState">
                                    <div class="d-inline-flex p-3 rounded-circle bg-light border mb-3">
                                        <i data-lucide="check-circle" class="text-success" style="width: 32px; height: 32px; opacity: 0.8;"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">All Caught Up!</h6>
                                    <p class="small text-muted mb-0">No unread notifications. New lead activities and tasks will appear here instantly.</p>
                                </div>
                            `;
                            if (typeof lucide !== 'undefined') lucide.createIcons();
                        }
                    }, 320);
                    crmToast('Notification marked as read & removed', 'info');
                } else {
                    if (card) card.classList.remove('card-removing');
                }
            } catch (err) {
                console.error("Single mark read failed:", err);
                if (card) card.classList.remove('card-removing');
            }
        });
    }

    async function pollNotifications() {
        if (!notifBellBtn) return;
        try {
            const pollUrl = getAppUrl('notifications/unread');
            const res = await fetch(pollUrl);
            if (!res.ok) return;
            const data = await res.json();

            if (data.status === 'success') {
                const newLatestId = parseInt(data.latest_id, 10) || 0;

                if (lastKnownLatestId > 0 && newLatestId > lastKnownLatestId) {
                    const freshItems = data.notifications.filter(n => parseInt(n.id, 10) > lastKnownLatestId);

                    if (notifBellIcon) {
                        notifBellIcon.classList.add('bell-ringing');
                        setTimeout(() => notifBellIcon.classList.remove('bell-ringing'), 1800);
                    }

                    playNotificationChime();

                    if ('Notification' in window && Notification.permission === 'granted') {
                        freshItems.forEach(item => {
                            try {
                                const browserNotif = new Notification(item.title, {
                                    body: item.message,
                                    icon: "assets/images/logo.png"
                                });
                                browserNotif.onclick = function() {
                                    window.focus();
                                    crmNavigator.loadUrl(getAppUrl('notifications/read', 'id=' + item.id));
                                };
                            } catch (e) {}
                        });
                    }
                }

                if (newLatestId > lastKnownLatestId) {
                    lastKnownLatestId = newLatestId;
                }
                renderNotifications(data.notifications, data.unread_count);
            }
        } catch (e) {}
    }

    if (notifBellBtn) {
        setInterval(pollNotifications, 10000);
    }

    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const cards = notifList ? notifList.querySelectorAll('.notif-card') : [];
            cards.forEach(card => card.classList.add('card-removing'));

            try {
                const res = await fetch(getAppUrl('notifications/mark-all-read', 'format=json'));
                const data = await res.json();
                if (data.status === 'success') {
                    setTimeout(() => {
                        if (notifList) {
                            notifList.innerHTML = `
                                <div class="p-4 text-center text-muted" id="notifEmptyState">
                                    <div class="d-inline-flex p-3 rounded-circle bg-light border mb-3">
                                        <i data-lucide="check-circle" class="text-success" style="width: 32px; height: 32px; opacity: 0.8;"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">All Caught Up!</h6>
                                    <p class="small text-muted mb-0">No unread notifications. New lead activities and tasks will appear here instantly.</p>
                                </div>
                            `;
                            if (typeof lucide !== 'undefined') lucide.createIcons();
                        }
                    }, 320);
                    updateNotificationBadges(0, 0);
                    crmToast('All notifications marked as read & cleared', 'success');
                } else {
                    cards.forEach(card => card.classList.remove('card-removing'));
                }
            } catch (err) {
                console.error("Mark all read failed:", err);
                cards.forEach(card => card.classList.remove('card-removing'));
            }
        });
    }

    // 6. Global Click Delegation for Quick Status & Priority Drops & Offcanvas
    document.addEventListener('click', async (e) => {
        // Quick Status dropdown item clicked
        const statusItem = e.target.closest('.quick-change-status-item');
        if (statusItem) {
            e.preventDefault();
            const leadId = statusItem.getAttribute('data-lead-id');
            const newStatus = statusItem.getAttribute('data-status');
            const tableBadgeEl = document.getElementById(`status-badge-${leadId}`);
            const offcanvasBadgeEl = document.getElementById('offcanvasStatusBadge');

            if (!leadId || !newStatus) return;

            if (tableBadgeEl) tableBadgeEl.style.opacity = '0.5';
            if (offcanvasBadgeEl) offcanvasBadgeEl.style.opacity = '0.5';

            try {
                const formData = new FormData();
                formData.append('lead_id', leadId);
                formData.append('field', 'status');
                formData.append('value', newStatus);

                const res = await fetch(getAppUrl('leads/quick-update'), {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();

                if (data.status === 'success') {
                    if (tableBadgeEl) {
                        let pillClass = 'sample-status-active';
                        if (/pending|new/i.test(data.value)) pillClass = 'sample-status-pending';
                        else if (/lost|cancel|not|reject/i.test(data.value)) pillClass = 'sample-status-secondary';
                        tableBadgeEl.className = `sample-status-pill ${pillClass} badge-lead-status badge-status-${data.slug}`;
                        const textSpan = tableBadgeEl.querySelector('.badge-text') || tableBadgeEl;
                        textSpan.textContent = data.value;
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

                    // Update modal trigger buttons data-current-status
                    document.querySelectorAll(`.btn-open-status-modal[data-lead-id="${leadId}"]`).forEach(b => {
                        b.setAttribute('data-current-status', data.value);
                    });

                    if (offcanvasBadgeEl) {
                        offcanvasBadgeEl.className = `badge badge-lead-status w-100 justify-content-between py-2 px-3 badge-status-${data.slug}`;
                        const offText = document.getElementById('offcanvasStatusText');
                        if (offText) offText.textContent = data.value;
                    }

                    // Dynamically append to offcanvas timeline if currently viewing this lead
                    appendActivityToOffcanvas(leadId, {
                        type: 'status_change',
                        author: data.author || 'Staff',
                        time: data.formatted_time || 'Today',
                        remarks: data.remarks || `Status updated to ${data.value}`
                    });

                    if (window.crmNavigator && window.crmNavigator.cache) {
                        window.crmNavigator.cache.clear();
                    }
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                    crmToast(data.message || `Status updated to ${data.value}`, 'success');
                } else {
                    crmToast(data.message || 'Failed to update status', 'error');
                }
            } catch (err) {
                console.error('Quick status update error:', err);
            } finally {
                if (tableBadgeEl) tableBadgeEl.style.opacity = '1';
                if (offcanvasBadgeEl) offcanvasBadgeEl.style.opacity = '1';
            }
            return;
        }

        // Quick Priority dropdown item clicked
        const priorityItem = e.target.closest('.quick-change-priority-item');
        if (priorityItem) {
            e.preventDefault();
            const leadId = priorityItem.getAttribute('data-lead-id');
            const newPriority = priorityItem.getAttribute('data-priority');
            const tableBadgeEl = document.getElementById(`priority-badge-${leadId}`);
            const offcanvasBadgeEl = document.getElementById('offcanvasPriorityBadge');

            if (!leadId || !newPriority) return;

            if (tableBadgeEl) tableBadgeEl.style.opacity = '0.5';
            if (offcanvasBadgeEl) offcanvasBadgeEl.style.opacity = '0.5';

            try {
                const formData = new FormData();
                formData.append('lead_id', leadId);
                formData.append('field', 'priority');
                formData.append('value', newPriority);

                const res = await fetch(getAppUrl('leads/quick-update'), {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();

                if (data.status === 'success') {
                    if (tableBadgeEl) {
                        tableBadgeEl.className = `badge priority-badge priority-badge-${data.value} px-2.5 py-1`;
                        const textSpan = tableBadgeEl.querySelector('.badge-text') || tableBadgeEl;
                        textSpan.textContent = data.value;
                        tableBadgeEl.closest('tr')?.classList.add('row-updated-flash');
                        setTimeout(() => tableBadgeEl.closest('tr')?.classList.remove('row-updated-flash'), 1500);

                        // Update checkmarks in priority dropdown if present
                        const priorityDropdown = tableBadgeEl.closest('.dropdown');
                        if (priorityDropdown) {
                            priorityDropdown.querySelectorAll('.quick-change-priority-item').forEach(it => {
                                const isCur = it.getAttribute('data-priority') === data.value;
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

                    if (offcanvasBadgeEl) {
                        offcanvasBadgeEl.className = `badge priority-badge px-2.5 py-1 priority-badge-${data.value}`;
                        const offText = document.getElementById('offcanvasPriorityText');
                        if (offText) offText.textContent = data.value;
                    }

                    // Dynamically append to offcanvas timeline if currently viewing this lead
                    appendActivityToOffcanvas(leadId, {
                        type: 'priority_change',
                        author: data.author || 'Staff',
                        time: data.formatted_time || 'Today',
                        remarks: data.remarks || `Priority updated to ${data.value}`
                    });

                    if (window.crmNavigator && window.crmNavigator.cache) {
                        window.crmNavigator.cache.clear();
                    }
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                    crmToast(data.message || `Priority set to ${data.value}`, 'success');
                } else {
                    crmToast(data.message || 'Failed to update priority', 'error');
                }
            } catch (err) {
                console.error('Quick priority update error:', err);
            } finally {
                if (tableBadgeEl) tableBadgeEl.style.opacity = '1';
                if (offcanvasBadgeEl) offcanvasBadgeEl.style.opacity = '1';
            }
            return;
        }

        // Sliding Offcanvas Drawer Inspector
        const offcanvasTrigger = e.target.closest('.btn-trigger-offcanvas');
        if (offcanvasTrigger) {
            e.preventDefault();
            const leadId = offcanvasTrigger.getAttribute('data-lead-id');
            const offcanvasEl = document.getElementById('leadDetailsOffcanvas');

            if (!leadId || !offcanvasEl) return;

            if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
                bsOffcanvas.show();
            }

            const followupListEl = document.getElementById('offcanvasFollowupList');
            if (followupListEl) {
                followupListEl.innerHTML = `
                    <div class="text-center py-3 text-muted">
                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                        <span class="ms-2">Loading details & logs...</span>
                    </div>
                `;
            }

            try {
                const res = await fetch(getAppUrl('leads/quick-details', 'id=' + leadId));
                const data = await res.json();

                if (data.status === 'success' && data.lead) {
                    const l = data.lead;
                    window.currentOffcanvasLead = l;

                    setText('offcanvasStudentName', l.student_name);
                    setText('offcanvasLeadRef', l.lead_ref);
                    setText('offcanvasClassApplied', l.class_applied || 'Grade Applied');
                    setText('offcanvasAvatarChar', l.student_initial || 'S');
                    setText('offcanvasEmail', l.email || 'No email provided');
                    setText('offcanvasClass', l.class_applied || '—');
                    setText('offcanvasCounselor', l.assigned_name || 'Unassigned');
                    setText('offcanvasCity', l.city || 'Purnea');

                    const fullUrl = getAppUrl('leads/show', 'id=' + l.id);
                    setHref('offcanvasFullProfileLink', fullUrl);
                    setHref('offcanvasFooterFullBtn', fullUrl);
                    setHref('offcanvasEditBtn', getAppUrl('leads/edit', 'id=' + l.id));
                    setHref('offcanvasViewLogsLink', fullUrl + '#timeline-tab');
                    setHref('offcanvasAddNoteBtn', fullUrl + '#timeline-tab');

                    // Set data-lead-id on offcanvas status and priority menu items
                    document.querySelectorAll('.offcanvas-status-option').forEach(el => el.setAttribute('data-lead-id', l.id));
                    document.querySelectorAll('.offcanvas-priority-option').forEach(el => el.setAttribute('data-lead-id', l.id));

                    // 1. Call Button
                    const callBtn = document.getElementById('offcanvasCallBtn');
                    if (callBtn) callBtn.href = l.mobile ? `tel:${l.clean_mobile || l.mobile}` : '#';

                    // 2. WhatsApp Button
                    const waBtn = document.getElementById('offcanvasWhatsAppBtn');
                    if (waBtn) waBtn.setAttribute('data-lead-id', l.id);

                    // 3. Email Button
                    const emailBtn = document.getElementById('offcanvasEmailBtn');
                    if (emailBtn) emailBtn.setAttribute('data-lead-id', l.id);

                    const progBar = document.getElementById('offcanvasProgressBar');
                    if (progBar) {
                        progBar.style.width = `${l.progress_percent}%`;
                        setText('offcanvasProgressText', `${l.progress_percent}%`);
                        setText('offcanvasProgressLabel', `${l.progress_percent}% completed`);
                    }

                    const offStatusBadge = document.getElementById('offcanvasStatusBadge');
                    if (offStatusBadge) {
                        offStatusBadge.className = `badge badge-lead-status px-2.5 py-1 badge-status-${l.status_slug}`;
                        setText('offcanvasStatusText', l.status);
                    }

                    const offPriorityBadge = document.getElementById('offcanvasPriorityBadge');
                    if (offPriorityBadge) {
                        offPriorityBadge.className = `badge priority-badge px-2.5 py-1 priority-badge-${l.priority}`;
                        setText('offcanvasPriorityText', l.priority);
                    }

                    setText('offcanvasNoteAuthor', l.assigned_name || 'Staff');
                    setText('offcanvasNoteDate', l.enquiry_date ? `Today 12:00 PM` : 'Recent');
                    setText('offcanvasRemarks', l.remarks && l.remarks.trim() ? l.remarks : 'Admission documents and notes recorded for candidate.');
                    setText('offcanvasNotesCount', l.remarks && l.remarks.trim() ? '1' : '0');

                    setText('offcanvasParentName', l.parent_name || '—');
                    setText('offcanvasMobile', l.mobile || '—');
                    setText('offcanvasSource', l.enquiry_source || '—');
                    setText('offcanvasFollowupDate', l.next_followup_date || '—');

                    const timeline = data.timeline || [];
                    const followups = data.followups || [];
                    const assignments = data.assignments || [];
                    setText('offcanvasActivityCount', timeline.length || (followups.length + assignments.length));

                    if (followupListEl) {
                        let fHtml = '';
                        if (timeline.length > 0) {
                            timeline.forEach(item => {
                                const timeStr = escapeHtml(item.formatted_time || item.created_at || 'Recently');
                                const author = escapeHtml(item.author || 'Staff');

                                if (item.type === 'task_created') {
                                    fHtml += `
                                        <div class="timeline-v2-item">
                                            <div class="timeline-v2-node timeline-node-lavender">
                                                <i data-lucide="calendar-plus" style="width: 15px; height: 15px;"></i>
                                            </div>
                                            <div class="timeline-v2-content">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="text-dark small"><strong>${author}</strong> created task: <strong>${escapeHtml(item.task_title || item.title)}</strong></span>
                                                    <span class="text-muted" style="font-size: 0.72rem;">${timeStr}</span>
                                                </div>
                                                <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                                                    <span class="badge bg-primary text-white border-0 px-2 py-0.5 fw-semibold" style="font-size: 0.72rem; border-radius: 4px;">
                                                        ${escapeHtml(item.task_type || 'Follow-up')}
                                                    </span>
                                                    <span class="badge priority-badge-${escapeHtml(item.priority || 'Medium')} py-0.5" style="font-size: 0.72rem;">
                                                        ${escapeHtml(item.priority || 'Medium')} Priority
                                                    </span>
                                                    ${item.assignee_name ? `<span class="text-muted small" style="font-size: 0.72rem;">Assigned to ${escapeHtml(item.assignee_name)}</span>` : ''}
                                                </div>
                                                ${item.description ? `<div class="text-secondary small mt-1.5 p-2 bg-light rounded" style="font-size: 0.78rem;">${escapeHtml(item.description)}</div>` : ''}
                                            </div>
                                        </div>
                                    `;
                                } else if (item.type === 'task_completed') {
                                    fHtml += `
                                        <div class="timeline-v2-item">
                                            <div class="timeline-v2-node timeline-node-emerald">
                                                <i data-lucide="check-circle-2" style="width: 15px; height: 15px;"></i>
                                            </div>
                                            <div class="timeline-v2-content">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="text-dark small"><strong>${author}</strong> completed task: <strong>${escapeHtml(item.task_title || item.title)}</strong></span>
                                                    <span class="text-muted" style="font-size: 0.72rem;">${timeStr}</span>
                                                </div>
                                                <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                                                    <span class="badge bg-success text-white border-0 px-2 py-0.5 fw-semibold" style="font-size: 0.72rem; border-radius: 4px;">
                                                        <i data-lucide="check-check" style="width: 12px; height: 12px;" class="me-1"></i> Completed
                                                    </span>
                                                    <span class="badge bg-light text-secondary border px-2 py-0.5 fw-medium" style="font-size: 0.72rem; border-radius: 4px;">
                                                        ${escapeHtml(item.task_type || 'Task')}
                                                    </span>
                                                    ${item.assignee_name ? `<span class="text-muted small" style="font-size: 0.72rem;">Assignee: ${escapeHtml(item.assignee_name)}</span>` : ''}
                                                </div>
                                                ${item.completion_remarks ? `
                                                    <div class="text-secondary small mt-1.5 p-2 bg-light rounded" style="border-left: 3px solid #10b981; font-size: 0.78rem;">
                                                        <strong class="text-success d-inline-flex align-items-center gap-1 mb-1">
                                                            <i data-lucide="message-square" style="width: 12px; height: 12px;"></i> Outcome:
                                                        </strong>
                                                        <div>${escapeHtml(item.completion_remarks)}</div>
                                                    </div>
                                                ` : ''}
                                            </div>
                                        </div>
                                    `;
                                } else if (item.type === 'lead_created') {
                                    fHtml += `
                                        <div class="timeline-v2-item">
                                            <div class="timeline-v2-node timeline-node-blue">
                                                <i data-lucide="user-plus" style="width: 15px; height: 15px;"></i>
                                            </div>
                                            <div class="timeline-v2-content">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="text-dark small"><strong>${author}</strong> registered enquiry</span>
                                                    <span class="text-muted" style="font-size: 0.72rem;">${timeStr}</span>
                                                </div>
                                                <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                                                    <span class="badge bg-primary text-white border-0 px-2 py-0.5 fw-semibold" style="font-size: 0.72rem; border-radius: 4px;">Enquiry Created</span>
                                                    ${item.class_applied ? `<span class="badge bg-light text-secondary border px-2 py-0.5 fw-medium" style="font-size: 0.72rem; border-radius: 4px;">Class: ${escapeHtml(item.class_applied)}</span>` : ''}
                                                    ${item.enquiry_source ? `<span class="text-muted small" style="font-size: 0.72rem;">Source: ${escapeHtml(item.enquiry_source)}</span>` : ''}
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                } else if (item.type === 'assignment') {
                                    fHtml += `
                                        <div class="timeline-v2-item">
                                            <div class="timeline-v2-node timeline-node-white">
                                                <i data-lucide="loader" style="width: 15px; height: 15px;"></i>
                                            </div>
                                            <div class="timeline-v2-content">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="text-dark small"><strong>${author}</strong> shared deal progress</span>
                                                    <span class="text-muted" style="font-size: 0.72rem;">${timeStr}</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-1 mt-1">
                                                    <span class="badge-stage-purple">${escapeHtml(item.prev_user_name || 'New')}</span>
                                                    <span class="text-muted small mx-1">&rarr;</span>
                                                    <span class="badge-stage-green">${escapeHtml(item.new_user_name || 'Assigned')}</span>
                                                </div>
                                                ${item.reason && item.reason !== 'Initial assignment' ? `
                                                    <div class="text-secondary small mt-1.5 p-2 bg-light rounded" style="font-size: 0.78rem;">${escapeHtml(item.reason)}</div>
                                                ` : ''}
                                            </div>
                                        </div>
                                    `;
                                } else if (item.type === 'status_change') {
                                    fHtml += `
                                        <div class="timeline-v2-item">
                                            <div class="timeline-v2-node timeline-node-lavender">
                                                <i data-lucide="tag" style="width: 15px; height: 15px;" class="text-primary"></i>
                                            </div>
                                            <div class="timeline-v2-content">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="text-dark small"><strong>${author}</strong> updated lead status</span>
                                                    <span class="text-muted" style="font-size: 0.72rem;">${timeStr}</span>
                                                </div>
                                                <div class="text-secondary small mt-1.5 p-2 bg-light rounded d-flex align-items-center gap-1.5" style="font-size: 0.78rem;">
                                                    <i data-lucide="activity" style="width: 13px; height: 13px;" class="text-primary flex-shrink-0"></i>
                                                    <span>${escapeHtml(item.remarks || item.description || 'Status changed')}</span>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                } else if (item.type === 'priority_change') {
                                    fHtml += `
                                        <div class="timeline-v2-item">
                                            <div class="timeline-v2-node timeline-node-lavender">
                                                <i data-lucide="flame" style="width: 15px; height: 15px;" class="text-warning"></i>
                                            </div>
                                            <div class="timeline-v2-content">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="text-dark small"><strong>${author}</strong> updated lead priority</span>
                                                    <span class="text-muted" style="font-size: 0.72rem;">${timeStr}</span>
                                                </div>
                                                <div class="text-secondary small mt-1.5 p-2 bg-light rounded d-flex align-items-center gap-1.5" style="font-size: 0.78rem;">
                                                    <i data-lucide="alert-circle" style="width: 13px; height: 13px;" class="text-warning flex-shrink-0"></i>
                                                    <span>${escapeHtml(item.remarks || item.description || 'Priority changed')}</span>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                } else if (item.type === 'document') {
                                    fHtml += `
                                        <div class="timeline-v2-item">
                                            <div class="timeline-v2-node timeline-node-white">
                                                <i data-lucide="file-text" style="width: 15px; height: 15px;"></i>
                                            </div>
                                            <div class="timeline-v2-content">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="text-dark small"><strong>${author}</strong> uploaded document: <strong>${escapeHtml(item.file_name || '')}</strong></span>
                                                    <span class="text-muted" style="font-size: 0.72rem;">${timeStr}</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    <span class="badge bg-secondary text-white px-2 py-0.5" style="font-size: 0.72rem; border-radius: 4px;">${escapeHtml(item.verification_status || 'Uploaded')}</span>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                } else {
                                    // call_log
                                    const icon = (item.contact_method === 'WhatsApp') ? 'message-square' : ((item.contact_method === 'In-Person') ? 'users' : 'phone-call');
                                    fHtml += `
                                        <div class="timeline-v2-item">
                                            <div class="timeline-v2-node timeline-node-lavender">
                                                <i data-lucide="${icon}" style="width: 15px; height: 15px;"></i>
                                            </div>
                                            <div class="timeline-v2-content">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="text-dark small"><strong>${author}</strong> logged follow-up interaction</span>
                                                    <span class="text-muted" style="font-size: 0.72rem;">${timeStr}</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    <span class="badge bg-success text-white border-0 px-2 py-0.5 fw-semibold" style="font-size: 0.72rem; border-radius: 4px;">
                                                        <i data-lucide="check" style="width: 12px; height: 12px;" class="me-1"></i> ${escapeHtml(item.outcome || 'Connected')}
                                                    </span>
                                                    <span class="text-muted small" style="font-size: 0.72rem;">via ${escapeHtml(item.contact_method || 'Phone')}</span>
                                                </div>
                                                ${item.remarks ? `<div class="text-secondary small mt-1.5 p-2 bg-light rounded" style="font-size: 0.78rem;">${escapeHtml(item.remarks)}</div>` : ''}
                                            </div>
                                        </div>
                                    `;
                                }
                            });
                        } else {
                            fHtml = `
                                <div class="timeline-v2-item">
                                    <div class="timeline-v2-node timeline-node-lavender">
                                        <i data-lucide="message-square" style="width: 15px; height: 15px;"></i>
                                    </div>
                                    <div class="timeline-v2-content">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="text-dark small"><strong>${escapeHtml(l.assigned_name || 'Counselor')}</strong> reviewed enquiry</span>
                                            <span class="text-muted" style="font-size: 0.72rem;">Recently</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                        followupListEl.innerHTML = fHtml;
                    }

                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            } catch (err) {
                console.error("Offcanvas load error:", err);
            }
        }
    });

    function appendActivityToOffcanvas(leadId, activity) {
        let iconName = 'activity';
        let iconColor = 'text-primary';
        let actionLabel = 'updated candidate';
        let nodeClass = 'timeline-node-lavender';

        if (activity.type === 'status_change') {
            iconName = 'tag';
            iconColor = 'text-primary';
            actionLabel = 'updated lead status';
            nodeClass = 'timeline-node-lavender';
        } else if (activity.type === 'priority_change') {
            iconName = 'flame';
            iconColor = 'text-warning';
            actionLabel = 'updated lead priority';
            nodeClass = 'timeline-node-lavender';
        } else if (activity.type === 'whatsapp') {
            iconName = 'message-circle';
            iconColor = 'text-success';
            actionLabel = 'sent WhatsApp message';
            nodeClass = 'timeline-node-emerald';
        } else if (activity.type === 'email') {
            iconName = 'mail';
            iconColor = 'text-primary';
            actionLabel = 'sent Email message';
            nodeClass = 'timeline-node-blue';
        }

        const itemHtml = `
            <div class="timeline-v2-item row-updated-flash">
                <div class="timeline-v2-node ${nodeClass}">
                    <i data-lucide="${iconName}" style="width: 15px; height: 15px;" class="${iconColor}"></i>
                </div>
                <div class="timeline-v2-content">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-dark small"><strong>${escapeHtml(activity.author || 'Staff')}</strong> ${actionLabel}</span>
                        <span class="text-muted" style="font-size: 0.72rem;">${escapeHtml(activity.time || 'Today')}</span>
                    </div>
                    <div class="text-secondary small mt-1.5 p-2 bg-light rounded d-flex align-items-center gap-1.5" style="font-size: 0.78rem;">
                        <i data-lucide="${iconName}" style="width: 13px; height: 13px;" class="${iconColor} flex-shrink-0"></i>
                        <span>${escapeHtml(activity.remarks || '')}</span>
                    </div>
                </div>
            </div>
        `;

        // 1. Offcanvas Timeline List
        const fullProfileLink = document.getElementById('offcanvasFullProfileLink');
        const followupListEl = document.getElementById('offcanvasFollowupList');
        if (fullProfileLink && followupListEl) {
            const currentHref = fullProfileLink.getAttribute('href') || '';
            if (currentHref.includes('id=' + leadId)) {
                followupListEl.insertAdjacentHTML('afterbegin', itemHtml);
                const countBadge = document.getElementById('offcanvasActivityCount');
                if (countBadge) {
                    countBadge.textContent = parseInt(countBadge.textContent || '0', 10) + 1;
                }
            }
        }

        // 2. Lead Details Page (leads/show) Timelines
        document.querySelectorAll('.timeline-v2-container').forEach(c => {
            if (c.id !== 'offcanvasFollowupList') {
                c.insertAdjacentHTML('afterbegin', itemHtml);
            }
        });

        document.querySelectorAll('.badge-tab-count, .badge-yellow-pill').forEach(b => {
            if (b.closest('#timeline-tab') || b.closest('.lead-card-header')) {
                const cur = parseInt(b.textContent || '0', 10);
                if (!isNaN(cur)) b.textContent = cur + 1;
            }
        });

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // =========================================================================
    // QUICK WHATSAPP & EMAIL MODALS (Status Templates, Summernote & Formatter)
    // =========================================================================

    // Official School Sign-off snippets
    const WA_SIGN_OFF = `*Thanks & Regards,*
*GD Goenka Public School, Purnea*
📞 Toll-Free: 18008911934
✉️ info@gdgoenkapurnea.com
🌐 www.gdgoenkapurnea.com`;

    const EMAIL_SIGN_OFF_HTML = `<br>
<p>Thanks &amp; Regards,<br>
<strong>GD Goenka Public School, Purnea</strong><br>
📞 Toll-Free: <strong>18008911934</strong><br>
✉️ Email: <a href="mailto:info@gdgoenkapurnea.com">info@gdgoenkapurnea.com</a><br>
🌐 Website: <a href="https://gdgoenkapurnea.com" target="_blank">www.gdgoenkapurnea.com</a></p>`;

    const EMAIL_SIGN_OFF_PLAIN = `\n\nThanks & Regards,
GD Goenka Public School, Purnea
📞 Toll-Free: 18008911934
✉️ Email: info@gdgoenkapurnea.com
🌐 Website: www.gdgoenkapurnea.com`;

    // WhatsApp status templates with bolding, paragraphs, bullet points and proper spacing
    const quickWaTemplates = {
        greeting: (l) => `Dear *${l.parent_name || 'Parent'}*,

Warm Greetings from *GD Goenka Public School, Purnea*! 🎓

We have received your admission enquiry regarding *${l.student_name || 'your ward'}* for *Grade ${l.class_applied || 'the desired grade'}* (Ref: *${l.lead_ref || 'GDG-ADM'}*).

At GD Goenka Public School, we offer:
• CBSE World-Class Smart Classrooms & STEAM Labs
• State-of-the-Art Sports Complex & Performing Arts
• GPS-Enabled AC Buses & CCTV Secured Campus
• Individual Attention & 100% Focused Mentorship

We would be delighted to guide you through our admission procedure and counseling session. Please feel free to reply here or let us know a convenient time to connect.

${WA_SIGN_OFF}`,

        campus_visit: (l) => `Dear *${l.parent_name || 'Parent'}*,

We look forward to welcoming you and *${l.student_name || 'your ward'}* to *GD Goenka Public School, Purnea*! 🏫

*Campus Tour & Academic Counseling Details:*
• Candidate: *${l.student_name || 'Candidate'}* (Grade ${l.class_applied || 'Admission'})
• Timings: Monday to Saturday (9:00 AM – 3:30 PM)
• Campus Location: https://maps.google.com/?q=GD+Goenka+Public+School+Purnea

During your visit, you will meet our educators, explore the smart classrooms, robotics lab, library, and sports arena.

Please reply to confirm your preferred day and time for the visit.

${WA_SIGN_OFF}`,

        fee_details: (l) => `Dear *${l.parent_name || 'Parent'}*,

Thank you for your interest in *GD Goenka Public School, Purnea* for *${l.student_name || 'your child'}*'s education. 💰

*Fee Structure & Admission Guidelines for Grade ${l.class_applied || 'Applied Grade'}:*
• Transparent composite fee with flexible installment options
• Covers academic tuition, digital lab facilities, sports, and library
• Sibling and merit scholarship considerations available

Please let us know if you would like us to share the complete detailed fee breakdown PDF and registration form.

${WA_SIGN_OFF}`,

        doc_pending: (l) => `Dear *${l.parent_name || 'Parent'}*,

Friendly reminder from *GD Goenka Public School, Purnea* regarding pending admission documents for *${l.student_name || 'the candidate'}* (Grade *${l.class_applied || 'enrolled'}*). 📋

*Kindly share or submit the following required documents:*
• 1) Birth Certificate of the student (Self-attested copy)
• 2) Previous School Report Card / Transfer Certificate (TC)
• 3) 4 Recent Passport-size Photographs of Student & Parents
• 4) Student & Parent Aadhar Card copies
• 5) Duly signed Admission Registration Form

Kindly share clear scanned copies here on WhatsApp or visit our admission office to finalize your seat reservation.

${WA_SIGN_OFF}`,

        followup: (l) => `Dear *${l.parent_name || 'Parent'}*,

Following up regarding the admission enquiry for *${l.student_name || 'your ward'}* in *Grade ${l.class_applied || 'the desired grade'}* at *GD Goenka Public School, Purnea*. 📞

Seats for the upcoming academic session are filling quickly. Have you had an opportunity to review the prospectus and school curriculum?

We are here to assist with any questions regarding transportation routes, fee schedules, or academic programs.

Please let us know if we can schedule a quick counseling call today.

${WA_SIGN_OFF}`,

        admission_confirmed: (l) => `🎉 *Congratulations ${l.parent_name || 'Parent'}!*

We are thrilled to confirm the admission of *${l.student_name || 'your ward'}* to *Grade ${l.class_applied || 'Enrolled Grade'}* at *GD Goenka Public School, Purnea*! 🎓✨

*Enrolment Reference:* *${l.lead_ref || 'GDG-CONFIRMED'}*

Welcome to the Goenkan family! We are dedicated to providing a nurturing, world-class educational environment for your child.

Our admissions desk will share the parent orientation schedule, booklist, uniform details, and academic calendar shortly.

${WA_SIGN_OFF}`,

        custom: (l) => `Dear *${l.parent_name || 'Parent'}*,

Regarding admission for *${l.student_name || 'Student'}* in Grade *${l.class_applied || ''}* at *GD Goenka Public School, Purnea*:



${WA_SIGN_OFF}`
    };

    function getWaTemplateForStatus(status) {
        const s = (status || '').toLowerCase();
        if (s.includes('visit')) return 'campus_visit';
        if (s.includes('fee')) return 'fee_details';
        if (s.includes('doc') || s.includes('form')) return 'doc_pending';
        if (s.includes('confirm') || s.includes('enroll')) return 'admission_confirmed';
        if (s.includes('follow') || s.includes('contact')) return 'followup';
        return 'greeting';
    }

    // Rich Email Templates with HTML (for Summernote) and Plain Text (for mailto links)
    const quickEmailTemplates = {
        brochure: (l) => ({
            subject: `Admission Enquiry - GD Goenka Public School, Purnea | ${l.student_name || 'Candidate'} (Grade ${l.class_applied || 'Admissions'})`,
            bodyHtml: `<p>Dear <strong>${escapeHtml(l.parent_name || 'Parent')}</strong>,</p>
<p>Thank you for expressing your interest in <strong>GD Goenka Public School, Purnea</strong> for the academic journey of <strong>${escapeHtml(l.student_name || 'your ward')}</strong> in <strong>Grade ${escapeHtml(l.class_applied || 'the upcoming session')}</strong> (Ref: <code>${escapeHtml(l.lead_ref || 'GDG-ENQ')}</code>).</p>
<p>At GD Goenka Public School, we provide a holistic, CBSE-affiliated curriculum enriched with:</p>
<ul>
    <li><strong>Digital Classrooms &amp; Advanced STEAM Labs:</strong> Smart interactive teaching tools and robotics.</li>
    <li><strong>Comprehensive Sports &amp; Cultural Infrastructure:</strong> Swimming, skating, cricket, music, and art studios.</li>
    <li><strong>Safety &amp; Well-being:</strong> Complete CCTV surveillance, infirmary, and GPS-enabled transportation.</li>
    <li><strong>Individual Student Focus:</strong> Personalized academic counseling and skill-building modules.</li>
</ul>
<p>We cordially invite you and your family to visit our campus for an interactive counseling session and guided tour.</p>
<p>Please feel free to reply directly to this email or call our toll-free helpline if you have any questions.</p>
${EMAIL_SIGN_OFF_HTML}`,
            bodyPlain: `Dear ${l.parent_name || 'Parent'},

Thank you for expressing your interest in GD Goenka Public School, Purnea for the academic journey of ${l.student_name || 'your ward'} in Grade ${l.class_applied || 'the upcoming session'} (Ref: ${l.lead_ref || 'GDG-ENQ'}).

At GD Goenka Public School, we provide a holistic, CBSE-affiliated curriculum enriched with:
• Digital Classrooms & Advanced STEAM Labs: Smart interactive teaching tools and robotics.
• Comprehensive Sports & Cultural Infrastructure: Swimming, skating, cricket, music, and art studios.
• Safety & Well-being: Complete CCTV surveillance, infirmary, and GPS-enabled transportation.
• Individual Student Focus: Personalized academic counseling and skill-building modules.

We cordially invite you and your family to visit our campus for an interactive counseling session and guided tour.

Please feel free to reply directly to this email or call our toll-free helpline if you have any questions.${EMAIL_SIGN_OFF_PLAIN}`
        }),

        campus_tour: (l) => ({
            subject: `Invitation for Campus Tour & Counseling Interaction - GD Goenka Public School, Purnea`,
            bodyHtml: `<p>Dear <strong>${escapeHtml(l.parent_name || 'Parent')}</strong>,</p>
<p>We are delighted to invite you and <strong>${escapeHtml(l.student_name || 'your ward')}</strong> for an experiential campus tour and counseling interaction at <strong>GD Goenka Public School, Purnea</strong>.</p>
<p><strong>Campus Visit Details:</strong></p>
<ul>
    <li><strong>Candidate:</strong> ${escapeHtml(l.student_name || 'Student')} (Grade ${escapeHtml(l.class_applied || 'Admission')})</li>
    <li><strong>Timings:</strong> Monday to Saturday (9:00 AM – 3:30 PM)</li>
    <li><strong>Campus Location:</strong> By-Pass Road, Purnea, Bihar</li>
</ul>
<p>During the visit, you will have the opportunity to:</p>
<ul>
    <li>Tour our smart classrooms, science &amp; computer laboratories, and sports arenas.</li>
    <li>Meet our academic leadership and discuss the curriculum in detail.</li>
    <li>Understand transport routes, boarding options, and school timings.</li>
</ul>
<p>Please reply with your preferred day and time so we may reserve an admission coordinator for you.</p>
${EMAIL_SIGN_OFF_HTML}`,
            bodyPlain: `Dear ${l.parent_name || 'Parent'},

We are delighted to invite you and ${l.student_name || 'your ward'} for an experiential campus tour and counseling interaction at GD Goenka Public School, Purnea.

Campus Visit Details:
• Candidate: ${l.student_name || 'Student'} (Grade ${l.class_applied || 'Admission'})
• Timings: Monday to Saturday (9:00 AM – 3:30 PM)
• Campus Location: By-Pass Road, Purnea, Bihar

During the visit, you will have the opportunity to:
• Tour our smart classrooms, science & computer laboratories, and sports arenas.
• Meet our academic leadership and discuss the curriculum in detail.
• Understand transport routes, boarding options, and school timings.

Please reply with your preferred day and time so we may reserve an admission coordinator for you.${EMAIL_SIGN_OFF_PLAIN}`
        }),

        fee_structure: (l) => ({
            subject: `Fee Schedule & Admission Guidelines for Grade ${l.class_applied || ''} - GD Goenka Public School`,
            bodyHtml: `<p>Dear <strong>${escapeHtml(l.parent_name || 'Parent')}</strong>,</p>
<p>Thank you for considering <strong>GD Goenka Public School, Purnea</strong> for the education of <strong>${escapeHtml(l.student_name || 'your ward')}</strong>.</p>
<p>As requested, please find below the key highlights of our admission fee structure for <strong>Grade ${escapeHtml(l.class_applied || 'the applied class')}</strong>:</p>
<ul>
    <li><strong>Registration &amp; Composite Tuition Fee:</strong> Comprehensive coverage for academic curriculum, laboratory supplies, and digital learning tools.</li>
    <li><strong>Payment Modes:</strong> Convenient quarterly or term installments via online portal, UPI, or demand draft.</li>
    <li><strong>Scholarships:</strong> Merit concessions and sibling benefits as per school guidelines.</li>
</ul>
<p>Should you require the detailed official fee breakdown PDF or guidance on the online fee gateway, please feel free to reach out to our accounts desk.</p>
${EMAIL_SIGN_OFF_HTML}`,
            bodyPlain: `Dear ${l.parent_name || 'Parent'},

Thank you for considering GD Goenka Public School, Purnea for the education of ${l.student_name || 'your ward'}.

As requested, please find below the key highlights of our admission fee structure for Grade ${l.class_applied || 'the applied class'}:
• Registration & Composite Tuition Fee: Comprehensive coverage for academic curriculum, laboratory supplies, and digital learning tools.
• Payment Modes: Convenient quarterly or term installments via online portal, UPI, or demand draft.
• Scholarships: Merit concessions and sibling benefits as per school guidelines.

Should you require the detailed official fee breakdown PDF or guidance on the online fee gateway, please feel free to reach out to our accounts desk.${EMAIL_SIGN_OFF_PLAIN}`
        }),

        docs_checklist: (l) => ({
            subject: `Required Documents for Admission Registration - ${l.student_name || 'Candidate'} | GD Goenka School`,
            bodyHtml: `<p>Dear <strong>${escapeHtml(l.parent_name || 'Parent')}</strong>,</p>
<p>To finalize the admission registration for <strong>${escapeHtml(l.student_name || 'the student')}</strong> in <strong>Grade ${escapeHtml(l.class_applied || 'Enrolled Class')}</strong>, kindly submit self-attested copies of the following documents at your earliest convenience:</p>
<ol>
    <li><strong>Student Birth Certificate:</strong> Issued by the Municipal Corporation / Competent Authority.</li>
    <li><strong>Transfer Certificate (TC) &amp; Report Card:</strong> Original TC and previous class marksheet.</li>
    <li><strong>Passport Photographs:</strong> 4 recent passport-size photographs of the student and 2 each of parents/guardians.</li>
    <li><strong>Aadhar Cards:</strong> Photocopy of student and parent Aadhar cards.</li>
    <li><strong>Medical Record Form:</strong> Basic blood group and immunization history.</li>
</ol>
<p>You may submit these documents directly at our school admissions office or reply to this email with clear scanned copies.</p>
${EMAIL_SIGN_OFF_HTML}`,
            bodyPlain: `Dear ${l.parent_name || 'Parent'},

To finalize the admission registration for ${l.student_name || 'the student'} in Grade ${l.class_applied || 'Enrolled Class'}, kindly submit self-attested copies of the following documents at your earliest convenience:

1. Student Birth Certificate: Issued by the Municipal Corporation / Competent Authority.
2. Transfer Certificate (TC) & Report Card: Original TC and previous class marksheet.
3. Passport Photographs: 4 recent passport-size photographs of the student and 2 each of parents/guardians.
4. Aadhar Cards: Photocopy of student and parent Aadhar cards.
5. Medical Record Form: Basic blood group and immunization history.

You may submit these documents directly at our school admissions office or reply to this email with clear scanned copies.${EMAIL_SIGN_OFF_PLAIN}`
        }),

        welcome_letter: (l) => ({
            subject: `Welcome to GD Goenka Public School - Admission Confirmed for ${l.student_name || 'Student'}!`,
            bodyHtml: `<p>Dear <strong>${escapeHtml(l.parent_name || 'Parent')}</strong>,</p>
<p>On behalf of the Management, Principal, and Faculty of <strong>GD Goenka Public School, Purnea</strong>, we extend a hearty congratulations and warm welcome to <strong>${escapeHtml(l.student_name || 'your child')}</strong> into the Goenkan family!</p>
<p>The admission to <strong>Grade ${escapeHtml(l.class_applied || 'Enrolled Grade')}</strong> has been successfully confirmed under Registration Ref: <code>${escapeHtml(l.lead_ref || 'GDG-ADM')}</code>.</p>
<p>We are dedicated to fostering intellectual curiosity, athletic excellence, and moral character in every student. You will receive detailed communications regarding:</p>
<ul>
    <li>Orientation Session Date &amp; Parent Induction</li>
    <li>Uniform &amp; Book Distribution Schedule</li>
    <li>Transport Route &amp; Pickup/Drop Timings</li>
    <li>School ERP Login Credentials</li>
</ul>
<p>Once again, congratulations and welcome aboard!</p>
${EMAIL_SIGN_OFF_HTML}`,
            bodyPlain: `Dear ${l.parent_name || 'Parent'},

On behalf of the Management, Principal, and Faculty of GD Goenka Public School, Purnea, we extend a hearty congratulations and warm welcome to ${l.student_name || 'your child'} into the Goenkan family!

The admission to Grade ${l.class_applied || 'Enrolled Grade'} has been successfully confirmed under Registration Ref: ${l.lead_ref || 'GDG-ADM'}.

We are dedicated to fostering intellectual curiosity, athletic excellence, and moral character in every student. You will receive detailed communications regarding:
• Orientation Session Date & Parent Induction
• Uniform & Book Distribution Schedule
• Transport Route & Pickup/Drop Timings
• School ERP Login Credentials

Once again, congratulations and welcome aboard!${EMAIL_SIGN_OFF_PLAIN}`
        }),

        custom: (l) => ({
            subject: `Regarding Admission for ${l.student_name || 'Student'} - GD Goenka Public School, Purnea`,
            bodyHtml: `<p>Dear <strong>${escapeHtml(l.parent_name || 'Parent')}</strong>,</p>
<p>Regarding the admission enquiry for <strong>${escapeHtml(l.student_name || 'your ward')}</strong> (Grade ${escapeHtml(l.class_applied || '')}) at GD Goenka Public School, Purnea:</p>
<p><br></p>
${EMAIL_SIGN_OFF_HTML}`,
            bodyPlain: `Dear ${l.parent_name || 'Parent'},

Regarding the admission enquiry for ${l.student_name || 'your ward'} (Grade ${l.class_applied || ''}) at GD Goenka Public School, Purnea:

${EMAIL_SIGN_OFF_PLAIN}`
        })
    };

    function getEmailTemplateForStatus(status) {
        const s = (status || '').toLowerCase();
        if (s.includes('visit')) return 'campus_tour';
        if (s.includes('fee')) return 'fee_structure';
        if (s.includes('doc') || s.includes('form')) return 'docs_checklist';
        if (s.includes('confirm') || s.includes('enroll')) return 'welcome_letter';
        return 'brochure';
    }

    // Helper: Convert Summernote HTML to formatted plain text for mailto / clipboard
    function htmlToFormattedText(html) {
        if (!html) return '';
        let text = html;
        // Break lines
        text = text.replace(/<br\s*[\/]?>/gi, '\n');
        text = text.replace(/<\/p>/gi, '\n\n');
        text = text.replace(/<\/h[1-6]>/gi, '\n\n');
        text = text.replace(/<\/div>/gi, '\n');
        text = text.replace(/<li[^>]*>/gi, '• ');
        text = text.replace(/<\/li>/gi, '\n');
        text = text.replace(/<\/ul>/gi, '\n');
        text = text.replace(/<\/ol>/gi, '\n');
        text = text.replace(/<hr\s*[\/]?>/gi, '\n---\n');

        const tmp = document.createElement('div');
        tmp.innerHTML = text;
        let clean = tmp.textContent || tmp.innerText || '';
        // Reduce 3+ consecutive newlines to 2
        clean = clean.replace(/\n{3,}/g, '\n\n').trim();
        return clean;
    }

    // Helper: Safely detect if jQuery and Summernote are available
    function hasSummernote() {
        try {
            const j = window.jQuery || (typeof $ === 'function' && typeof $.fn !== 'undefined' ? $ : null);
            return !!(j && j.fn && typeof j.fn.summernote === 'function');
        } catch (e) {
            return false;
        }
    }

    function getJQuery() {
        return window.jQuery || (typeof $ === 'function' && typeof $.fn !== 'undefined' ? $ : null);
    }

    // Helper: Initialize Summernote WYSIWYG editor on Email modal textarea
    function initSummernoteForEmail() {
        if (hasSummernote()) {
            const $ = getJQuery();
            const $body = $('#quickEmailBody');
            if ($body.length && !$body.next('.note-editor').length) {
                $body.summernote({
                    placeholder: 'Compose formatted email with bold, paragraphs, lists, and spacing...',
                    tabsize: 2,
                    height: 220,
                    toolbar: [
                        ['style', ['style', 'bold', 'italic', 'underline', 'clear']],
                        ['font', ['strikethrough']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link', 'hr']],
                        ['view', ['codeview']]
                    ],
                    callbacks: {
                        onChange: function(contents) {
                            const plain = $('<div>').html(contents).text();
                            setText('quickEmailCharCount', `${plain.length} characters`);
                        }
                    }
                });
            }
        }
    }

    function setSummernoteContent(html, plainFallback) {
        if (hasSummernote()) {
            const $ = getJQuery();
            const $body = $('#quickEmailBody');
            if ($body.length && $body.next('.note-editor').length) {
                $body.summernote('code', html);
                const plain = $('<div>').html(html).text();
                setText('quickEmailCharCount', `${plain.length} characters`);
                return;
            }
        }
        const el = document.getElementById('quickEmailBody');
        if (el) {
            el.value = plainFallback || htmlToFormattedText(html);
            setText('quickEmailCharCount', `${el.value.length} characters`);
        }
    }

    function getSummernoteHtml() {
        if (hasSummernote()) {
            const $ = getJQuery();
            const $body = $('#quickEmailBody');
            if ($body.length && $body.next('.note-editor').length) {
                return $body.summernote('code');
            }
        }
        return document.getElementById('quickEmailBody')?.value || '';
    }

    // WhatsApp formatting toolbar buttons click handler
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.wa-format-btn');
        if (!btn) return;
        e.preventDefault();
        const textarea = document.getElementById('quickWaMessage');
        if (!textarea) return;

        const format = btn.getAttribute('data-format');
        const insert = btn.getAttribute('data-insert');
        const start = textarea.selectionStart || 0;
        const end = textarea.selectionEnd || 0;
        const val = textarea.value;
        const selected = val.substring(start, end);

        let replacement = '';
        let newCursorPos = start;

        if (format === 'bold') {
            replacement = selected ? `*${selected}*` : `*bold text*`;
            newCursorPos = start + replacement.length;
        } else if (format === 'italic') {
            replacement = selected ? `_${selected}_` : `_italic text_`;
            newCursorPos = start + replacement.length;
        } else if (format === 'bullet') {
            const prefix = (start > 0 && val[start - 1] !== '\n') ? '\n• ' : '• ';
            replacement = prefix + (selected || 'List item');
            newCursorPos = start + replacement.length;
        } else if (insert) {
            replacement = insert;
            newCursorPos = start + replacement.length;
        }

        if (replacement) {
            textarea.value = val.substring(0, start) + replacement + val.substring(end);
            textarea.focus();
            textarea.setSelectionRange(newCursorPos, newCursorPos);
            setText('quickWaCharCount', `${textarea.value.length} characters`);
        }
    // Helper to sanitize any Indian phone number into exactly 10 digits
    function extractValidIndianMobile(raw) {
        if (!raw) return '';
        let digits = String(raw).replace(/\D/g, '');
        // Strip leading zeros (e.g. 08287704101 -> 8287704101)
        digits = digits.replace(/^0+/, '');
        // Strip leading country code 91 if 12 digits (e.g. 918287704101 -> 8287704101)
        if (digits.length === 12 && digits.startsWith('91')) {
            digits = digits.substring(2);
        }
        // If longer than 10 digits from imports, pick last 10 if valid mobile starting with 6/7/8/9
        if (digits.length > 10) {
            const last10 = digits.slice(-10);
            if (/^[6-9]\d{9}$/.test(last10)) {
                digits = last10;
            }
        }
        return digits;
    }

    // Universal Activity Timeline Logger (Works on both leads/index offcanvas and leads/show page)
    async function recordQuickCommunication(type, outcome, remarks, details) {
        const l = window.currentOffcanvasLead;
        if (!l || !l.id) return;
        try {
            const formData = new FormData();
            formData.append('lead_id', l.id);
            formData.append('contact_method', type === 'whatsapp' ? 'WhatsApp' : 'Email');
            formData.append('outcome', outcome);
            formData.append('remarks', remarks);
            formData.append('lead_status', l.status || '');
            formData.append('_csrf_token', window.CSRF_TOKEN || document.querySelector('input[name="_csrf_token"]')?.value || '');

            const res = await fetch(getAppUrl('followups/store'), {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.status === 'success') {
                const author = data.author || 'Staff';
                const time = data.formatted_time || 'Today';

                // 1. Offcanvas activity list (leads/index)
                if (typeof appendActivityToOffcanvas === 'function') {
                    appendActivityToOffcanvas(l.id, {
                        type: type,
                        author: author,
                        time: time,
                        remarks: details || remarks
                    });
                }

                // 2. Timeline on Lead Details page (leads/show)
                const timelineContainer = document.querySelector('#timeline .timeline-v2, .timeline-v2');
                if (timelineContainer) {
                    const icon = type === 'whatsapp' ? 'message-circle' : 'mail';
                    const nodeClass = type === 'whatsapp' ? 'timeline-node-lavender' : 'timeline-node-white';
                    const badgeColor = type === 'whatsapp' ? 'bg-success' : 'bg-primary';

                    const itemHtml = `
                        <div class="timeline-v2-item" style="animation: fadeIn 0.4s ease;">
                            <div class="timeline-v2-node ${nodeClass}">
                                <i data-lucide="${icon}" style="width: 15px; height: 15px;"></i>
                            </div>
                            <div class="timeline-v2-content">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="text-dark small"><strong>${escapeHtml(author)}</strong> logged ${type === 'whatsapp' ? 'WhatsApp' : 'Email'} interaction</span>
                                    <span class="text-muted" style="font-size: 0.72rem;">${escapeHtml(time)}</span>
                                </div>
                                <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                                    <span class="badge ${badgeColor} text-white border-0 px-2 py-0.5 fw-semibold" style="font-size: 0.72rem; border-radius: 4px;">
                                        <i data-lucide="check" style="width: 12px; height: 12px;" class="me-1"></i> ${escapeHtml(outcome)}
                                    </span>
                                </div>
                                <div class="notes-card-box mt-2">
                                    <div class="notes-card-body" style="font-size: 0.82rem; white-space: pre-wrap;">${escapeHtml(details || remarks)}</div>
                                </div>
                            </div>
                        </div>
                    `;
                    timelineContainer.insertAdjacentHTML('afterbegin', itemHtml);
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }

                crmToast(`${type === 'whatsapp' ? 'WhatsApp' : 'Email'} activity recorded on timeline.`, 'success');
            }
        } catch (err) {
            console.error('Followup log error:', err);
        }
    }

    // Open WhatsApp Modal (supports offcanvas button and lead details page buttons)
    document.addEventListener('click', (e) => {
        const waBtn = e.target.closest('#offcanvasWhatsAppBtn, .btn-open-quick-whatsapp');
        if (waBtn) {
            e.preventDefault();
            const l = window.currentOffcanvasLead;
            if (!l) {
                alert('Lead details are still loading. Please try again.');
                return;
            }

            const rawMobile = l.clean_mobile || l.mobile || '';
            const valid10Digits = extractValidIndianMobile(rawMobile);

            setText('quickWaStudentName', l.student_name || 'Student');
            setText('quickWaClassBadge', l.class_applied || 'General');
            setText('quickWaMobileBadge', valid10Digits ? `+91 ${valid10Digits}` : (rawMobile || '—'));
            setText('quickWaStatusText', l.status || 'New');

            const badgeEl = document.getElementById('quickWaStatusBadge');
            if (badgeEl) {
                badgeEl.className = `badge badge-lead-status px-2 py-0.5 badge-status-${l.status_slug || 'new'}`;
            }

            const templateSelect = document.getElementById('quickWaTemplateSelect');
            if (templateSelect) templateSelect.value = 'auto';

            const defaultKey = getWaTemplateForStatus(l.status);
            const msgText = (quickWaTemplates[defaultKey] || quickWaTemplates.greeting)(l);
            const textarea = document.getElementById('quickWaMessage');
            if (textarea) {
                textarea.value = msgText;
                setText('quickWaCharCount', `${msgText.length} characters`);
            }

            const modalEl = document.getElementById('leadQuickWhatsAppModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
            return;
        }

        const emailBtn = e.target.closest('#offcanvasEmailBtn, .btn-open-quick-email');
        if (emailBtn) {
            e.preventDefault();
            const l = window.currentOffcanvasLead;
            if (!l) {
                alert('Lead details are still loading. Please try again.');
                return;
            }

            const toInput = document.getElementById('quickEmailTo');
            if (toInput) toInput.value = l.email || '';
            setText('quickEmailStudentName', l.student_name || 'Student');

            const templateSelect = document.getElementById('quickEmailTemplateSelect');
            if (templateSelect) templateSelect.value = 'auto';

            const defaultKey = getEmailTemplateForStatus(l.status);
            const tpl = (quickEmailTemplates[defaultKey] || quickEmailTemplates.brochure)(l);
            const subjectInput = document.getElementById('quickEmailSubject');
            if (subjectInput) subjectInput.value = tpl.subject;

            const modalEl = document.getElementById('leadQuickEmailModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                bsModal.show();

                // Initialize Summernote and set template content cleanly
                initSummernoteForEmail();
                setSummernoteContent(tpl.bodyHtml, tpl.bodyPlain);

                setTimeout(() => {
                    initSummernoteForEmail();
                    setSummernoteContent(tpl.bodyHtml, tpl.bodyPlain);
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }, 150);
            }
            return;
        }
    });

    // WhatsApp Template Change Listener
    document.getElementById('quickWaTemplateSelect')?.addEventListener('change', function() {
        const l = window.currentOffcanvasLead || {};
        let key = this.value;
        if (key === 'auto') key = getWaTemplateForStatus(l.status);
        const fn = quickWaTemplates[key] || quickWaTemplates.greeting;
        const msg = fn(l);
        const textarea = document.getElementById('quickWaMessage');
        if (textarea) {
            textarea.value = msg;
            setText('quickWaCharCount', `${msg.length} characters`);
        }
    });

    document.getElementById('quickWaMessage')?.addEventListener('input', function() {
        setText('quickWaCharCount', `${this.value.length} characters`);
    });

    document.getElementById('btnCopyWhatsAppMsg')?.addEventListener('click', function() {
        const msg = document.getElementById('quickWaMessage')?.value || '';
        if (!msg) return;
        navigator.clipboard.writeText(msg).then(() => {
            crmToast('Formatted WhatsApp message copied to clipboard!', 'success');
        }).catch(() => {
            crmToast('Failed to copy text', 'error');
        });
    });

    // Send WhatsApp (Auto / App)
    async function executeSendWhatsApp(useWebOnly = false) {
        const l = window.currentOffcanvasLead;
        if (!l) return;
        const raw = l.clean_mobile || l.mobile || '';
        const validDigits = extractValidIndianMobile(raw);
        if (!validDigits || validDigits.length < 10) {
            alert(`This candidate does not have a valid 10-digit mobile number recorded (Found: "${raw}"). Please update the candidate mobile number first.`);
            return;
        }
        const fullWaPhone = '91' + validDigits;
        const msg = document.getElementById('quickWaMessage')?.value?.trim() || '';
        if (!msg) {
            alert('Please enter a WhatsApp message to send.');
            return;
        }

        // Clean newlines: normalize \r\n to \n for WhatsApp formatting
        const cleanMsg = msg.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
        const encodedText = encodeURIComponent(cleanMsg);

        const waUrl = useWebOnly
            ? `https://web.whatsapp.com/send?phone=${fullWaPhone}&text=${encodedText}`
            : `https://api.whatsapp.com/send?phone=${fullWaPhone}&text=${encodedText}`;

        const openedWin = window.open(waUrl, '_blank');
        if (!openedWin) {
            // Popup blocked fallback
            window.location.href = waUrl;
        }

        // Record interaction in timeline
        const logActivity = document.getElementById('quickWaLogActivity')?.checked;
        if (logActivity) {
            await recordQuickCommunication(
                'whatsapp',
                'WhatsApp Message Sent',
                cleanMsg.length > 250 ? (cleanMsg.substring(0, 250) + '...') : cleanMsg,
                `Sent WhatsApp to +${fullWaPhone}:\n\n${cleanMsg.length > 150 ? cleanMsg.substring(0, 150) + '...' : cleanMsg}`
            );
        }

        const modalEl = document.getElementById('leadQuickWhatsAppModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const inst = bootstrap.Modal.getInstance(modalEl);
            if (inst) inst.hide();
        }
    }

    document.getElementById('btnConfirmSendWhatsApp')?.addEventListener('click', function() {
        executeSendWhatsApp(false);
    });

    document.getElementById('btnSendWhatsAppWeb')?.addEventListener('click', function() {
        executeSendWhatsApp(true);
    });

    // Email Template Change Listener
    document.getElementById('quickEmailTemplateSelect')?.addEventListener('change', function() {
        const l = window.currentOffcanvasLead || {};
        let key = this.value;
        if (key === 'auto') key = getEmailTemplateForStatus(l.status);
        const fn = quickEmailTemplates[key] || quickEmailTemplates.brochure;
        const tpl = fn(l);
        const subjectInput = document.getElementById('quickEmailSubject');
        if (subjectInput) subjectInput.value = tpl.subject;
        setSummernoteContent(tpl.bodyHtml, tpl.bodyPlain);
    });

    document.getElementById('quickEmailBody')?.addEventListener('input', function() {
        setText('quickEmailCharCount', `${this.value.length} characters`);
    });

    // Copy Formatted Email (Rich HTML + Plain Text fallback)
    document.getElementById('btnCopyEmailBody')?.addEventListener('click', function() {
        const subject = document.getElementById('quickEmailSubject')?.value || '';
        const rawContent = getSummernoteHtml();
        const formattedPlain = htmlToFormattedText(rawContent);
        const fullText = `Subject: ${subject}\n\n${formattedPlain}`;

        if (navigator.clipboard && window.ClipboardItem) {
            const blobHtml = new Blob([`<p><strong>Subject: ${escapeHtml(subject)}</strong></p>${rawContent}`], { type: 'text/html' });
            const blobText = new Blob([fullText], { type: 'text/plain' });
            navigator.clipboard.write([
                new ClipboardItem({ 'text/html': blobHtml, 'text/plain': blobText })
            ]).then(() => {
                crmToast('Formatted email copied! Paste with Ctrl+V into Gmail or Outlook.', 'success');
            }).catch(() => {
                navigator.clipboard.writeText(fullText).then(() => {
                    crmToast('Email text copied to clipboard!', 'success');
                });
            });
        } else {
            navigator.clipboard.writeText(fullText).then(() => {
                crmToast('Email text copied to clipboard!', 'success');
            }).catch(() => {
                crmToast('Failed to copy email', 'error');
            });
        }
    });

    // Dispatch Email via Gmail Web
    async function executeSendEmailViaGmail() {
        const l = window.currentOffcanvasLead;
        if (!l) return;
        const recipient = document.getElementById('quickEmailTo')?.value?.trim() || '';
        if (!recipient) {
            alert('Please enter a recipient email address.');
            return;
        }
        const subject = document.getElementById('quickEmailSubject')?.value?.trim() || 'Admission Enquiry';
        const rawHtml = getSummernoteHtml();
        const bodyPlain = htmlToFormattedText(rawHtml);
        if (!bodyPlain) {
            alert('Please enter email content.');
            return;
        }

        const gmailUrl = `https://mail.google.com/mail/?view=cm&fs=1&to=${encodeURIComponent(recipient)}&su=${encodeURIComponent(subject)}&body=${encodeURIComponent(bodyPlain)}`;
        window.open(gmailUrl, '_blank');

        if (document.getElementById('quickEmailLogActivity')?.checked) {
            await recordQuickCommunication(
                'email',
                'Email Sent (Gmail Web)',
                `Subject: ${subject} | ${bodyPlain.length > 200 ? bodyPlain.substring(0, 200) + '...' : bodyPlain}`,
                `Sent email via Gmail Web to ${recipient}:\nSubject: ${subject}\n\n${bodyPlain.length > 200 ? bodyPlain.substring(0, 200) + '...' : bodyPlain}`
            );
        }

        const modalEl = document.getElementById('leadQuickEmailModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const inst = bootstrap.Modal.getInstance(modalEl);
            if (inst) inst.hide();
        }
    }

    document.getElementById('btnSendViaGmail')?.addEventListener('click', executeSendEmailViaGmail);

    // Dispatch Email via Default System Mail Client (Outlook / Mailto)
    document.getElementById('btnConfirmSendEmail')?.addEventListener('click', async function() {
        const l = window.currentOffcanvasLead;
        if (!l) return;
        const recipient = document.getElementById('quickEmailTo')?.value?.trim() || '';
        if (!recipient) {
            alert('Please enter a recipient email address.');
            return;
        }
        const subject = document.getElementById('quickEmailSubject')?.value?.trim() || 'Admission Enquiry';
        const rawHtml = getSummernoteHtml();
        const bodyPlain = htmlToFormattedText(rawHtml);
        if (!bodyPlain) {
            alert('Please enter email body content.');
            return;
        }

        // CRLF linebreaks for mailto compatibility
        const crlfBody = bodyPlain.replace(/\r\n/g, '\n').replace(/\n/g, '\r\n');
        const mailtoUrl = `mailto:${encodeURIComponent(recipient)}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(crlfBody)}`;
        window.location.href = mailtoUrl;

        // Optionally record in activity timeline
        const logActivity = document.getElementById('quickEmailLogActivity')?.checked;
        if (logActivity) {
            await recordQuickCommunication(
                'email',
                'Email Sent (Default App)',
                `Subject: ${subject} | ${bodyPlain.length > 200 ? bodyPlain.substring(0, 200) + '...' : bodyPlain}`,
                `Sent email to ${recipient}:\nSubject: ${subject}\n\n${bodyPlain.length > 200 ? bodyPlain.substring(0, 200) + '...' : bodyPlain}`
            );
        }

        const modalEl = document.getElementById('leadQuickEmailModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const inst = bootstrap.Modal.getInstance(modalEl);
            if (inst) inst.hide();
        }
    });

    function setText(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    }

    function setHref(id, url) {
        const el = document.getElementById(id);
        if (el) el.href = url;
    }

    // Rebind listeners when page container changes via Instant PJAX
    document.addEventListener('crm:page-loaded', () => {
        ensurePageScrollable();
        initLeadCheckboxes();
        initDuplicateDetection();
        if (typeof window.initReportsCharts === 'function' && document.getElementById('statusDonutChart')) {
            window.initReportsCharts();
        }
    });

    // 7. Initial Lucide icons mount
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
