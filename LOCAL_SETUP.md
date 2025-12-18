# Local Development Setup - Complete Guide

**🤖 LLM-Friendly Documentation**: This guide is structured for AI assistants to help you through each step. Feel free to paste sections into Claude, ChatGPT, or Cursor for guided assistance.

---

## Table of Contents
1. [Why Local First?](#why-local-first)
2. [System Requirements](#system-requirements)
3. [Installation Steps](#installation-steps)
4. [Google OAuth Setup](#google-oauth-setup)
5. [Testing & Verification](#testing--verification)
6. [Common Issues](#common-issues)
7. [Next Steps](#next-steps)

---

## Why Local First?

We **strongly recommend** setting up locally before deploying to production:

✅ **See it work** - Test everything before going live  
✅ **Fast debugging** - Errors appear immediately in terminal  
✅ **No deployment costs** - Test for free on your machine  
✅ **Learn the codebase** - Understand how it works  
✅ **Confidence** - Know it works before deploying  

**Most customers complete local setup in 10-15 minutes.**

---

## System Requirements

### Required Software

| Software | Version | Check Command | Install Guide |
|----------|---------|---------------|---------------|
| PHP | 8.1 or higher | `php --version` | [php.net](https://www.php.net/downloads) |
| Composer | Latest | `composer --version` | [getcomposer.org](https://getcomposer.org/download/) |
| SQLite | Built into PHP | `php -m \| grep sqlite` | Included with PHP |

### Platform-Specific Installation

**macOS:**
```bash
# PHP (via Homebrew)
brew install php@8.2

# Composer
brew install composer
```

**Windows:**
- Download PHP: https://windows.php.net/download/
- Download Composer: https://getcomposer.org/Composer-Setup.exe

**Linux (Ubuntu/Debian):**
```bash
sudo apt update
sudo apt install php8.2 php8.2-cli php8.2-sqlite3 composer
```

**💡 AI Prompt:** "I'm on [OS]. How do I install PHP 8.2 and Composer?"

---

## Installation Steps

### Step 1: Extract the Template

Extract the ZIP file you received to a convenient location:

```bash
# Example locations:
# macOS/Linux: ~/Projects/my-saas-app
# Windows: C:\Projects\my-saas-app
```

### Step 2: Navigate to Directory

Open your terminal and navigate to the extracted directory:

```bash
cd /path/to/PHP\ SaaS\ Template\ -\ v1.0
```

**💡 Tip:** You can drag the folder onto Terminal (macOS) or use `cd` with the path.

### Step 3: Install PHP Dependencies

Run Composer to install all required packages:

```bash
composer install
```

**What this does:**
- Installs Google OAuth libraries
- Installs Stripe payment libraries
- Sets up autoloading
- Installs cache dependencies

**Expected output:**
```
Loading composer repositories with package information
Installing dependencies from lock file
...
Generating autoload files
```

**⏱️ Time:** ~30-60 seconds depending on internet speed

### Step 4: Create Local Configuration

Copy the example configuration file:

```bash
cp config.local.example.php config.local.php
```

This creates your local config file (ignored by git, safe for credentials).

---

## Google OAuth Setup

### Why Google OAuth?

This template uses **Google Sign-In** for authentication. Users click "Sign in with Google" and log in with their Google account - no password management required!

### Creating OAuth Credentials

#### Step 1: Go to Google Cloud Console

Open: https://console.cloud.google.com/apis/credentials

#### Step 2: Create Project (if needed)

1. Click the project dropdown at the top
2. Click **"New Project"**
3. Name it (e.g., "My SaaS App")
4. Click **Create**

#### Step 3: Configure OAuth Consent Screen

1. In the left sidebar, click **"OAuth consent screen"**
2. Select **"External"** (for testing)
3. Click **Create**
4. Fill in required fields:
   - **App name:** Your SaaS App Name
   - **User support email:** Your email
   - **Developer contact:** Your email
5. Click **Save and Continue**
6. Skip scopes (click **Save and Continue**)
7. Add test users: Your Google email address
8. Click **Save and Continue**

#### Step 4: Create OAuth Credentials

1. Go back to **"Credentials"** in left sidebar
2. Click **"+ Create Credentials"** → **"OAuth 2.0 Client ID"**
3. Application type: **"Web application"**
4. Name: "Local Development"
5. Under **"Authorized redirect URIs"**, click **"+ Add URI"**
6. Add:
   ```
   http://localhost:9000/auth/google-callback.php
   ```
7. Click **Create**
8. **Save your credentials!**
   - Client ID: `xxxxx-xxxxx.apps.googleusercontent.com`
   - Client Secret: `GOCSPX-xxxxxxxxxxxxx`

**📸 Screenshot tip:** Take a screenshot of your credentials for reference.

#### Step 5: Add Credentials to Config

Edit `config.local.php` and replace the placeholders:

```php
// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '370540305379-xxxxx.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'GOCSPX-xxxxxxxxxxxxx');
```

**💡 AI Prompt:** "Help me edit config.local.php with my Google OAuth credentials: [paste your credentials]"

---

## Testing & Verification

### Step 1: Start PHP Development Server

In your terminal, from the template directory:

```bash
php -S localhost:9000
```

**Expected output:**
```
[Sat Oct 25 12:00:00 2025] PHP 8.2.x Development Server (http://localhost:9000) started
```

**✅ Success indicators:**
- No error messages
- Server is "started"
- Terminal stays active (doesn't exit)

**❌ If port 9000 is in use:**
```bash
# Try a different port
php -S localhost:9001

# Remember to update Google OAuth redirect URI to:
# http://localhost:9001/auth/google-callback.php
```

### Step 2: Open in Browser

Open your web browser and navigate to:
```
http://localhost:9000
```

**You should see:**
- Homepage with hero section
- "Sign in with Google" button
- Clean, modern design

**❌ If you see an error:** Check the terminal for error messages.

### Step 3: Test Authentication

1. Click **"Sign in with Google"**
2. You'll be redirected to Google
3. Choose your Google account
4. Grant permissions (if prompted)
5. You should be redirected back to the dashboard

**✅ Success indicators:**
- Welcome message with your name
- Dashboard loaded
- No errors in terminal

### Step 4: Test Core Features

Once logged in, test these features:

**Create an Item:**
1. Click **"Add New Item"**
2. Fill in title and description
3. Click **"Create Item"**
4. Should redirect to dashboard showing your item

**View Profile:**
1. Click **"Profile"** in navigation
2. Should show your Google account info
3. Avatar image (from Google)

**Test Limits (Free Tier):**
1. Create 5 items (free tier limit)
2. Try creating a 6th
3. Should see upgrade prompt

**✅ All working?** Congratulations! Your template is fully functional locally.

---

## Common Issues

### Issue: "composer: command not found"

**Cause:** Composer not installed or not in PATH

**Solution:**
```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

**AI Prompt:** "I get 'composer: command not found'. How do I install Composer on [your OS]?"

---

### Issue: "Error 400: redirect_uri_mismatch"

**Cause:** The redirect URI in Google OAuth doesn't match your server

**Solution:**
1. Check what port your server is running on (9000 or 9001?)
2. Go to Google Cloud Console → Credentials
3. Edit your OAuth client
4. Ensure redirect URI matches exactly:
   - If running on port 9000: `http://localhost:9000/auth/google-callback.php`
   - If running on port 9001: `http://localhost:9001/auth/google-callback.php`
5. Save and try again

---

### Issue: "Failed to open stream: No such file or directory"

**Cause:** Usually means `composer install` wasn't run or failed

**Solution:**
```bash
# Clean up and reinstall
rm -rf vendor/
composer install
```

---

### Issue: "Database not found or empty"

**Cause:** Database auto-initializes on first run

**Solution:** This is **normal**! You'll see this message once:
```
Database not found or empty, auto-initializing...
Database auto-initialization completed successfully
```

The database is created automatically in `database/saas.db`.

---

### Issue: "Class 'Google\Auth\Cache\MemoryCacheItemPool' not found"

**Cause:** Missing cache dependency (shouldn't happen with v1.0+)

**Solution:**
```bash
# Update composer.json to include symfony/cache
# This should already be in v1.0, but if not:
composer require symfony/cache
```

---

### Issue: Port Already in Use

**Cause:** Another service is using port 9000

**Solution:**
```bash
# Find what's using the port
lsof -i :9000

# Use a different port
php -S localhost:9001

# Update Google OAuth redirect URI to use new port!
```

---

## Next Steps

### Now That It Works Locally...

**🎨 Customize Your App**
- Modify homepage text in `index.php`
- Update pricing in `config.php`
- Add your branding and colors

**💳 Set Up Stripe (Optional)**
See `SETUP.md` for Stripe payment configuration

**🚀 Deploy to Production**
See `RAILWAY_DEPLOY.md` for Railway deployment guide

**📖 Read Full Documentation**
- `README.md` - Overview and features
- `SETUP.md` - Detailed configuration options
- `RAILWAY_DEPLOY.md` - Production deployment

---

## Development Workflow

Once you're comfortable with local setup:

1. **Make changes** - Edit PHP/CSS/JS files
2. **Refresh browser** - See changes immediately
3. **Check terminal** - Watch for errors
4. **Test features** - Ensure everything works
5. **Commit to git** - Save your changes
6. **Deploy** - Push to production

**💡 AI Prompt:** "I want to customize [feature]. Where should I start?"

---

## Getting Help

### Using AI Assistants

This template is designed to work seamlessly with AI coding assistants:

**Good prompts:**
- "Show me where user authentication is handled"
- "How do I change the pricing plans?"
- "Help me add a new feature to the dashboard"
- "I'm getting [error], what's wrong?"

**Include context:**
- File you're working on
- What you tried
- Error messages (full text)
- Your goal

### Files Reference

Quick reference for common tasks:

| Task | File | Line |
|------|------|------|
| Homepage content | `index.php` | 70-130 |
| Pricing plans | `config.php` | 87-112 |
| Authentication logic | `includes/Auth.php` | All |
| Dashboard layout | `dashboard/index.php` | All |
| Styles | `assets/css/style.css` | All |

---

## Summary

✅ **Installed:** PHP 8.1+, Composer  
✅ **Dependencies:** `composer install`  
✅ **Configuration:** `config.local.php` with OAuth credentials  
✅ **Server:** `php -S localhost:9000`  
✅ **Tested:** Authentication and core features  
✅ **Ready:** For customization and deployment  

**🎉 Congratulations!** You have a fully working local development environment.

---

**Questions?** Paste relevant sections into your AI assistant with your specific question!

