<?php
/**
 * Stripe Webhook Handler
 * Processes webhook events from Stripe
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../includes/Subscription.php';

// Set Stripe API key
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Get the webhook secret
$endpointSecret = STRIPE_WEBHOOK_SECRET;

// Get the raw POST body
$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

$event = null;
$db = Database::getInstance();

try {
    // Verify webhook signature
    if (!empty($endpointSecret) && $endpointSecret !== 'whsec_YOUR_WEBHOOK_SECRET') {
        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $sigHeader,
            $endpointSecret
        );
    } else {
        // For testing without signature verification (not recommended for production)
        $event = json_decode($payload, false);
        error_log('WARNING: Webhook signature verification is disabled. This is not secure for production!');
    }
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    error_log('Webhook error: Invalid payload');
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    error_log('Webhook error: Invalid signature');
    http_response_code(400);
    exit();
}

// Log the webhook event
try {
    $db->insert('webhook_events', [
        'stripe_event_id' => $event->id,
        'event_type' => $event->type,
        'payload' => json_encode($event->data),
        'processed' => 0
    ]);
} catch (Exception $e) {
    error_log('Error logging webhook event: ' . $e->getMessage());
}

// Handle the event
try {
    switch ($event->type) {
        case 'checkout.session.completed':
            handleCheckoutSessionCompleted($event->data->object);
            break;
            
        case 'customer.subscription.created':
        case 'customer.subscription.updated':
            handleSubscriptionUpdated($event->data->object);
            break;
            
        case 'customer.subscription.deleted':
            handleSubscriptionDeleted($event->data->object);
            break;
            
        case 'invoice.payment_succeeded':
            handleInvoicePaymentSucceeded($event->data->object);
            break;
            
        case 'invoice.payment_failed':
            handleInvoicePaymentFailed($event->data->object);
            break;
            
        case 'customer.subscription.trial_will_end':
            // Handle trial ending (optional)
            error_log('Trial ending for subscription: ' . $event->data->object->id);
            break;
            
        default:
            error_log('Unhandled webhook event type: ' . $event->type);
    }
    
    // Mark webhook as processed
    $db->query(
        'UPDATE webhook_events SET processed = 1, processed_at = :now WHERE stripe_event_id = :event_id',
        ['now' => date('Y-m-d H:i:s'), 'event_id' => $event->id]
    );
    
} catch (Exception $e) {
    error_log('Webhook processing error: ' . $e->getMessage());
    
    // Log error in database
    $db->query(
        'UPDATE webhook_events SET error_message = :error WHERE stripe_event_id = :event_id',
        ['error' => $e->getMessage(), 'event_id' => $event->id]
    );
    
    http_response_code(500);
    exit();
}

http_response_code(200);
exit();

/**
 * Handle successful checkout session
 */
function handleCheckoutSessionCompleted($session) {
    error_log('Checkout session completed: ' . $session->id);
    
    // Get subscription ID from the session
    if (isset($session->subscription)) {
        try {
            $subscription = \Stripe\Subscription::retrieve($session->subscription);
            $planName = $session->metadata->plan_name ?? 'pro';
            
            $subscriptionManager = new Subscription();
            $subscriptionManager->syncSubscriptionFromStripe($subscription, $planName);
            
            error_log('Subscription synced: ' . $subscription->id);
        } catch (Exception $e) {
            error_log('Error syncing subscription from checkout: ' . $e->getMessage());
            throw $e;
        }
    }
}

/**
 * Handle subscription created or updated
 */
function handleSubscriptionUpdated($subscription) {
    error_log('Subscription updated: ' . $subscription->id);
    
    try {
        // Get plan name from metadata or determine from price
        $planName = $subscription->metadata->plan_name ?? 'pro';
        
        // If not in metadata, try to determine from price
        if (!$planName || $planName === 'pro') {
            $priceId = $subscription->items->data[0]->price->id;
            $plans = PRICING_PLANS;
            
            foreach ($plans as $name => $plan) {
                if ($plan['stripe_price_id'] === $priceId) {
                    $planName = $name;
                    break;
                }
            }
        }
        
        $subscriptionManager = new Subscription();
        $subscriptionManager->syncSubscriptionFromStripe($subscription, $planName);
        
        error_log('Subscription synced: ' . $subscription->id);
    } catch (Exception $e) {
        error_log('Error syncing subscription: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle subscription deleted (cancelled)
 */
function handleSubscriptionDeleted($subscription) {
    error_log('Subscription deleted: ' . $subscription->id);
    
    try {
        $db = Database::getInstance();
        
        // Update subscription status to cancelled
        $db->query(
            "UPDATE subscriptions SET status = 'cancelled', ends_at = :ends_at WHERE stripe_subscription_id = :sub_id",
            [
                'ends_at' => date('Y-m-d H:i:s'),
                'sub_id' => $subscription->id
            ]
        );
        
        // Get user ID to create a free subscription
        $sub = $db->fetchOne(
            'SELECT user_id FROM subscriptions WHERE stripe_subscription_id = :sub_id',
            ['sub_id' => $subscription->id]
        );
        
        if ($sub) {
            // Create a free subscription for the user
            $db->insert('subscriptions', [
                'user_id' => $sub['user_id'],
                'plan_name' => 'free',
                'status' => 'active',
                'amount' => 0,
                'currency' => 'USD',
                'billing_cycle' => 'month',
                'started_at' => date('Y-m-d H:i:s')
            ]);
            
            error_log('Created free subscription for user: ' . $sub['user_id']);
        }
    } catch (Exception $e) {
        error_log('Error handling subscription deletion: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle successful invoice payment
 */
function handleInvoicePaymentSucceeded($invoice) {
    error_log('Invoice payment succeeded: ' . $invoice->id);
    
    try {
        $subscriptionManager = new Subscription();
        $subscriptionManager->saveInvoice($invoice);
        
        // If there's a subscription, sync it
        if (isset($invoice->subscription) && $invoice->subscription) {
            $subscription = \Stripe\Subscription::retrieve($invoice->subscription);
            $planName = $subscription->metadata->plan_name ?? 'pro';
            $subscriptionManager->syncSubscriptionFromStripe($subscription, $planName);
        }
        
        error_log('Invoice saved: ' . $invoice->id);
    } catch (Exception $e) {
        error_log('Error saving invoice: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle failed invoice payment
 */
function handleInvoicePaymentFailed($invoice) {
    error_log('Invoice payment failed: ' . $invoice->id);
    
    try {
        $subscriptionManager = new Subscription();
        $subscriptionManager->saveInvoice($invoice);
        
        // TODO: Send email notification to user about payment failure
        // You could add email notification logic here
        
        error_log('Failed invoice saved: ' . $invoice->id);
    } catch (Exception $e) {
        error_log('Error saving failed invoice: ' . $e->getMessage());
        throw $e;
    }
}

