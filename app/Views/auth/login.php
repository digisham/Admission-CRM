<?php
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$flashes = View::getFlashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | GD Goenka Public School, Purnea — Admission CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= View::asset('css/style.css') ?>">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">
            <div class="text-center mb-4">
                <div class="brand-badge d-inline-block mb-2" style="font-size: 1.25rem; padding: 10px 18px;">GDG</div>
                <h4 class="fw-bold text-navy mb-1">GD GOENKA PUBLIC SCHOOL</h4>
                <div class="text-gold fw-semibold small text-uppercase" style="letter-spacing: 1px;">Purnea, Bihar &bull; Admission CRM</div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-navy mb-3">Staff Sign In</h5>

                    <?php if (!empty($flashes)): ?>
                        <?php foreach ($flashes as $type => $msg): ?>
                            <div class="alert alert-<?= $type === 'success' ? 'success' : 'danger' ?> small py-2 mb-3">
                                <?= Sanitizer::e($msg) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <form action="<?= View::url('login') ?>" method="POST">
                        <?= Csrf::field() ?>

                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold text-muted">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required autofocus placeholder="admin@gdgoenkapurnea.com" value="admin@gdgoenkapurnea.com">
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="password" class="form-label small fw-semibold text-muted">Password</label>
                                <a href="<?= View::url('forgot-password') ?>" class="small text-decoration-none text-muted">Forgot?</a>
                            </div>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••" value="Admin@12345">
                        </div>

                        <button type="submit" class="btn btn-navy w-100 py-2 fw-semibold shadow-sm">
                            Sign In to Portal
                        </button>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center text-muted small">
                        <div class="fw-semibold text-navy">Default Demo Credentials:</div>
                        <div>Admin: <code>admin@gdgoenkapurnea.com</code> / <code>Admin@12345</code></div>
                        <div>Counselor: <code>priya@gdgoenkapurnea.com</code> / <code>Priya@12345</code></div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4 text-muted small">
                &copy; <?= date('Y') ?> GD Goenka Public School, Purnea
            </div>
        </div>
    </div>
</div>
</body>
</html>
