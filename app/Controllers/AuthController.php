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
        View::render('auth/forgot_password', [], false);
    }

    public function forgotPassword(): void
    {
        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('forgot-password'));
        }

        $email = trim($_POST['email'] ?? '');
        $user = User::findByEmail($email);

        // For security, show neutral message whether user exists or not
        View::flash('info', 'If an active account exists for that email, password reset instructions have been dispatched.');
        View::redirect(View::url('login'));
    }
}
