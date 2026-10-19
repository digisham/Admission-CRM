<?php
use App\Helpers\Sanitizer;
use App\Helpers\View;
?>
<div class="row justify-content-center py-5">
    <div class="col-md-6 text-center">
        <h1 class="display-1 fw-bold text-navy">404</h1>
        <h4 class="fw-bold mb-3">Page or Action Not Found</h4>
        <p class="text-muted mb-4">The requested CRM route <code><?= Sanitizer::e($requestedRoute ?? '') ?></code> does not exist.</p>
        <a href="<?= View::url('dashboard') ?>" class="btn btn-navy shadow-sm">
            &larr; Return to Dashboard
        </a>
    </div>
</div>
