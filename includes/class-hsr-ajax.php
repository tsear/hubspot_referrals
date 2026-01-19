<?php
/**
 * AJAX Handlers
 * 
 * Handles all AJAX requests for the plugin
 *
 * @package HubSpot_Referrals
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSR_Ajax {
    
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
        // Admin AJAX
        add_action('wp_ajax_hsr_generate_code', array($this, 'generate_code'));
        add_action('wp_ajax_hsr_test_connection', array($this, 'test_connection'));
        add_action('wp_ajax_hsr_refresh_data', array($this, 'refresh_data'));
        
        // Public AJAX (for frontend forms)
        add_action('wp_ajax_hsr_generate_referral_link', array($this, 'generate_code'));
        add_action('wp_ajax_nopriv_hsr_generate_referral_link', array($this, 'generate_code'));
    }
    
    /**
     * Generate referral code
     */
    public function generate_code() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? $_POST['hsr_nonce'] ?? '', 'hsr_admin') && 
            !wp_verify_nonce($_POST['nonce'] ?? '', 'hsr_generate_code') &&
            !wp_verify_nonce($_POST['nonce'] ?? '', 'hsr_public')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'hubspot-referrals')
            ));
        }
        
        // Get form data
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $organization = sanitize_text_field($_POST['organization'] ?? '');
        $custom_code = sanitize_text_field($_POST['custom_code'] ?? '');
        
        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($email) || empty($organization)) {
            wp_send_json_error(array(
                'message' => __('Please fill in all required fields.', 'hubspot-referrals')
            ));
        }
        
        // Validate email
        if (!is_email($email)) {
            wp_send_json_error(array(
                'message' => __('Please enter a valid email address.', 'hubspot-referrals')
            ));
        }
        
        $api = HSR_API::instance();
        
        // Generate or validate custom code
        if (!empty($custom_code)) {
            // Validate custom code format
            if (!preg_match('/^[a-zA-Z0-9]{6,20}$/', $custom_code)) {
                wp_send_json_error(array(
                    'message' => __('Referral code must be 6-20 alphanumeric characters.', 'hubspot-referrals')
                ));
            }
            
            // Check if code exists
            if ($api->code_exists($custom_code)) {
                wp_send_json_error(array(
                    'message' => __('This referral code is already taken.', 'hubspot-referrals')
                ));
            }
            
            $referral_code = $custom_code;
        } else {
            // Auto-generate code
            $referral_code = $api->generate_unique_code($first_name, $last_name);
        }
        
        // Create contact in HubSpot
        $contact_id = $api->create_referral_contact($email, $first_name, $last_name, $organization, $referral_code);
        
        if (!$contact_id) {
            wp_send_json_error(array(
                'message' => __('Failed to create referral code. Please try again.', 'hubspot-referrals')
            ));
        }
        
        // Build referral link
        $contact_page = HubSpot_Referrals::get_setting('contact_page', '/contact/');
        $referral_param = HubSpot_Referrals::get_setting('referral_param', 'referral_source');
        $referral_link = home_url($contact_page . '?' . $referral_param . '=' . $referral_code);
        
        wp_send_json_success(array(
            'message' => __('Referral link created successfully!', 'hubspot-referrals'),
            'referral_code' => $referral_code,
            'referral_link' => $referral_link,
            'contact_id' => $contact_id
        ));
    }
    
    /**
     * Test HubSpot connection
     */
    public function test_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hsr_admin')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'hubspot-referrals')
            ));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'hubspot-referrals')
            ));
        }
        
        $api = HSR_API::instance();
        $result = $api->test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Refresh referral data
     */
    public function refresh_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hsr_admin')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'hubspot-referrals')
            ));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'hubspot-referrals')
            ));
        }
        
        // Clear any cached data
        delete_transient('hsr_referral_data');
        
        // Fetch fresh data
        $api = HSR_API::instance();
        $referrals = $api->get_all_referrals();
        
        wp_send_json_success(array(
            'message' => __('Data refreshed from HubSpot.', 'hubspot-referrals'),
            'count' => count($referrals)
        ));
    }
}
