<?php
/**
 * Local Configuration Template
 * 
 * INSTRUCTIONS FOR LOCAL DEVELOPMENT:
 * 1. Copy this file to config.local.php
 * 2. Replace all placeholder values with your actual credentials
 * 3. Never commit config.local.php to version control
 * 
 * FOR RAILWAY DEPLOYMENT:
 * Set these as environment variables in your Railway dashboard instead.
 * Railway will automatically use environment variables over this file.
 */

// ============================================================================
// Google OAuth Configuration (REQUIRED)
// ============================================================================
// Get these from: https://console.cloud.google.com/
// See SETUP.md for complete Google OAuth setup instructions

define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');

// ============================================================================
// Stripe API Keys (OPTIONAL - for payment processing)
// ============================================================================
// Get these from: https://dashboard.stripe.com/test/apikeys
// Use TEST keys for development (pk_test_ and sk_test_)
// Use LIVE keys for production (pk_live_ and sk_live_)

define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_STRIPE_PUBLISHABLE_KEY');
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_STRIPE_SECRET_KEY');
define('STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_STRIPE_WEBHOOK_SECRET');

// ============================================================================
// Notes:
// ============================================================================
// - All values above should be replaced with your actual credentials
// - This file is gitignored to protect your sensitive information
// - For Railway deployment, use environment variables instead
// - See RAILWAY_DEPLOY.md for Railway-specific setup instructions
// - See SETUP.md for detailed configuration guide

