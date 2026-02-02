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
        add_action('wp_ajax_hsr_bulk_import', array($this, 'bulk_import'));
        add_action('wp_ajax_hsr_clear_logs', array($this, 'clear_logs'));
        add_action('wp_ajax_hsr_update_partner', array($this, 'update_partner'));
        add_action('wp_ajax_hsr_toggle_directory', array($this, 'toggle_directory'));
        add_action('wp_ajax_hsr_get_partner', array($this, 'get_partner'));
        
        // Public AJAX (for frontend forms)
        add_action('wp_ajax_hsr_generate_referral_link', array($this, 'generate_code'));
        add_action('wp_ajax_nopriv_hsr_generate_referral_link', array($this, 'generate_code'));
        add_action('wp_ajax_hsr_partner_login', array($this, 'partner_login'));
        add_action('wp_ajax_nopriv_hsr_partner_login', array($this, 'partner_login'));
        add_action('wp_ajax_hsr_preview_form', array($this, 'preview_form'));
    }
    
    /**
     * Generate referral code
     */
    public function generate_code() {
        // Verify nonce - check all possible nonce fields
        $nonce_verified = false;
        
        // Check admin nonce
        if (isset($_POST['hsr_nonce']) && wp_verify_nonce($_POST['hsr_nonce'], 'hsr_admin')) {
            $nonce_verified = true;
        }
        
        // Check public form nonce
        if (isset($_POST['hsr_public_nonce']) && wp_verify_nonce($_POST['hsr_public_nonce'], 'hsr_public')) {
            $nonce_verified = true;
        }
        
        // Check generic nonce field
        if (isset($_POST['nonce']) && (wp_verify_nonce($_POST['nonce'], 'hsr_admin') || wp_verify_nonce($_POST['nonce'], 'hsr_public'))) {
            $nonce_verified = true;
        }
        
        if (!$nonce_verified) {
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
        
        // Prepare partner data
        $partner_data = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'organization' => $organization,
            'referral_code' => $referral_code,
            'referral_link' => $referral_link,
            'contact_id' => $contact_id
        );
        
        // Send welcome email if requested
        $email_sent = false;
        if (!empty($_POST['send_email']) && $_POST['send_email'] === '1') {
            $email_handler = HSR_Email::instance();
            $email_sent = $email_handler->send_welcome_email($partner_data);
            
            // Notify admin
            $email_handler->notify_admin_new_partner($partner_data);
        }
        
        // Fire action hook for extensibility
        do_action('hsr_code_generated', $referral_code, $email, $partner_data);
        
        wp_send_json_success(array(
            'message' => __('Referral link created successfully!', 'hubspot-referrals'),
            'referral_code' => $referral_code,
            'referral_link' => $referral_link,
            'contact_id' => $contact_id,
            'email_sent' => $email_sent
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
    
    /**
     * Partner login - retrieve stats by email
     */
    public function partner_login() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hsr_partner_login')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'hubspot-referrals')
            ));
        }
        
        $email = sanitize_email($_POST['email'] ?? '');
        
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array(
                'message' => __('Please enter a valid email address.', 'hubspot-referrals')
            ));
        }
        
        $api = HSR_API::instance();
        
        // Find partner by email
        $partner = $api->find_contact_by_email($email);
        
        if (!$partner || empty($partner['properties']['referral_code'])) {
            wp_send_json_error(array(
                'message' => __('No referral partner found with this email.', 'hubspot-referrals')
            ));
        }
        
        // Get stats
        $referral_code = $partner['properties']['referral_code'];
        $clicks = intval($partner['properties']['referral_clicks'] ?? 0);
        $conversions = intval($partner['properties']['conversion_count'] ?? 0);
        $conversion_rate = $clicks > 0 ? round(($conversions / $clicks) * 100, 1) . '%' : '0%';
        
        // Build referral link
        $settings = get_option('hsr_settings', array());
        $contact_page = $settings['contact_page'] ?? '/contact/';
        $referral_param = $settings['referral_param'] ?? 'referral_source';
        $referral_link = home_url($contact_page . '?' . $referral_param . '=' . $referral_code);
        
        // Get recent conversions
        $recent_conversions = $api->get_recent_conversions($referral_code);
        
        wp_send_json_success(array(
            'first_name' => $partner['properties']['firstname'] ?? 'Partner',
            'last_name' => $partner['properties']['lastname'] ?? '',
            'organization' => $partner['properties']['company'] ?? '',
            'referral_code' => $referral_code,
            'referral_link' => $referral_link,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'conversion_rate' => $conversion_rate,
            'recent_conversions' => $recent_conversions
        ));
    }
    
    /**
     * Bulk import partners from CSV
     */
    public function bulk_import() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hsr_bulk_import')) {
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
        
        // Get CSV data
        $csv_data = $_POST['csv_data'] ?? '';
        
        if (empty($csv_data)) {
            wp_send_json_error(array(
                'message' => __('No CSV data provided.', 'hubspot-referrals')
            ));
        }
        
        $api = HSR_API::instance();
        $email_handler = HSR_Email::instance();
        
        // Parse CSV
        $lines = explode("\n", trim($csv_data));
        $header = str_getcsv(array_shift($lines));
        
        $imported = 0;
        $failed = 0;
        $errors = array();
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $data = str_getcsv($line);
            $row = array_combine($header, $data);
            
            // Validate required fields
            if (empty($row['email']) || empty($row['first_name']) || empty($row['last_name'])) {
                $failed++;
                $errors[] = sprintf(__('Missing required fields for: %s', 'hubspot-referrals'), $row['email'] ?? 'unknown');
                continue;
            }
            
            $email = sanitize_email($row['email']);
            $first_name = sanitize_text_field($row['first_name']);
            $last_name = sanitize_text_field($row['last_name']);
            $organization = sanitize_text_field($row['organization'] ?? $row['company'] ?? '');
            $custom_code = sanitize_text_field($row['referral_code'] ?? '');
            
            // Generate or use custom code
            if (!empty($custom_code)) {
                if ($api->code_exists($custom_code)) {
                    $failed++;
                    $errors[] = sprintf(__('Code already exists: %s', 'hubspot-referrals'), $custom_code);
                    continue;
                }
                $referral_code = $custom_code;
            } else {
                $referral_code = $api->generate_unique_code($first_name, $last_name);
            }
            
            // Create contact
            $contact_id = $api->create_referral_contact($email, $first_name, $last_name, $organization, $referral_code);
            
            if ($contact_id) {
                $imported++;
                
                // Send welcome email if requested
                if (!empty($_POST['send_emails']) && $_POST['send_emails'] === '1') {
                    $settings = get_option('hsr_settings', array());
                    $contact_page = $settings['contact_page'] ?? '/contact/';
                    $referral_param = $settings['referral_param'] ?? 'referral_source';
                    $referral_link = home_url($contact_page . '?' . $referral_param . '=' . $referral_code);
                    
                    $partner_data = array(
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'organization' => $organization,
                        'referral_code' => $referral_code,
                        'referral_link' => $referral_link,
                        'contact_id' => $contact_id
                    );
                    
                    $email_handler->send_welcome_email($partner_data);
                }
            } else {
                $failed++;
                $errors[] = sprintf(__('Failed to create: %s', 'hubspot-referrals'), $email);
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Import complete: %d imported, %d failed', 'hubspot-referrals'), $imported, $failed),
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors
        ));
    }
    
    /**
     * Clear webhook logs
     */
    public function clear_logs() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hsr_clear_logs')) {
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
        
        HSR_Webhook::clear_logs();
        
        wp_send_json_success(array(
            'message' => __('Logs cleared successfully.', 'hubspot-referrals')
        ));
    }
    
    /**
     * Preview form with current builder settings
     */
    public function preview_form() {
        // Get form builder settings
        $builder_settings = get_option('hsr_form_builder', array());
        
        // Build shortcode attributes from settings
        $atts = array(
            'button_color' => $builder_settings['button_color'] ?? '#667eea',
            'accent_color' => $builder_settings['accent_color'] ?? '#764ba2',
            'form_width' => $builder_settings['form_width'] ?? '700px',
            'hide_custom_code' => $builder_settings['hide_custom_code'] ?? '0',
            'title' => !empty($builder_settings['default_title']) ? $builder_settings['default_title'] : 'Request Your Referral Link',
            'subtitle' => !empty($builder_settings['default_subtitle']) ? $builder_settings['default_subtitle'] : 'Join our referral program',
            'button_text' => !empty($builder_settings['default_button_text']) ? $builder_settings['default_button_text'] : 'Get My Referral Link',
        );
        
        // Output minimal HTML with form
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <?php wp_head(); ?>
        </head>
        <body style="background: #f5f5f5; padding: 20px;">
            <?php echo do_shortcode('[hsr_request_code ' . http_build_query($atts, '', ' ') . ']'); ?>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Update partner directory information
     */
    public function update_partner() {
        check_ajax_referer('hsr_update_partner', 'hsr_partner_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'hubspot-referrals')));
        }
        
        $contact_id = sanitize_text_field($_POST['contact_id'] ?? '');
        $logo_url = esc_url_raw($_POST['logo_url'] ?? '');
        $directory_description = sanitize_textarea_field($_POST['directory_description'] ?? '');
        $website_url = esc_url_raw($_POST['website_url'] ?? '');
        $directory_order = intval($_POST['directory_order'] ?? 999);
        
        if (empty($contact_id)) {
            wp_send_json_error(array('message' => __('Invalid contact ID.', 'hubspot-referrals')));
        }
        
        $api = HSR_API::instance();
        $properties = array(
            'logo_url' => $logo_url,
            'directory_description' => $directory_description,
            'website_url' => $website_url,
            'directory_order' => (string) $directory_order
        );
        
        $success = $api->update_contact($contact_id, $properties);
        
        if ($success) {
            // Clear cache
            delete_transient('hsr_api_cache_referrals');
            
            wp_send_json_success(array(
                'message' => __('Partner information updated successfully.', 'hubspot-referrals')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to update partner information.', 'hubspot-referrals')
            ));
        }
    }
    
    /**
     * Toggle partner directory visibility
     */
    public function toggle_directory() {
        check_ajax_referer('hsr_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'hubspot-referrals')));
        }
        
        $contact_id = sanitize_text_field($_POST['contact_id'] ?? '');
        $show_in_directory = filter_var($_POST['show_in_directory'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        if (empty($contact_id)) {
            wp_send_json_error(array('message' => __('Invalid contact ID.', 'hubspot-referrals')));
        }
        
        $api = HSR_API::instance();
        $success = $api->update_contact($contact_id, array(
            'show_in_directory' => $show_in_directory ? 'true' : 'false'
        ));
        
        if ($success) {
            // Clear cache
            delete_transient('hsr_api_cache_referrals');
            
            wp_send_json_success(array(
                'message' => __('Directory visibility updated.', 'hubspot-referrals')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to update directory visibility.', 'hubspot-referrals')
            ));
        }
    }
    
    /**
     * Get partner data for editing
     */
    public function get_partner() {
        check_ajax_referer('hsr_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'hubspot-referrals')));
        }
        
        $contact_id = sanitize_text_field($_POST['contact_id'] ?? '');
        
        if (empty($contact_id)) {
            wp_send_json_error(array('message' => __('Invalid contact ID.', 'hubspot-referrals')));
        }
        
        $api = HSR_API::instance();
        $result = $api->get_contact($contact_id);
        
        if (!is_wp_error($result) && !empty($result['data']['properties'])) {
            wp_send_json_success(array(
                'partner' => $result['data']['properties']
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to load partner data.', 'hubspot-referrals')
            ));
        }
    }
}
