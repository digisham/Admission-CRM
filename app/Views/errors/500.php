<?php
use App\Helpers\View;
?>
<div class="row justify-content-center py-5">
    <div class="col-md-6 text-center">
        <h1 class="display-1 fw-bold text-danger">500</h1>
        <h4 class="fw-bold mb-3">Server Encountered an Error</h4>
        <p class="text-muted mb-4">An unexpected issue occurred while processing your request. Please contact the administrator.</p>
        <a href="<?= View::url('dashboard') ?>" class="btn btn-navy shadow-sm">
            &larr; Return to Dashboard
        </a>
    </div>
</div>
