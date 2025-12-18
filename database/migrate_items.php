<?php
/**
 * Items table migration
 * Creates the items table for user-created items
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Create items table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create indexes for better performance
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_items_user ON items(user_id)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_items_created ON items(created_at)");
    
    echo "✅ Items table created successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Items migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

