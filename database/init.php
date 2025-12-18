<?php
/**
 * Database initialization script
 * Creates the SQLite database and tables
 */

require_once __DIR__ . '/../config.php';

try {
    // Create database directory if it doesn't exist
    $dbDir = dirname(DB_PATH);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
    
    // Create database connection
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            avatar_url VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME,
            is_active INTEGER DEFAULT 1,
            is_admin INTEGER DEFAULT 0,
            email_verified INTEGER DEFAULT 0,
            subscription_tier VARCHAR(20) DEFAULT 'free'
        )
    ");
    
    // Create sessions table
    $db->exec("
        CREATE TABLE IF NOT EXISTS sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            session_token VARCHAR(255) UNIQUE NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create password reset tokens table
    $db->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token VARCHAR(255) UNIQUE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            used INTEGER DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create subscriptions table
    $db->exec("
        CREATE TABLE IF NOT EXISTS subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            plan_name VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT 'active',
            amount DECIMAL(10,2),
            currency VARCHAR(3) DEFAULT 'USD',
            billing_cycle VARCHAR(20),
            started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            ends_at DATETIME,
            cancelled_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create activity log table
    $db->exec("
        CREATE TABLE IF NOT EXISTS activity_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    
    // Create indexes for better performance
    $db->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(session_token)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_sessions_user ON sessions(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_activity_user ON activity_log(user_id)");
    
    echo "✅ Database initialized successfully!\n";
    echo "Database location: " . DB_PATH . "\n";
    
} catch (PDOException $e) {
    echo "❌ Database initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}

