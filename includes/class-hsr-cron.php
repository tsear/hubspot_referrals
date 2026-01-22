<?php
/**
 * Cron Jobs and Scheduled Tasks
 * 
 * Handles scheduled tasks like monthly stats emails
 *
 * @package HubSpot_Referrals
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSR_Cron {
    
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
        // Register cron hooks
        add_action('hsr_monthly_stats_cron', array($this, 'send_monthly_stats'));
        
        // Schedule cron on plugin activation (if not already scheduled)
        if (!wp_next_scheduled('hsr_monthly_stats_cron')) {
            // Schedule for first day of each month at 9 AM
            $first_run = strtotime('first day of next month 09:00:00');
            wp_schedule_event($first_run, 'monthly', 'hsr_monthly_stats_cron');
        }
    }
    
    /**
     * Send monthly stats to all partners
     */
    public function send_monthly_stats() {
        $api = HSR_API::instance();
        $email_handler = HSR_Email::instance();
        
        if (!$api->is_configured()) {
            error_log('HSR: Cannot send monthly stats - API not configured');
            return;
        }
        
        // Get all referral partners
        $referral_codes = $api->get_all_referrals();
        
        if (empty($referral_codes)) {
            error_log('HSR: No referral partners found for monthly stats');
            return;
        }
        
        $sent_count = 0;
        
        foreach ($referral_codes as $code => $data) {
            // Skip if no email
            if (empty($data['email'])) {
                continue;
            }
            
            $partner_name = $data['first_name'] . ' ' . $data['last_name'];
            
            // Calculate stats for this partner
            $stats = array(
                'clicks' => $data['click_count'] ?? 0,
                'conversions' => $data['conversion_count'] ?? 0,
                'conversion_rate' => '0%'
            );
            
            // Calculate conversion rate
            if ($stats['clicks'] > 0) {
                $rate = round(($stats['conversions'] / $stats['clicks']) * 100, 1);
                $stats['conversion_rate'] = $rate . '%';
            }
            
            // Send email
            $sent = $email_handler->send_monthly_stats(
                $data['email'],
                $partner_name,
                $stats
            );
            
            if ($sent) {
                $sent_count++;
            }
            
            // Small delay to avoid overwhelming email server
            usleep(500000); // 0.5 second delay
        }
        
        error_log("HSR: Sent monthly stats to {$sent_count} partners");
        
        do_action('hsr_monthly_stats_sent', $sent_count);
    }
    
    /**
     * Unschedule cron on plugin deactivation
     */
    public static function unschedule() {
        $timestamp = wp_next_scheduled('hsr_monthly_stats_cron');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'hsr_monthly_stats_cron');
        }
    }
}
