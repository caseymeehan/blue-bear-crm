# Quick Start - Get Running in 10 Minutes

**👋 New to this? You can paste this entire file into an AI assistant (Claude, ChatGPT, Cursor) and ask them to guide you through the setup!**

---

## Prerequisites

Before starting, ensure you have:
- **PHP 8.1+** installed on your computer
- **Composer** (PHP dependency manager) installed
- A **Google account** for OAuth setup
- A web browser

### Check if you have PHP and Composer:
```bash
php --version
composer --version
```

If either command fails, you need to install them first. Ask your AI assistant: "How do I install PHP 8.1 and Composer on [your operating system]?"

---

## 5-Step Setup

### Step 1: Install Dependencies (2 minutes)

Open your terminal, navigate to this directory, and run:

```bash
composer install
```

This installs all required packages. You should see a success message.

---

### Step 2: Set Up Google OAuth (5 minutes)

You need Google OAuth credentials to enable "Sign in with Google":

1. Go to: https://console.cloud.google.com/apis/credentials
2. Create a new project (or select existing)
3. Click **"Create Credentials"** → **"OAuth 2.0 Client ID"**
4. Configure consent screen if prompted (use "External" for testing)
5. Application type: **Web application**
6. Add these authorized redirect URIs:
   ```
   http://localhost:9000/auth/google-callback.php
   ```
7. Click **Create** and copy your:
   - Client ID (looks like: `xxxxx.apps.googleusercontent.com`)
   - Client Secret (looks like: `GOCSPX-xxxxx`)

---

### Step 3: Configure Local Settings (1 minute)

Create your local configuration file:

```bash
cp config.local.example.php config.local.php
```

Now edit `config.local.php` and add your Google credentials:

```php
define('GOOGLE_CLIENT_ID', 'YOUR_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'GOCSPX-YOUR_CLIENT_SECRET');
```

**💡 Tip:** Ask your AI assistant: "Help me edit config.local.php with my credentials"

---

### Step 4: Start Local Server (1 minute)

Start the PHP development server:

```bash
php -S localhost:9000
```

You should see: `PHP 8.x.x Development Server (http://localhost:9000) started`

**Keep this terminal window open!**

---

### Step 5: Test It Works! (1 minute)

1. Open your browser: **http://localhost:9000**
2. Click **"Sign in with Google"**
3. Complete the OAuth flow
4. You should be logged in and see your dashboard! 🎉

Try creating an item to test full functionality.

---

## ✅ Success! What's Next?

Your template is working locally! Now you can:

1. **Customize it** - Modify the code to fit your needs
2. **Deploy to production** - See `RAILWAY_DEPLOY.md` for deployment guide
3. **Set up Stripe** - See `SETUP.md` for payment processing

---

## 🆘 Troubleshooting

### "composer: command not found"
You need to install Composer. Ask your AI: "How do I install Composer?"

### "redirect_uri_mismatch" error
Make sure you added `http://localhost:9000/auth/google-callback.php` to your Google OAuth redirect URIs.

### Port 9000 already in use
Change the port: `php -S localhost:9001` (remember to update Google OAuth redirect URIs)

### Still stuck?
Paste this entire file + your error message into an AI assistant and ask for help!

---

## 🤖 AI Assistant Prompt

If you're using an AI assistant, try this prompt:

```
I just purchased a PHP SaaS template and I'm following QUICKSTART.md. 
I'm currently at [step X] and [describe your situation/error]. 
Can you help me troubleshoot?
```

---

**Next:** Once everything works locally, see `LOCAL_SETUP.md` for detailed documentation and `RAILWAY_DEPLOY.md` for production deployment.
