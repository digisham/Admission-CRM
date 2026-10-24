<?php
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Helpers\Network;

$flashes = View::getFlashes();
$schoolIp = Network::getServerLanIp();
$schoolUrl = Network::getSchoolNetworkUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &bull; Admission CRM Portal</title>
    
    <!-- Modern Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --auth-primary: #5B4DF3;
            --auth-primary-hover: #4A3DE5;
            --auth-primary-subtle: rgba(91, 77, 243, 0.12);
            --auth-text-dark: #0F172A;
            --auth-text-muted: #64748B;
            --auth-text-label: #475569;
            --auth-border: #E2E8F0;
            --auth-border-focus: #5B4DF3;
            --auth-bg-light: #F8FAFC;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #F8FAFC;
            background-image: 
                radial-gradient(at 0% 0%, rgba(91, 77, 243, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.04) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
            margin: 0;
            color: var(--auth-text-dark);
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
        }

        .auth-card {
            background: #FFFFFF;
            border-radius: 20px;
            border: 1px solid #EBEFF5;
            box-shadow: 0 10px 30px -5px rgba(91, 77, 243, 0.07), 0 4px 14px -3px rgba(0, 0, 0, 0.03);
            padding: 40px 36px 36px;
            transition: all 0.25s ease;
        }

        .auth-logo-wrapper {
            margin-bottom: 22px;
            display: inline-block;
        }

        .auth-logo-img { 
            max-width: 100%;
            max-height: 75px;
            width: auto;
            height: auto;
            object-fit: contain; 
            display: block;
            margin: 0 auto;
        }

        .auth-title {
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--auth-text-dark);
            letter-spacing: -0.025em;
            margin-bottom: 6px;
        }

        .auth-subtitle {
            font-size: 0.88rem;
            color: var(--auth-text-muted);
            margin-bottom: 26px;
            line-height: 1.45;
        }

        .auth-label {
            display: block;
            font-size: 0.86rem;
            font-weight: 500;
            color: var(--auth-text-label);
            margin-bottom: 7px;
            text-align: left;
        }

        .auth-input {
            width: 100%;
            height: 48px;
            border-radius: 10px;
            border: 1.5px solid var(--auth-border);
            padding: 0 16px;
            font-size: 0.92rem;
            color: var(--auth-text-dark);
            background: #FFFFFF;
            transition: all 0.18s ease-in-out;
        }

        .auth-input:focus {
            border-color: var(--auth-border-focus);
            outline: none;
            box-shadow: 0 0 0 3.5px var(--auth-primary-subtle);
            background: #FFFFFF;
        }

        .auth-input::placeholder {
            color: #94A3B8;
            font-size: 0.9rem;
        }

        .auth-pw-toggle-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            padding: 4px;
            color: #94A3B8;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s ease;
        }

        .auth-pw-toggle-btn:hover {
            color: var(--auth-text-dark);
        }

        .auth-checkbox {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 1.5px solid #CBD5E1;
            cursor: pointer;
            accent-color: var(--auth-primary);
        }

        .auth-btn-primary {
            width: 100%;
            height: 48px;
            background: var(--auth-primary);
            color: #FFFFFF;
            font-weight: 600;
            font-size: 0.96rem;
            border: none;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(91, 77, 243, 0.32);
        }

        .auth-btn-primary:hover {
            background: var(--auth-primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(91, 77, 243, 0.4);
            color: #FFFFFF;
        }

        .auth-btn-secondary {
            width: 100%;
            height: 48px;
            background: #F1F5F9;
            color: #334155;
            font-weight: 600;
            font-size: 0.96rem;
            border: none;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.2s ease;
        }

        .auth-btn-secondary:hover {
            background: #E2E8F0;
            color: var(--auth-text-dark);
        }

        .auth-link {
            color: var(--auth-primary);
            font-weight: 500;
            font-size: 0.86rem;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .auth-link:hover {
            color: var(--auth-primary-hover);
            text-decoration: underline;
        }

        /* View Step Animation Transitions */
        .auth-view-pane {
            display: none;
            animation: fadeInView 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .auth-view-pane.active {
            display: block;
        }

        @keyframes fadeInView {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

<div class="auth-container">
    <!-- Main Form Card -->
    <div class="auth-card">
        
        <!-- Center Logo Emblem -->
        <div class="text-center auth-logo-wrapper">
            <img src="<?= View::asset('images/logo.png') ?>" 
                 alt="GD Goenka School Purnea" 
                 class="auth-logo-img"
                 onerror="this.onerror=null; this.src='<?= View::asset('images/logo.png') ?>';">
        </div>

        <!-- Global Flash Alerts -->
        <?php if (!empty($flashes)): ?>
            <?php foreach ($flashes as $type => $msg): ?>
                <div class="alert alert-<?= $type === 'success' ? 'success' : ($type === 'info' ? 'primary' : 'danger') ?> small py-2 px-3 mb-3 d-flex align-items-center gap-2 rounded-3 border-0 text-start shadow-xs">
                    <i data-lucide="<?= $type === 'success' ? 'check-circle-2' : ($type === 'info' ? 'info' : 'alert-circle') ?>" style="width: 16px; height: 16px;" class="flex-shrink-0"></i>
                    <div><?= Sanitizer::e($msg) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- VIEW 1: SIGN IN (IMAGE 1)                  -->
        <!-- ========================================== -->
        <div id="viewLogin" class="auth-view-pane active">
            <div class="text-center">
                <h3 class="auth-title">Admission CRM</h3>
                <p class="auth-subtitle">Sign in to access your secure admin dashboard.</p>
            </div>

            <form action="<?= View::url('login') ?>" method="POST" id="loginForm">
                <?= Csrf::field() ?>

                <!-- Email Address -->
                <div class="mb-3 text-start">
                    <label for="loginEmail" class="auth-label">Email Address</label>
                    <input type="email" 
                           class="auth-input" 
                           id="loginEmail" 
                           name="email" 
                           required 
                           autofocus 
                           placeholder="info@example.com" 
                           value="admin@gdgoenkapurnea.com">
                </div>

                <!-- Password with Eye Toggle -->
                <div class="mb-3 text-start">
                    <label for="loginPassword" class="auth-label">Password</label>
                    <div class="position-relative">
                        <input type="password" 
                               class="auth-input pe-5" 
                               id="loginPassword" 
                               name="password" 
                               required 
                               placeholder="********" 
                               value="Admin@12345">
                        <button type="button" class="auth-pw-toggle-btn" onclick="togglePasswordVisibility('loginPassword', this)" title="Show/Hide Password" aria-label="Toggle password visibility">
                            <i data-lucide="eye" style="width: 17px; height: 17px;"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password Row -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <input type="checkbox" class="auth-checkbox" id="rememberMe" name="remember" checked>
                        <label class="small text-muted mb-0" for="rememberMe" style="font-size: 0.85rem; cursor: pointer;">Remember Me</label>
                    </div>
                    <a href="#forgot" class="auth-link" id="linkToForgotStep1">Forgot Password?</a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="auth-btn-primary">
                    <span>Login</span>
                </button>

                <!-- Public Admission Enquiry Option -->
                <div class="mt-3.5 pt-3 border-top text-center">
                    <span class="text-muted small" style="font-size: 0.8rem;">Are you a parent seeking admission?</span>
                    <a href="<?= View::url('inquire') ?>" class="d-inline-flex align-items-center justify-content-center gap-1.5 w-100 mt-2 py-2 px-3 rounded-3 text-decoration-none fw-semibold border bg-light text-dark" style="font-size: 0.84rem; transition: all 0.15s ease;">
                        <i data-lucide="graduation-cap" style="width: 16px; height: 16px;" class="text-primary"></i>
                        <span>Online Admission Enquiry Form</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- ========================================== -->
        <!-- VIEW 2: FORGOT PASSWORD - STEP 1 (IMAGE 2) -->
        <!-- ========================================== -->
        <div id="viewForgotStep1" class="auth-view-pane">
            <div class="text-center">
                <h3 class="auth-title">Forget Password</h3>
                <p class="auth-subtitle">Enter your email to reset your password.</p>
            </div>

            <form id="forgotStep1Form" onsubmit="handleForgotStep1(event)">
                <div class="mb-4 text-start">
                    <label for="forgotEmail" class="auth-label">Email address</label>
                    <input type="email" 
                           class="auth-input" 
                           id="forgotEmail" 
                           name="email" 
                           required 
                           placeholder="info@example.com">
                    <div id="forgotEmailError" class="text-danger small mt-1.5 d-none" style="font-size: 0.8rem;"></div>
                </div>

                <!-- Action Buttons matching Image 2 -->
                <button type="submit" class="auth-btn-primary" id="btnForgotNext">
                    <span>Forgot Password</span>
                </button>

                <button type="button" class="auth-btn-secondary" onclick="switchView('viewLogin')">
                    Cancel
                </button>
            </form>
        </div>

        <!-- ========================================== -->
        <!-- VIEW 3: SET NEW PASSWORD - STEP 2 (IMAGE 3) -->
        <!-- ========================================== -->
        <div id="viewForgotStep2" class="auth-view-pane">
            <div class="text-center">
                <h3 class="auth-title">Set New Password</h3>
                <p class="auth-subtitle">Enter your new password to reset your password.</p>
            </div>

            <form action="<?= View::url('forgot-password') ?>" method="POST" id="resetPasswordForm" onsubmit="handleResetSubmit(event)">
                <?= Csrf::field() ?>
                <!-- Hidden carrying user email from step 1 -->
                <input type="hidden" name="email" id="resetPasswordEmailHidden">

                <!-- New Password Field -->
                <div class="mb-3 text-start">
                    <label for="newPassword" class="auth-label">New Password</label>
                    <div class="position-relative">
                        <input type="password" 
                               class="auth-input pe-5" 
                               id="newPassword" 
                               name="new_password" 
                               required 
                               minlength="6"
                               placeholder="********">
                        <button type="button" class="auth-pw-toggle-btn" onclick="togglePasswordVisibility('newPassword', this)" title="Show/Hide Password" aria-label="Toggle password visibility">
                            <i data-lucide="eye" style="width: 17px; height: 17px;"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password Field -->
                <div class="mb-3 text-start">
                    <label for="confirmPassword" class="auth-label">Confirm Password</label>
                    <div class="position-relative">
                        <input type="password" 
                               class="auth-input pe-5" 
                               id="confirmPassword" 
                               name="confirm_password" 
                               required 
                               minlength="6"
                               placeholder="********">
                        <button type="button" class="auth-pw-toggle-btn" onclick="togglePasswordVisibility('confirmPassword', this)" title="Show/Hide Password" aria-label="Toggle password visibility">
                            <i data-lucide="eye" style="width: 17px; height: 17px;"></i>
                        </button>
                    </div>
                    <div id="resetPwMismatchError" class="text-danger small mt-1.5 d-none" style="font-size: 0.8rem;">
                        Passwords do not match.
                    </div>
                </div>

                <!-- Terms and Conditions Checkbox matching Image 3 -->
                <div class="d-flex align-items-center gap-2 mb-4 text-start">
                    <input type="checkbox" class="auth-checkbox" id="agreeTerms" name="agree_terms" checked required>
                    <label class="small text-muted mb-0" for="agreeTerms" style="font-size: 0.84rem; cursor: pointer;">
                        I Agree &amp; <span style="color: var(--auth-primary);">Terms and conditions.</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="auth-btn-primary" id="btnResetSubmit">
                    <span>Submit</span>
                </button>

                <button type="button" class="auth-btn-secondary" onclick="switchView('viewForgotStep1')">
                    Back
                </button>
            </form>
        </div>

    </div>

    <!-- Quick Demo Accounts (Collapse/Accordion) -->
    <div class="text-center mt-3">
        <a class="text-muted small text-decoration-none" data-bs-toggle="collapse" href="#demoCredentialsCollapse" role="button" aria-expanded="false" style="font-size: 0.78rem;">
            <i data-lucide="key-round" style="width: 13px; height: 13px;" class="me-1"></i> Quick Staff Login Credentials &bull; Click to View
        </a>
        <div class="collapse mt-2" id="demoCredentialsCollapse">
            <div class="card card-body bg-white border-0 shadow-sm rounded-3 py-2.5 px-3 text-start small" style="font-size: 0.78rem;">
                <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                    <div><strong>Admin:</strong> <code>admin@gdgoenkapurnea.com</code></div>
                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" onclick="fillCredentials('admin@gdgoenkapurnea.com', 'Admin@12345')">Use</button>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div><strong>Counselor:</strong> <code>priya@gdgoenkapurnea.com</code></div>
                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" onclick="fillCredentials('priya@gdgoenkapurnea.com', 'Priya@12345')">Use</button>
                </div>
            </div>
        </div>
    </div>

    <!-- School Network Access URL Box (Only when connected to school LAN/Wi-Fi) -->
    <?php if ($schoolIp && $schoolUrl): ?>
    <div class="card shadow-sm border-0 rounded-3 mt-3 p-3 bg-white text-center">
        <div class="d-flex align-items-center justify-content-center gap-1.5 text-success fw-bold small mb-1" style="font-size: 0.8rem;">
            <span class="spinner-grow spinner-grow-sm text-success" style="width: 7px; height: 7px;" role="status"></span>
            <i data-lucide="wifi" style="width: 14px; height: 14px;"></i>
            <span>School Network Online (LAN / Wi-Fi)</span>
        </div>
        <div class="text-muted" style="font-size: 0.75rem;">
            Open on other staff PCs, laptops &amp; mobile devices:
        </div>
        <div class="input-group input-group-sm mt-2">
            <input type="text" class="form-control text-center font-monospace fw-bold text-navy" value="<?= $schoolUrl ?>" readonly id="schoolIpLoginInput" style="font-size: 0.78rem; background: #F8FAFC;">
            <button class="btn btn-outline-primary px-3 fw-semibold" type="button" onclick="copySchoolLink()" style="font-size: 0.78rem;">
                Copy Link
            </button>
        </div>
    </div>
    <?php endif; ?>

    <div class="text-center mt-3 text-muted small" style="font-size: 0.76rem;">
        &copy; <?= date('Y') ?> GD Goenka Public School, Purnea &bull; Admission CRM
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // View Switcher between Login, Forgot Step 1, and Forgot Step 2
    function switchView(viewId) {
        document.querySelectorAll('.auth-view-pane').forEach(el => el.classList.remove('active'));
        const target = document.getElementById(viewId);
        if (target) {
            target.classList.add('active');
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    }

    // Toggle Password Visibility (Eye Icon)
    function togglePasswordVisibility(inputId, btnEl) {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        // Update icon
        const icon = btnEl.querySelector('svg, i');
        if (icon) {
            btnEl.innerHTML = isPassword 
                ? '<i data-lucide="eye-off" style="width: 17px; height: 17px;"></i>' 
                : '<i data-lucide="eye" style="width: 17px; height: 17px;"></i>';
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    }

    // Handle Forgot Password Step 1
    function handleForgotStep1(e) {
        e.preventDefault();
        const emailInput = document.getElementById('forgotEmail');
        const errEl = document.getElementById('forgotEmailError');
        const email = emailInput.value.trim();

        if (!email) {
            errEl.textContent = 'Please enter your registered email address.';
            errEl.classList.remove('d-none');
            return;
        }

        errEl.classList.add('d-none');
        
        // Pass email to step 2 hidden input
        document.getElementById('resetPasswordEmailHidden').value = email;
        
        // Advance to Step 2 (Image 3)
        switchView('viewForgotStep2');
        setTimeout(() => {
            const newPw = document.getElementById('newPassword');
            if (newPw) newPw.focus();
        }, 100);
    }

    // Handle Reset Password Submit (Step 2)
    function handleResetSubmit(e) {
        const newPw = document.getElementById('newPassword').value;
        const confirmPw = document.getElementById('confirmPassword').value;
        const mismatchErr = document.getElementById('resetPwMismatchError');

        if (newPw !== confirmPw) {
            e.preventDefault();
            mismatchErr.textContent = 'Passwords do not match. Please re-enter.';
            mismatchErr.classList.remove('d-none');
            return false;
        }

        if (newPw.length < 6) {
            e.preventDefault();
            mismatchErr.textContent = 'Password must be at least 6 characters.';
            mismatchErr.classList.remove('d-none');
            return false;
        }

        mismatchErr.classList.add('d-none');
        return true;
    }

    // Pre-fill credentials helper
    function fillCredentials(email, password) {
        switchView('viewLogin');
        const emailEl = document.getElementById('loginEmail');
        const pwEl = document.getElementById('loginPassword');
        if (emailEl) emailEl.value = email;
        if (pwEl) pwEl.value = password;
    }

    // Copy School Network URL Helper
    function copySchoolLink() {
        const input = document.getElementById('schoolIpLoginInput');
        if (input) {
            navigator.clipboard.writeText(input.value);
            alert('School Network URL copied to clipboard:\n' + input.value);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Handle #forgot hash in URL
        if (window.location.hash === '#forgot') {
            const loginEmailVal = document.getElementById('loginEmail')?.value;
            if (loginEmailVal) {
                document.getElementById('forgotEmail').value = loginEmailVal;
            }
            switchView('viewForgotStep1');
        }

        document.getElementById('linkToForgotStep1')?.addEventListener('click', function(e) {
            e.preventDefault();
            const loginEmailVal = document.getElementById('loginEmail')?.value;
            if (loginEmailVal) {
                document.getElementById('forgotEmail').value = loginEmailVal;
            }
            switchView('viewForgotStep1');
        });
    });
</script>
</body>
</html>
