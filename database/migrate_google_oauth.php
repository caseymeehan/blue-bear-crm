<?php
/**
 * Migration script: Add Google OAuth support
 * Adds google_id and oauth_provider columns to users table
 */

require_once __DIR__ . '/../config.php';

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔄 Running Google OAuth migration...\n";
    
    // Check if columns already exist
    $result = $db->query("PRAGMA table_info(users)");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'name');
    
    // Check if we need to add columns
    $needsGoogleId = !in_array('google_id', $columnNames);
    $needsOAuthProvider = !in_array('oauth_provider', $columnNames);
    
    // Check if password_hash is still NOT NULL
    $passwordHashColumn = array_filter($columns, function($col) {
        return $col['name'] === 'password_hash';
    });
    $passwordHashColumn = array_values($passwordHashColumn)[0];
    $needsPasswordHashNullable = $passwordHashColumn['notnull'] == 1;
    
    // If we need to make any changes, recreate the table
    if ($needsGoogleId || $needsOAuthProvider || $needsPasswordHashNullable) {
        echo "🔄 Updating users table schema...\n";
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Create new table with updated schema
            $db->exec("
                CREATE TABLE users_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password_hash VARCHAR(255),
                    full_name VARCHAR(100),
                    avatar_url VARCHAR(255),
                    google_id VARCHAR(255) UNIQUE,
                    oauth_provider VARCHAR(20),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_login DATETIME,
                    is_active INTEGER DEFAULT 1,
                    is_admin INTEGER DEFAULT 0,
                    email_verified INTEGER DEFAULT 0,
                    subscription_tier VARCHAR(20) DEFAULT 'free'
                )
            ");
            
            // Get all existing columns to build SELECT statement
            $existingColumns = [];
            foreach ($columns as $col) {
                $existingColumns[] = $col['name'];
            }
            
            // Build INSERT statement with only existing columns
            $selectColumns = implode(', ', $existingColumns);
            $insertColumns = implode(', ', $existingColumns);
            
            // Copy data from old table to new table
            $db->exec("
                INSERT INTO users_new ($insertColumns)
                SELECT $selectColumns FROM users
            ");
            
            // Drop old table
            $db->exec("DROP TABLE users");
            
            // Rename new table
            $db->exec("ALTER TABLE users_new RENAME TO users");
            
            // Recreate indexes
            $db->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_users_google_id ON users(google_id)");
            
            // Commit transaction
            $db->commit();
            
            if ($needsPasswordHashNullable) {
                echo "✅ Made password_hash nullable\n";
            }
            if ($needsGoogleId) {
                echo "✅ Added google_id column\n";
            }
            if ($needsOAuthProvider) {
                echo "✅ Added oauth_provider column\n";
            }
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    } else {
        echo "ℹ️  Schema already up to date\n";
    }
    
    echo "✅ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

