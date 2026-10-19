<?php
use App\Helpers\Csrf;
use App\Helpers\View;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | GD Goenka Public School, Purnea</title>
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
                <div class="brand-badge d-inline-block mb-2">GDG</div>
                <h5 class="fw-bold text-navy mb-1">Reset Password</h5>
                <div class="text-muted small">Enter your staff email to receive recovery instructions</div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    <form action="<?= View::url('forgot-password') ?>" method="POST">
                        <?= Csrf::field() ?>

                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold text-muted">Registered Email</label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="name@gdgoenkapurnea.com">
                        </div>

                        <button type="submit" class="btn btn-navy w-100 py-2 fw-semibold shadow-sm mb-3">
                            Send Reset Link
                        </button>

                        <div class="text-center">
                            <a href="<?= View::url('login') ?>" class="small text-decoration-none text-navy fw-semibold">
                                &larr; Back to Sign In
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
