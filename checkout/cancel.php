<?php
/**
 * Checkout Cancelled Page
 * Displayed when user cancels the payment process
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();

$pageTitle = 'Checkout Cancelled';
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

        .cancel-container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 1rem;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .cancel-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }

        .cancel-icon::after {
            content: "⚠";
            color: white;
            font-size: 3rem;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .cancel-message {
            font-size: 1.125rem;
            color: #6b7280;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .info-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .info-box h3 {
            color: #92400e;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .info-box p {
            color: #92400e;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .info-box ul {
            margin-top: 0.5rem;
            padding-left: 1.5rem;
            color: #92400e;
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

        .support-link {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .support-link a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
        }

        .support-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="cancel-container">
        <div class="cancel-icon"></div>
        
        <h1>Checkout Cancelled</h1>
        
        <p class="cancel-message">
            No worries! Your payment was not processed and you haven't been charged.
        </p>

        <div class="info-box">
            <h3>What would you like to do?</h3>
            <ul>
                <li>Try the checkout process again</li>
                <li>Review our pricing plans</li>
                <li>Continue with your current plan</li>
                <li>Contact support if you have questions</li>
            </ul>
        </div>

        <div class="button-group">
            <a href="<?php echo url('pricing.php'); ?>" class="btn btn-primary">View Pricing</a>
            <a href="<?php echo url('dashboard/'); ?>" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <div class="support-link">
            Need help? <a href="mailto:<?php echo SITE_EMAIL; ?>">Contact Support</a>
        </div>
    </div>
</body>
</html>

