<?php
/**
 * Frontend Tracker
 * 
 * Handles referral cookie tracking on the frontend
 *
 * @package HubSpot_Referrals
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSR_Tracker {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Cookie name
     */
    private $cookie_name = 'hsr_referral_code';
    
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
        add_action('init', array($this, 'track_referral_visit'));
        add_action('wp_head', array($this, 'output_tracking_data'));
    }
    
    /**
     * Track referral visit
     */
    public function track_referral_visit() {
        $param = HubSpot_Referrals::get_setting('referral_param', 'referral_source');
        
        if (isset($_GET[$param]) && !empty($_GET[$param])) {
            $referral_code = sanitize_text_field($_GET[$param]);
            $this->set_referral_cookie($referral_code);
            
            // Track click in HubSpot
            $api = HSR_API::instance();
            $api->track_click($referral_code);
        }
    }
    
    /**
     * Set referral cookie
     */
    public function set_referral_cookie($code) {
        $duration = intval(HubSpot_Referrals::get_setting('cookie_duration', 30));
        $expiry = time() + ($duration * DAY_IN_SECONDS);
        
        setcookie($this->cookie_name, $code, $expiry, '/', '', is_ssl(), false);
        $_COOKIE[$this->cookie_name] = $code;
    }
    
    /**
     * Get referral code from cookie
     */
    public function get_referral_code() {
        return isset($_COOKIE[$this->cookie_name]) ? sanitize_text_field($_COOKIE[$this->cookie_name]) : '';
    }
    
    /**
     * Output tracking data for frontend JS
     */
    public function output_tracking_data() {
        $referral_code = $this->get_referral_code();
        
        if (empty($referral_code)) {
            return;
        }
        
        ?>
        <script type="text/javascript">
            window.hsrReferralCode = <?php echo json_encode($referral_code); ?>;
        </script>
        <?php
    }
    
    /**
     * Check if visitor has referral code
     */
    public function has_referral() {
        return !empty($this->get_referral_code());
    }
    
    /**
     * Clear referral cookie
     */
    public function clear_referral() {
        setcookie($this->cookie_name, '', time() - 3600, '/', '', is_ssl(), false);
        unset($_COOKIE[$this->cookie_name]);
    }
}
