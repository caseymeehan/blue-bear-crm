<?php
/**
 * Social Stats table migration
 * Creates the social_stats table for structured follower/subscriber counts
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Create social_stats table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS social_stats (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contact_id INTEGER NOT NULL,
            platform TEXT NOT NULL,
            followers INTEGER DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
            UNIQUE(contact_id, platform)
        )
    ");
    
    // Create indexes for better performance
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_social_stats_contact ON social_stats(contact_id)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_social_stats_platform ON social_stats(platform)");
    
    echo "✅ Social Stats table created successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Social Stats migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

