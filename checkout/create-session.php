<?php
/**
 * Create Stripe Checkout Session
 * Initiates the payment flow for a subscription plan
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Subscription.php';
require_once __DIR__ . '/../includes/helpers.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
if (!$user) {
    flashMessage('error', 'Unable to load user data.');
    redirect('../dashboard/');
}

// Get plan from query parameter
$planName = $_GET['plan'] ?? '';

// Validate plan
if (!in_array($planName, ['pro', 'enterprise'])) {
    flashMessage('error', 'Invalid plan selected.');
    redirect('../pricing.php');
}

try {
    $subscriptionManager = new Subscription($user['id']);
    
    // Check if user already has this plan
    $currentSubscription = $subscriptionManager->getCurrentSubscription();
    if ($currentSubscription && $currentSubscription['plan_name'] === $planName) {
        flashMessage('info', 'You already have this plan.');
        redirect('../dashboard/profile.php');
    }
    
    // Create checkout session
    $checkoutUrl = $subscriptionManager->createCheckoutSession($planName, $user);
    
    // Redirect to Stripe Checkout
    header('Location: ' . $checkoutUrl);
    exit;
    
} catch (Exception $e) {
    error_log('Checkout session creation error: ' . $e->getMessage());
    
    if (strpos($e->getMessage(), 'not configured') !== false) {
        flashMessage('error', 'Payment system is not configured yet. Please contact support.');
    } else {
        flashMessage('error', 'Unable to initiate checkout. Please try again.');
    }
    
    redirect('../pricing.php');
}

