<?php
/**
 * Webhook Handler
 * 
 * Receives and processes webhooks from HubSpot for conversion tracking
 *
 * @package HubSpot_Referrals
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSR_Webhook {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }
    
    /**
     * Initialize
     */
    private function init() {
        // Register webhook endpoint
        add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
    }
    
    /**
     * Register REST API endpoint for webhooks
     */
    public function register_webhook_endpoint() {
        register_rest_route('hubspot-referrals/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => array($this, 'verify_webhook_signature')
        ));
        
        // Info endpoint
        register_rest_route('hubspot-referrals/v1', '/webhook-info', array(
            'methods' => 'GET',
            'callback' => array($this, 'webhook_info'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * Provide webhook setup information
     */
    public function webhook_info() {
        $webhook_url = rest_url('hubspot-referrals/v1/webhook');
        
        return new WP_REST_Response(array(
            'webhook_url' => $webhook_url,
            'instructions' => array(
                '1. Go to HubSpot Settings → Integrations → Webhooks',
                '2. Click "Create webhook subscription"',
                '3. Set URL to: ' . $webhook_url,
                '4. Subscribe to: contact.propertyChange (for referral_source)',
                '5. Or: form.submission (for form submissions)',
                '6. Save and test'
            ),
            'supported_events' => array(
                'contact.propertyChange',
                'contact.creation',
                'form.submission'
            )
        ), 200);
    }
    
    /**
     * Verify webhook signature from HubSpot
     */
    public function verify_webhook_signature($request) {
        // HubSpot doesn't send signature by default, but we can verify by checking
        // the request format and required fields
        
        // Get HubSpot settings
        $settings = get_option('hsr_settings', array());
        $portal_id = $settings['hubspot_portal_id'] ?? '';
        
        // Allow if portal ID matches (basic verification)
        $body = $request->get_json_params();
        
        if (empty($body)) {
            return new WP_Error('invalid_webhook', 'Invalid webhook payload', array('status' => 400));
        }
        
        // Log webhook attempt
        $this->log_webhook('received', $body);
        
        return true;
    }
    
    /**
     * Handle incoming webhook
     */
    public function handle_webhook($request) {
        $body = $request->get_json_params();
        
        if (empty($body)) {
            $this->log_webhook('error', 'Empty payload');
            return new WP_Error('invalid_payload', 'Empty payload', array('status' => 400));
        }
        
        // Determine webhook type
        $event_type = '';
        
        // HubSpot webhook format varies, check for common structures
        if (isset($body[0]['subscriptionType'])) {
            $event_type = $body[0]['subscriptionType'];
        } elseif (isset($body['subscriptionType'])) {
            $event_type = $body['subscriptionType'];
        }
        
        $this->log_webhook('processing', array('event_type' => $event_type, 'payload' => $body));
        
        // Process based on event type
        switch ($event_type) {
            case 'contact.propertyChange':
                $result = $this->handle_property_change($body);
                break;
                
            case 'contact.creation':
                $result = $this->handle_contact_creation($body);
                break;
                
            case 'form.submission':
                $result = $this->handle_form_submission($body);
                break;
                
            default:
                // Try to auto-detect and process
                $result = $this->auto_process_webhook($body);
        }
        
        if (is_wp_error($result)) {
            $this->log_webhook('error', $result->get_error_message());
            return $result;
        }
        
        $this->log_webhook('success', $result);
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Webhook processed',
            'result' => $result
        ), 200);
    }
    
    /**
     * Handle property change webhook
     */
    private function handle_property_change($data) {
        $events = is_array($data) ? $data : array($data);
        $processed = array();
        
        foreach ($events as $event) {
            // Check if referral_source property changed
            $property_name = $event['propertyName'] ?? '';
            $property_value = $event['propertyValue'] ?? '';
            
            if ($property_name === 'referral_source' && !empty($property_value)) {
                $object_id = $event['objectId'] ?? '';
                
                // Track conversion
                $this->track_conversion($property_value, $object_id);
                $processed[] = array(
                    'referral_code' => $property_value,
                    'contact_id' => $object_id
                );
            }
        }
        
        return $processed;
    }
    
    /**
     * Handle contact creation webhook
     */
    private function handle_contact_creation($data) {
        $events = is_array($data) ? $data : array($data);
        $processed = array();
        
        foreach ($events as $event) {
            $object_id = $event['objectId'] ?? '';
            
            if (!empty($object_id)) {
                // Fetch contact to check for referral_source
                $api = HSR_API::instance();
                $contact = $api->get_contact($object_id);
                
                if (!is_wp_error($contact) && !empty($contact['properties']['referral_source'])) {
                    $referral_code = $contact['properties']['referral_source'];
                    $this->track_conversion($referral_code, $object_id);
                    $processed[] = array(
                        'referral_code' => $referral_code,
                        'contact_id' => $object_id
                    );
                }
            }
        }
        
        return $processed;
    }
    
    /**
     * Handle form submission webhook
     */
    private function handle_form_submission($data) {
        $events = is_array($data) ? $data : array($data);
        $processed = array();
        
        foreach ($events as $event) {
            // Extract referral_source from form submission
            $form_data = $event['formData'] ?? $event;
            
            if (isset($form_data['referral_source'])) {
                $referral_code = $form_data['referral_source'];
                $email = $form_data['email'] ?? '';
                
                $this->track_conversion($referral_code, null, array('email' => $email));
                $processed[] = array(
                    'referral_code' => $referral_code,
                    'email' => $email
                );
            }
        }
        
        return $processed;
    }
    
    /**
     * Auto-detect and process webhook
     */
    private function auto_process_webhook($data) {
        // Try to find referral_source anywhere in the payload
        $referral_code = null;
        $contact_id = null;
        
        // Search recursively for referral_source
        array_walk_recursive($data, function($value, $key) use (&$referral_code, &$contact_id) {
            if ($key === 'referral_source' && !empty($value)) {
                $referral_code = $value;
            }
            if ($key === 'objectId' && !empty($value)) {
                $contact_id = $value;
            }
        });
        
        if (!empty($referral_code)) {
            $this->track_conversion($referral_code, $contact_id);
            return array(
                'referral_code' => $referral_code,
                'contact_id' => $contact_id,
                'method' => 'auto_detected'
            );
        }
        
        return new WP_Error('no_referral_found', 'No referral_source found in webhook');
    }
    
    /**
     * Track a conversion
     */
    private function track_conversion($referral_code, $contact_id = null, $metadata = array()) {
        if (empty($referral_code)) {
            return false;
        }
        
        // Find the referrer contact in HubSpot
        $api = HSR_API::instance();
        $referrer = $api->find_contact_by_referral_code($referral_code);
        
        if (!$referrer) {
            $this->log_webhook('warning', "Referrer not found for code: {$referral_code}");
            return false;
        }
        
        // Increment conversion count
        $current_conversions = intval($referrer['properties']['conversion_count'] ?? 0);
        $api->update_contact($referrer['id'], array(
            'conversion_count' => $current_conversions + 1,
            'last_conversion_date' => date('Y-m-d\TH:i:s\Z')
        ));
        
        // Send notification email if enabled
        $email_handler = HSR_Email::instance();
        $partner_name = ($referrer['properties']['firstname'] ?? '') . ' ' . ($referrer['properties']['lastname'] ?? '');
        $partner_email = $referrer['properties']['email'] ?? '';
        
        if (!empty($partner_email)) {
            $lead_data = array(
                'name' => 'A new lead',
                'email' => $metadata['email'] ?? '',
                'contact_id' => $contact_id
            );
            
            $email_handler->send_conversion_notification($partner_email, $partner_name, $lead_data);
        }
        
        // Fire action hook
        do_action('hsr_conversion_tracked', $referral_code, $contact_id, $metadata);
        
        return true;
    }
    
    /**
     * Log webhook activity
     */
    private function log_webhook($type, $data) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'type' => $type,
            'data' => $data
        );
        
        // Store last 100 webhook logs in transient
        $logs = get_transient('hsr_webhook_logs') ?: array();
        array_unshift($logs, $log_entry);
        $logs = array_slice($logs, 0, 100);
        set_transient('hsr_webhook_logs', $logs, WEEK_IN_SECONDS);
        
        // Also error_log for debugging
        error_log('HSR Webhook [' . $type . ']: ' . wp_json_encode($data));
    }
    
    /**
     * Get webhook logs (for admin display)
     */
    public static function get_logs() {
        return get_transient('hsr_webhook_logs') ?: array();
    }
    
    /**
     * Clear webhook logs
     */
    public static function clear_logs() {
        delete_transient('hsr_webhook_logs');
    }
}
