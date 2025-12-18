<?php
/**
 * Stripe Integration Database Migration
 * Adds Stripe-specific fields and tables for subscription management
 */

require_once __DIR__ . '/../config.php';

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🚀 Starting Stripe migration...\n\n";
    
    // Check if subscriptions table exists
    $tableExists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='subscriptions'")->fetch();
    
    if ($tableExists) {
        echo "📋 Updating subscriptions table with Stripe fields...\n";
        
        // Add Stripe-specific columns to subscriptions table
        $columns = [
            'stripe_customer_id' => "ALTER TABLE subscriptions ADD COLUMN stripe_customer_id VARCHAR(255)",
            'stripe_subscription_id' => "ALTER TABLE subscriptions ADD COLUMN stripe_subscription_id VARCHAR(255)",
            'stripe_price_id' => "ALTER TABLE subscriptions ADD COLUMN stripe_price_id VARCHAR(255)",
            'current_period_start' => "ALTER TABLE subscriptions ADD COLUMN current_period_start DATETIME",
            'current_period_end' => "ALTER TABLE subscriptions ADD COLUMN current_period_end DATETIME",
            'cancel_at_period_end' => "ALTER TABLE subscriptions ADD COLUMN cancel_at_period_end INTEGER DEFAULT 0"
        ];
        
        foreach ($columns as $columnName => $sql) {
            try {
                $db->exec($sql);
                echo "  ✅ Added column: $columnName\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'duplicate column name') !== false) {
                    echo "  ⏭️  Column already exists: $columnName\n";
                } else {
                    throw $e;
                }
            }
        }
        
        // Create indexes for Stripe IDs
        try {
            $db->exec("CREATE INDEX IF NOT EXISTS idx_subscriptions_stripe_customer ON subscriptions(stripe_customer_id)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_subscriptions_stripe_subscription ON subscriptions(stripe_subscription_id)");
            echo "  ✅ Created indexes for Stripe fields\n";
        } catch (PDOException $e) {
            echo "  ⏭️  Indexes already exist\n";
        }
    }
    
    // Create invoices table
    echo "\n📋 Creating invoices table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS invoices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            stripe_invoice_id VARCHAR(255) UNIQUE NOT NULL,
            stripe_customer_id VARCHAR(255),
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            status VARCHAR(50) NOT NULL,
            invoice_pdf VARCHAR(255),
            hosted_invoice_url VARCHAR(255),
            billing_reason VARCHAR(50),
            period_start DATETIME,
            period_end DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            paid_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "  ✅ Invoices table created\n";
    
    // Create indexes for invoices
    $db->exec("CREATE INDEX IF NOT EXISTS idx_invoices_user ON invoices(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_invoices_stripe_id ON invoices(stripe_invoice_id)");
    echo "  ✅ Created indexes for invoices\n";
    
    // Create payment_methods table
    echo "\n📋 Creating payment_methods table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS payment_methods (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            stripe_payment_method_id VARCHAR(255) UNIQUE NOT NULL,
            type VARCHAR(50) NOT NULL,
            card_brand VARCHAR(50),
            card_last4 VARCHAR(4),
            card_exp_month INTEGER,
            card_exp_year INTEGER,
            is_default INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "  ✅ Payment methods table created\n";
    
    // Create indexes for payment methods
    $db->exec("CREATE INDEX IF NOT EXISTS idx_payment_methods_user ON payment_methods(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_payment_methods_stripe_id ON payment_methods(stripe_payment_method_id)");
    echo "  ✅ Created indexes for payment methods\n";
    
    // Create webhook_events table for debugging and audit trail
    echo "\n📋 Creating webhook_events table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS webhook_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            stripe_event_id VARCHAR(255) UNIQUE NOT NULL,
            event_type VARCHAR(100) NOT NULL,
            payload TEXT NOT NULL,
            processed INTEGER DEFAULT 0,
            error_message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME
        )
    ");
    echo "  ✅ Webhook events table created\n";
    
    // Create index for webhook events
    $db->exec("CREATE INDEX IF NOT EXISTS idx_webhook_events_stripe_id ON webhook_events(stripe_event_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_webhook_events_type ON webhook_events(event_type)");
    echo "  ✅ Created indexes for webhook events\n";
    
    echo "\n✅ Stripe migration completed successfully!\n";
    echo "📊 Database location: " . DB_PATH . "\n";
    
} catch (PDOException $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

