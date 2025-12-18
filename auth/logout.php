<?php
/**
 * Logout
 * Logs out the current user and destroys the session
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();

// Logout the user
$auth->logout();

// Set flash message
flashMessage('success', 'You have been logged out successfully.');

// Redirect to homepage
redirect('../');

