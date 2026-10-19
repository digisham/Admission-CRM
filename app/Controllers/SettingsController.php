<?php
namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\View;
use App\Models\Setting;

class SettingsController
{
    public function index(): void
    {
        if (!Auth::isAdmin()) {
            View::flash('error', 'Only administrators can access system settings.');
            View::redirect(View::url('dashboard'));
        }

        $settings = Setting::all();
        $sources = Setting::getLeadSources();
        $statuses = Setting::getLeadStatuses();
        $classes = Setting::getClassesOffered();

        View::render('settings/index', [
            'settings' => $settings,
            'sources' => $sources,
            'statuses' => $statuses,
            'classes' => $classes,
        ]);
    }

    public function update(): void
    {
        if (!Auth::isAdmin()) {
            View::flash('error', 'Unauthorized.');
            View::redirect(View::url('settings'));
        }

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('settings'));
        }

        // Update school contact details
        Setting::set('school_name', trim($_POST['school_name'] ?? 'GD Goenka Public School, Purnea'), 'general');
        Setting::set('school_email', trim($_POST['school_email'] ?? 'admissions@gdgoenkapurnea.com'), 'general');
        Setting::set('school_phone', trim($_POST['school_phone'] ?? '+91 99999 99999'), 'general');
        Setting::set('school_address', trim($_POST['school_address'] ?? 'NH-31, Purnea, Bihar'), 'general');

        // Update sources if provided
        if (!empty($_POST['lead_sources'])) {
            $sourcesArray = array_values(array_filter(array_map('trim', explode("\n", $_POST['lead_sources']))));
            Setting::set('lead_sources', $sourcesArray, 'leads');
        }

        // Update classes if provided
        if (!empty($_POST['classes_offered'])) {
            $classesArray = array_values(array_filter(array_map('trim', explode("\n", $_POST['classes_offered']))));
            Setting::set('classes_offered', $classesArray, 'academics');
        }

        View::flash('success', 'School and CRM settings saved successfully.');
        View::redirect(View::url('settings'));
    }
}
