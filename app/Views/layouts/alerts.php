<?php
use App\Helpers\Sanitizer;

$flashes = \App\Helpers\View::getFlashes();
if (!empty($flashes)):
    foreach ($flashes as $type => $message):
        $alertClass = match($type) {
            'success' => 'alert-success',
            'error', 'danger' => 'alert-danger',
            'warning' => 'alert-warning',
            default => 'alert-info'
        };
?>
<div class="alert <?= $alertClass ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
    <div class="d-flex align-items-center">
        <div><?= Sanitizer::e($message) ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php 
    endforeach;
endif; 
?>
