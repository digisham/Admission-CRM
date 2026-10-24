<?php
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;

$flashes = View::getFlashes();
$captchaQ = $captchaQuestion ?? '7 + 5';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Enquiry &bull; GD Goenka Public School, Purnea</title>
    
    <!-- Meta tags for social share & ads -->
    <meta name="description" content="Apply for Admission Session <?= Sanitizer::e($activeSession['session_label']) ?> at GD Goenka Public School, Purnea. CBSE World-Class Smart Education, Sports & Activities.">
    <meta name="keywords" content="GD Goenka Purnea, Admission Enquiry, School Admission Purnea, CBSE School Admission Bihar">
    
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --brand-navy: #0F172A;
            --brand-blue: #1E3A8A;
            --brand-primary: #4F46E5;
            --brand-primary-hover: #4338CA;
            --brand-gold: #D97706;
            --bg-page: #F8FAFC;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, sans-serif;
            background-color: var(--bg-page);
            background-image: 
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(217, 119, 6, 0.04) 0px, transparent 50%);
            min-height: 100vh;
            color: #1E293B;
            padding:0 ; 
        }

        .enquiry-layout-wrapper { 
            margin: 0 auto;
             padding: 0 15px;
           
        }

        /* Top Brand Header Card */
        .school-header-card {
            background: #1E3A8A; 
            padding: 10px 20px; 
        }

        .school-brand-logo {
            width: 350px;  
            border-radius: 10px; 
            margin: 0 auto;
        }

        .school-name-title {
            font-size: 1.28rem;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.3px;
            line-height: 1.25;
            margin-bottom: 2px;
        }

        .school-tagline {
            font-size: 0.8rem;
            color: #64748B;
            font-weight: 500;
        }

        /* Form Card */
        .enquiry-form-card {
            background: #FFFFFF; 
            overflow: hidden;
        }

        .section-header-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .section-badge-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .section-badge-blue {
            background-color: #EEF2FF;
            color: #4F46E5;
        }

        .section-badge-indigo {
            background-color: #EDE9FE;
            color: #6D28D9;
        }

        .section-badge-emerald {
            background-color: #ECFDF5;
            color: #059669;
        }

        .section-badge-amber {
            background-color: #FEF3C7;
            color: #D97706;
        }

        .form-field-label {
            font-size: 0.77rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 4px;
            display: block;
        }

        .input-icon-wrap {
            position: relative;
        }

        .input-icon-wrap .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 15px;
            height: 15px;
            color: #94A3B8;
            pointer-events: none;
            transition: color 0.15s ease;
        }

        .compact-input {
            height: 40px;
            font-size: 0.86rem;
            border-radius: 8px;
            border: 1px solid #CBD5E1;
            padding-left: 36px !important;
            transition: all 0.18s ease;
        }

        .compact-input:focus {
            border-color: #4F46E5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        .compact-input:focus + .input-icon,
        .input-icon-wrap:focus-within .input-icon {
            color: #4F46E5;
        }

        /* Anti-Spam Captcha Box */
        .captcha-security-box { 
            margin: 0;
        }

        .captcha-math-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #0F172A;
            color: #F8FAFC;
            font-family: 'Inter', monospace;
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: 2px;
            padding: 6px 14px;
            border-radius: 8px;
            min-width: 110px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.3);
            user-select: none;
        }

        .captcha-refresh-btn {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 0.8rem;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 600;
        }

        .captcha-refresh-btn:hover {
            background: #F1F5F9;
            color: #0F172A;
            border-color: #94A3B8;
        }

        /* Right Poster Container (Desktop) */
        .admission-poster-card {
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid #E2E8F0;
            box-shadow: 0 8px 30px -4px rgba(15, 23, 42, 0.12);
            background: #000000;
            transition: transform 0.25s ease;
        }

        .admission-poster-img {
            width: 100%;
            height: auto;
            display: block;
            object-fit: cover;
            border-radius: 18px;
        }

        /* Mobile specific adjustments */
        @media (max-width: 991.98px) {
            .poster-desktop-wrapper {
                margin-top: 24px;
            }
            .mobile-top-banner {
                margin-bottom: 16px;
            }
            .compact-input {
                font-size: 16px !important; /* Prevents auto-zoom on iOS safari */
                height: 44px;
            }
        }

        /* Success View */
            .success-hero-card {
                background: #FFFFFF;
                border-radius: 0;
                border: 1px solid #E2E8F0;
                /* box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.1); */
                padding: 40px 24px;
                text-align: center;
                max-width: 1200px;
                margin: 0 auto;
            }

        .success-icon-badge {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: #ECFDF5;
            color: #10B981;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            border: 3px solid #A7F3D0;
        }

        .reference-code-box {
            background: #F8FAFC;
            border: 1.5px dashed #CBD5E1;
            border-radius: 12px;
            padding: 14px 24px;
            display: inline-block;
            margin: 16px 0 24px;
        }

        .school-contact-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 16px;
            padding: 14px 18px;
            background: #FFFFFF;
            border-radius: 12px;
            border: 1px solid #E2E8F0; 
            font-size: 0.82rem;
            color: #475569;
        }

        .school-contact-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            color: #1E293B;
            font-weight: 600;
        }

        .school-contact-item:hover {
            color: var(--brand-primary);
        }

        /* Hidden Honeypot */
        .hp-field {
            display: none !important;
            visibility: hidden !important;
            position: absolute !important;
            left: -9999px !important;
        }
        .backbtnn {
    position: absolute;
    left: 22%;
    border: 2px solid #ffffff30;
    border-radius: 21px;
    padding: 3px 10px;
    display: inline-block;
    font-size: 13px;
    text-decoration: none;
    color: #fff;
}

        @media screen and (max-width: 768px) { 
           .enquiry-layout-wrapper {
	padding: 0;
	overflow: hidden;
}
            .school-brand-logo {
                width: 260px;
                border-radius: 10px;
                margin: 0 auto;
                margin-left: 33px;
            }

           .backbtnn {
	left: 5%;
	padding: 3px 10px;
	display: inline-block;
	font-size: 13px;
}
            .backbtnn b {
                display: none;
}
        }
        textarea.form-control.compact-input {
	font-size: 13px !important;
	height: 100px !important;
}

    </style>
</head>
<body>

    
    <!-- Top Header Card -->
    <div class="school-header-card">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-3 text-center text-md-start">
           <a href="https://www.gdgoenkapurnea.com" class="backbtnn"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left preview-icon"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg> <b>Back</b> </a>
        <div class="d-flex align-items-center gap-3">
               <a href="https://www.gdgoenkapurnea.com"><img src="<?= View::asset('images/white_logo.png') ?>" 
                     alt="GD Goenka School Purnea" 
                     class="school-brand-logo"
                     onerror="this.onerror=null; this.src='<?= View::asset('images/white_logo.png') ?>';"></a>
            </div>
            <!-- <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill bg-light text-dark border px-3 py-1.5 fw-semibold" style="font-size: 0.78rem;">
                    <i data-lucide="calendar" style="width: 13px; height: 13px;" class="text-primary me-1"></i>
                    Session <?= Sanitizer::e($activeSession['session_label']) ?>
                </span>
                <a href="<?= View::url('login') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 0.78rem;">
                    <i data-lucide="lock" style="width: 13px; height: 13px;"></i>
                    <span>Staff Login</span>
                </a>
            </div> -->
        </div>
    </div>

<div class="enquiry-layout-wrapper">

    <!-- Flash Alerts -->
    <?php if (!empty($flashes)): ?>
        <?php foreach ($flashes as $type => $msg): ?>
            <div class="alert alert-<?= $type === 'success' ? 'success' : ($type === 'warning' ? 'warning' : 'danger') ?> small py-2.5 px-3 mb-3 d-flex align-items-center gap-2 rounded-3 border-0 shadow-sm">
                <i data-lucide="<?= $type === 'success' ? 'check-circle' : ($type === 'warning' ? 'alert-triangle' : 'alert-circle') ?>" style="width: 17px; height: 17px;" class="flex-shrink-0"></i>
                <div class="fw-medium"><?= Sanitizer::e($msg) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($successData)): ?>
        <!-- ================================================================= -->
        <!-- SUBMISSION SUCCESS CONFIRMATION VIEW                             -->
        <!-- ================================================================= -->
        <div class="success-hero-card">
            <div class="success-icon-badge">
                <i data-lucide="check" style="width: 42px; height: 42px;"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1" style="font-size: 1.5rem;">Admission Enquiry Registered!</h3>
            <p class="text-muted small mb-2" style="font-size: 0.9rem;">
                Thank you, <strong><?= Sanitizer::e($successData['parent_name']) ?></strong>. Your enquiry for <strong><?= Sanitizer::e($successData['student_name']) ?></strong> has been logged in our Admissions CRM.
            </p>

            <div class="reference-code-box">
                <div class="text-muted small text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.8px;">Your Enquiry Reference Number</div>
                <div class="fs-4 fw-extrabold text-navy font-monospace my-0.5" style="letter-spacing: 0.6px;"><?= Sanitizer::e($successData['lead_ref']) ?></div>
                <div class="text-muted small" style="font-size: 0.75rem;">Grade Applied: <strong><?= Sanitizer::e($successData['class_applied']) ?></strong> &bull; Registered Mobile: <strong>+91 <?= Sanitizer::e($successData['mobile']) ?></strong></div>
            </div>

            <!-- What Happens Next Strip -->
            <div class="row g-2.5 text-start justify-content-center mb-4">
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 h-100 border">
                        <div class="d-flex align-items-center gap-2 text-primary fw-bold small mb-1">
                            <i data-lucide="user-check" style="width: 16px; height: 16px;"></i>
                            <span>Admission Counselor Allocated</span>
                        </div>
                        <div class="text-muted small" style="font-size: 0.78rem;">
                            Your enquiry has been routed to our admission counselors for Grade <?= Sanitizer::e($successData['class_applied']) ?>.
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 h-100 border">
                        <div class="d-flex align-items-center gap-2 text-success fw-bold small mb-1">
                            <i data-lucide="phone-call" style="width: 16px; height: 16px;"></i>
                            <span>Priority Call &amp; WhatsApp</span>
                        </div>
                        <div class="text-muted small" style="font-size: 0.78rem;">
                            Our admissions desk will connect with you on <strong>+91 <?= Sanitizer::e($successData['mobile']) ?></strong> within 24 hours.
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 h-100 border">
                        <div class="d-flex align-items-center gap-2 text-warning fw-bold small mb-1">
                            <i data-lucide="map-pin" style="width: 16px; height: 16px;"></i>
                            <span>Guided Campus Tour</span>
                        </div>
                        <div class="text-muted small" style="font-size: 0.78rem;">
                            We invite you to experience our smart classrooms, STEAM labs, and sports complex in person.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Direct Outreach Action Buttons -->
            <div class="d-flex flex-wrap align-items-center justify-content-center gap-2">
                <a href="https://wa.me/9118008911934?text=<?= urlencode("Hello GD Goenka Public School Purnea! I have submitted an admission enquiry for my child " . $successData['student_name'] . " (Ref: " . $successData['lead_ref'] . "). I would like to get more information.") ?>" 
                   target="_blank" 
                   class="btn text-white px-4 py-2 rounded-pill d-inline-flex align-items-center gap-2 fw-semibold shadow-sm"
                   style="background: #25D366;">
                    <i data-lucide="message-circle" style="width: 17px; height: 17px;"></i>
                    <span>Connect on WhatsApp</span>
                </a>
                <a href="tel:18008911934" class="btn btn-navy text-white px-4 py-2 rounded-pill d-inline-flex align-items-center gap-2 fw-semibold shadow-sm" style="background: #0F172A;">
                    <i data-lucide="phone" style="width: 16px; height: 16px;"></i>
                    <span>Call Toll-Free: 1800 891 1934</span>
                </a>
                <a href="<?= View::url('inquire') ?>" class="btn btn-outline-secondary px-3.5 py-2 rounded-pill d-inline-flex align-items-center gap-1.5" style="font-size: 0.85rem;">
                    <i data-lucide="plus" style="width: 15px; height: 15px;"></i>
                    <span>Submit Another Enquiry</span>
                </a>
            </div>
        </div>

    <?php else: ?>
        <!-- ================================================================= -->
        <!-- TWO-COLUMN LAYOUT: DESKTOP LEFT FORM, RIGHT IMAGE                 -->
        <!-- MOBILE: FULLY RESPONSIVE STACK                                    -->
        <!-- ================================================================= -->
        <div class="row justify-content-center">
            
            <!-- LEFT COLUMN: ADMISSION ENQUIRY FORM (COL-LG-7) -->
            <div class="col-lg-7 col-xl-7">
                <div class="enquiry-form-card">
                    
                    <!-- Card Header -->
                    <div class="py-3 px-3 px-md-4 border-bottom bg-light  d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="section-badge-icon section-badge-blue">
                                <i data-lucide="file-text" style="width: 15px; height: 15px;"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.08rem; letter-spacing: -0.2px;">Online Admission Enquiry</h5>
                                <div class="text-muted small" style="font-size: 0.74rem;">Fill candidate details for Academic Session <?= Sanitizer::e($activeSession['session_label']) ?></div>
                            </div>
                        </div>
                        <span class="badge bg-white text-dark border px-2.5 py-1 fw-semibold d-none d-lg-block" style="font-size: 0.75rem;">
                            Admissions Open
                        </span>
                    </div>

                    <form action="<?= View::url('inquire/store') ?>" method="POST" id="publicEnquiryForm" onsubmit="return validateEnquiryForm(event)">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="session_id" value="<?= (int)$activeSession['id'] ?>">

                        <!-- Honeypot anti-spam trap fields (Hidden from humans, filled by spam bots) -->
                        <div class="hp-field" aria-hidden="true">
                            <label for="hp_website_fax">Leave this field blank</label>
                            <input type="text" name="hp_website_fax" id="hp_website_fax" tabindex="-1" autocomplete="off">
                            <input type="text" name="company_tax_id" id="company_tax_id" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="p-4   pt-2">
                            
                            <!-- SECTION 1: Student & Academic Details -->
                            <div class="mb-3">
                                <div class="section-header-wrap">
                                   
                                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.88rem; letter-spacing: -0.1px;">1. Student &amp; Academic Details</h6>
                                </div>

                                <div class="row g-2">
                                    <!-- Academic Session (Readonly badge) -->
                                    <div class="col-md-2">
                                        <label class="form-field-label">  Session <span class="text-danger">*</span></label>
                                        <div class="input-icon-wrap">
                                            <i data-lucide="calendar" class="input-icon"></i>
                                            <input type="text" class="form-control form-control-sm compact-input bg-light fw-medium" value="<?= Sanitizer::e($activeSession['session_label']) ?>" readonly style="font-size:12px">
                                        </div>
                                    </div>

                                    <!-- Student Full Name -->
                                    <div class="col-md-7">
                                        <label class="form-field-label">Student Full Name <span class="text-danger">*</span></label>
                                        <div class="input-icon-wrap">
                                            <i data-lucide="user" class="input-icon"></i>
                                            <input type="text" name="student_name" id="student_name" class="form-control form-control-sm compact-input" required placeholder="e.g. Aarav Sharma" autocomplete="name" minlength="2" maxlength="100">
                                        </div>
                                    </div>

                                    <!-- Class Applied -->
                                    <div class="col-md-3">
                                        <label class="form-field-label">Class / Grade Applied <span class="text-danger">*</span></label>
                                        <div class="input-icon-wrap">
                                            <i data-lucide="book-open" class="input-icon"></i>
                                            <select name="class_applied" id="class_applied" class="form-select form-select-sm compact-input" required>
                                                <option value="">-- Choose Class --</option>
                                                <?php foreach ($classes as $cl): ?>
                                                    <option value="<?= Sanitizer::e($cl) ?>" <?= $cl === 'Grade 1' ? 'selected' : '' ?>><?= Sanitizer::e($cl) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION 2: Parent & Contact Details -->
                            <div class="mb-3  ">
                                <div class="section-header-wrap">
                                  
                                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.88rem; letter-spacing: -0.1px;">2. Parent &amp; Contact Details</h6>
                                </div>

                                <div class="row g-2">
                                    <!-- Parent Name -->
                                    <div class="col-md-6">
                                        <label class="form-field-label">Parent / Guardian Name <span class="text-danger">*</span></label>
                                        <div class="input-icon-wrap">
                                            <i data-lucide="user-check" class="input-icon"></i>
                                            <input type="text" name="parent_name" id="parent_name" class="form-control form-control-sm compact-input" required placeholder="e.g. Mr. Rajesh Sharma" autocomplete="name" minlength="2" maxlength="100">
                                        </div>
                                    </div>

                                    <!-- Primary Mobile with Strict 10-digit validation -->
                                    <div class="col-md-6">
                                        <label class="form-field-label d-flex justify-content-between align-items-center">
                                            <span>Primary Mobile Number <span class="text-danger">*</span></span>
                                            <span class="text-muted" id="mobileCounter" style="font-size: 0.68rem;">10 digits</span>
                                        </label>
                                        <div class="input-icon-wrap">
                                            <i data-lucide="phone" class="input-icon"></i>
                                            <input type="tel" 
                                                   name="mobile" 
                                                   id="mobileInput" 
                                                   class="form-control form-control-sm compact-input" 
                                                   required 
                                                   placeholder="10-digit mobile number" 
                                                   maxlength="10" 
                                                   pattern="[6-9][0-9]{9}" 
                                                   inputmode="numeric"
                                                   autocomplete="tel"
                                                   oninput="handleMobileInput(this)">
                                        </div>
                                        <!-- <div id="mobileValidationMsg" class="form-text" style="font-size: 0.7rem; color: #64748B;">Enter 10-digit mobile starting with 6, 7, 8 or 9.</div> -->
                                    </div>

                                    <!-- Alternate Mobile -->
                                    <div class="col-md-6">
                                        <label class="form-field-label">Alternate Mobile <span class="text-muted fw-normal">(Optional)</span></label>
                                        <div class="input-icon-wrap">
                                            <i data-lucide="phone-forwarded" class="input-icon"></i>
                                            <input type="tel" name="alternate_mobile" id="altMobileInput" class="form-control form-control-sm compact-input" placeholder="Secondary contact number" maxlength="10" inputmode="numeric">
                                        </div>
                                    </div>

                                    <!-- Email Address with Anti-Spam Check -->
                                    <div class="col-md-6">
                                        <label class="form-field-label">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                                        <div class="input-icon-wrap">
                                            <i data-lucide="mail" class="input-icon"></i>
                                            <input type="email" name="email" id="emailInput" class="form-control form-control-sm compact-input" placeholder="parent@example.com" autocomplete="email">
                                        </div>
                                     </div>

                                    <!-- City / Locality -->
                                    <div class="col-md-6">
                                        <label class="form-field-label">City / Locality</label>
                                        <div class="input-icon-wrap">
                                            <i data-lucide="map-pin" class="input-icon"></i>
                                            <input type="text" name="city" class="form-control form-control-sm compact-input"  placeholder="Purnea, Katihar, Araria...">
                                        </div>
                                    </div>

                                    <!-- Residential Address -->
                                    <div class="col-md-6">
                                        <label class="form-field-label">Residential Address <span class="text-muted fw-normal">(Optional)</span></label>
                                        <div class="input-icon-wrap">
                                            <i data-lucide="home" class="input-icon"></i>
                                            <input type="text" name="address" class="form-control form-control-sm compact-input" placeholder="Street, area, town...">
                                        </div>
                                    </div>

                                    <!-- Initial Remarks / Questions -->
                                    <div class="col-12 mt-1">
                                        <label class="form-field-label">Remarks / Queries / Questions <span class="text-muted fw-normal">(Optional)</span></label>
                                        <textarea name="remarks" class="form-control compact-input" rows="2" placeholder="Mention queries regarding bus routes, smart classrooms, day-boarding, sports amenities, or fee structure..." style="font-size: 0.82rem; height: auto; border-radius: 8px; padding-left: 12px !important;"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- ANTI-SPAM PROTECTION: MATH CAPTCHA & SECURITY BADGE -->
                            <div class="captcha-security-box mb-3">
                                <div class="row">
                                    
                                <div class="col-md-12">
                                        <div class="d-flex align-items-center gap-1.5 text-dark fw-bold small mb-1">
                                            <i data-lucide="shield-check" style="width: 16px; height: 16px;" class="text-success"></i>
                                            <span>Human Security Verification</span>
                                            <!-- <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 0.68rem;">Anti-Spam</span> -->
                                        </div>
                                        <!-- <div class="text-muted small" style="font-size: 0.73rem;">Solve the simple math puzzle to verify you are a genuine human:</div> -->
                                    </div>

                                     <div class="col-sm-7">
                                                                                    <label class="form-field-label mb-0" for="captcha_solution">Enter the Answer <span class="text-danger">*</span></label>

                                            <input type="number" 
                                                   name="captcha_solution" 
                                                   id="captcha_solution" 
                                                   class="form-control form-control-sm" 
                                                   required 
                                                   placeholder="e.g. 12" 
                                                   style="height: 38px; font-weight: 600; font-size: 0.9rem; border-radius: 8px;"
                                                   autocomplete="off">
                                        </div>
                                        <div class="col-sm-5">
                                          <label class="form-field-label mb-0" for=" ">  &nbsp;</label>

                                             <div class="d-flex align-items-center gap-2">
                                        <span class="captcha-math-pill" id="captchaDisplay" title="Solve this math problem"><?= $captchaQ ?> = ?</span>
                                        <button type="button" class="captcha-refresh-btn" onclick="refreshMathCaptcha()" title="Get new math problem">
                                            
                                            <span><i data-lucide="rotate-cw" style="width: 13px; height: 13px;"></i>  </span>
                                        </button>
                                    </div>
                                        </div>
                                       

                                   
                                </div> 
                            </div>

                            <!-- AUTOMATIC ENQUIRY MANAGEMENT STRIP -->
                            <!-- <div class="p-2.5 bg-light rounded-3 border d-flex align-items-center gap-2.5">
                                <span class="section-badge-icon section-badge-emerald flex-shrink-0" style="width: 28px; height: 28px;">
                                    <i data-lucide="zap" style="width: 15px; height: 15px;"></i>
                                </span>
                                <div class="small" style="font-size: 0.76rem; line-height: 1.4; color: #475569;">
                                    <strong>Instant Counselor Allocation:</strong> Enquiry is routed directly to the Admissions Desk for Session <?= Sanitizer::e($activeSession['session_label']) ?>.
                                </div>
                            </div> -->

                            
                        <!-- Footer Action Bar -->
                        <div class="p-1 mb-4 bg-light d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <!-- <span class="text-muted small" style="font-size: 0.74rem;">
                                <i data-lucide="lock" style="width: 12px; height: 12px;" class="me-1"></i>
                                Protected by Anti-Spam &amp; SSL Security.
                            </span> -->
                            <button type="submit" id="btnSubmitEnquiry" class="btn btn-submit px-4 py-2 rounded-pill d-inline-flex align-items-center gap-2 shadow-sm fw-semibold" style="background: #1e3a8a; border-color: #1e3a8a; font-size: 1rem;color:#fff;">
                                <i data-lucide="send" style="width: 16px; height: 16px;"></i>
                                <span>Submit Admission Enquiry</span>
                            </button>
                        </div>

                        </div>

                    </form>
                </div>
            </div>

      

        </div>
    <?php endif; ?>

    <!-- Bottom School Help Strip -->
    <div class="school-contact-strip">
        <span class="text-muted fw-medium">Have questions or need assistance?</span>
        <a href="tel:18008911934" class="school-contact-item">
            <i data-lucide="phone-call" style="width: 14px; height: 14px;" class="text-success"></i>
            <span>Toll-Free: 1800 891 1934</span>
        </a>
        <span class="text-muted">&bull;</span>
        <a href="mailto:info@gdgoenkapurnea.com" class="school-contact-item">
            <i data-lucide="mail" style="width: 14px; height: 14px;" class="text-primary"></i>
            <span>info@gdgoenkapurnea.com</span>
        </a>
        <span class="text-muted">&bull;</span>
        <span class="d-inline-flex align-items-center gap-1.5 text-secondary">
            <i data-lucide="map-pin" style="width: 14px; height: 14px;"></i>
            <span>
Matia Chowk, Ranipatra, NH - 131 A, Dist-Purnea, Bihar- 854337</span>
        </span>
    </div>

</div>

<!-- Realtime Client-side Anti-Spam Validation & Captcha Refresh Script -->
<script>
    // Initialize Lucide Icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Refresh Math Captcha without reloading page
    async function refreshMathCaptcha() {
        const display = document.getElementById('captchaDisplay');
        if (display) display.textContent = '...';
        try {
            const res = await fetch('<?= View::url('inquire/captcha-refresh') ?>', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.status === 'success' && data.question) {
                if (display) display.textContent = data.question + ' = ?';
                const input = document.getElementById('captcha_solution');
                if (input) {
                    input.value = '';
                    input.focus();
                }
            }
        } catch (e) {
            console.error('Captcha refresh error:', e);
            if (display) display.textContent = '8 + 4 = ?';
        }
    }

    // Realtime 10-digit Mobile Validator
    function handleMobileInput(input) {
        // Strip any non-digits
        let val = input.value.replace(/\D/g, '');
        if (val.length > 10) val = val.substring(0, 10);
        input.value = val;

        const counter = document.getElementById('mobileCounter');
        const msg = document.getElementById('mobileValidationMsg');

        if (counter) counter.textContent = `${val.length}/10 digits`;

        if (val.length === 0) {
            if (msg) {
                msg.textContent = 'Enter 10-digit mobile starting with 6, 7, 8 or 9.';
                msg.className = 'form-text text-muted';
            }
            return;
        }

        // Validate first digit
        const firstDigit = val[0];
        if (!['6', '7', '8', '9'].includes(firstDigit)) {
            if (msg) {
                msg.textContent = '⚠️ Indian mobile number must start with 6, 7, 8, or 9.';
                msg.className = 'form-text text-danger fw-semibold';
            }
            return;
        }

        // Validate length
        if (val.length < 10) {
            if (msg) {
                msg.textContent = `Need ${10 - val.length} more digit(s)...`;
                msg.className = 'form-text text-muted';
            }
            return;
        }

        // Check for spam patterns (all identical digits)
        if (/^(\d)\1{9}$/.test(val)) {
            if (msg) {
                msg.textContent = '❌ Repeated identical digits (e.g. 9999999999) are not accepted.';
                msg.className = 'form-text text-danger fw-semibold';
            }
            return;
        }

        // Check dummy sequence
        const dummyList = ['1234567890', '0123456789', '9876543210', '0987654321', '1212121212', '9898989898'];
        if (dummyList.includes(val)) {
            if (msg) {
                msg.textContent = '❌ Dummy sequence numbers are not accepted.';
                msg.className = 'form-text text-danger fw-semibold';
            }
            return;
        }

        if (msg) {
            msg.textContent = '✓ Valid 10-digit mobile number';
            msg.className = 'form-text text-success fw-semibold';
        }
    }

    // Comprehensive Form Validation before Submit
    function validateEnquiryForm(e) {
        // Honeypot check
        const hp1 = document.getElementById('hp_website_fax')?.value;
        const hp2 = document.getElementById('company_tax_id')?.value;
        if (hp1 || hp2) {
            e.preventDefault();
            alert('Spam submission detected.');
            return false;
        }

        // Mobile check
        const mobile = document.getElementById('mobileInput')?.value.trim() || '';
        if (!/^[6-9]\d{9}$/.test(mobile)) {
            e.preventDefault();
            alert('Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.');
            document.getElementById('mobileInput')?.focus();
            return false;
        }

        if (/^(\d)\1{9}$/.test(mobile)) {
            e.preventDefault();
            alert('Please provide a genuine mobile number (repeated identical digits are blocked).');
            document.getElementById('mobileInput')?.focus();
            return false;
        }

        // Email check (if provided)
        const email = document.getElementById('emailInput')?.value.trim() || '';
        if (email) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address or leave it blank.');
                document.getElementById('emailInput')?.focus();
                return false;
            }
            const dummyEmails = ['test@test.com', 'asdf@asdf.com', 'fake@fake.com', 'abc@abc.com', 'admin@admin.com'];
            if (dummyEmails.includes(email.toLowerCase())) {
                e.preventDefault();
                alert('Please provide a genuine email address.');
                document.getElementById('emailInput')?.focus();
                return false;
            }
        }

        // Captcha check
        const captcha = document.getElementById('captcha_solution')?.value.trim() || '';
        if (!captcha) {
            e.preventDefault();
            alert('Please solve the simple math verification puzzle before submitting.');
            document.getElementById('captcha_solution')?.focus();
            return false;
        }

        const btn = document.getElementById('btnSubmitEnquiry');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1.5" role="status"></span> Submitting Enquiry...';
        }

        return true;
    }
</script>

</body>
</html>
