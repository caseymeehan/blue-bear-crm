<?php
/**
 * SocialStats Class
 * Handles CRUD operations for contact social media statistics
 */

require_once __DIR__ . '/../database/Database.php';

class SocialStats {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all social stats for a contact
     * @param int $contactId
     * @param int $userId (to ensure user owns the contact)
     * @return array
     */
    public function getContactStats($contactId, $userId) {
        $query = "SELECT s.* FROM social_stats s 
                  INNER JOIN contacts c ON s.contact_id = c.id 
                  WHERE s.contact_id = :contact_id AND c.user_id = :user_id 
                  ORDER BY s.platform ASC";
        
        return $this->db->fetchAll($query, [
            'contact_id' => $contactId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Get stats for a specific platform
     * @param int $contactId
     * @param int $userId
     * @param string $platform
     * @return array|null
     */
    public function getPlatformStats($contactId, $userId, $platform) {
        $query = "SELECT s.* FROM social_stats s 
                  INNER JOIN contacts c ON s.contact_id = c.id 
                  WHERE s.contact_id = :contact_id AND c.user_id = :user_id AND s.platform = :platform";
        
        return $this->db->fetchOne($query, [
            'contact_id' => $contactId,
            'user_id' => $userId,
            'platform' => $platform
        ]);
    }
    
    /**
     * Create or update social stats (upsert)
     * @param int $contactId
     * @param int $userId
     * @param string $platform
     * @param int $followers
     * @return bool
     */
    public function upsertStats($contactId, $userId, $platform, $followers) {
        // Validate platform
        if (!self::isValidPlatform($platform)) {
            return false;
        }
        
        // Verify contact belongs to user
        $contact = $this->db->fetchOne(
            "SELECT id FROM contacts WHERE id = :id AND user_id = :user_id",
            ['id' => $contactId, 'user_id' => $userId]
        );
        
        if (!$contact) {
            return false;
        }
        
        // Check if stats already exist
        $existing = $this->db->fetchOne(
            "SELECT id FROM social_stats WHERE contact_id = :contact_id AND platform = :platform",
            ['contact_id' => $contactId, 'platform' => $platform]
        );
        
        if ($existing) {
            // Update
            return $this->db->update('social_stats',
                [
                    'followers' => (int)$followers,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                ['id' => $existing['id']]
            );
        } else {
            // Insert
            return $this->db->insert('social_stats', [
                'contact_id' => $contactId,
                'platform' => $platform,
                'followers' => (int)$followers
            ]) !== false;
        }
    }
    
    /**
     * Delete social stats for a platform
     * @param int $contactId
     * @param int $userId
     * @param string $platform
     * @return bool
     */
    public function deleteStats($contactId, $userId, $platform) {
        // Verify contact belongs to user
        $contact = $this->db->fetchOne(
            "SELECT id FROM contacts WHERE id = :id AND user_id = :user_id",
            ['id' => $contactId, 'user_id' => $userId]
        );
        
        if (!$contact) {
            return false;
        }
        
        return $this->db->delete('social_stats',
            'contact_id = :contact_id AND platform = :platform',
            [
                'contact_id' => $contactId,
                'platform' => $platform
            ]
        );
    }
    
    /**
     * Bulk update social stats for a contact
     * @param int $contactId
     * @param int $userId
     * @param array $stats Array of ['platform' => 'youtube', 'followers' => 1000]
     * @return bool
     */
    public function bulkUpdateStats($contactId, $userId, $stats) {
        foreach ($stats as $stat) {
            if (isset($stat['platform']) && isset($stat['followers'])) {
                // Only upsert if followers value is provided (not empty)
                if ($stat['followers'] !== '' && $stat['followers'] !== null) {
                    $this->upsertStats($contactId, $userId, $stat['platform'], $stat['followers']);
                } else {
                    // Delete if followers is empty
                    $this->deleteStats($contactId, $userId, $stat['platform']);
                }
            }
        }
        return true;
    }
    
    /**
     * Get total followers across all platforms for a contact
     * @param int $contactId
     * @param int $userId
     * @return int
     */
    public function getTotalFollowers($contactId, $userId) {
        $query = "SELECT SUM(s.followers) as total FROM social_stats s 
                  INNER JOIN contacts c ON s.contact_id = c.id 
                  WHERE s.contact_id = :contact_id AND c.user_id = :user_id";
        
        $result = $this->db->fetchOne($query, [
            'contact_id' => $contactId,
            'user_id' => $userId
        ]);
        
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Get all valid platforms
     * @return array
     */
    public static function getPlatforms() {
        return SOCIAL_PLATFORMS;
    }
    
    /**
     * Validate platform value
     * @param string $platform
     * @return bool
     */
    public static function isValidPlatform($platform) {
        return array_key_exists($platform, SOCIAL_PLATFORMS);
    }
    
    /**
     * Format follower count for display (e.g., 1.5K, 2.3M)
     * @param int $count
     * @return string
     */
    public static function formatFollowers($count) {
        if ($count >= 1000000) {
            return round($count / 1000000, 1) . 'M';
        } elseif ($count >= 1000) {
            return round($count / 1000, 1) . 'K';
        }
        return (string)$count;
    }
}

