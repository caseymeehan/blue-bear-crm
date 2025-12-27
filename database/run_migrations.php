<?php
/**
 * Migration runner - runs all database migrations in order
 * Safe to run multiple times (migrations use IF NOT EXISTS and check for existing columns)
 * 
 * This script is automatically run on deployment before the web server starts.
 */

echo "🔄 Running database migrations...\n";

// Migrations in order of dependency
$migrations = [
    'init.php',                    // Core tables (users, sessions, etc.)
    'migrate_contacts.php',        // Contacts table
    'migrate_google_oauth.php',    // Google OAuth fields
    'migrate_interactions.php',    // Interactions table
    'migrate_interaction_titles.php', // Title column for interactions
    'migrate_items.php',           // Items table
    'migrate_social_stats.php',    // Social stats table
    'migrate_stripe.php',          // Stripe integration tables
];

$migrationDir = __DIR__;
$failed = false;
$successCount = 0;

foreach ($migrations as $migration) {
    $path = $migrationDir . '/' . $migration;
    if (file_exists($path)) {
        echo "  Running: $migration\n";
        try {
            require_once $path;
            $successCount++;
        } catch (Exception $e) {
            echo "  ❌ Failed: " . $e->getMessage() . "\n";
            $failed = true;
        }
    } else {
        echo "  ⚠️  Skipping (not found): $migration\n";
    }
}

echo "\n";

if ($failed) {
    echo "❌ Some migrations failed! Check errors above.\n";
    exit(1);
}

echo "✅ All migrations completed successfully! ($successCount migrations)\n";

