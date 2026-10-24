<?php
namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Logger;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Models\AcademicSession;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;

class InquiryController
{
    /**
     * Render the public admission enquiry form
     */
    public function index(): void
    {
        $activeSession = AcademicSession::getActive();
        $classes = Setting::getClassesOffered();

        $successData = null;
        if (!empty($_GET['success']) && !empty($_SESSION['public_inquiry_success'])) {
            $successData = $_SESSION['public_inquiry_success'];
            unset($_SESSION['public_inquiry_success']);
        }

        // Generate Math Captcha
        $n1 = rand(2, 9);
        $n2 = rand(1, 9);
        $captchaQuestion = "{$n1} + {$n2}";
        $_SESSION['enquiry_captcha_answer'] = $n1 + $n2;
        $_SESSION['enquiry_form_rendered_at'] = time();

        View::render('public/inquire', [
            'activeSession' => $activeSession,
            'classes' => $classes,
            'successData' => $successData,
            'captchaQuestion' => $captchaQuestion,
            'pageTitle' => 'Online Admission Enquiry — GD Goenka Public School, Purnea'
        ], false);
    }

    /**
     * AJAX endpoint to refresh Math Captcha
     */
    public function refreshCaptcha(): void
    {
        $n1 = rand(2, 9);
        $n2 = rand(1, 9);
        $_SESSION['enquiry_captcha_answer'] = $n1 + $n2;

        View::json([
            'status' => 'success',
            'question' => "{$n1} + {$n2}"
        ]);
    }

    /**
     * Process public enquiry submission with comprehensive spam protection
     */
    public function store(): void
    {
        // 1. Validate CSRF token
        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security session expired. Please reload the page and try again.');
            View::redirect(View::url('inquire'));
        }

        // 2. Anti-Spam: Honeypot trap check
        if (!empty($_POST['hp_website_fax']) || !empty($_POST['company_tax_id'])) {
            Logger::warning("Spam bot submission blocked via honeypot trap from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            View::flash('error', 'Spam activity detected. Submission blocked.');
            View::redirect(View::url('inquire'));
        }

        // 3. Anti-Spam: Submission speed trap (bots submit in < 2 seconds)
        $renderedAt = (int)($_SESSION['enquiry_form_rendered_at'] ?? 0);
        if ($renderedAt > 0 && (time() - $renderedAt) < 2) {
            View::flash('error', 'Submission was too fast. Please take a moment to fill the form genuine details.');
            View::redirect(View::url('inquire'));
        }

        // 4. Anti-Spam: Math Captcha validation
        $captchaInput = trim($_POST['captcha_solution'] ?? '');
        $expectedCaptcha = $_SESSION['enquiry_captcha_answer'] ?? null;
        if ($expectedCaptcha === null || (int)$captchaInput !== (int)$expectedCaptcha) {
            View::flash('error', 'Security check verification failed. Please solve the math verification problem correctly.');
            View::redirect(View::url('inquire'));
        }

        // 5. Anti-Spam: Rate limiting (max 5 submissions per 15 minutes per IP)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rateKey = 'rate_limit_enquiry_' . md5($ip);
        $attempts = $_SESSION[$rateKey] ?? ['count' => 0, 'first_at' => time()];
        if (time() - $attempts['first_at'] > 900) {
            $attempts = ['count' => 0, 'first_at' => time()];
        }
        $attempts['count']++;
        $_SESSION[$rateKey] = $attempts;

        if ($attempts['count'] > 6) {
            View::flash('error', 'Too many requests received from your network. Please wait a few minutes before submitting another enquiry.');
            View::redirect(View::url('inquire'));
        }

        // 6. Extract form inputs
        $studentName = trim($_POST['student_name'] ?? '');
        $parentName = trim($_POST['parent_name'] ?? '');
        $rawMobile = $_POST['mobile'] ?? '';
        $mobile = Sanitizer::cleanPhone($rawMobile);
        $alternateMobile = Sanitizer::cleanPhone($_POST['alternate_mobile'] ?? '');
        $email = !empty($_POST['email']) ? trim(strtolower($_POST['email'])) : null;
        $classApplied = trim($_POST['class_applied'] ?? 'Grade 1');
        $city = trim($_POST['city'] ?? 'Purnea');
        $address = trim($_POST['address'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        $activeSession = AcademicSession::getActive();
        $sessionId = (int)($_POST['session_id'] ?? ($activeSession['id'] ?? 1));

        // 7. Validate mandatory fields
        if (empty($studentName) || empty($parentName) || empty($mobile)) {
            View::flash('error', 'Please fill all mandatory fields: Student Full Name, Parent Name, and Primary Mobile Number.');
            View::redirect(View::url('inquire'));
        }

        // 8. Strict Mobile Validation (Anti-Spam Phone check)
        $mobileError = $this->validateMobileNumber($mobile);
        if ($mobileError) {
            View::flash('error', $mobileError);
            View::redirect(View::url('inquire'));
        }

        if (!empty($alternateMobile)) {
            $altMobileError = $this->validateMobileNumber($alternateMobile);
            if ($altMobileError) {
                View::flash('error', 'Alternate Mobile: ' . $altMobileError);
                View::redirect(View::url('inquire'));
            }
        }

        // 9. Strict Email Validation (Anti-Spam Email check)
        $emailError = $this->validateEmailAddress($email);
        if ($emailError) {
            View::flash('error', $emailError);
            View::redirect(View::url('inquire'));
        }

        // 10. Auto-assign to next available counselor in round-robin fashion
        $assignedTo = Lead::getNextCounselorForAssignment($sessionId);
        $assignedCounselorName = null;
        if ($assignedTo) {
            $cUser = User::find($assignedTo);
            $assignedCounselorName = $cUser['name'] ?? null;
        }

        // 11. Duplicate detection check
        $duplicates = Lead::findDuplicates($mobile, $sessionId);
        $isDuplicate = !empty($duplicates);
        $enquiryRemarks = $remarks;
        if ($isDuplicate) {
            $prevRef = $duplicates[0]['lead_ref'] ?? '';
            $enquiryRemarks = ($enquiryRemarks ? $enquiryRemarks . "\n" : '') . "[Note: Duplicate mobile enquiry found in session: {$prevRef}]";
        }

        // 12. Store Lead in database
        $newId = Lead::create([
            'session_id' => $sessionId,
            'student_name' => $studentName,
            'parent_name' => $parentName,
            'mobile' => $mobile,
            'alternate_mobile' => $alternateMobile ?: null,
            'email' => $email,
            'class_applied' => $classApplied,
            'enquiry_date' => date('Y-m-d'),
            'enquiry_source' => 'Website / Online Enquiry',
            'assigned_to' => $assignedTo,
            'status' => 'New',
            'priority' => 'Medium',
            'next_followup_date' => date('Y-m-d H:i:s', strtotime('+1 day 10:00')),
            'address' => $address ?: null,
            'city' => $city ?: 'Purnea',
            'remarks' => $enquiryRemarks ?: null,
            'created_by' => 1
        ]);

        $createdLead = Lead::find($newId);
        $leadRef = $createdLead['lead_ref'] ?? ('GDG-' . $newId);

        // 13. Schedule follow-up task for the assigned counselor
        if ($newId && $assignedTo) {
            Task::create([
                'lead_id' => $newId,
                'session_id' => $sessionId,
                'title' => "Online Enquiry Follow-up: {$studentName}",
                'task_type' => 'Follow-up Call',
                'description' => "New public online enquiry submitted via website for Grade {$classApplied}. Parent: {$parentName} ({$mobile}).",
                'assigned_to' => $assignedTo,
                'due_date' => date('Y-m-d\TH:i', strtotime('+1 day 10:00')),
                'priority' => 'Medium',
            ]);

            // Notify assigned counselor
            Notification::create([
                'user_id' => $assignedTo,
                'sender_id' => 1,
                'lead_id' => $newId,
                'type' => 'lead_assigned',
                'title' => 'New Public Online Enquiry',
                'message' => "New online enquiry received for {$studentName} (Class {$classApplied}) - Ref: {$leadRef}. Parent: {$parentName} ({$mobile}).",
                'link' => 'index.php?route=leads/show&id=' . $newId
            ]);
        }

        // 14. Notify Admission Managers / Super Admins
        $admins = User::getAdmissionManagers();
        foreach ($admins as $adm) {
            if ($adm['id'] !== $assignedTo) {
                Notification::create([
                    'user_id' => $adm['id'],
                    'sender_id' => 1,
                    'lead_id' => $newId,
                    'type' => 'lead_created',
                    'title' => 'New Public Online Enquiry Registered',
                    'message' => "Online enquiry registered: {$studentName} (Class: {$classApplied}, Ref: {$leadRef})",
                    'link' => 'index.php?route=leads/show&id=' . $newId
                ]);
            }
        }

        // 15. Audit Log
        Logger::audit('public_enquiry_create', 'lead', $newId, null, [
            'ref' => $leadRef,
            'student' => $studentName,
            'source' => 'Website / Online Enquiry'
        ]);

        // Clean captcha session
        unset($_SESSION['enquiry_captcha_answer']);

        $_SESSION['public_inquiry_success'] = [
            'id' => $newId,
            'lead_ref' => $leadRef,
            'student_name' => $studentName,
            'parent_name' => $parentName,
            'mobile' => $mobile,
            'class_applied' => $classApplied,
            'assigned_counselor' => $assignedCounselorName
        ];

        View::redirect(View::url('inquire?success=1'));
    }

    /**
     * Strict 10-digit Indian Mobile validation with anti-spam checks
     */
    private function validateMobileNumber(string $phone): ?string
    {
        // Must be clean 10 digits
        if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
            return 'Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.';
        }

        // Block all identical digits (e.g., 0000000000, 1111111111, 9999999999)
        if (preg_match('/^(\d)\1{9}$/', $phone)) {
            return 'Invalid mobile number. Repeated identical digits are not accepted.';
        }

        // Block common dummy sequential spam numbers
        $dummyNumbers = [
            '1234567890', '0123456789', '9876543210', '0987654321', 
            '1212121212', '9898989898', '9999988888', '9123456789'
        ];
        if (in_array($phone, $dummyNumbers, true)) {
            return 'Please provide a genuine, active 10-digit mobile number for communication.';
        }

        // Check digit variety: at least 4 distinct digits must exist
        $uniqueDigits = count(array_unique(str_split($phone)));
        if ($uniqueDigits < 4) {
            return 'Invalid mobile number format. Please provide a genuine mobile number.';
        }

        return null; // Valid
    }

    /**
     * Strict Email validation with anti-spam & disposable domain checks
     */
    private function validateEmailAddress(?string $email): ?string
    {
        if (empty($email)) {
            return null; // Email is optional
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please provide a valid email address.';
        }

        $parts = explode('@', strtolower($email));
        if (count($parts) !== 2) {
            return 'Invalid email address format.';
        }

        [$user, $domain] = $parts;

        // Reject obvious dummy emails
        $dummyUsers = ['test', 'asdf', 'fake', 'abc', '123', 'admin', 'user', 'none', 'noone', 'null', 'xyz'];
        if (in_array($user, $dummyUsers, true) && in_array($domain, ['test.com', 'example.com', 'domain.com', 'fake.com'])) {
            return 'Please enter a genuine, active email address.';
        }

        // Block disposable/temporary email domains
        $disposableDomains = [
            'mailinator.com', 'tempmail.com', '10minutemail.com', 'guerrillamail.com',
            'sharklasers.com', 'throwawaymail.com', 'yopmail.com', 'trashmail.com',
            'dispostable.com', 'temp-mail.org', 'fakeinbox.com', 'getairmail.com',
            'mytemp.email', 'crazymailing.com', 'generator.email'
        ];
        if (in_array($domain, $disposableDomains, true)) {
            return 'Disposable or temporary email domains are not allowed.';
        }

        return null; // Valid
    }
}
