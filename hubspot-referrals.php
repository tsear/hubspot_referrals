<?php
/**
 * Plugin Name: HubSpot Referrals
 * Plugin URI: https://github.com/tsear/hubspot_referrals
 * Description: A complete referral tracking system that integrates with HubSpot CRM. Track referral codes, conversions, and manage your referral program from WordPress.
 * Version: 1.0.0
 * Author: Tyler Sear
 * Author URI: https://smartgrantsolutions.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hubspot-referrals
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('HSR_VERSION', '1.0.0');
define('HSR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HSR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HSR_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
final class HubSpot_Referrals {
    
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
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core classes
        require_once HSR_PLUGIN_DIR . 'includes/class-hsr-api.php';
        require_once HSR_PLUGIN_DIR . 'includes/class-hsr-tracker.php';
        require_once HSR_PLUGIN_DIR . 'includes/class-hsr-admin.php';
        require_once HSR_PLUGIN_DIR . 'includes/class-hsr-settings.php';
        require_once HSR_PLUGIN_DIR . 'includes/class-hsr-ajax.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Init
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        if (!get_option('hsr_settings')) {
            update_option('hsr_settings', array(
                'hubspot_api_key' => '',
                'hubspot_portal_id' => '',
                'cookie_duration' => 30,
                'referral_param' => 'referral_source',
                'contact_page' => '/contact/'
            ));
        }
        
        // Clear any cached data
        delete_transient('hsr_referral_data');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear transients
        delete_transient('hsr_referral_data');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('hubspot-referrals', false, dirname(HSR_PLUGIN_BASENAME) . '/languages');
        
        // Initialize components
        HSR_Tracker::instance();
        HSR_Ajax::instance();
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        HSR_Admin::instance();
        HSR_Settings::instance();
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        $settings = get_option('hsr_settings', array());
        
        wp_enqueue_script(
            'hsr-referral-tracker',
            HSR_PLUGIN_URL . 'assets/js/referral-tracker.js',
            array(),
            HSR_VERSION,
            true
        );
        
        wp_localize_script('hsr-referral-tracker', 'hsrConfig', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hsr_tracking'),
            'cookieDuration' => intval($settings['cookie_duration'] ?? 30),
            'referralParam' => $settings['referral_param'] ?? 'referral_source',
            'contactPage' => $settings['contact_page'] ?? '/contact/',
            'siteUrl' => home_url()
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'hubspot-referrals') === false) {
            return;
        }
        
        wp_enqueue_style(
            'hsr-admin-styles',
            HSR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            HSR_VERSION
        );
        
        wp_enqueue_script(
            'hsr-admin-scripts',
            HSR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            HSR_VERSION,
            true
        );
        
        wp_localize_script('hsr-admin-scripts', 'hsrAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hsr_admin'),
            'contactPage' => get_option('hsr_settings')['contact_page'] ?? '/contact/',
            'siteUrl' => home_url()
        ));
    }
    
    /**
     * Get plugin settings
     */
    public static function get_settings() {
        return get_option('hsr_settings', array());
    }
    
    /**
     * Get a specific setting
     */
    public static function get_setting($key, $default = '') {
        $settings = self::get_settings();
        return $settings[$key] ?? $default;
    }
}

/**
 * Initialize plugin
 */
function hubspot_referrals() {
    return HubSpot_Referrals::instance();
}

// Start the plugin
hubspot_referrals();
