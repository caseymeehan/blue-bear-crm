<?php
/**
 * Subscription Management Class
 * Handles all subscription and billing operations with Stripe
 */

require_once __DIR__ . '/../database/Database.php';

class Subscription {
    private $db;
    private $userId;
    
    public function __construct($userId = null) {
        $this->db = Database::getInstance();
        $this->userId = $userId;
        
        // Initialize Stripe
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    }
    
    /**
     * Get item limit for a given plan
     * 
     * @param string $planName The plan name (free, pro, enterprise)
     * @return int|null Item limit, or null for unlimited
     */
    public function getItemLimit($planName) {
        $limits = [
            'free' => 5,
            'pro' => 50,
            'enterprise' => null // unlimited
        ];
        
        return $limits[$planName] ?? 5; // Default to free tier limit
    }
    
    /**
     * Get the current user's subscription
     * 
     * @return array|false Subscription data or false if none
     */
    public function getCurrentSubscription() {
        if (!$this->userId) {
            return false;
        }
        
        return $this->db->fetchOne(
            'SELECT * FROM subscriptions WHERE user_id = :user_id AND status = :status ORDER BY started_at DESC LIMIT 1',
            [
                'user_id' => $this->userId,
                'status' => 'active'
            ]
        );
    }
    
    /**
     * Check if user can create more items
     * 
     * @return array ['can_create' => bool, 'current' => int, 'limit' => int|null, 'plan' => string]
     */
    public function canCreateItem() {
        $subscription = $this->getCurrentSubscription();
        $planName = $subscription ? $subscription['plan_name'] : 'free';
        $limit = $this->getItemLimit($planName);
        
        // Count current items
        $currentCount = $this->db->count('items', 'user_id = :user_id', ['user_id' => $this->userId]);
        
        // Unlimited for enterprise
        if ($limit === null) {
            return [
                'can_create' => true,
                'current' => $currentCount,
                'limit' => null,
                'plan' => $planName
            ];
        }
        
        return [
            'can_create' => $currentCount < $limit,
            'current' => $currentCount,
            'limit' => $limit,
            'plan' => $planName
        ];
    }
    
    /**
     * Create or retrieve Stripe customer for user
     * 
     * @param array $user User data
     * @return string Stripe customer ID
     */
    public function getOrCreateStripeCustomer($user) {
        // Check if user already has a Stripe customer ID
        $subscription = $this->getCurrentSubscription();
        
        if ($subscription && !empty($subscription['stripe_customer_id'])) {
            return $subscription['stripe_customer_id'];
        }
        
        // Check all subscriptions for this user
        $allSubs = $this->db->fetchAll(
            'SELECT stripe_customer_id FROM subscriptions WHERE user_id = :user_id AND stripe_customer_id IS NOT NULL LIMIT 1',
            ['user_id' => $user['id']]
        );
        
        if (!empty($allSubs) && !empty($allSubs[0]['stripe_customer_id'])) {
            return $allSubs[0]['stripe_customer_id'];
        }
        
        // Create new Stripe customer
        try {
            $customer = \Stripe\Customer::create([
                'email' => $user['email'],
                'name' => $user['full_name'] ?? $user['username'],
                'metadata' => [
                    'user_id' => $user['id'],
                    'username' => $user['username']
                ]
            ]);
            
            return $customer->id;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe customer creation failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create Stripe Checkout session for subscription
     * 
     * @param string $planName Plan name (pro or enterprise)
     * @param array $user User data
     * @return string Checkout session URL
     */
    public function createCheckoutSession($planName, $user) {
        $plans = PRICING_PLANS;
        
        if (!isset($plans[$planName]) || $planName === 'free') {
            throw new Exception('Invalid plan selected');
        }
        
        $plan = $plans[$planName];
        
        if (empty($plan['stripe_price_id']) || $plan['stripe_price_id'] === 'price_YOUR_PRO_PRICE_ID' || $plan['stripe_price_id'] === 'price_YOUR_ENTERPRISE_PRICE_ID') {
            throw new Exception('Stripe price ID not configured. Please set up Stripe products and update config.local.php');
        }
        
        // Get or create Stripe customer
        $customerId = $this->getOrCreateStripeCustomer($user);
        
        try {
            $session = \Stripe\Checkout\Session::create([
                'customer' => $customerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $plan['stripe_price_id'],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => SITE_URL . '/checkout/success.php?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => SITE_URL . '/checkout/cancel.php',
                'metadata' => [
                    'user_id' => $user['id'],
                    'plan_name' => $planName
                ],
                'subscription_data' => [
                    'metadata' => [
                        'user_id' => $user['id'],
                        'plan_name' => $planName
                    ]
                ]
            ]);
            
            return $session->url;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe checkout session creation failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create or update subscription from Stripe data
     * 
     * @param object $stripeSubscription Stripe subscription object
     * @param string $planName Plan name
     * @return bool Success status
     */
    public function syncSubscriptionFromStripe($stripeSubscription, $planName) {
        try {
            // Get user ID from metadata
            $userId = $stripeSubscription->metadata->user_id ?? null;
            
            if (!$userId) {
                error_log('No user_id in subscription metadata');
                return false;
            }
            
            // Check if subscription already exists
            $existing = $this->db->fetchOne(
                'SELECT * FROM subscriptions WHERE stripe_subscription_id = :sub_id',
                ['sub_id' => $stripeSubscription->id]
            );
            
            $data = [
                'user_id' => $userId,
                'plan_name' => $planName,
                'status' => $stripeSubscription->status,
                'amount' => $stripeSubscription->items->data[0]->price->unit_amount / 100,
                'currency' => strtoupper($stripeSubscription->currency),
                'billing_cycle' => $stripeSubscription->items->data[0]->price->recurring->interval,
                'stripe_customer_id' => $stripeSubscription->customer,
                'stripe_subscription_id' => $stripeSubscription->id,
                'stripe_price_id' => $stripeSubscription->items->data[0]->price->id,
                'current_period_start' => date('Y-m-d H:i:s', $stripeSubscription->current_period_start),
                'current_period_end' => date('Y-m-d H:i:s', $stripeSubscription->current_period_end),
                'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end ? 1 : 0
            ];
            
            if ($existing) {
                // Update existing subscription
                unset($data['user_id']); // Don't update user_id
                $this->db->update(
                    'subscriptions',
                    $data,
                    'id = :id',
                    ['id' => $existing['id']]
                );
            } else {
                // Deactivate any existing active subscriptions for this user
                $this->db->query(
                    "UPDATE subscriptions SET status = 'cancelled', cancelled_at = :now WHERE user_id = :user_id AND status = 'active'",
                    ['now' => date('Y-m-d H:i:s'), 'user_id' => $userId]
                );
                
                // Create new subscription
                $data['started_at'] = date('Y-m-d H:i:s', $stripeSubscription->start_date);
                $this->db->insert('subscriptions', $data);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Error syncing subscription: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancel subscription at period end
     * 
     * @return bool Success status
     */
    public function cancelSubscription() {
        $subscription = $this->getCurrentSubscription();
        
        if (!$subscription || empty($subscription['stripe_subscription_id'])) {
            return false;
        }
        
        try {
            // Cancel at period end in Stripe
            $stripeSubscription = \Stripe\Subscription::update(
                $subscription['stripe_subscription_id'],
                ['cancel_at_period_end' => true]
            );
            
            // Update local database
            $this->db->update(
                'subscriptions',
                [
                    'cancel_at_period_end' => 1,
                    'cancelled_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                ['id' => $subscription['id']]
            );
            
            return true;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Subscription cancellation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reactivate a cancelled subscription
     * 
     * @return bool Success status
     */
    public function reactivateSubscription() {
        $subscription = $this->getCurrentSubscription();
        
        if (!$subscription || empty($subscription['stripe_subscription_id'])) {
            return false;
        }
        
        try {
            // Reactivate in Stripe
            $stripeSubscription = \Stripe\Subscription::update(
                $subscription['stripe_subscription_id'],
                ['cancel_at_period_end' => false]
            );
            
            // Update local database
            $this->db->update(
                'subscriptions',
                [
                    'cancel_at_period_end' => 0,
                    'cancelled_at' => null
                ],
                'id = :id',
                ['id' => $subscription['id']]
            );
            
            return true;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Subscription reactivation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's invoice history
     * 
     * @param int $limit Number of invoices to retrieve
     * @return array Invoice data
     */
    public function getInvoices($limit = 10) {
        if (!$this->userId) {
            return [];
        }
        
        return $this->db->fetchAll(
            'SELECT * FROM invoices WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit',
            ['user_id' => $this->userId, 'limit' => $limit]
        );
    }
    
    /**
     * Save invoice from Stripe
     * 
     * @param object $stripeInvoice Stripe invoice object
     * @return bool Success status
     */
    public function saveInvoice($stripeInvoice) {
        try {
            // Get user ID from customer metadata
            $customer = \Stripe\Customer::retrieve($stripeInvoice->customer);
            $userId = $customer->metadata->user_id ?? null;
            
            if (!$userId) {
                error_log('No user_id in customer metadata for invoice');
                return false;
            }
            
            // Check if invoice already exists
            $existing = $this->db->fetchOne(
                'SELECT id FROM invoices WHERE stripe_invoice_id = :invoice_id',
                ['invoice_id' => $stripeInvoice->id]
            );
            
            $data = [
                'user_id' => $userId,
                'stripe_invoice_id' => $stripeInvoice->id,
                'stripe_customer_id' => $stripeInvoice->customer,
                'amount' => $stripeInvoice->amount_paid / 100,
                'currency' => strtoupper($stripeInvoice->currency),
                'status' => $stripeInvoice->status,
                'invoice_pdf' => $stripeInvoice->invoice_pdf,
                'hosted_invoice_url' => $stripeInvoice->hosted_invoice_url,
                'billing_reason' => $stripeInvoice->billing_reason,
                'period_start' => $stripeInvoice->period_start ? date('Y-m-d H:i:s', $stripeInvoice->period_start) : null,
                'period_end' => $stripeInvoice->period_end ? date('Y-m-d H:i:s', $stripeInvoice->period_end) : null,
                'paid_at' => $stripeInvoice->status_transitions->paid_at ? date('Y-m-d H:i:s', $stripeInvoice->status_transitions->paid_at) : null
            ];
            
            if ($existing) {
                // Update existing invoice
                unset($data['user_id']);
                unset($data['stripe_invoice_id']);
                unset($data['stripe_customer_id']);
                $this->db->update(
                    'invoices',
                    $data,
                    'id = :id',
                    ['id' => $existing['id']]
                );
            } else {
                // Insert new invoice
                $this->db->insert('invoices', $data);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Error saving invoice: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get billing portal URL for customer to manage subscription
     * 
     * @return string|false Portal URL or false on failure
     */
    public function getBillingPortalUrl() {
        $subscription = $this->getCurrentSubscription();
        
        if (!$subscription || empty($subscription['stripe_customer_id'])) {
            return false;
        }
        
        try {
            $session = \Stripe\BillingPortal\Session::create([
                'customer' => $subscription['stripe_customer_id'],
                'return_url' => SITE_URL . '/dashboard/profile.php',
            ]);
            
            return $session->url;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Billing portal creation failed: ' . $e->getMessage());
            return false;
        }
    }
}

