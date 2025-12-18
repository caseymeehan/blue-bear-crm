<?php
/**
 * Configuration file for PHP SaaS Template
 */

// Autoload Composer dependencies
require_once __DIR__ . '/vendor/autoload.php';

// Database configuration
// Use Railway volume mount path if available, otherwise use local database folder
$railwayVolumePath = '/app/data';
if (is_dir($railwayVolumePath) && is_writable($railwayVolumePath)) {
    define('DB_PATH', $railwayVolumePath . '/saas.db');
} else {
    define('DB_PATH', __DIR__ . '/database/saas.db');
}

// Auto-initialize database if it doesn't exist (Railway deployment support)
if (!file_exists(DB_PATH) || filesize(DB_PATH) === 0) {
    error_log('Database not found or empty, auto-initializing...');
    
    // Create database directory if it doesn't exist
    $dbDir = dirname(DB_PATH);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
    
    // Suppress output and run initialization scripts
    ob_start();
    try {
        // Initialize base tables
        require __DIR__ . '/database/init.php';
        
        // Run migrations
        require __DIR__ . '/database/migrate_google_oauth.php';
        require __DIR__ . '/database/migrate_items.php';
        require __DIR__ . '/database/migrate_stripe.php';
        require __DIR__ . '/database/migrate_contacts.php';
        require __DIR__ . '/database/migrate_interactions.php';
        require __DIR__ . '/database/migrate_social_stats.php';
        
        error_log('Database auto-initialization completed successfully');
    } catch (Exception $e) {
        error_log('Database auto-initialization failed: ' . $e->getMessage());
    }
    ob_end_clean();
}

// Load local configuration overrides FIRST (gitignored - for actual credentials)
// This allows config.local.php to override SITE_URL for local development
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Site configuration
define('SITE_NAME', 'BlueBear CRM');
// Auto-detect Railway URL or use localhost (can be overridden by config.local.php)
if (!defined('SITE_URL')) {
    $railwayUrl = getenv('RAILWAY_PUBLIC_DOMAIN');
    define('SITE_URL', $railwayUrl ? 'https://' . $railwayUrl : 'http://localhost:9000');
}
define('SITE_EMAIL', 'hello@yoursaas.com');

// Google OAuth Configuration
// These will be overridden by config.local.php if it exists
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID');
}
if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET');
}
define('GOOGLE_REDIRECT_URI', SITE_URL . '/auth/google-callback.php');

// Stripe Configuration
// These should be overridden in config.local.php with your actual keys
if (!defined('STRIPE_PUBLISHABLE_KEY')) {
    define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: 'pk_test_YOUR_KEY');
}
if (!defined('STRIPE_SECRET_KEY')) {
    define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: 'sk_test_YOUR_KEY');
}
if (!defined('STRIPE_WEBHOOK_SECRET')) {
    define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_YOUR_WEBHOOK_SECRET');
}
define('STRIPE_WEBHOOK_URL', SITE_URL . '/webhooks/stripe.php');

// Security
define('SESSION_LIFETIME', 86400); // 24 hours
define('PASSWORD_MIN_LENGTH', 8);

// Features
define('ENABLE_REGISTRATION', true);
define('REQUIRE_EMAIL_VERIFICATION', false);

// Pricing
define('FREE_TIER_ENABLED', true);

// Pricing Plans
define('PRICING_PLANS', [
    'free' => [
        'name' => 'Free',
        'price' => 0,
        'currency' => 'USD',
        'billing_cycle' => 'month',
        'stripe_price_id' => null,
        'item_limit' => 5,
        'contact_limit' => 30,
        'features' => ['All features included']
    ],
    'pro' => [
        'name' => 'Pro',
        'price' => 19,
        'currency' => 'USD',
        'billing_cycle' => 'month',
        'stripe_price_id' => 'price_YOUR_PRO_PRICE_ID_HERE',
        'item_limit' => 50,
        'contact_limit' => 200,
        'features' => ['All features included']
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'price' => 49,
        'currency' => 'USD',
        'billing_cycle' => 'month',
        'stripe_price_id' => 'price_YOUR_ENTERPRISE_PRICE_ID_HERE',
        'item_limit' => null, // unlimited
        'contact_limit' => null, // unlimited
        'features' => ['All features included']
    ]
]);

// Contact Pipeline Stages
define('CONTACT_STAGES', [
    'identified' => ['name' => 'Identified', 'color' => 'gray'],
    'outreach_sent' => ['name' => 'Outreach Sent', 'color' => 'blue'],
    'in_discussion' => ['name' => 'In Discussion', 'color' => 'yellow'],
    'negotiating' => ['name' => 'Negotiating', 'color' => 'orange'],
    'active_partner' => ['name' => 'Active Partner', 'color' => 'green'],
    'churned' => ['name' => 'Churned', 'color' => 'red']
]);

// Social Media Platforms
define('SOCIAL_PLATFORMS', [
    'youtube' => ['name' => 'YouTube', 'icon' => 'youtube', 'metric' => 'subscribers'],
    'instagram' => ['name' => 'Instagram', 'icon' => 'instagram', 'metric' => 'followers'],
    'twitter' => ['name' => 'Twitter/X', 'icon' => 'twitter', 'metric' => 'followers'],
    'linkedin' => ['name' => 'LinkedIn', 'icon' => 'linkedin', 'metric' => 'connections'],
    'tiktok' => ['name' => 'TikTok', 'icon' => 'tiktok', 'metric' => 'followers']
]);

// Timezone
date_default_timezone_set('UTC');

// Detect production environment (Railway provides RAILWAY_PUBLIC_DOMAIN or RAILWAY_ENVIRONMENT_NAME)
$isProduction = getenv('RAILWAY_PUBLIC_DOMAIN') !== false || getenv('RAILWAY_ENVIRONMENT_NAME') !== false;

// Error reporting (disable display in production)
error_reporting(E_ALL);
if ($isProduction) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '1');
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $isProduction ? 1 : 0);
ini_set('session.use_strict_mode', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

