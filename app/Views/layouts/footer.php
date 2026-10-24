            </div>
        </main>

        <!-- Footer -->
        <footer class="footer bg-white border-top py-3 px-4 text-center text-md-start">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted">
                <div>
                    &copy; <?= date('Y') ?> <strong class="text-navy">GD Goenka Public School, Purnea</strong> &bull; Admission CRM & Lead Management System
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge bg-light text-secondary border">Academic Session: <?= htmlspecialchars($currentSession['session_label'] ?? '2027–28') ?></span>
                    <span class="text-muted ms-2">v1.0 (Core PHP 8.2)</span>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS (Includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Initialize Lucide Icons -->
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

<!-- jQuery (for Summernote) -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script>
    if (typeof window.jQuery === 'undefined') {
        document.write('<script src="https://code.jquery.com/jquery-3.7.1.min.js"><\/script>');
    }
</script>

<!-- Summernote Lite JS for Rich Text Editor -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

<!-- Custom App Scripts -->
<script src="<?= \App\Helpers\View::asset('js/app.js') ?>"></script>
</body>
</html>
