<?php
/**
 * Shortcodes
 * 
 * Handles all public-facing shortcodes for the referral system
 *
 * @package HubSpot_Referrals
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSR_Shortcodes {
    
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
        add_shortcode('hsr_request_code', array($this, 'request_code_form'));
        add_shortcode('hubspot_referral_form', array($this, 'request_code_form')); // Alias
        add_shortcode('hsr_partner_dashboard', array($this, 'partner_dashboard'));
        add_shortcode('hubspot_partner_stats', array($this, 'partner_dashboard')); // Alias
    }
    
    /**
     * Public form for partners to request their referral code
     * 
     * Usage: [hsr_request_code]
     * Usage: [hsr_request_code title="Become a Partner" button_color="#ff0000"]
     * 
     * Attributes:
     * - title: Form title
     * - subtitle: Form subtitle
     * - button_text: Submit button text
     * - success_message: Success message
     * - button_color: Button background color (hex)
     * - accent_color: Accent color for gradients (hex)
     * - form_width: Maximum form width (px, %, etc)
     * - hide_custom_code: Hide custom code field (true/false)
     */
    public function request_code_form($atts) {
        // Get form builder settings
        $builder = get_option('hsr_form_builder', array());
        
        $atts = shortcode_atts(array(
            'title' => !empty($builder['default_title']) ? $builder['default_title'] : __('Request Your Referral Link', 'hubspot-referrals'),
            'subtitle' => !empty($builder['default_subtitle']) ? $builder['default_subtitle'] : __('Join our referral program and start earning rewards', 'hubspot-referrals'),
            'button_text' => !empty($builder['default_button_text']) ? $builder['default_button_text'] : __('Get My Referral Link', 'hubspot-referrals'),
            'success_message' => !empty($builder['default_success_message']) ? $builder['default_success_message'] : __('Success! Check your email for your unique referral link.', 'hubspot-referrals'),
            'button_color' => $builder['button_color'] ?? '#667eea',
            'button_hover_color' => $builder['button_hover_color'] ?? '#5568d3',
            'accent_color' => $builder['accent_color'] ?? '#764ba2',
            'text_color' => $builder['text_color'] ?? '#2c3e50',
            'border_color' => $builder['border_color'] ?? '#e0e0e0',
            'background_color' => $builder['background_color'] ?? '#ffffff',
            'form_width' => $builder['form_width'] ?? '700px',
            'border_radius' => $builder['border_radius'] ?? '12px',
            'padding' => $builder['padding'] ?? '40px',
            'field_style' => $builder['field_style'] ?? 'rounded',
            'title_size' => $builder['title_size'] ?? '28px',
            'title_weight' => $builder['title_weight'] ?? '700',
            'button_size' => $builder['button_size'] ?? '16px',
            'button_weight' => $builder['button_weight'] ?? '600',
            'hide_custom_code' => $builder['hide_custom_code'] ?? '0',
            'show_organization' => $builder['show_organization'] ?? '1',
            'show_info_box' => $builder['show_info_box'] ?? '1'
        ), $atts, 'hsr_request_code');
        
        // Normalize boolean values
        $atts['hide_custom_code'] = filter_var($atts['hide_custom_code'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_organization'] = filter_var($atts['show_organization'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_info_box'] = filter_var($atts['show_info_box'], FILTER_VALIDATE_BOOLEAN);
        
        ob_start();
        include HSR_PLUGIN_DIR . 'templates/public-form.php';
        return ob_get_clean();
    }
    
    /**
     * Partner dashboard for viewing their own stats
     * 
     * Usage: [hsr_partner_dashboard]
     * Partners enter their email to view their stats
     */
    public function partner_dashboard($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Partner Dashboard', 'hubspot-referrals'),
            'subtitle' => __('View your referral performance', 'hubspot-referrals')
        ), $atts, 'hsr_partner_dashboard');
        
        ob_start();
        include HSR_PLUGIN_DIR . 'templates/partner-dashboard.php';
        return ob_get_clean();
    }
}
