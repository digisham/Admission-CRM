<?php
namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\View;
use App\Helpers\Logger;
use App\Models\User;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            View::redirect(View::url('dashboard'));
        }
        View::render('auth/login', [], false);
    }

    public function login(): void
    {
        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security session expired. Please try again.');
            View::redirect(View::url('login'));
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            View::flash('error', 'Please enter your registered email and password.');
            View::redirect(View::url('login'));
        }

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            View::flash('error', 'Invalid login credentials. Please check and retry.');
            View::redirect(View::url('login'));
        }

        if (empty($user['is_active'])) {
            View::flash('error', 'This account has been deactivated. Please contact the administrator.');
            View::redirect(View::url('login'));
        }

        Auth::login($user);
        Logger::audit('user_login', 'user', $user['id'], null, ['email' => $email]);

        View::flash('success', "Welcome back, {$user['name']}!");
        View::redirect(View::url('dashboard'));
    }

    public function logout(): void
    {
        if (Auth::check()) {
            Logger::audit('user_logout', 'user', Auth::id());
        }
        Auth::logout();
        View::flash('info', 'You have been signed out successfully.');
        View::redirect(View::url('login'));
    }

    public function showForgotPassword(): void
    {
        View::redirect(View::url('login') . '#forgot');
    }

    public function forgotPassword(): void
    {
        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security session expired. Please try again.');
            View::redirect(View::url('login'));
        }

        $email = trim(strtolower($_POST['email'] ?? ''));
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($email)) {
            View::flash('error', 'Please enter your registered email address.');
            View::redirect(View::url('login') . '#forgot');
        }

        $user = User::findByEmail($email);
        if (!$user) {
            View::flash('error', "No staff account found for '{$email}'. Please verify your email.");
            View::redirect(View::url('login') . '#forgot');
        }

        if (empty($user['is_active'])) {
            View::flash('error', 'This account has been deactivated. Please contact the administrator.');
            View::redirect(View::url('login'));
        }

        if (empty($newPassword) || strlen($newPassword) < 6) {
            View::flash('error', 'New password must be at least 6 characters long.');
            View::redirect(View::url('login') . '#forgot');
        }

        if ($newPassword !== $confirmPassword) {
            View::flash('error', 'Passwords do not match. Please re-enter.');
            View::redirect(View::url('login') . '#forgot');
        }

        // Direct reset password in database
        User::updatePassword((int)$user['id'], $newPassword);

        // Automatically log the user into the session
        Auth::login($user);
        Logger::audit('direct_password_reset_and_login', 'user', $user['id'], null, ['email' => $email]);

        View::flash('success', "Password successfully updated! Welcome to dashboard, {$user['name']}.");
        View::redirect(View::url('dashboard'));
    }

    public function checkEmail(): void
    {
        $email = trim(strtolower($_GET['email'] ?? $_POST['email'] ?? ''));
        if (empty($email)) {
            View::json(['exists' => false, 'message' => 'Email required'], 400);
        }

        $user = User::findByEmail($email);
        if ($user && !empty($user['is_active'])) {
            View::json(['exists' => true, 'name' => $user['name']]);
        } else {
            View::json(['exists' => false, 'message' => 'No active staff account found with this email.']);
        }
    }
}
