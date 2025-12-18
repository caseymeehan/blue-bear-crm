<?php
/**
 * Interaction titles migration
 * Adds a title column to the interactions table for better note organization
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if column already exists
    $result = $conn->query("PRAGMA table_info(interactions)");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'name');
    
    if (!in_array('title', $columnNames)) {
        // Add title column to interactions table
        $conn->exec("ALTER TABLE interactions ADD COLUMN title VARCHAR(255) DEFAULT NULL");
        echo "✅ Added 'title' column to interactions table!\n";
    } else {
        echo "ℹ️  Title column already exists in interactions table.\n";
    }
    
    echo "✅ Interaction titles migration completed!\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

