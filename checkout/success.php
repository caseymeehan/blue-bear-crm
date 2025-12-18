<?php
/**
 * Checkout Success Page
 * Displayed after successful payment
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Subscription.php';
require_once __DIR__ . '/../includes/helpers.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$sessionId = $_GET['session_id'] ?? '';

// Process the subscription if we have a session ID
$planName = 'unknown';
if ($sessionId) {
    try {
        // Initialize Stripe
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        
        // Retrieve the checkout session
        $session = \Stripe\Checkout\Session::retrieve($sessionId);
        
        if ($session && $session->subscription) {
            // Get the subscription from Stripe
            $stripeSubscription = \Stripe\Subscription::retrieve($session->subscription);
            
            // Get plan name from metadata
            $planName = $session->metadata->plan_name ?? 'pro';
            
            // Sync subscription to database
            $subscriptionManager = new Subscription($user['id']);
            $subscriptionManager->syncSubscriptionFromStripe($stripeSubscription, $planName);
            
            // Set success message
            flashMessage('success', 'Your subscription has been activated! Welcome to the ' . ucfirst($planName) . ' plan.');
        }
    } catch (Exception $e) {
        error_log('Error processing subscription on success page: ' . $e->getMessage());
        // Don't show error to user, webhook will handle it
    }
}

$pageTitle = 'Payment Successful';
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .success-container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 1rem;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: scaleIn 0.5s ease-out;
        }

        .success-icon::after {
            content: "✓";
            color: white;
            font-size: 3rem;
            font-weight: bold;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .success-message {
            font-size: 1.125rem;
            color: #6b7280;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .info-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .info-box h3 {
            color: #166534;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .info-box p {
            color: #166534;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .info-box ul {
            margin-top: 0.5rem;
            padding-left: 1.5rem;
            color: #166534;
            font-size: 0.875rem;
        }

        .info-box li {
            margin-bottom: 0.25rem;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-block;
            border: 2px solid;
            font-size: 1rem;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }

        .btn-primary:hover {
            background: #4f46e5;
            border-color: #4f46e5;
        }

        .btn-secondary {
            background: white;
            color: #6366f1;
            border-color: #e5e7eb;
        }

        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        .session-id {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.75rem;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon"></div>
        
        <h1>Payment Successful!</h1>
        
        <p class="success-message">
            Thank you for your subscription! Your account has been upgraded and all features are now available.
        </p>

        <div class="info-box">
            <h3>What happens next?</h3>
            <ul>
                <li>Your subscription is now active</li>
                <li>You'll receive a confirmation email shortly</li>
                <li>All premium features are unlocked</li>
                <li>You can manage your billing in the dashboard</li>
            </ul>
        </div>

        <div class="button-group">
            <a href="<?php echo url('dashboard/'); ?>" class="btn btn-primary">Go to Dashboard</a>
            <a href="<?php echo url('dashboard/profile.php'); ?>" class="btn btn-secondary">View Subscription</a>
        </div>

        <?php if ($sessionId): ?>
            <div class="session-id">
                Session ID: <?php echo htmlspecialchars($sessionId); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

