<?php
/**
 * Contacts Class
 * Handles CRUD operations for partnership contacts
 */

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/Subscription.php';

class Contacts {
    private $db;
    private $subscription;
    
    public function __construct($userId = null) {
        $this->db = Database::getInstance();
        if ($userId) {
            $this->subscription = new Subscription($userId);
        }
    }
    
    /**
     * Get all contacts for a user
     * @param int $userId
     * @param string|null $stage Filter by stage
     * @param string|null $search Search term
     * @param string $orderBy Order by field
     * @param string $order ASC or DESC
     * @return array
     */
    public function getUserContacts($userId, $stage = null, $search = null, $orderBy = 'created_at', $order = 'DESC') {
        $params = ['user_id' => $userId];
        $where = ['user_id = :user_id'];
        
        // Stage filter
        if ($stage && $stage !== 'all') {
            $where[] = 'stage = :stage';
            $params['stage'] = $stage;
        }
        
        // Search filter
        if ($search) {
            $where[] = '(name LIKE :search OR company LIKE :search OR email LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        
        // Validate order by field
        $allowedOrderBy = ['created_at', 'updated_at', 'name', 'company', 'stage'];
        if (!in_array($orderBy, $allowedOrderBy)) {
            $orderBy = 'created_at';
        }
        
        // Validate order direction
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        $whereClause = implode(' AND ', $where);
        $query = "SELECT * FROM contacts WHERE $whereClause ORDER BY $orderBy $order";
        
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Get a single contact by ID
     * @param int $contactId
     * @param int $userId (to ensure user owns the contact)
     * @return array|null
     */
    public function getContact($contactId, $userId) {
        $query = "SELECT * FROM contacts WHERE id = :id AND user_id = :user_id";
        return $this->db->fetchOne($query, [
            'id' => $contactId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Create a new contact
     * @param int $userId
     * @param array $data Contact data
     * @return int|false (new contact ID or false)
     */
    public function createContact($userId, $data) {
        $insertData = [
            'user_id' => $userId,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'title' => $data['title'] ?? null,
            'stage' => $data['stage'] ?? 'identified',
            'linkedin_url' => $data['linkedin_url'] ?? null,
            'twitter_handle' => $data['twitter_handle'] ?? null,
            'youtube_channel' => $data['youtube_channel'] ?? null,
            'instagram_handle' => $data['instagram_handle'] ?? null,
            'tiktok_handle' => $data['tiktok_handle'] ?? null
        ];
        
        return $this->db->insert('contacts', $insertData);
    }
    
    /**
     * Update a contact
     * @param int $contactId
     * @param int $userId (to ensure user owns the contact)
     * @param array $data Contact data
     * @return bool
     */
    public function updateContact($contactId, $userId, $data) {
        // First verify the contact belongs to the user
        $contact = $this->getContact($contactId, $userId);
        if (!$contact) {
            return false;
        }
        
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'title' => $data['title'] ?? null,
            'stage' => $data['stage'] ?? $contact['stage'],
            'linkedin_url' => $data['linkedin_url'] ?? null,
            'twitter_handle' => $data['twitter_handle'] ?? null,
            'youtube_channel' => $data['youtube_channel'] ?? null,
            'instagram_handle' => $data['instagram_handle'] ?? null,
            'tiktok_handle' => $data['tiktok_handle'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update('contacts', 
            $updateData,
            'id = :id AND user_id = :user_id',
            [
                'id' => $contactId,
                'user_id' => $userId
            ]
        );
    }
    
    /**
     * Delete a contact
     * @param int $contactId
     * @param int $userId (to ensure user owns the contact)
     * @return bool
     */
    public function deleteContact($contactId, $userId) {
        // First verify the contact belongs to the user
        $contact = $this->getContact($contactId, $userId);
        if (!$contact) {
            return false;
        }
        
        return $this->db->delete('contacts', 
            'id = :id AND user_id = :user_id',
            [
                'id' => $contactId,
                'user_id' => $userId
            ]
        );
    }
    
    /**
     * Get contact count for a user
     * @param int $userId
     * @return int
     */
    public function getContactCount($userId) {
        $query = "SELECT COUNT(*) as count FROM contacts WHERE user_id = :user_id";
        $result = $this->db->fetchOne($query, ['user_id' => $userId]);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get contacts count by stage for a user
     * @param int $userId
     * @return array
     */
    public function getContactCountByStage($userId) {
        $query = "SELECT stage, COUNT(*) as count FROM contacts WHERE user_id = :user_id GROUP BY stage";
        $results = $this->db->fetchAll($query, ['user_id' => $userId]);
        
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['stage']] = (int)$row['count'];
        }
        
        return $counts;
    }
    
    /**
     * Get contact limit for a given plan
     * @param string $planName The plan name (free, pro, enterprise)
     * @return int|null Contact limit, or null for unlimited
     */
    public function getContactLimit($planName) {
        $plans = PRICING_PLANS;
        return $plans[$planName]['contact_limit'] ?? 30; // Default to free tier limit
    }
    
    /**
     * Get usage information for a user (contacts count with limits)
     * @param int $userId
     * @return array ['current' => int, 'limit' => int|null, 'plan' => string, 'can_create' => bool, 'percentage' => float]
     */
    public function getUserUsage($userId) {
        if (!$this->subscription) {
            $this->subscription = new Subscription($userId);
        }
        
        $subscription = $this->subscription->getCurrentSubscription();
        $planName = $subscription ? $subscription['plan_name'] : 'free';
        $limit = $this->getContactLimit($planName);
        
        // Count current contacts
        $currentCount = $this->getContactCount($userId);
        
        // Calculate percentage for progress bar
        $percentage = 0;
        if ($limit !== null && $limit > 0) {
            $percentage = ($currentCount / $limit) * 100;
        }
        
        // Unlimited for enterprise
        $canCreate = ($limit === null) || ($currentCount < $limit);
        
        return [
            'can_create' => $canCreate,
            'current' => $currentCount,
            'limit' => $limit,
            'plan' => $planName,
            'percentage' => $percentage
        ];
    }
    
    /**
     * Check if user can create more contacts
     * @param int $userId
     * @return bool
     */
    public function canCreateContact($userId) {
        $usageInfo = $this->getUserUsage($userId);
        return $usageInfo['can_create'];
    }
    
    /**
     * Get all valid stages
     * @return array
     */
    public static function getStages() {
        return CONTACT_STAGES;
    }
    
    /**
     * Validate stage value
     * @param string $stage
     * @return bool
     */
    public static function isValidStage($stage) {
        return array_key_exists($stage, CONTACT_STAGES);
    }
}

