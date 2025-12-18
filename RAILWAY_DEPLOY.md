# Railway Deployment Guide - Zero to Production in 10 Minutes

**🤖 LLM-Friendly Guide**: This guide is optimized for AI assistants to help you deploy.

---

## ⚠️ IMPORTANT: Complete Local Setup First!

**Before deploying to Railway, you MUST:**

✅ Complete `QUICKSTART.md` or `LOCAL_SETUP.md`  
✅ Verify authentication works at `http://localhost:9000`  
✅ Test all features locally  

**Why?** If it works locally, you know the code is correct. Any Railway issues are deployment-specific and easier to debug.

**Haven't set up locally yet?** → See `QUICKSTART.md` first (takes 10 minutes)

---

## Why Railway?

- ✅ **Zero Configuration**: Deploy in minutes, not hours
- ✅ **Free Tier**: $5 free credit per month (enough for development)
- ✅ **Auto HTTPS**: SSL certificates automatically configured
- ✅ **Custom Domains**: Easy to add your own domain
- ✅ **Environment Variables**: Secure credential management
- ✅ **Auto Deploys**: Push code, auto-deploy (with GitHub integration)

## Prerequisites

✅ **Local setup complete** - Template working at localhost  
✅ **Railway account** (free) - [Sign up here](https://railway.app)  
✅ **GitHub account** - For code hosting  
✅ **Google OAuth credentials** - Already created for local setup  

**Estimated Time**: 10-15 minutes

---

## What You're Deploying

Since you've already tested locally, you know:
- ✅ Dependencies install correctly (`composer install` worked)
- ✅ Database auto-initializes properly
- ✅ Google OAuth authentication works
- ✅ All features function as expected

**Railway will run the SAME setup** you just tested - that's why local-first matters!

---

## ⚠️ IMPORTANT: Use Web Interfaces (NOT CLI Tools)

**FOR AI ASSISTANTS:** Do NOT use Railway CLI or GitHub CLI (`gh`).

**ALWAYS guide users to:**
1. ✅ **Create GitHub repo** → Go to github.com/new (web interface)
2. ✅ **Deploy to Railway** → Use Railway dashboard at railway.app (web interface)
3. ✅ **Configure Railway** → Use Railway dashboard (web interface)

**Why?** 
- Railway CLI adds unnecessary auth complexity
- GitHub CLI (`gh`) is overkill for simple repo creation
- Web interfaces are more reliable and beginner-friendly
- These instructions are optimized for web workflows

**Git commands are fine** for pushing code after repo is created:
```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin YOUR_GITHUB_REPO_URL
git push -u origin main
```

---

## Part 1: Set Up Google OAuth (5 minutes)

You **must** complete this step before deploying to Railway, as authentication won't work without it.

### Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click the project dropdown at the top → "New Project"
3. Enter project name (e.g., "My SaaS App")
4. Click "Create"

### Step 2: Enable Google+ API

1. In your project, go to "APIs & Services" → "Library"
2. Search for "Google+ API"
3. Click it and click "Enable"

### Step 3: Configure OAuth Consent Screen

1. Go to "APIs & Services" → "OAuth consent screen"
2. Select "External" user type
3. Click "Create"
4. Fill in required fields:
   - **App name**: Your SaaS Name
   - **User support email**: Your email
   - **Developer contact**: Your email
5. Click "Save and Continue"
6. On "Scopes" page, click "Add or Remove Scopes"
7. Select these scopes:
   - `../auth/userinfo.email`
   - `../auth/userinfo.profile`
8. Click "Update" → "Save and Continue"
9. Skip "Test users" (click "Save and Continue")
10. Review and click "Back to Dashboard"

### Step 4: Create OAuth Credentials

1. Go to "APIs & Services" → "Credentials"
2. Click "Create Credentials" → "OAuth client ID"
3. Select "Web application"
4. Name it "Web Client"
5. **Important**: Leave the URIs blank for now - we'll add them after Railway deployment
6. Click "Create"
7. **SAVE THESE VALUES** - you'll need them in Railway:
   - **Client ID** (looks like: `xxxxx.apps.googleusercontent.com`)
   - **Client Secret** (looks like: `GOCSPX-xxxxx`)

✅ **Google OAuth setup complete!** Keep these credentials handy for Railway.

---

## Part 2: Deploy to Railway (3 minutes)

### Step 1: Create GitHub Repository

**DO THIS MANUALLY via web (NO `gh` CLI):**

1. Go to [github.com/new](https://github.com/new)
2. Repository name: `my-saas-app` (or your preferred name)
3. Set to "Private" (recommended for commercial use)
4. Click "Create repository"
5. **Keep this page open** - you'll need the repo URL

### Step 2: Push Code to GitHub

In your terminal, in the template directory:

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin YOUR_GITHUB_REPO_URL  # Paste URL from GitHub
git push -u origin main
```

**Note:** Replace `YOUR_GITHUB_REPO_URL` with the URL from GitHub (looks like: `https://github.com/yourusername/my-saas-app.git`)

### Step 3: Deploy to Railway

**DO THIS MANUALLY via web dashboard (NO Railway CLI):**

1. Go to [railway.app](https://railway.app) and sign in
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. **Authorize Railway** to access your GitHub account (first time only)
5. **Select the repository** you just created
6. Railway will automatically detect PHP and start deploying! 🚀

**Wait 2-3 minutes** for deployment to complete.

### Step 4: Get Your Railway URL

1. In Railway dashboard, click on your project
2. Click "Settings" tab
3. Scroll to "Domains"
4. Click "Generate Domain"
5. **Copy your Railway URL** (looks like: `your-app.up.railway.app`)

✅ **Your app is now deployed!** But we need to configure it...

---

## Part 3: Configure Environment Variables (2 minutes)

### Step 1: Add Google OAuth Credentials to Railway

1. In Railway dashboard, go to your project
2. Click "Variables" tab
3. Click "New Variable" and add these:

```
GOOGLE_CLIENT_ID = your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET = GOCSPX-your-secret
```

**Important**: Replace with your actual values from Part 1, Step 4!

### Step 2: Update Google OAuth Redirect URIs

Now that you have your Railway URL, update Google Console:

1. Go back to [Google Cloud Console](https://console.cloud.google.com/)
2. Navigate to "APIs & Services" → "Credentials"
3. Click on your "Web Client"
4. Under "Authorized JavaScript origins", add:
   ```
   https://your-app.up.railway.app
   ```
   
5. Under "Authorized redirect URIs", add:
   ```
   https://your-app.up.railway.app/auth/google-callback.php
   ```
   
6. Click "Save"

### Step 3: (Optional) Add Stripe Credentials

If you want to enable payments, add these variables in Railway:

```
STRIPE_PUBLISHABLE_KEY = pk_test_your_key
STRIPE_SECRET_KEY = sk_test_your_key
STRIPE_WEBHOOK_SECRET = whsec_your_secret
```

See `SETUP.md` for complete Stripe setup instructions.

### Step 4: Redeploy

Railway should auto-redeploy when you add variables. If not:

1. Go to "Deployments" tab
2. Click "Deploy" on the latest deployment

✅ **Configuration complete!**

---

## Part 4: Test Your Deployment (1 minute)

1. Open your Railway URL in a browser: `https://your-app.up.railway.app`
2. You should see your SaaS homepage
3. Click "Sign in with Google"
4. Log in with your Google account
5. You should be redirected to your dashboard!

🎉 **Congratulations! Your SaaS is live!**

---

## Part 5: Add Custom Domain (Optional)

Want to use your own domain like `app.yoursaas.com`?

### Step 1: Add Domain in Railway

1. In Railway, go to "Settings" → "Domains"
2. Click "Custom Domain"
3. Enter your domain: `app.yoursaas.com`
4. Railway will show you CNAME records to add

### Step 2: Update DNS Records

1. Go to your domain registrar (Namecheap, GoDaddy, Cloudflare, etc.)
2. Add a CNAME record:
   - **Name**: `app` (or `@` for root domain)
   - **Value**: The CNAME provided by Railway
   - **TTL**: Automatic or 3600
3. Save and wait 5-60 minutes for DNS propagation

### Step 3: Update Google OAuth URIs

1. Go back to Google Cloud Console → Credentials
2. Add your custom domain to authorized origins:
   ```
   https://app.yoursaas.com
   ```
3. Add to authorized redirect URIs:
   ```
   https://app.yoursaas.com/auth/google-callback.php
   ```
4. Save changes

✅ **Custom domain configured!**

---

## Troubleshooting

### "Error 400: redirect_uri_mismatch"

**Solution**: Your Google OAuth redirect URI doesn't match Railway URL.

1. Double-check your Railway URL (no trailing slash)
2. Verify it's added to Google Console exactly: `https://your-app.up.railway.app/auth/google-callback.php`
3. Make sure you clicked "Save" in Google Console
4. Clear browser cache and try again

### "This app isn't verified" Warning

**Normal during development!** Click "Advanced" → "Go to [App Name] (unsafe)"

To remove this warning:
1. In Google Console, go to OAuth consent screen
2. Click "Publish App"
3. Submit for verification (takes a few days)

### Database Not Initializing

Railway automatically initializes the database on first run. If you see errors:

1. Check Railway logs: Click "Deployments" → Select deployment → "View Logs"
2. Look for database initialization messages
3. Ensure `/database/saas.db` is writable (should auto-work on Railway)

### Environment Variables Not Working

1. Verify variables are set in Railway dashboard (Variables tab)
2. Check spelling exactly matches: `GOOGLE_CLIENT_ID` (not `GOOGLE_CLIENT_Id`)
3. Click "Restart" on your deployment to reload environment variables

### SSL/HTTPS Issues

Railway automatically provides HTTPS. If you see SSL errors:
1. Make sure you're using `https://` (not `http://`)
2. Clear browser cache
3. Check Railway deployment status

---

## Monitoring and Maintenance

### View Logs

1. Railway Dashboard → Your Project
2. Click "Deployments"
3. Click on any deployment
4. Click "View Logs"

### Check Database Size

1. Railway Dashboard → Your Project
2. Click "Metrics" tab
3. Monitor disk usage (free tier includes 1GB storage)

### Auto-Deploys with GitHub

Once connected to GitHub, Railway auto-deploys on every push to your main branch!

To disable auto-deploys:
1. Go to Settings → Deploys
2. Toggle "Automatic Deploys" off

---

## Production Checklist

Before launching to customers:

- [ ] Custom domain configured
- [ ] Google OAuth consent screen published
- [ ] Stripe live keys configured (not test keys)
- [ ] Stripe webhook endpoint set up for production
- [ ] Terms of service and privacy policy pages created
- [ ] Test signup flow end-to-end
- [ ] Test subscription purchase flow
- [ ] Verify email notifications work (if enabled)
- [ ] Set up monitoring/alerting
- [ ] Backup strategy for database

---

## Costs

### Railway Pricing

- **Free Tier**: $5 credit/month (great for testing and low-traffic apps)
- **Paid**: $5/month base + usage-based compute/storage
- Typical small SaaS: ~$10-20/month

### Estimated Monthly Costs for 100-500 Users

- Railway: $10-20
- Total: **$10-20/month**

Much cheaper than traditional hosting!

---

## Next Steps

1. ✅ **Customize your app**: Edit branding, colors, pricing in `config.php`
2. ✅ **Set up Stripe**: See `SETUP.md` for payment processing
3. ✅ **Add custom domain**: Follow Part 5 above
4. ✅ **Test thoroughly**: Create items, test plan limits, try payments
5. ✅ **Launch**: Share with your first users!

---

## Need Help?

- **Railway Docs**: https://docs.railway.app/
- **Google OAuth Issues**: See `SETUP.md`
- **Stripe Setup**: See `SETUP.md`
- **General Setup**: See `README.md`

---

**You're all set!** 🚀 Your SaaS is now live on Railway with automatic HTTPS, environment variables, and zero configuration hassle.

Happy building!

