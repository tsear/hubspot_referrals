<?php
/**
 * HubSpot API Integration
 * 
 * Handles all communication with HubSpot CRM API
 *
 * @package HubSpot_Referrals
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSR_API {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * API base URL
     */
    private $api_base = 'https://api.hubapi.com';
    
    /**
     * Get single instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get API key
     */
    private function get_api_key() {
        return HubSpot_Referrals::get_setting('hubspot_api_key', '');
    }
    
    /**
     * Check if API is configured
     */
    public function is_configured() {
        return !empty($this->get_api_key());
    }
    
    /**
     * Make authenticated request to HubSpot API
     */
    public function request($endpoint, $method = 'GET', $body = null) {
        $api_key = $this->get_api_key();
        
        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('HubSpot API key not configured', 'hubspot-referrals'));
        }
        
        $url = $this->api_base . $endpoint;
        
        // Check if it's a Private App token (starts with pat-) or Legacy API key
        $is_private_app = strpos($api_key, 'pat-') === 0;
        
        $args = array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        );
        
        if ($is_private_app) {
            $args['headers']['Authorization'] = 'Bearer ' . $api_key;
        } else {
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'hapikey=' . $api_key;
        }
        
        if ($body !== null) {
            $args['body'] = is_array($body) ? json_encode($body) : $body;
        }
        
        switch ($method) {
            case 'GET':
                $response = wp_remote_get($url, $args);
                break;
            case 'POST':
                $response = wp_remote_post($url, $args);
                break;
            case 'PATCH':
            case 'PUT':
            case 'DELETE':
                $args['method'] = $method;
                $response = wp_remote_request($url, $args);
                break;
            default:
                return new WP_Error('invalid_method', __('Invalid HTTP method', 'hubspot-referrals'));
        }
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return array(
            'status' => $status_code,
            'data' => $body
        );
    }
    
    /**
     * Check if referral code exists
     */
    public function code_exists($code) {
        if (!$this->is_configured()) {
            return false;
        }
        
        $result = $this->request('/crm/v3/objects/contacts/search', 'POST', array(
            'filterGroups' => array(
                array(
                    'filters' => array(
                        array(
                            'propertyName' => 'referral_code',
                            'operator' => 'EQ',
                            'value' => $code
                        )
                    )
                )
            ),
            'limit' => 1
        ));
        
        if (is_wp_error($result)) {
            return false;
        }
        
        return !empty($result['data']['results']);
    }
    
    /**
     * Generate unique referral code
     */
    public function generate_unique_code($first_name, $last_name) {
        // Clean and create base code
        $clean_first = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($first_name));
        $clean_last = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($last_name));
        $base = substr($clean_first, 0, 4) . substr($clean_last, 0, 4);
        
        // Try base code
        if (!$this->code_exists($base) && strlen($base) >= 6) {
            return $base;
        }
        
        // Add random suffix
        $attempts = 0;
        while ($attempts < 100) {
            $suffix = substr(md5(uniqid()), 0, 4);
            $code = substr($base . $suffix, 0, 20);
            
            if (!$this->code_exists($code) && strlen($code) >= 6) {
                return $code;
            }
            $attempts++;
        }
        
        // Fallback
        return substr(md5(uniqid()), 0, 12);
    }
    
    /**
     * Create or update contact with referral code
     */
    public function create_referral_contact($email, $first_name, $last_name, $organization, $referral_code) {
        if (!$this->is_configured()) {
            return false;
        }
        
        $properties = array(
            'email' => $email,
            'firstname' => $first_name,
            'lastname' => $last_name,
            'referral_code' => $referral_code
        );
        
        if (!empty($organization)) {
            $properties['company'] = $organization;
        }
        
        $result = $this->request('/crm/v3/objects/contacts', 'POST', array(
            'properties' => $properties
        ));
        
        if (is_wp_error($result)) {
            return false;
        }
        
        // Contact created successfully
        if ($result['status'] === 201 && !empty($result['data']['id'])) {
            return $result['data']['id'];
        }
        
        // Contact already exists - update instead
        if ($result['status'] === 409) {
            if (!empty($result['data']['message']) && 
                preg_match('/Contact already exists\. Existing ID: (\d+)/', $result['data']['message'], $matches)) {
                
                $contact_id = $matches[1];
                
                $update_result = $this->request('/crm/v3/objects/contacts/' . $contact_id, 'PATCH', array(
                    'properties' => $properties
                ));
                
                if (!is_wp_error($update_result)) {
                    return $contact_id;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Fetch all referral contacts with conversion counts
     */
    public function get_all_referrals() {
        if (!$this->is_configured()) {
            return array();
        }
        
        // Get all contacts with referral_code (referrers)
        $result = $this->request('/crm/v3/objects/contacts/search', 'POST', array(
            'filterGroups' => array(
                array(
                    'filters' => array(
                        array(
                            'propertyName' => 'referral_code',
                            'operator' => 'HAS_PROPERTY'
                        )
                    )
                )
            ),
            'properties' => array(
                'email',
                'firstname',
                'lastname',
                'company',
                'referral_code',
                'createdate'
            ),
            'limit' => 100
        ));
        
        if (is_wp_error($result) || empty($result['data']['results'])) {
            return array();
        }
        
        // Format referrers
        $referrals = array();
        
        foreach ($result['data']['results'] as $contact) {
            $props = $contact['properties'];
            $code = $props['referral_code'] ?? '';
            
            if (empty($code)) {
                continue;
            }
            
            $referrals[$code] = array(
                'first_name' => $props['firstname'] ?? '',
                'last_name' => $props['lastname'] ?? '',
                'email' => $props['email'] ?? '',
                'organization' => $props['company'] ?? 'N/A',
                'created_at' => !empty($props['createdate']) ? date('Y-m-d H:i:s', strtotime($props['createdate'])) : 'N/A',
                'hubspot_contact_id' => $contact['id'],
                'conversion_count' => 0,
                'referrals' => array()
            );
        }
        
        // Get all contacts with referral_source (converted leads)
        $conversions_result = $this->request('/crm/v3/objects/contacts/search', 'POST', array(
            'filterGroups' => array(
                array(
                    'filters' => array(
                        array(
                            'propertyName' => 'referral_source',
                            'operator' => 'HAS_PROPERTY'
                        )
                    )
                )
            ),
            'properties' => array(
                'email',
                'firstname',
                'lastname',
                'referral_source',
                'createdate'
            ),
            'limit' => 100
        ));
        
        if (!is_wp_error($conversions_result) && !empty($conversions_result['data']['results'])) {
            foreach ($conversions_result['data']['results'] as $converted_contact) {
                $converted_props = $converted_contact['properties'];
                $source_code = $converted_props['referral_source'] ?? '';
                
                if (!empty($source_code) && isset($referrals[$source_code])) {
                    $referrals[$source_code]['conversion_count']++;
                    $referrals[$source_code]['referrals'][] = array(
                        'email' => $converted_props['email'] ?? '',
                        'first_name' => $converted_props['firstname'] ?? '',
                        'last_name' => $converted_props['lastname'] ?? '',
                        'created_at' => !empty($converted_props['createdate']) ? date('Y-m-d H:i:s', strtotime($converted_props['createdate'])) : 'N/A'
                    );
                }
            }
        }
        
        // Cache for 5 minutes
        set_transient($cache_key, $referrals, 5 * MINUTE_IN_SECONDS);
        
        return $referrals;
    }
    
    /**
     * Get contact by referral code
     */
    public function get_contact_by_code($code) {
        if (!$this->is_configured()) {
            return false;
        }
        
        $result = $this->request('/crm/v3/objects/contacts/search', 'POST', array(
            'filterGroups' => array(
                array(
                    'filters' => array(
                        array(
                            'propertyName' => 'referral_code',
                            'operator' => 'EQ',
                            'value' => $code
                        )
                    )
                )
            ),
            'limit' => 1
        ));
        
        if (is_wp_error($result) || empty($result['data']['results'][0])) {
            return false;
        }
        
        return $result['data']['results'][0];
    }
    
    /**
     * Get contact by email
     */
    public function get_contact_by_email($email) {
        if (!$this->is_configured()) {
            return false;
        }
        
        $result = $this->request('/crm/v3/objects/contacts/search', 'POST', array(
            'filterGroups' => array(
                array(
                    'filters' => array(
                        array(
                            'propertyName' => 'email',
                            'operator' => 'EQ',
                            'value' => $email
                        )
                    )
                )
            ),
            'limit' => 1
        ));
        
        if (is_wp_error($result) || empty($result['data']['results'][0])) {
            return false;
        }
        
        return $result['data']['results'][0];
    }
    
    /**
     * Track referral click
     */
    public function track_click($referral_code) {
        $contact = $this->get_contact_by_code($referral_code);
        
        if (!$contact) {
            return false;
        }
        
        $contact_id = $contact['id'];
        $current_clicks = intval($contact['properties']['referral_clicks'] ?? 0);
        
        $result = $this->request('/crm/v3/objects/contacts/' . $contact_id, 'PATCH', array(
            'properties' => array(
                'referral_clicks' => $current_clicks + 1,
                'last_referral_click' => date('Y-m-d\TH:i:s\Z')
            )
        ));
        
        return !is_wp_error($result);
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $result = $this->request('/crm/v3/objects/contacts?limit=1', 'GET');
        
        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => $result->get_error_message()
            );
        }
        
        if ($result['status'] === 200) {
            return array(
                'success' => true,
                'message' => __('Successfully connected to HubSpot', 'hubspot-referrals')
            );
        }
        
        return array(
            'success' => false,
            'message' => sprintf(__('API returned status %d', 'hubspot-referrals'), $result['status'])
        );
    }
    
    /**
     * Enroll contact in HubSpot workflow
     * 
     * @param string $email Contact email to enroll
     * @param int $workflow_id HubSpot workflow ID
     * @return bool Success status
     */
    public function enroll_in_workflow($email, $workflow_id) {
        if (empty($email) || empty($workflow_id)) {
            return false;
        }
        
        // HubSpot Workflows API v3 endpoint
        $result = $this->request("/automation/v4/flows/{$workflow_id}/enrollments/contacts", 'POST', array(
            'emails' => array($email)
        ));
        
        if (is_wp_error($result)) {
            error_log('HSR: Failed to enroll in workflow - ' . $result->get_error_message());
            return false;
        }
        
        return $result['status'] >= 200 && $result['status'] < 300;
    }
    
    /**
     * Find contact by email
     */
    public function find_contact_by_email($email) {
        if (empty($email)) {
            return false;
        }
        
        // Check cache first
        $cache_key = 'hsr_contact_' . md5($email);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $result = $this->request('/crm/v3/objects/contacts/search', 'POST', array(
            'filterGroups' => array(
                array(
                    'filters' => array(
                        array(
                            'propertyName' => 'email',
                            'operator' => 'EQ',
                            'value' => $email
                        )
                    )
                )
            ),
            'properties' => array(
                'firstname',
                'lastname',
                'email',
                'company',
                'referral_code',
                'referral_clicks',
                'conversion_count',
                'last_referral_click',
                'last_conversion_date'
            ),
            'limit' => 1
        ));
        
        if (is_wp_error($result) || empty($result['data']['results'][0])) {
            return false;
        }
        
        $contact = $result['data']['results'][0];
        
        // Cache for 5 minutes
        set_transient($cache_key, $contact, 5 * MINUTE_IN_SECONDS);
        
        return $contact;
    }
    
    /**
     * Find contact by referral code
     */
    public function find_contact_by_referral_code($code) {
        if (empty($code)) {
            return false;
        }
        
        $cache_key = 'hsr_referrer_' . md5($code);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $result = $this->request('/crm/v3/objects/contacts/search', 'POST', array(
            'filterGroups' => array(
                array(
                    'filters' => array(
                        array(
                            'propertyName' => 'referral_code',
                            'operator' => 'EQ',
                            'value' => $code
                        )
                    )
                )
            ),
            'properties' => array(
                'firstname',
                'lastname',
                'email',
                'company',
                'referral_code',
                'conversion_count',
                'last_conversion_date'
            ),
            'limit' => 1
        ));
        
        if (is_wp_error($result) || empty($result['data']['results'][0])) {
            return false;
        }
        
        $contact = $result['data']['results'][0];
        
        // Cache for 5 minutes
        set_transient($cache_key, $contact, 5 * MINUTE_IN_SECONDS);
        
        return $contact;
    }
    
    /**
     * Get recent conversions for a referral code
     */
    public function get_recent_conversions($referral_code, $limit = 10) {
        $result = $this->request('/crm/v3/objects/contacts/search', 'POST', array(
            'filterGroups' => array(
                array(
                    'filters' => array(
                        array(
                            'propertyName' => 'referral_source',
                            'operator' => 'EQ',
                            'value' => $referral_code
                        )
                    )
                )
            ),
            'properties' => array(
                'firstname',
                'lastname',
                'email',
                'createdate'
            ),
            'sorts' => array(
                array(
                    'propertyName' => 'createdate',
                    'direction' => 'DESCENDING'
                )
            ),
            'limit' => $limit
        ));
        
        if (is_wp_error($result) || empty($result['data']['results'])) {
            return array();
        }
        
        $conversions = array();
        foreach ($result['data']['results'] as $contact) {
            $props = $contact['properties'];
            $conversions[] = array(
                'name' => ($props['firstname'] ?? '') . ' ' . ($props['lastname'] ?? ''),
                'email' => $props['email'] ?? '',
                'date' => !empty($props['createdate']) ? date('M j, Y', strtotime($props['createdate'])) : 'N/A'
            );
        }
        
        return $conversions;
    }
    
    /**
     * Get contact by ID
     */
    public function get_contact($contact_id) {
        if (empty($contact_id)) {
            return new WP_Error('invalid_id', 'Invalid contact ID');
        }
        
        return $this->request("/crm/v3/objects/contacts/{$contact_id}", 'GET');
    }
    
    /**
     * Update contact properties
     */
    public function update_contact($contact_id, $properties) {
        if (empty($contact_id) || empty($properties)) {
            return false;
        }
        
        $result = $this->request("/crm/v3/objects/contacts/{$contact_id}", 'PATCH', array(
            'properties' => $properties
        ));
        
        // Clear cache
        delete_transient('hsr_api_cache_referrals');
        
        return !is_wp_error($result);
    }
}
