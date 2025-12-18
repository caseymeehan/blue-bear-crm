<?php
/**
 * Google OAuth Handler
 * Manages Google OAuth 2.0 authentication flow
 */

class GoogleOAuth {
    private $client;
    private $db;
    
    public function __construct() {
        $this->client = new Google_Client();
        $this->client->setClientId(GOOGLE_CLIENT_ID);
        $this->client->setClientSecret(GOOGLE_CLIENT_SECRET);
        $this->client->setRedirectUri(GOOGLE_REDIRECT_URI);
        $this->client->addScope('email');
        $this->client->addScope('profile');
        
        // Set access type to online (we don't need offline access/refresh tokens)
        $this->client->setAccessType('online');
        
        require_once __DIR__ . '/../database/Database.php';
        $this->db = Database::getInstance();
    }
    
    /**
     * Generate the Google OAuth authorization URL
     * 
     * @return string The authorization URL
     */
    public function getAuthUrl() {
        // Generate and store state parameter for CSRF protection
        $state = generateSecureToken(32);
        $_SESSION['google_oauth_state'] = $state;
        $this->client->setState($state);
        
        return $this->client->createAuthUrl();
    }
    
    /**
     * Verify the OAuth state parameter
     * 
     * @param string $state The state parameter from the callback
     * @return bool True if valid, false otherwise
     */
    public function verifyState($state) {
        if (empty($_SESSION['google_oauth_state'])) {
            return false;
        }
        
        $valid = hash_equals($_SESSION['google_oauth_state'], $state);
        
        // Clear the state after verification
        unset($_SESSION['google_oauth_state']);
        
        return $valid;
    }
    
    /**
     * Exchange authorization code for access token
     * 
     * @param string $code The authorization code
     * @return array|false The token data or false on failure
     */
    public function getAccessToken($code) {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                error_log('Google OAuth error: ' . $token['error']);
                return false;
            }
            
            return $token;
        } catch (Exception $e) {
            error_log('Google OAuth exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user profile information from Google
     * 
     * @param array $token The access token data
     * @return array|false The user profile data or false on failure
     */
    public function getUserProfile($token) {
        try {
            $this->client->setAccessToken($token);
            
            // Get user info using Google OAuth2 service
            $oauth2 = new Google_Service_Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();
            
            return [
                'google_id' => $userInfo->id,
                'email' => $userInfo->email,
                'full_name' => $userInfo->name,
                'avatar_url' => $userInfo->picture,
                'email_verified' => $userInfo->verifiedEmail ? 1 : 0
            ];
        } catch (Exception $e) {
            error_log('Error fetching Google user profile: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Complete OAuth flow: get token and user profile
     * 
     * @param string $code The authorization code
     * @param string $state The state parameter for CSRF verification
     * @return array|false User profile data or false on failure
     */
    public function handleCallback($code, $state) {
        // Verify state parameter
        if (!$this->verifyState($state)) {
            error_log('Google OAuth state verification failed');
            return false;
        }
        
        // Exchange code for access token
        $token = $this->getAccessToken($code);
        if (!$token) {
            return false;
        }
        
        // Get user profile
        $profile = $this->getUserProfile($token);
        if (!$profile) {
            return false;
        }
        
        return $profile;
    }
    
    /**
     * Revoke the user's Google token (logout from Google)
     * 
     * @return bool True on success, false on failure
     */
    public function revokeToken() {
        try {
            if ($this->client->getAccessToken()) {
                return $this->client->revokeToken();
            }
            return true;
        } catch (Exception $e) {
            error_log('Error revoking Google token: ' . $e->getMessage());
            return false;
        }
    }
}

