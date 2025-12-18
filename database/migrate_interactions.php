<?php
/**
 * Interactions table migration
 * Creates the interactions table for timestamped notes per contact
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Create interactions table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS interactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contact_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            note TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create indexes for better performance
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_interactions_contact ON interactions(contact_id)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_interactions_user ON interactions(user_id)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_interactions_created ON interactions(created_at)");
    
    echo "✅ Interactions table created successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Interactions migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

