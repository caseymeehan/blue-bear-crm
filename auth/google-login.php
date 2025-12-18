<?php
/**
 * Google OAuth Login
 * Initiates the Google OAuth flow
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();

// If already logged in, redirect to dashboard
if ($auth->isLoggedIn()) {
    redirect('../dashboard/');
}

// Get Google OAuth authorization URL
$googleOAuth = $auth->getGoogleOAuth();
$authUrl = $googleOAuth->getAuthUrl();

// Redirect to Google
redirect($authUrl);

