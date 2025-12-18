<?php
/**
 * Interactions Class
 * Handles CRUD operations for contact interaction notes
 */

require_once __DIR__ . '/../database/Database.php';

class Interactions {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all interactions for a contact
     * @param int $contactId
     * @param int $userId (to ensure user owns the contact)
     * @param string $order ASC or DESC
     * @return array
     */
    public function getContactInteractions($contactId, $userId, $order = 'DESC') {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        $query = "SELECT i.* FROM interactions i 
                  INNER JOIN contacts c ON i.contact_id = c.id 
                  WHERE i.contact_id = :contact_id AND c.user_id = :user_id 
                  ORDER BY i.created_at $order";
        
        return $this->db->fetchAll($query, [
            'contact_id' => $contactId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Get a single interaction by ID
     * @param int $interactionId
     * @param int $userId (to ensure user owns it)
     * @return array|null
     */
    public function getInteraction($interactionId, $userId) {
        $query = "SELECT i.* FROM interactions i 
                  INNER JOIN contacts c ON i.contact_id = c.id 
                  WHERE i.id = :id AND c.user_id = :user_id";
        
        return $this->db->fetchOne($query, [
            'id' => $interactionId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Create a new interaction
     * @param int $contactId
     * @param int $userId
     * @param string $note
     * @param string|null $title Optional title for the note
     * @return int|false (new interaction ID or false)
     */
    public function createInteraction($contactId, $userId, $note, $title = null) {
        // Verify contact belongs to user first
        $contact = $this->db->fetchOne(
            "SELECT id FROM contacts WHERE id = :id AND user_id = :user_id",
            ['id' => $contactId, 'user_id' => $userId]
        );
        
        if (!$contact) {
            return false;
        }
        
        $data = [
            'contact_id' => $contactId,
            'user_id' => $userId,
            'note' => $note
        ];
        
        // Add title if provided
        if ($title !== null && trim($title) !== '') {
            $data['title'] = trim($title);
        }
        
        $result = $this->db->insert('interactions', $data);
        
        // Update the contact's updated_at timestamp
        if ($result) {
            $this->db->update('contacts', 
                ['updated_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $contactId]
            );
        }
        
        return $result;
    }
    
    /**
     * Update an interaction
     * @param int $interactionId
     * @param int $userId
     * @param string $note
     * @param string|null $title Optional title for the note
     * @return bool
     */
    public function updateInteraction($interactionId, $userId, $note, $title = null) {
        // Verify interaction belongs to user
        $interaction = $this->getInteraction($interactionId, $userId);
        if (!$interaction) {
            return false;
        }
        
        $data = ['note' => $note];
        
        // Update title (can be set to null to clear it)
        if ($title !== null) {
            $data['title'] = trim($title) !== '' ? trim($title) : null;
        }
        
        return $this->db->update('interactions',
            $data,
            'id = :id',
            ['id' => $interactionId]
        );
    }
    
    /**
     * Delete an interaction
     * @param int $interactionId
     * @param int $userId
     * @return bool
     */
    public function deleteInteraction($interactionId, $userId) {
        // Verify interaction belongs to user
        $interaction = $this->getInteraction($interactionId, $userId);
        if (!$interaction) {
            return false;
        }
        
        return $this->db->delete('interactions',
            'id = :id',
            ['id' => $interactionId]
        );
    }
    
    /**
     * Get interaction count for a contact
     * @param int $contactId
     * @return int
     */
    public function getInteractionCount($contactId) {
        $query = "SELECT COUNT(*) as count FROM interactions WHERE contact_id = :contact_id";
        $result = $this->db->fetchOne($query, ['contact_id' => $contactId]);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get most recent interaction for a contact
     * @param int $contactId
     * @param int $userId
     * @return array|null
     */
    public function getLatestInteraction($contactId, $userId) {
        $query = "SELECT i.* FROM interactions i 
                  INNER JOIN contacts c ON i.contact_id = c.id 
                  WHERE i.contact_id = :contact_id AND c.user_id = :user_id 
                  ORDER BY i.created_at DESC LIMIT 1";
        
        return $this->db->fetchOne($query, [
            'contact_id' => $contactId,
            'user_id' => $userId
        ]);
    }
}

