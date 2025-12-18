<?php
/**
 * Contacts table migration
 * Creates the contacts table for partnership tracking
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Create contacts table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS contacts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            email TEXT,
            phone TEXT,
            company TEXT,
            title TEXT,
            stage TEXT DEFAULT 'identified',
            linkedin_url TEXT,
            twitter_handle TEXT,
            youtube_channel TEXT,
            instagram_handle TEXT,
            tiktok_handle TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create indexes for better performance
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_contacts_user ON contacts(user_id)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_contacts_stage ON contacts(stage)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_contacts_created ON contacts(created_at)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_contacts_name ON contacts(name)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_contacts_company ON contacts(company)");
    
    echo "✅ Contacts table created successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Contacts migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

