<?php
/**
 * Google OAuth Callback
 * Handles the callback from Google OAuth
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();

// Check for error from Google
if (isset($_GET['error'])) {
    flashMessage('error', 'Authentication cancelled or failed. Please try again.');
    redirect('../');
}

// Get authorization code and state from callback
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

if (!$code || !$state) {
    flashMessage('error', 'Invalid authentication response. Please try again.');
    redirect('../');
}

// Handle the OAuth callback
$result = $auth->handleGoogleCallback($code, $state);

if (!$result) {
    flashMessage('error', 'Failed to authenticate with Google. Please try again.');
    redirect('../');
}

// Check if this is a new user
if ($result['is_new_user']) {
    flashMessage('success', 'Welcome! Your account has been created successfully.');
} else {
    flashMessage('success', 'Welcome back, ' . escape($result['user']['full_name']) . '!');
}

// Redirect to intended page or dashboard
$redirectUrl = $_SESSION['redirect_after_login'] ?? '../dashboard/';
unset($_SESSION['redirect_after_login']);

redirect($redirectUrl);

