<?php
/**
 * Helper functions for the application
 * Provides security and utility functions
 */

/**
 * Sanitize user input to prevent XSS attacks
 * 
 * @param string $data The input data to sanitize
 * @return string The sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Escape output for display (shorthand for htmlspecialchars)
 * 
 * @param string $data The data to escape
 * @return string The escaped data
 */
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a CSRF token and store it in the session
 * 
 * @return string The generated CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token against the session token
 * 
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Perform a safe redirect
 * 
 * @param string $url The URL to redirect to
 * @param int $statusCode The HTTP status code (default: 302)
 * @return void
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * Set a flash message in the session
 * 
 * @param string $type The message type (success, error, warning, info)
 * @param string $message The message to display
 * @return void
 */
function flashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear all flash messages
 * 
 * @return array Array of flash messages
 */
function getFlashMessages() {
    if (!isset($_SESSION['flash_messages'])) {
        return [];
    }
    $messages = $_SESSION['flash_messages'];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Check if there are any flash messages
 * 
 * @return bool True if there are flash messages, false otherwise
 */
function hasFlashMessages() {
    return isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages']);
}

/**
 * Generate a random secure token
 * 
 * @param int $length The length of the token (default: 32)
 * @return string The generated token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Validate an email address
 * 
 * @param string $email The email address to validate
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get the client's IP address
 * 
 * @return string The client's IP address
 */
function getClientIP() {
    $ipKeys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($ipKeys as $key) {
        if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
            return $_SERVER[$key];
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Get the client's user agent
 * 
 * @return string The client's user agent
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Format a date for display
 * 
 * @param string $date The date to format
 * @param string $format The format to use (default: 'F j, Y')
 * @return string The formatted date
 */
function formatDate($date, $format = 'F j, Y') {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Format a datetime for display
 * 
 * @param string $datetime The datetime to format
 * @param string $format The format to use (default: 'F j, Y g:i A')
 * @return string The formatted datetime
 */
function formatDateTime($datetime, $format = 'F j, Y g:i A') {
    if (empty($datetime)) {
        return '';
    }
    
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date($format, $timestamp);
}

/**
 * Get relative time (e.g., "2 hours ago")
 * 
 * @param string $datetime The datetime to convert
 * @return string The relative time string
 */
function timeAgo($datetime) {
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}

/**
 * Check if the request is a POST request
 * 
 * @return bool True if POST request, false otherwise
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if the request is a GET request
 * 
 * @return bool True if GET request, false otherwise
 */
function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Get a value from $_POST with optional default
 * 
 * @param string $key The key to retrieve
 * @param mixed $default The default value if key doesn't exist
 * @return mixed The value or default
 */
function post($key, $default = null) {
    return $_POST[$key] ?? $default;
}

/**
 * Get a value from $_GET with optional default
 * 
 * @param string $key The key to retrieve
 * @param mixed $default The default value if key doesn't exist
 * @return mixed The value or default
 */
function get($key, $default = null) {
    return $_GET[$key] ?? $default;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 * 
 * @return int|null The user ID or null if not logged in
 */
function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Generate a URL for the application
 * 
 * @param string $path The path to append to the site URL
 * @return string The full URL
 */
function url($path = '') {
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Generate an asset URL
 * 
 * @param string $path The asset path
 * @return string The full asset URL
 */
function asset($path) {
    return url('assets/' . ltrim($path, '/'));
}

