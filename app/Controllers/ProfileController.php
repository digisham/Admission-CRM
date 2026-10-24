<?php
namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\View;
use App\Helpers\Logger;
use App\Models\User;

class ProfileController
{
    public function index(): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        $stats = User::getProfileStats($user['id']);

        View::render('profile/index', [
            'pageTitle' => 'My Profile',
            'user' => $user,
            'stats' => $stats
        ]);
    }

    public function update(): void
    {
        Auth::requireLogin();

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid. Please retry.');
            View::redirect(View::url('profile'));
        }

        $userId = Auth::id();
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $designation = trim($_POST['designation'] ?? '');

        if (empty($name) || strlen($name) < 2) {
            View::flash('error', 'Please enter a valid full name (minimum 2 characters).');
            View::redirect(View::url('profile'));
        }

        $data = [
            'name' => $name,
            'phone' => $phone,
            'designation' => $designation
        ];

        $updated = User::updateProfile($userId, $data);
        if ($updated) {
            Auth::resetCache();
            $_SESSION['user_name'] = $name;
            View::flash('success', 'Profile details updated successfully.');
        } else {
            View::flash('error', 'Failed to update profile details.');
        }

        View::redirect(View::url('profile'));
    }

    public function changePassword(): void
    {
        Auth::requireLogin();

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid. Please retry.');
            View::redirect(View::url('profile#security'));
        }

        $user = Auth::user();
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword)) {
            View::flash('error', 'Please enter your current password.');
            View::redirect(View::url('profile#security'));
        }

        if (!password_verify($currentPassword, $user['password'])) {
            View::flash('error', 'Current password is incorrect. Please check and retry.');
            View::redirect(View::url('profile#security'));
        }

        if (empty($newPassword) || strlen($newPassword) < 6) {
            View::flash('error', 'New password must be at least 6 characters long.');
            View::redirect(View::url('profile#security'));
        }

        if ($newPassword !== $confirmPassword) {
            View::flash('error', 'New password and confirmation password do not match.');
            View::redirect(View::url('profile#security'));
        }

        $updated = User::updatePassword($user['id'], $newPassword);
        if ($updated) {
            Auth::resetCache();
            Logger::audit('user_password_change', 'user', $user['id'], null, ['action' => 'Password updated from profile']);
            View::flash('success', 'Your password has been updated successfully.');
        } else {
            View::flash('error', 'Failed to update password.');
        }

        View::redirect(View::url('profile#security'));
    }
}
