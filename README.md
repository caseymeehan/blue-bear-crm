# PHP SaaS Template 🚀

A modern, simple, and powerful SaaS template built with PHP and SQLite.

> **👋 Just purchased?** Start here: [QUICKSTART.md](QUICKSTART.md) (10 minutes)
> 
> **🤖 Using an AI assistant?** This template is optimized for LLM-guided setup. Paste any `.md` file into Claude, ChatGPT, or Cursor for step-by-step guidance!

## 🚀 Quick Start (Recommended Path)

**We strongly recommend testing locally first:**

1. **[QUICKSTART.md](QUICKSTART.md)** ← Start here! (10 min)
   - Install dependencies
   - Set up Google OAuth
   - Test locally at `http://localhost:9000`
   - Verify everything works

2. **[RAILWAY_DEPLOY.md](RAILWAY_DEPLOY.md)** ← Deploy to production (10 min)
   - After local setup works
   - Push to GitHub
   - Deploy to Railway
   - Go live!

**Why local first?** You'll catch issues immediately, test with real credentials, and deploy with confidence knowing it works.

---

## ⚖️ License

**This is a commercial product with unlimited project use.** By purchasing this template, you receive a license to use it for unlimited commercial projects. You may modify the code freely for your own use, but you may not resell, redistribute, or share the template source code with others. See `LICENSE` file for complete terms.

---

## Features ✨

- **Modern Design**: Beautiful, responsive homepage with hero section
- **Simple Stack**: Pure PHP with SQLite - no complex dependencies
- **Google OAuth Authentication**: One-click sign-in with Google (no passwords!)
- **User Dashboard**: Personalized dashboard for authenticated users
- **Items CRUD**: Full create, read, update, delete functionality
- **Stripe Integration**: Complete subscription and payment processing
- **Feature Gating**: Plan-based limits (Free: 5 items, Pro: 50, Enterprise: unlimited)
- **Usage Tracking**: Real-time usage display with progress bars
- **Subscription Management**: Upgrade, downgrade, cancel subscriptions
- **Database Ready**: SQLite database with proper schema
- **Secure Session Management**: Database-backed session handling with token authentication
- **Activity Logging**: Track user actions and events
- **Webhook Handling**: Automated sync with Stripe events
- **Responsive**: Mobile-first design that works on all devices
- **Fast & Lightweight**: Minimal dependencies, maximum performance

## Quick Start 🏁

### 🚀 Recommended: Deploy to Railway (10 minutes)

**Perfect for beginners!** Railway provides automatic deployment with zero configuration.

1. **Set up Google OAuth credentials** (5 minutes)
   - See `RAILWAY_DEPLOY.md` Part 1 for step-by-step instructions
   - You'll need a Google Cloud account (free)

2. **Deploy to Railway** (3 minutes)
   - See `RAILWAY_DEPLOY.md` Part 2
   - Push to GitHub or use Railway CLI
   - Railway automatically handles everything!

3. **Configure environment variables** (2 minutes)
   - Add your Google OAuth credentials in Railway dashboard
   - See `RAILWAY_DEPLOY.md` Part 3

**That's it!** Your SaaS is live with HTTPS at `your-app.up.railway.app`

📖 **Complete Railway Guide**: See `RAILWAY_DEPLOY.md`

---

### 🖥️ Alternative: Local Development Setup

For advanced users who want to develop locally:

#### Prerequisites

- PHP 7.4 or higher
- SQLite3 extension enabled
- Composer (for dependency management)
- A Google Cloud account (for OAuth)
- A web server (Apache, Nginx, or PHP built-in server)

#### Installation Steps

1. **Extract the template files**

2. **Install dependencies** (already included in vendor/ folder)
   ```bash
   # Optional - dependencies are pre-included
   composer install
   ```

3. **Create local configuration**
   ```bash
   # Copy the example file
   cp config.local.example.php config.local.php
   ```
   
   Edit `config.local.php` and add your credentials:
   - Google Client ID and Secret
   - (Optional) Stripe API keys

4. **Initialize the database**
   ```bash
   php database/init.php
   php database/migrate_google_oauth.php
   php database/migrate_items.php
   php database/migrate_stripe.php
   ```

5. **Set up Google OAuth** (required)
   
   Follow the detailed guide in `SETUP.md` to:
   - Create a Google Cloud project
   - Configure OAuth consent screen
   - Get your Client ID and Client Secret
   - Add to your `config.local.php`

6. **Start the server**
   ```bash
   php -S localhost:9000
   ```

7. **Open your browser**
   ```
   http://localhost:9000
   ```

8. **Sign in with Google**
   
   Click "Sign in with Google" and authenticate. Your account will be created automatically!

📖 **Complete Local Setup Guide**: See `SETUP.md`

## Project Structure 📁

```
PHP SaaS Template/
├── index.php                     # Homepage
├── pricing.php                   # Pricing page with 3 tiers
├── config.php                    # Configuration settings
├── config.local.php              # Local config (gitignored)
├── composer.json                 # Dependency management
├── README.md                     # Main documentation
├── SETUP.md                     # Google OAuth setup guide
├── STRIPE_SETUP.md              # Stripe integration guide
├── STRIPE_TESTING.md            # Stripe testing guide
├── .gitignore                   # Git ignore rules
├── auth/
│   ├── google-login.php         # Initiate Google OAuth
│   ├── google-callback.php      # OAuth callback handler
│   └── logout.php               # Logout handler
├── checkout/
│   ├── create-session.php       # Create Stripe checkout session
│   ├── success.php              # Payment success page
│   └── cancel.php               # Payment cancelled page
├── webhooks/
│   └── stripe.php               # Stripe webhook handler
├── dashboard/
│   ├── index.php                # Items dashboard
│   ├── item-new.php             # Create new item
│   ├── item-edit.php            # Edit item
│   ├── item-actions.php         # Item CRUD actions
│   └── profile.php              # User profile & subscription management
├── includes/
│   ├── Auth.php                 # Authentication class
│   ├── GoogleOAuth.php          # Google OAuth handler
│   ├── Items.php                # Items CRUD class
│   ├── Subscription.php         # Subscription management class
│   └── helpers.php              # Helper functions
├── assets/
│   ├── css/
│   │   └── style.css            # Main stylesheet
│   ├── js/
│   │   └── main.js              # JavaScript functionality
│   └── images/                  # Your image assets
├── database/
│   ├── init.php                 # Database initialization
│   ├── migrate_google_oauth.php # OAuth migration
│   ├── migrate_items.php        # Items table migration
│   ├── migrate_stripe.php       # Stripe tables migration
│   ├── Database.php             # Database class
│   └── saas.db                  # SQLite database
├── uploads/
│   └── avatars/                 # User avatar uploads
└── vendor/                      # Composer dependencies
```

## Database Schema 🗄️

### Users Table
- User authentication and profile information
- Fields: id, username, email, password_hash (nullable), full_name, avatar_url, google_id, oauth_provider, created_at, etc.

### Sessions Table
- Manage user sessions securely
- Fields: id, user_id, session_token, ip_address, user_agent, expires_at

### Items Table
- Store user items (your main SaaS feature)
- Fields: id, user_id, title, description, created_at, updated_at

### Subscriptions Table
- Handle user subscriptions and billing
- Fields: id, user_id, plan_name, status, amount, currency, billing_cycle, stripe_customer_id, stripe_subscription_id, stripe_price_id, current_period_start, current_period_end

### Invoices Table
- Track payment history
- Fields: id, user_id, stripe_invoice_id, amount, currency, status, invoice_pdf, hosted_invoice_url, period_start, period_end, paid_at

### Payment Methods Table
- Store customer payment methods
- Fields: id, user_id, stripe_payment_method_id, type, card_brand, card_last4, card_exp_month, card_exp_year

### Webhook Events Table
- Log all Stripe webhook events for debugging
- Fields: id, stripe_event_id, event_type, payload, processed, error_message, created_at, processed_at

### Activity Log Table
- Track user actions and events
- Fields: id, user_id, action, description, ip_address, created_at

### Password Resets Table
- Manage password reset tokens
- Fields: id, user_id, token, created_at, expires_at, used

## Configuration ⚙️

Edit `config.php` to customize:

```php
// Site settings
define('SITE_NAME', 'YourSaaS');
define('SITE_URL', 'http://localhost:9000');
define('SITE_EMAIL', 'hello@yoursaas.com');

// Google OAuth (REQUIRED - see SETUP.md)
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');

// Security
define('SESSION_LIFETIME', 86400); // 24 hours
define('ENABLE_REGISTRATION', true);
```

**Important**: You must set up Google OAuth credentials before the authentication will work. See `SETUP.md` for detailed instructions.

## Stripe Setup 💳

**Good news:** The template works WITHOUT Stripe! Feature gating and limits work immediately out of the box.

To enable actual payments (optional):

### Quick Setup (15 minutes)

1. **Sign up for Stripe** (free)
   - Go to: https://stripe.com
   - Create account - takes 2 minutes
   - Use test mode for development

2. **Get Your API Keys**
   - Go to: https://dashboard.stripe.com/test/apikeys
   - Copy your **Publishable key** (starts with `pk_test_`)
   - Copy your **Secret key** (starts with `sk_test_`)

3. **Add Keys to `config.local.php`**
   ```php
   // Replace the placeholders:
   define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_ACTUAL_KEY');
   define('STRIPE_SECRET_KEY', 'sk_test_YOUR_ACTUAL_KEY');
   ```

4. **Create Products in Stripe Dashboard**
   - Go to: https://dashboard.stripe.com/test/products
   - Create "Pro Plan": $29/month recurring
   - Create "Enterprise Plan": $99/month recurring
   - Copy each **Price ID** (starts with `price_`)

5. **Update Price IDs in `config.php`**
   ```php
   // In the PRICING_PLANS array:
   'stripe_price_id' => 'price_YOUR_ACTUAL_PRO_PRICE_ID',    // Pro
   'stripe_price_id' => 'price_YOUR_ACTUAL_ENTERPRISE_PRICE_ID', // Enterprise
   ```

6. **Test with Test Cards**
   - Card: `4242 4242 4242 4242`
   - Expiry: Any future date
   - CVC: Any 3 digits

**That's it!** See `STRIPE_SETUP.md` for detailed instructions and webhook setup.

### How It Works

**Without Stripe configured:**
- ✅ Pricing page displays
- ✅ Feature limits enforced (5 items for free, etc.)
- ✅ Upgrade prompts appear
- ❌ Payment buttons show "not configured" message

**With Stripe configured:**
- ✅ Everything above PLUS
- ✅ Users can actually pay
- ✅ Automatic subscription management
- ✅ Webhook sync with Stripe
- ✅ Invoice tracking

## Current Status 📊

### ✅ Milestone 1: Authentication & Basic Dashboard - COMPLETE

- **Google OAuth Authentication**: One-click sign-in with Google
- **User Management**: Automatic user creation and profile updates
- **Session Management**: Secure database-backed sessions with tokens
- **Activity Logging**: Track user login/logout events
- **Basic Dashboard**: Personalized dashboard for authenticated users
- **Security**: CSRF protection, XSS prevention, secure session handling

### ✅ Milestone 2: Items CRUD Dashboard - COMPLETE

- **Items Management**: Full CRUD operations (Create, Read, Update, Delete)
- **Modern Dashboard**: Clean, responsive layout with item cards
- **Profile Management**: Edit profile, upload avatar
- **Item Actions**: Edit, duplicate, delete with confirmation
- **Empty States**: Beautiful empty state when no items exist

### ✅ Milestone 3: Stripe Integration - COMPLETE

- **Pricing Page**: Beautiful 3-tier pricing display (Free, Pro, Enterprise)
- **Stripe Checkout**: Seamless payment flow with Stripe Checkout
- **Subscription Management**: View, cancel, and reactivate subscriptions
- **Feature Gating**: Item limits enforced by plan (5/50/unlimited)
- **Usage Tracking**: Real-time usage widget with progress bars
- **Billing Page**: View subscription details and payment history
- **Webhook Handler**: Automated sync with Stripe events
- **Invoice History**: View all payment records
- **Plan Upgrades**: Seamless plan switching with proration

### 📋 Future Enhancements (Optional)

- Email notifications for payment events
- Team collaboration features
- Advanced analytics dashboard
- API access for enterprise customers
- Custom branding options

## Getting Started 🛠️

### 🚀 Fastest Route: Railway Deployment (10 minutes)

**Recommended for beginners** - Zero configuration, auto HTTPS, production-ready instantly!

See **[RAILWAY_DEPLOY.md](RAILWAY_DEPLOY.md)** for the complete guide.

### 🖥️ Local Development (20 minutes)

**For advanced users** who want to customize locally before deploying:

1. **Create Local Config**: Copy `config.local.example.php` to `config.local.php`
2. **Setup Database**: Run all migration scripts (or Railway auto-initializes)
3. **Configure Google OAuth**: See `SETUP.md` for step-by-step guide
4. **Optional: Add Stripe**: Enable subscription payments
5. **Start Building**: Customize for your specific use case

For complete local setup, see **[SETUP.md](SETUP.md)**

## Customization 🎨

### Quick Customizations (5 minutes)

**1. Update Site Name & Branding**
- Edit `config.php`: Change `SITE_NAME`, `SITE_URL`, `SITE_EMAIL`
- Replace `hero.jpg` with your own background image (2400×750px recommended)

**2. Customize Homepage Content** (`index.php`)
- Main headline and tagline
- Three benefit bullets
- Call-to-action button text
- Signup form text

**3. Change Colors** (`assets/css/style.css`)
```css
/* Primary CTA Button */
.btn-cta {
    background: linear-gradient(135deg, #ff6b6b 0%, #f06595 100%);
}

/* Popular alternatives: */
/* Blue: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
/* Green: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); */
/* Purple: linear-gradient(135deg, #a855f7 0%, #ec4899 100%); */
```

**4. Adjust Pricing & Limits** (`config.php`)
```php
define('PRICING_PLANS', [
    'free' => ['price' => 0, 'item_limit' => 5],
    'pro' => ['price' => 29, 'item_limit' => 50],
    'enterprise' => ['price' => 99, 'item_limit' => null]
]);
```

**5. Replace "Items" with Your Feature**
- Update database schema for your data model
- Modify `includes/Items.php` with your business logic
- Update UI terminology throughout the app

## Security Notes 🔒

### Implemented Security Features

This template includes common security patterns:

- ✅ **SQL Injection Prevention**: Prepared statements used throughout
- ✅ **XSS Protection**: Output sanitized with `htmlspecialchars()`
- ✅ **Secure Sessions**: HTTP-only cookies, database-backed sessions
- ✅ **PCI Compliance**: All payments handled by Stripe (no card data stored)
- ✅ **Webhook Verification**: Signature validation on Stripe webhooks

### Important: Your Security Responsibility

This template implements common security best practices, but **you are responsible for**:

- Reviewing and testing code for your specific security requirements
- Conducting security audits before production deployment
- Keeping dependencies updated (run `composer update` regularly)
- Monitoring for security vulnerabilities in your deployment
- Implementing additional security measures as needed for your use case

**No software is 100% secure.** This template is provided "AS-IS" per the LICENSE agreement. Always perform your own security review and testing before deploying to production with real users.

### Important: Protect Your Credentials
- **Never commit** `config.local.php` to version control (already in `.gitignore`)
- **Rotate credentials** if ever accidentally exposed
- **Use environment variables** in production deployments
- **Enable HTTPS** before going live (set `session.cookie_secure` to `1` in `config.php`)

### Production Checklist
- [ ] Disable error display: `error_reporting(0);` and `ini_set('display_errors', '0');`
- [ ] Enable HTTPS and secure cookies
- [ ] Use production Stripe keys (not test keys)
- [ ] Set up webhook signature verification
- [ ] Monitor error logs regularly

## Authentication Usage 🔐

### Using the Auth Class

```php
require_once 'config.php';
require_once 'includes/Auth.php';

$auth = new Auth();

// Check if user is logged in
if ($auth->isLoggedIn()) {
    // Get current user data
    $user = $auth->getCurrentUser();
    echo "Welcome, " . $user['full_name'];
}

// Protect a page (requires authentication)
$auth->requireAuth();

// Get the Google OAuth URL
$googleOAuth = $auth->getGoogleOAuth();
$authUrl = $googleOAuth->getAuthUrl();

// Logout
$auth->logout();
```

### Using the Database Class

```php
require_once 'config.php';
require_once 'database/Database.php';

$db = Database::getInstance();

// Fetch user data
$user = $db->fetchOne('SELECT * FROM users WHERE id = :id', [
    'id' => 1
]);

// Insert data
$id = $db->insert('activity_log', [
    'user_id' => 1,
    'action' => 'page_view',
    'description' => 'Viewed pricing page'
]);

// Update data
$db->update('users', 
    ['full_name' => 'John Doe'],
    'id = :id',
    ['id' => 1]
);
```

## Technologies Used 🔧

- **PHP 7.4+**: Server-side logic
- **Composer**: Dependency management
- **Google API Client**: OAuth 2.0 authentication
- **SQLite**: Lightweight database
- **Stripe PHP SDK**: Payment processing (Milestone 3)
- **CSS3**: Modern styling with CSS Grid and Flexbox
- **JavaScript**: Interactive features
- **SVG**: Scalable icons

## License 📄

**Commercial License - Unlimited Projects** - This template is licensed for unlimited commercial projects. You may modify the code for your own use, but may not resell, redistribute, or share the template source code with others. See `LICENSE` file for complete terms.

## Support 💬

For questions or issues, please create an issue in the repository.

---

**Built with ❤️ for the indie maker community**

Happy building! 🚀

