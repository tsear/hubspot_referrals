<?php
/**
 * Settings Page
 * 
 * Handles plugin settings and configuration
 *
 * @package HubSpot_Referrals
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSR_Settings {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Option name
     */
    private $option_name = 'hsr_settings';
    
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
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'hsr_settings_group',
            $this->option_name,
            array($this, 'sanitize_settings')
        );
        
        // HubSpot API Section
        add_settings_section(
            'hsr_hubspot_section',
            __('HubSpot Configuration', 'hubspot-referrals'),
            array($this, 'render_hubspot_section'),
            'hubspot-referrals-settings'
        );
        
        add_settings_field(
            'hubspot_api_key',
            __('HubSpot API Key', 'hubspot-referrals'),
            array($this, 'render_api_key_field'),
            'hubspot-referrals-settings',
            'hsr_hubspot_section'
        );
        
        add_settings_field(
            'hubspot_portal_id',
            __('HubSpot Portal ID', 'hubspot-referrals'),
            array($this, 'render_portal_id_field'),
            'hubspot-referrals-settings',
            'hsr_hubspot_section'
        );
        
        // Tracking Section
        add_settings_section(
            'hsr_tracking_section',
            __('Tracking Settings', 'hubspot-referrals'),
            array($this, 'render_tracking_section'),
            'hubspot-referrals-settings'
        );
        
        add_settings_field(
            'referral_param',
            __('Referral URL Parameter', 'hubspot-referrals'),
            array($this, 'render_param_field'),
            'hubspot-referrals-settings',
            'hsr_tracking_section'
        );
        
        add_settings_field(
            'cookie_duration',
            __('Cookie Duration (days)', 'hubspot-referrals'),
            array($this, 'render_cookie_field'),
            'hubspot-referrals-settings',
            'hsr_tracking_section'
        );
        
        add_settings_field(
            'contact_page',
            __('Contact Page Path', 'hubspot-referrals'),
            array($this, 'render_contact_page_field'),
            'hubspot-referrals-settings',
            'hsr_tracking_section'
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['hubspot_api_key'] = sanitize_text_field($input['hubspot_api_key'] ?? '');
        $sanitized['hubspot_portal_id'] = sanitize_text_field($input['hubspot_portal_id'] ?? '');
        $sanitized['referral_param'] = sanitize_key($input['referral_param'] ?? 'referral_source');
        $sanitized['cookie_duration'] = absint($input['cookie_duration'] ?? 30);
        $sanitized['contact_page'] = sanitize_text_field($input['contact_page'] ?? '/contact/');
        
        // Ensure cookie duration is reasonable
        if ($sanitized['cookie_duration'] < 1) {
            $sanitized['cookie_duration'] = 1;
        }
        if ($sanitized['cookie_duration'] > 365) {
            $sanitized['cookie_duration'] = 365;
        }
        
        return $sanitized;
    }
    
    /**
     * Render HubSpot section description
     */
    public function render_hubspot_section() {
        echo '<p>' . esc_html__('Connect your HubSpot account to enable referral tracking.', 'hubspot-referrals') . '</p>';
    }
    
    /**
     * Render tracking section description
     */
    public function render_tracking_section() {
        echo '<p>' . esc_html__('Configure how referrals are tracked on your site.', 'hubspot-referrals') . '</p>';
    }
    
    /**
     * Render API key field
     */
    public function render_api_key_field() {
        $settings = get_option($this->option_name, array());
        $value = $settings['hubspot_api_key'] ?? '';
        ?>
        <input type="password" 
               name="<?php echo esc_attr($this->option_name); ?>[hubspot_api_key]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               autocomplete="off">
        <p class="description">
            <?php 
            printf(
                esc_html__('Enter your HubSpot Private App token (starts with pat-). %sCreate one here%s.', 'hubspot-referrals'),
                '<a href="https://app.hubspot.com/private-apps/" target="_blank">',
                '</a>'
            ); 
            ?>
        </p>
        <?php
    }
    
    /**
     * Render portal ID field
     */
    public function render_portal_id_field() {
        $settings = get_option($this->option_name, array());
        $value = $settings['hubspot_portal_id'] ?? '';
        ?>
        <input type="text" 
               name="<?php echo esc_attr($this->option_name); ?>[hubspot_portal_id]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="12345678">
        <p class="description">
            <?php esc_html_e('Your HubSpot portal ID (found in your HubSpot URL).', 'hubspot-referrals'); ?>
        </p>
        <?php
    }
    
    /**
     * Render referral param field
     */
    public function render_param_field() {
        $settings = get_option($this->option_name, array());
        $value = $settings['referral_param'] ?? 'referral_source';
        ?>
        <input type="text" 
               name="<?php echo esc_attr($this->option_name); ?>[referral_param]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text">
        <p class="description">
            <?php esc_html_e('The URL parameter used for referral tracking (e.g., ?referral_source=CODE).', 'hubspot-referrals'); ?>
        </p>
        <?php
    }
    
    /**
     * Render cookie duration field
     */
    public function render_cookie_field() {
        $settings = get_option($this->option_name, array());
        $value = $settings['cookie_duration'] ?? 30;
        ?>
        <input type="number" 
               name="<?php echo esc_attr($this->option_name); ?>[cookie_duration]" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               max="365"
               class="small-text">
        <p class="description">
            <?php esc_html_e('How long the referral cookie persists (1-365 days).', 'hubspot-referrals'); ?>
        </p>
        <?php
    }
    
    /**
     * Render contact page field
     */
    public function render_contact_page_field() {
        $settings = get_option($this->option_name, array());
        $value = $settings['contact_page'] ?? '/contact/';
        ?>
        <input type="text" 
               name="<?php echo esc_attr($this->option_name); ?>[contact_page]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="/contact/">
        <p class="description">
            <?php esc_html_e('Path to your contact page where referral links will point.', 'hubspot-referrals'); ?>
        </p>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_page() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Show save message
        if (isset($_GET['settings-updated'])) {
            add_settings_error('hsr_messages', 'hsr_message', __('Settings saved.', 'hubspot-referrals'), 'updated');
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors('hsr_messages'); ?>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('hsr_settings_group');
                do_settings_sections('hubspot-referrals-settings');
                submit_button(__('Save Settings', 'hubspot-referrals'));
                ?>
            </form>
            
            <!-- Connection Test -->
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2><?php esc_html_e('Test Connection', 'hubspot-referrals'); ?></h2>
                <p><?php esc_html_e('Verify your HubSpot API connection is working.', 'hubspot-referrals'); ?></p>
                <button type="button" id="hsr-test-connection" class="button button-secondary">
                    <?php esc_html_e('Test Connection', 'hubspot-referrals'); ?>
                </button>
                <span id="hsr-connection-result" style="margin-left: 10px;"></span>
            </div>
            
            <!-- HubSpot Properties Info -->
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2><?php esc_html_e('Required HubSpot Properties', 'hubspot-referrals'); ?></h2>
                <p><?php esc_html_e('Make sure these custom properties exist in your HubSpot contacts:', 'hubspot-referrals'); ?></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><code>referral_code</code> - <?php esc_html_e('Single-line text (for referrers)', 'hubspot-referrals'); ?></li>
                    <li><code>referral_source</code> - <?php esc_html_e('Single-line text (for converted leads)', 'hubspot-referrals'); ?></li>
                    <li><code>referral_clicks</code> - <?php esc_html_e('Number (optional, for tracking clicks)', 'hubspot-referrals'); ?></li>
                    <li><code>last_referral_click</code> - <?php esc_html_e('Date (optional, for tracking clicks)', 'hubspot-referrals'); ?></li>
                </ul>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#hsr-test-connection').on('click', function() {
                const $btn = $(this);
                const $result = $('#hsr-connection-result');
                
                $btn.prop('disabled', true);
                $result.html('<?php esc_html_e('Testing...', 'hubspot-referrals'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hsr_test_connection',
                        nonce: '<?php echo wp_create_nonce('hsr_admin'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                        } else {
                            $result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                        }
                    },
                    error: function() {
                        $result.html('<span style="color: red;">✗ <?php esc_html_e('Network error', 'hubspot-referrals'); ?></span>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
