# Complete Setup Guide

**Note: This guide is for LOCAL DEVELOPMENT setup.**

For the easiest deployment experience (recommended for beginners), see **[RAILWAY_DEPLOY.md](RAILWAY_DEPLOY.md)** instead. Railway provides zero-configuration deployment with automatic HTTPS and environment variables.

---

## Overview

This guide covers **local development setup**:
1. **Dependencies & Database** (2 minutes)
2. **Google OAuth Setup** (10 minutes) - Required for authentication
3. **Stripe Setup** (8 minutes) - Optional, for payment processing

**Total Time**: ~20 minutes

---

## Part 1: Dependencies & Database (2 minutes)

### Install Dependencies

Dependencies are pre-included in the `vendor/` folder, so you can skip this step. However, if you want to update dependencies:

```bash
composer install
```

### Create Local Configuration

Copy the example configuration file:

```bash
cp config.local.example.php config.local.php
```

You'll add your credentials to this file in the next steps.

### Initialize Database

Run these commands to set up your local database:

```bash
php database/init.php
php database/migrate_google_oauth.php
php database/migrate_items.php
php database/migrate_stripe.php
```

You should see "✅ Migration completed successfully!" for each command.

**Note**: If deploying to Railway, database initialization happens automatically - you don't need to run these commands.

---

## Part 2: Google OAuth Setup (10 minutes)

**⚠️ REQUIRED: Authentication won't work without this**

### Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click the project dropdown at the top
3. Click "New Project"
4. Enter a project name (e.g., "YourSaaS")
5. Click "Create"

### Step 2: Enable Google+ API

1. In your project, go to "APIs & Services" > "Library"
2. Search for "Google+ API"
3. Click on it and click "Enable"

### Step 3: Configure OAuth Consent Screen

1. Go to "APIs & Services" > "OAuth consent screen"
2. Select "External" user type
3. Click "Create"
4. Fill in the required information:
   - **App name**: YourSaaS (or your app name)
   - **User support email**: Your email
   - **Developer contact information**: Your email
5. Click "Save and Continue"
6. On the "Scopes" page, click "Add or Remove Scopes"
7. Select:
   - `../auth/userinfo.email`
   - `../auth/userinfo.profile`
8. Click "Update" and then "Save and Continue"
9. On "Test users" page, you can add test users if needed
10. Click "Save and Continue"
11. Review and click "Back to Dashboard"

### Step 4: Create OAuth Credentials

1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "OAuth client ID"
3. Select "Web application"
4. Enter a name (e.g., "Web Client")
5. Under "Authorized JavaScript origins", add:
   - `http://localhost:9000` (for local development)
   - Your production URL (e.g., `https://yoursaas.com`)
6. Under "Authorized redirect URIs", add:
   - `http://localhost:9000/auth/google-callback.php` (for local)
   - Your production callback URL (e.g., `https://yoursaas.com/auth/google-callback.php`)
7. Click "Create"
8. **Copy your Client ID and Client Secret** - you'll need them next!

### Step 5: Update config.local.php

Open the `config.local.php` file you created in Part 1 and add your Google credentials:

```php
<?php
// Google OAuth Credentials
define('GOOGLE_CLIENT_ID', 'your-actual-client-id.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'your-actual-secret-here');

// Stripe Keys (optional - add later)
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_KEY');
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_KEY');
define('STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_SECRET');
```

**Important**: This file is gitignored to keep your credentials safe!

**Railway Users**: Instead of using this file, set these as environment variables in your Railway dashboard (see `RAILWAY_DEPLOY.md`).

### Step 6: Start Server

```bash
php -S localhost:9000
```

### Step 7: Test Authentication

1. Open your browser and go to: `http://localhost:9000`
2. Click "Sign in with Google" or "Continue with Google"
3. Sign in with your Google account
4. Grant permissions to your app
5. You should be redirected to your dashboard!

✅ **Google OAuth is now working!**

---

## Part 3: Stripe Setup (8 minutes)

**Optional but recommended** - Enables subscription payments and feature gating.

### Step 1: Create Stripe Account

1. Go to [stripe.com](https://stripe.com) and sign up (free)
2. Skip business details for now - test mode works without it
3. You'll be in test mode by default

### Step 2: Get Your API Keys

1. Go to [Stripe Dashboard → API Keys](https://dashboard.stripe.com/test/apikeys)
2. Copy your **Publishable key** (starts with `pk_test_`)
3. Copy your **Secret key** (starts with `sk_test_`)
4. Add them to your `config.local.php`:

```php
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_KEY_HERE');
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_KEY_HERE');
```

### Step 3: Create Products in Stripe

**Pro Plan:**
1. Go to [Products](https://dashboard.stripe.com/test/products) → "Add product"
2. Name: `Pro Plan`
3. Description: `Professional features for growing teams`
4. Pricing: `Recurring` → `$29/month`
5. Click "Save product"
6. **Copy the Price ID** (starts with `price_`)

**Enterprise Plan:**
1. Click "Add product" again
2. Name: `Enterprise Plan`
3. Description: `Enterprise features with unlimited usage`
4. Pricing: `Recurring` → `$99/month`
5. Click "Save product"
6. **Copy the Price ID** (starts with `price_`)

### Step 4: Update config.php with Price IDs

Open `config.php` and update the price IDs in the `PRICING_PLANS` array:

```php
'pro' => [
    // ...
    'stripe_price_id' => 'price_YOUR_ACTUAL_PRO_PRICE_ID', // ← Paste here
],
'enterprise' => [
    // ...
    'stripe_price_id' => 'price_YOUR_ACTUAL_ENTERPRISE_PRICE_ID', // ← Paste here
]
```

### Step 5: Test Payment Flow

1. Go to your pricing page: `http://localhost:9000/pricing.php`
2. Click "Upgrade to Pro"
3. Use test card: `4242 4242 4242 4242`
4. Expiry: any future date (e.g., `12/25`)
5. CVC: any 3 digits (e.g., `123`)
6. ZIP: any 5 digits (e.g., `12345`)
7. Click "Subscribe"
8. You should be redirected to success page
9. Check your dashboard - you should now have Pro plan with 50 item limit!

### Step 6: Set Up Webhooks (Optional)

For automatic subscription sync:

**Using Stripe CLI (easiest for local development):**

1. Install Stripe CLI: [stripe.com/docs/stripe-cli](https://stripe.com/docs/stripe-cli)
2. Login: `stripe login`
3. Forward webhooks: `stripe listen --forward-to http://localhost:9000/webhooks/stripe.php`
4. Copy the webhook secret (starts with `whsec_`) to your `config.local.php`

**For production:** Set up webhook endpoint in Stripe Dashboard pointing to `https://yourdomain.com/webhooks/stripe.php`

✅ **Stripe is now fully configured!**

---

## Testing Your Setup

### Test Stripe with Test Cards

- **Success**: `4242 4242 4242 4242`
- **Requires auth**: `4000 0025 0000 3155`
- **Declined**: `4000 0000 0000 9995`

More test cards: [stripe.com/docs/testing](https://stripe.com/docs/testing)

### Verify Everything Works

- [ ] Can sign in with Google
- [ ] Can create items in dashboard
- [ ] Free plan limited to 5 items
- [ ] Can upgrade to Pro via Stripe
- [ ] Pro plan allows 50 items
- [ ] Can view subscription in profile
- [ ] Can cancel subscription

---

## Common Issues

### "Error 400: redirect_uri_mismatch"

**Solution**: The redirect URI in your Google Cloud Console doesn't match the one in your config.

- Make sure the redirect URI in Google Console exactly matches: `http://localhost:9000/auth/google-callback.php`
- Don't forget the protocol (`http://` or `https://`)
- Don't forget the port (`:9000`)
- Make sure there's no trailing slash

### "This app isn't verified"

**Solution**: This is normal during development.

- Click "Advanced" or "Show Advanced"
- Click "Go to [App Name] (unsafe)"
- This warning won't appear once you publish your app

### "Access blocked: This app's request is invalid"

**Solution**: Check your OAuth consent screen configuration.

- Make sure you've selected the correct scopes
- Make sure your app domain is authorized

### Session/Cookie Issues

**Solution**: Check your browser's cookie settings.

- Make sure cookies are enabled
- Try clearing your browser cache
- Check if you're in incognito/private mode (sessions work differently)

### "Stripe price ID not configured"

**Solution**: Make sure you've created products in Stripe and copied the price IDs.

- Verify price IDs are in `config.php` (not `config.local.php`)
- Check that you're using PRICE ID (starts with `price_`), not product ID (starts with `prod_`)
- Ensure price IDs are for recurring products, not one-time payments

### Payment Succeeds But Subscription Not Showing

**Solution**: Check webhook setup.

- For local: Make sure Stripe CLI is running (`stripe listen...`)
- Check `webhook_events` table in database for errors
- Verify webhook secret in `config.local.php`

---

## Production Deployment

When deploying to production:

### 1. Update Configuration

In `config.php`:
```php
define('SITE_URL', 'https://yoursaas.com'); // Your production URL
ini_set('session.cookie_secure', 1); // Enable HTTPS-only cookies
error_reporting(0);
ini_set('display_errors', '0');
```

### 2. Update Google OAuth

- Add production URLs to Google Console:
  - Authorized JavaScript origins: `https://yoursaas.com`
  - Authorized redirect URIs: `https://yoursaas.com/auth/google-callback.php`
- Publish OAuth consent screen (to remove "unverified app" warning)

### 3. Update Stripe Configuration

In `config.local.php`, replace test keys with live keys:
```php
define('STRIPE_PUBLISHABLE_KEY', 'pk_live_YOUR_LIVE_KEY');
define('STRIPE_SECRET_KEY', 'sk_live_YOUR_LIVE_KEY');
```

- Create production products in Stripe Dashboard (live mode)
- Update price IDs in `config.php` with production price IDs
- Set up production webhook: Dashboard → Webhooks → Add endpoint
  - URL: `https://yourdomain.com/webhooks/stripe.php`
  - Events: Select all `customer.subscription.*` and `invoice.*` events
  - Copy webhook secret to `config.local.php`

### 4. Security Checklist

- [ ] HTTPS enabled
- [ ] Secure cookies enabled
- [ ] Error display disabled
- [ ] Using production API keys (not test keys)
- [ ] Webhook signature verification enabled
- [ ] `config.local.php` not in version control
- [ ] Database file not publicly accessible
- [ ] File permissions set correctly

---

## Resources

- [Google OAuth Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Stripe Documentation](https://stripe.com/docs)
- [Stripe Testing Guide](https://stripe.com/docs/testing)

---

**That's it!** Once Google OAuth is configured, your authentication system is ready to use. 🚀
