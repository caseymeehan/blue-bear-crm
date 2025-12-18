<?php
/**
 * Pricing Page
 * Display subscription plans and pricing information
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Subscription.php';

$auth = new Auth();
$user = $auth->getCurrentUser();

// Get current subscription if logged in
$currentPlan = 'free';
if ($user) {
    $subscriptionManager = new Subscription($user['id']);
    $subscription = $subscriptionManager->getCurrentSubscription();
    $currentPlan = $subscription ? $subscription['plan_name'] : 'free';
}

$pageTitle = 'Pricing';
$plans = PRICING_PLANS;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f9fafb;
            color: #1f2937;
            line-height: 1.6;
        }

        /* Navigation */
        .top-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 2rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .site-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #6366f1;
            text-decoration: none;
            display: flex;
        }

        .site-logo img {
            width: 32px;
            height: 32px;
            object-fit: contain;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: #4b5563;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: #6366f1;
        }

        .nav-links a.active {
            color: #6366f1;
        }

        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.25rem;
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Pricing Container */
        .pricing-container {
            max-width: 1200px;
            margin: -3rem auto 4rem;
            padding: 0 2rem;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        /* Pricing Card */
        .pricing-card {
            background: white;
            border-radius: 1rem;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            border: 2px solid transparent;
        }

        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .pricing-card.featured {
            border-color: #6366f1;
            transform: scale(1.05);
        }

        .pricing-card.featured:hover {
            transform: scale(1.05) translateY(-5px);
        }

        .featured-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #6366f1;
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .current-plan-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #10b981;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .plan-price {
            font-size: 3rem;
            font-weight: 800;
            color: #6366f1;
            margin-bottom: 0.25rem;
        }

        .plan-price small {
            font-size: 1.25rem;
            color: #6b7280;
            font-weight: 400;
        }

        .plan-billing {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 2rem;
        }

        .plan-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .plan-features li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .plan-features li:last-child {
            border-bottom: none;
        }

        .plan-features li::before {
            content: "✓";
            color: #10b981;
            font-weight: bold;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .plan-cta {
            width: 100%;
            padding: 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 1rem;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: all 0.2s;
            border: 2px solid;
        }

        .plan-cta.primary {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }

        .plan-cta.primary:hover {
            background: #4f46e5;
            border-color: #4f46e5;
        }

        .plan-cta.secondary {
            background: white;
            color: #6366f1;
            border-color: #6366f1;
        }

        .plan-cta.secondary:hover {
            background: #f9fafb;
        }

        .plan-cta.current {
            background: #f3f4f6;
            color: #6b7280;
            border-color: #e5e7eb;
            cursor: default;
        }

        /* FAQ Section */
        .faq-section {
            max-width: 800px;
            margin: 4rem auto;
            padding: 0 2rem;
        }

        .faq-section h2 {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 2rem;
            color: #1f2937;
        }

        .faq-item {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .faq-item h3 {
            color: #1f2937;
            margin-bottom: 0.5rem;
            font-size: 1.125rem;
        }

        .faq-item p {
            color: #6b7280;
        }

        /* Footer */
        .footer {
            background: #1f2937;
            color: #9ca3af;
            padding: 2rem;
            text-align: center;
            margin-top: 4rem;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .pricing-card.featured {
                transform: scale(1);
            }

            .pricing-card.featured:hover {
                transform: translateY(-5px);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="top-header">
        <div class="header-content">
            <a href="<?php echo $user ? url('/dashboard/') : url('/'); ?>" class="site-logo">
                <img src="<?php echo url('assets/images/logo-small.png'); ?>" alt="<?php echo SITE_NAME; ?>">
                <span><?php echo SITE_NAME; ?></span>
            </a>
            <div class="nav-links">
                <?php if ($user): ?>
                    <a href="<?php echo url('dashboard/'); ?>">Dashboard</a>
                    <a href="<?php echo url('pricing.php'); ?>" class="active">Pricing</a>
                    <a href="<?php echo url('dashboard/profile.php'); ?>">Profile</a>
                <?php else: ?>
                    <a href="<?php echo url('pricing.php'); ?>" class="active">Pricing</a>
                    <a href="<?php echo url('auth/google-login.php'); ?>" class="btn btn-primary">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="hero">
        <h1>Simple, Transparent Pricing</h1>
        <p>Choose the plan that's right for you. Upgrade or downgrade at any time.</p>
    </div>

    <!-- Pricing Cards -->
    <div class="pricing-container">
        <div class="pricing-grid">
            <!-- Free Plan -->
            <div class="pricing-card <?php echo $currentPlan === 'free' ? 'current' : ''; ?>">
                <?php if ($currentPlan === 'free'): ?>
                    <div class="current-plan-badge">Current Plan</div>
                <?php endif; ?>
                
                <div class="plan-name">Free</div>
                <div class="plan-price">
                    $0
                    <small>/month</small>
                </div>
                <div class="plan-billing">Perfect for getting started</div>
                
                <ul class="plan-features">
                    <li>Up to 30 contacts</li>
                    <li>Contact management</li>
                    <li>Interaction tracking</li>
                    <li>Social stats tracking</li>
                    <li>Pipeline management</li>
                </ul>
                
                <?php if ($user && $currentPlan === 'free'): ?>
                    <a href="#" class="plan-cta current">Current Plan</a>
                <?php elseif ($user): ?>
                    <a href="<?php echo url('dashboard/profile.php'); ?>" class="plan-cta secondary">Manage Plan</a>
                <?php else: ?>
                    <a href="<?php echo url('auth/google-login.php'); ?>" class="plan-cta secondary">Get Started Free</a>
                <?php endif; ?>
            </div>

            <!-- Pro Plan -->
            <div class="pricing-card featured <?php echo $currentPlan === 'pro' ? 'current' : ''; ?>">
                <div class="featured-badge">Most Popular</div>
                <?php if ($currentPlan === 'pro'): ?>
                    <div class="current-plan-badge">Current Plan</div>
                <?php endif; ?>
                
                <div class="plan-name">Pro</div>
                <div class="plan-price">
                    $<?php echo number_format($plans['pro']['price'], 2); ?>
                    <small>/month</small>
                </div>
                <div class="plan-billing">For growing creators</div>
                
                <ul class="plan-features">
                    <li>Up to 500 contacts</li>
                    <li>All Free features</li>
                    <li>Priority support</li>
                    <li>Export to CSV/JSON</li>
                    <li>Advanced analytics</li>
                </ul>
                
                <?php if ($user && $currentPlan === 'pro'): ?>
                    <a href="#" class="plan-cta current">Current Plan</a>
                <?php elseif ($user): ?>
                    <a href="<?php echo url('checkout/create-session.php?plan=pro'); ?>" class="plan-cta primary">
                        <?php echo $currentPlan === 'free' ? 'Upgrade to Pro' : 'Switch to Pro'; ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo url('auth/google-login.php'); ?>" class="plan-cta primary">Get Started</a>
                <?php endif; ?>
            </div>

            <!-- Enterprise Plan -->
            <div class="pricing-card <?php echo $currentPlan === 'enterprise' ? 'current' : ''; ?>">
                <?php if ($currentPlan === 'enterprise'): ?>
                    <div class="current-plan-badge">Current Plan</div>
                <?php endif; ?>
                
                <div class="plan-name">Enterprise</div>
                <div class="plan-price">
                    $<?php echo $plans['enterprise']['price']; ?>
                    <small>/month</small>
                </div>
                <div class="plan-billing">For power users</div>
                
                <ul class="plan-features">
                    <li>Unlimited contacts</li>
                    <li>All Pro features</li>
                    <li>AI Features <span style="color: #6366f1; font-size: 0.75rem;">(coming soon)</span></li>
                    <li>Dedicated support</li>
                    <li>Early access to new features</li>
                </ul>
                
                <?php if ($user && $currentPlan === 'enterprise'): ?>
                    <a href="#" class="plan-cta current">Current Plan</a>
                <?php elseif ($user): ?>
                    <a href="<?php echo url('checkout/create-session.php?plan=enterprise'); ?>" class="plan-cta primary">
                        <?php echo $currentPlan === 'free' ? 'Upgrade to Enterprise' : 'Upgrade'; ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo url('auth/google-login.php'); ?>" class="plan-cta primary">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="faq-section">
        <h2>Frequently Asked Questions</h2>
        
        <div class="faq-item">
            <h3>Can I change plans at any time?</h3>
            <p>Yes! You can upgrade or downgrade your plan at any time. Changes are prorated and take effect immediately.</p>
        </div>
        
        <div class="faq-item">
            <h3>What happens if I exceed my contact limit?</h3>
            <p>You'll be prompted to upgrade to a higher tier before adding additional contacts. Your existing contacts remain safe and accessible.</p>
        </div>
        
        <div class="faq-item">
            <h3>Do you offer refunds?</h3>
            <p>Yes, we offer a 30-day money-back guarantee on all paid plans. No questions asked.</p>
        </div>
        
        <div class="faq-item">
            <h3>Is there a setup fee?</h3>
            <p>No setup fees, no hidden charges. You only pay the monthly subscription price listed above.</p>
        </div>
        
        <div class="faq-item">
            <h3>What payment methods do you accept?</h3>
            <p>We accept all major credit cards (Visa, MasterCard, American Express) through our secure Stripe payment processor.</p>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
    </div>
</body>
</html>

