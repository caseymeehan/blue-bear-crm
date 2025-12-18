<?php
/**
 * Authentication Handler
 * Manages user authentication, sessions, and user management
 */

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/GoogleOAuth.php';

class Auth {
    private $db;
    private $googleOAuth;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->googleOAuth = new GoogleOAuth();
    }
    
    /**
     * Handle Google OAuth callback
     * 
     * @param string $code Authorization code from Google
     * @param string $state State parameter for CSRF verification
     * @return array|false Array with user data and status, or false on failure
     */
    public function handleGoogleCallback($code, $state) {
        // Get user profile from Google
        $profile = $this->googleOAuth->handleCallback($code, $state);
        
        if (!$profile) {
            return false;
        }
        
        // Create or update user
        $user = $this->createOrUpdateUser($profile);
        
        if (!$user) {
            return false;
        }
        
        // Create session
        if (!$this->createSession($user['id'])) {
            return false;
        }
        
        // Log the activity
        $this->logActivity($user['id'], 'login', 'User logged in via Google OAuth');
        
        return [
            'user' => $user,
            'is_new_user' => $user['is_new_user'] ?? false
        ];
    }
    
    /**
     * Create or update user from Google OAuth data
     * 
     * @param array $profile Google user profile data
     * @return array|false User data or false on failure
     */
    public function createOrUpdateUser($profile) {
        try {
            // Check if user exists by Google ID
            $existingUser = $this->db->fetchOne(
                'SELECT * FROM users WHERE google_id = :google_id',
                ['google_id' => $profile['google_id']]
            );
            
            if ($existingUser) {
                // Update existing user
                $this->db->update(
                    'users',
                    [
                        'full_name' => $profile['full_name'],
                        'avatar_url' => $profile['avatar_url'],
                        'email_verified' => $profile['email_verified'],
                        'last_login' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                    'id = :id',
                    ['id' => $existingUser['id']]
                );
                
                return array_merge($existingUser, ['is_new_user' => false]);
            }
            
            // Check if user exists by email (might have been created differently)
            $emailUser = $this->db->fetchOne(
                'SELECT * FROM users WHERE email = :email',
                ['email' => $profile['email']]
            );
            
            if ($emailUser) {
                // Link Google account to existing email user
                $this->db->update(
                    'users',
                    [
                        'google_id' => $profile['google_id'],
                        'oauth_provider' => 'google',
                        'full_name' => $profile['full_name'],
                        'avatar_url' => $profile['avatar_url'],
                        'email_verified' => $profile['email_verified'],
                        'last_login' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                    'id = :id',
                    ['id' => $emailUser['id']]
                );
                
                return array_merge($emailUser, ['is_new_user' => false]);
            }
            
            // Create new user
            $username = $this->generateUniqueUsername($profile['email']);
            
            $userId = $this->db->insert('users', [
                'username' => $username,
                'email' => $profile['email'],
                'google_id' => $profile['google_id'],
                'oauth_provider' => 'google',
                'full_name' => $profile['full_name'],
                'avatar_url' => $profile['avatar_url'],
                'email_verified' => $profile['email_verified'],
                'last_login' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$userId) {
                return false;
            }
            
            // Create free subscription for new user
            $this->db->insert('subscriptions', [
                'user_id' => $userId,
                'plan_name' => 'free',
                'status' => 'active',
                'amount' => 0,
                'currency' => 'USD',
                'billing_cycle' => 'month',
                'started_at' => date('Y-m-d H:i:s')
            ]);
            
            // Fetch and return the new user
            $newUser = $this->db->fetchOne(
                'SELECT * FROM users WHERE id = :id',
                ['id' => $userId]
            );
            
            return array_merge($newUser, ['is_new_user' => true]);
            
        } catch (Exception $e) {
            error_log('Error creating/updating user: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate a unique username from an email
     * 
     * @param string $email The email address
     * @return string A unique username
     */
    private function generateUniqueUsername($email) {
        // Extract username part from email
        $baseUsername = strtolower(explode('@', $email)[0]);
        
        // Remove special characters
        $baseUsername = preg_replace('/[^a-z0-9_]/', '', $baseUsername);
        
        // Ensure it's not empty
        if (empty($baseUsername)) {
            $baseUsername = 'user';
        }
        
        // Check if username exists
        $username = $baseUsername;
        $counter = 1;
        
        while ($this->db->exists('users', 'username = :username', ['username' => $username])) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Create a new session for the user
     * 
     * @param int $userId The user ID
     * @return bool True on success, false on failure
     */
    public function createSession($userId) {
        try {
            // Generate secure session token
            $sessionToken = generateSecureToken(64);
            
            // Calculate expiration time
            $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
            
            // Store session in database
            $sessionId = $this->db->insert('sessions', [
                'user_id' => $userId,
                'session_token' => $sessionToken,
                'ip_address' => getClientIP(),
                'user_agent' => getUserAgent(),
                'created_at' => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt
            ]);
            
            if (!$sessionId) {
                return false;
            }
            
            // Store session data in PHP session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['session_token'] = $sessionToken;
            $_SESSION['session_id'] = $sessionId;
            
            return true;
        } catch (Exception $e) {
            error_log('Error creating session: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if logged in, false otherwise
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
            return false;
        }
        
        // Verify session in database
        $session = $this->db->fetchOne(
            'SELECT * FROM sessions WHERE user_id = :user_id AND session_token = :token',
            [
                'user_id' => $_SESSION['user_id'],
                'token' => $_SESSION['session_token']
            ]
        );
        
        if (!$session) {
            return false;
        }
        
        // Check if session has expired
        if (strtotime($session['expires_at']) < time()) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    /**
     * Require authentication - redirect to login if not authenticated
     * 
     * @param string $redirectUrl URL to redirect to after login
     * @return void
     */
    public function requireAuth($redirectUrl = null) {
        if (!$this->isLoggedIn()) {
            if ($redirectUrl) {
                $_SESSION['redirect_after_login'] = $redirectUrl;
            }
            // Redirect to auth/google-login.php from current location
            $authPath = (strpos($_SERVER['REQUEST_URI'], '/dashboard/') !== false) 
                ? '../auth/google-login.php' 
                : 'auth/google-login.php';
            redirect($authPath);
        }
    }
    
    /**
     * Get the current user data
     * 
     * @return array|false User data or false if not logged in
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user = $this->db->fetchOne(
            'SELECT * FROM users WHERE id = :id',
            ['id' => $_SESSION['user_id']]
        );
        
        return $user;
    }
    
    /**
     * Log out the current user
     * 
     * @return bool True on success
     */
    public function logout() {
        // Log the activity before destroying session
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
            
            // Delete session from database
            if (isset($_SESSION['session_token'])) {
                $this->db->delete(
                    'sessions',
                    'session_token = :token',
                    ['token' => $_SESSION['session_token']]
                );
            }
        }
        
        // Clear all session data
        $_SESSION = [];
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
        
        return true;
    }
    
    /**
     * Clean up expired sessions
     * 
     * @return int Number of sessions deleted
     */
    public function cleanupExpiredSessions() {
        try {
            $result = $this->db->query(
                'DELETE FROM sessions WHERE expires_at < :now',
                ['now' => date('Y-m-d H:i:s')]
            );
            
            return $result->rowCount();
        } catch (Exception $e) {
            error_log('Error cleaning up expired sessions: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Log user activity
     * 
     * @param int $userId The user ID
     * @param string $action The action performed
     * @param string $description Description of the action
     * @return bool True on success, false on failure
     */
    private function logActivity($userId, $action, $description = '') {
        try {
            $this->db->insert('activity_log', [
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip_address' => getClientIP(),
                'user_agent' => getUserAgent(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('Error logging activity: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the Google OAuth handler
     * 
     * @return GoogleOAuth The Google OAuth instance
     */
    public function getGoogleOAuth() {
        return $this->googleOAuth;
    }
}

