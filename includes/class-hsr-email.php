<?php
/**
 * Email Handler
 * 
 * Handles all email notifications for the referral system
 *
 * @package HubSpot_Referrals
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSR_Email {
    
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
        // Email content type filter
        add_filter('wp_mail_content_type', array($this, 'set_html_content_type'));
    }
    
    /**
     * Set email content type to HTML
     */
    public function set_html_content_type() {
        return 'text/html';
    }
    
    /**
     * Send welcome email to new partner with their referral link
     */
    public function send_welcome_email($partner_data) {
        // Check email method setting
        $settings = get_option('hsr_settings', array());
        $email_method = $settings['email_method'] ?? 'wordpress';
        
        // If email disabled or HubSpot method, handle differently
        if ($email_method === 'none') {
            error_log('HSR: Email sending disabled in settings');
            return false;
        }
        
        if ($email_method === 'hubspot') {
            return $this->send_via_hubspot($partner_data);
        }
        
        // Default: WordPress email
        $to = $partner_data['email'];
        $subject = sprintf(
            __('Welcome to the %s Referral Program!', 'hubspot-referrals'),
            get_bloginfo('name')
        );
        
        $template_data = array(
            'first_name' => $partner_data['first_name'],
            'last_name' => $partner_data['last_name'],
            'organization' => $partner_data['organization'],
            'referral_code' => $partner_data['referral_code'],
            'referral_link' => $partner_data['referral_link'],
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url()
        );
        
        $message = $this->get_template('welcome', $template_data);
        
        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . get_option('admin_email')
        );
        
        $sent = wp_mail($to, $subject, $message, $headers);
        
        if ($sent) {
            error_log("HSR: Welcome email sent to {$to}");
            do_action('hsr_welcome_email_sent', $partner_data);
        } else {
            error_log("HSR: Failed to send welcome email to {$to}");
        }
        
        return $sent;
    }
    
    /**
     * Send email via HubSpot workflow
     */
    private function send_via_hubspot($partner_data) {
        $settings = get_option('hsr_settings', array());
        $workflow_id = $settings['hubspot_workflow_id'] ?? '';
        
        if (empty($workflow_id)) {
            error_log('HSR: HubSpot email method selected but no workflow ID configured');
            return false;
        }
        
        $api = HSR_API::instance();
        $enrolled = $api->enroll_in_workflow($partner_data['email'], $workflow_id);
        
        if ($enrolled) {
            error_log("HSR: Enrolled {$partner_data['email']} in HubSpot workflow {$workflow_id}");
            do_action('hsr_welcome_email_sent', $partner_data);
        } else {
            error_log("HSR: Failed to enroll {$partner_data['email']} in HubSpot workflow");
        }
        
        return $enrolled;
    }
    
    /**
     * Send conversion notification to partner
     */
    public function send_conversion_notification($partner_email, $partner_name, $lead_data) {
        // Check if emails are enabled
        $settings = get_option('hsr_settings', array());
        $email_method = $settings['email_method'] ?? 'wordpress';
        
        if ($email_method !== 'wordpress') {
            return false; // Conversion notifications only via WordPress for now
        }
        
        $to = $partner_email;
        $subject = __('New Referral Conversion! 🎉', 'hubspot-referrals');
        
        $template_data = array(
            'partner_name' => $partner_name,
            'lead_name' => $lead_data['name'] ?? 'A new lead',
            'lead_email' => $lead_data['email'] ?? '',
            'conversion_date' => date('F j, Y'),
            'site_name' => get_bloginfo('name')
        );
        
        $message = $this->get_template('conversion', $template_data);
        
        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send monthly stats to partner
     */
    public function send_monthly_stats($partner_email, $partner_name, $stats) {
        // Check if monthly stats are enabled
        $settings = get_option('hsr_settings', array());
        $send_monthly = $settings['send_monthly_stats'] ?? '1';
        $email_method = $settings['email_method'] ?? 'wordpress';
        
        if ($send_monthly !== '1' || $email_method !== 'wordpress') {
            return false; // Only send via WordPress if enabled
        }
        
        $to = $partner_email;
        $subject = sprintf(
            __('Your %s Referral Stats - %s', 'hubspot-referrals'),
            get_bloginfo('name'),
            date('F Y')
        );
        
        $template_data = array(
            'partner_name' => $partner_name,
            'month' => date('F Y'),
            'total_clicks' => $stats['clicks'] ?? 0,
            'total_conversions' => $stats['conversions'] ?? 0,
            'conversion_rate' => $stats['conversion_rate'] ?? '0%',
            'site_name' => get_bloginfo('name'),
            'dashboard_url' => admin_url('admin.php?page=hubspot-referrals')
        );
        
        $message = $this->get_template('monthly-stats', $template_data);
        
        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Notify admin of new partner signup
     */
    public function notify_admin_new_partner($partner_data) {
        $to = get_option('admin_email');
        $subject = __('New Referral Partner Enrolled', 'hubspot-referrals');
        
        $message = sprintf(
            __("A new partner has been enrolled in the referral program:\n\nName: %s %s\nOrganization: %s\nEmail: %s\nReferral Code: %s\n\nView in dashboard: %s", 'hubspot-referrals'),
            $partner_data['first_name'],
            $partner_data['last_name'],
            $partner_data['organization'],
            $partner_data['email'],
            $partner_data['referral_code'],
            admin_url('admin.php?page=hubspot-referrals')
        );
        
        return wp_mail($to, $subject, $message);
    }
    
    /**
     * Get email template
     */
    private function get_template($template_name, $data = array()) {
        $template_path = HSR_PLUGIN_DIR . 'templates/emails/' . $template_name . '.php';
        
        if (!file_exists($template_path)) {
            return $this->get_fallback_template($template_name, $data);
        }
        
        ob_start();
        extract($data);
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Get fallback template if file doesn't exist
     */
    private function get_fallback_template($template_name, $data) {
        switch ($template_name) {
            case 'welcome':
                return $this->get_welcome_fallback($data);
            case 'conversion':
                return $this->get_conversion_fallback($data);
            case 'monthly-stats':
                return $this->get_stats_fallback($data);
            default:
                return '';
        }
    }
    
    /**
     * Welcome email fallback template
     */
    private function get_welcome_fallback($data) {
        return sprintf('
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%%, #764ba2 100%%); padding: 40px 20px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px;">Welcome to Our Referral Program!</h1>
    </div>
    
    <div style="background: #f9f9f9; padding: 40px 30px; border-radius: 0 0 10px 10px;">
        <p style="font-size: 18px; margin-bottom: 20px;">Hi %s,</p>
        
        <p style="font-size: 16px; margin-bottom: 20px;">
            Thank you for joining the <strong>%s</strong> referral program! We\'re excited to partner with <strong>%s</strong>.
        </p>
        
        <div style="background: white; padding: 25px; border-radius: 8px; margin: 30px 0; border-left: 4px solid #667eea;">
            <h2 style="margin-top: 0; color: #667eea; font-size: 20px;">Your Unique Referral Link</h2>
            <p style="margin: 15px 0;"><strong>Referral Code:</strong> <code style="background: #e9ecef; padding: 5px 10px; border-radius: 4px; font-size: 16px;">%s</code></p>
            <p style="margin: 15px 0;"><strong>Your Link:</strong></p>
            <div style="background: #e9ecef; padding: 15px; border-radius: 5px; word-break: break-all;">
                <a href="%s" style="color: #667eea; text-decoration: none;">%s</a>
            </div>
        </div>
        
        <div style="background: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 30px 0;">
            <h3 style="margin-top: 0; color: #856404;">How It Works</h3>
            <ol style="margin: 10px 0; padding-left: 20px;">
                <li style="margin-bottom: 10px;">Share your unique referral link with potential clients</li>
                <li style="margin-bottom: 10px;">They click your link and visit our contact page</li>
                <li style="margin-bottom: 10px;">When they submit the form, you get credit for the referral</li>
                <li>Track your conversions in HubSpot or contact us for a report</li>
            </ol>
        </div>
        
        <p style="font-size: 16px; margin-top: 30px;">
            If you have any questions, feel free to reply to this email.
        </p>
        
        <p style="font-size: 16px; margin-top: 20px;">
            Best regards,<br>
            <strong>The %s Team</strong>
        </p>
    </div>
    
    <div style="text-align: center; padding: 20px; color: #999; font-size: 12px;">
        <p>© %s %s. All rights reserved.</p>
        <p><a href="%s" style="color: #667eea;">Visit our website</a></p>
    </div>
</body>
</html>
        ',
            esc_html($data['first_name']),
            esc_html($data['site_name']),
            esc_html($data['organization']),
            esc_html($data['referral_code']),
            esc_url($data['referral_link']),
            esc_url($data['referral_link']),
            esc_html($data['site_name']),
            esc_html($data['site_name']),
            date('Y'),
            esc_html($data['site_name']),
            esc_url($data['site_url'])
        );
    }
    
    /**
     * Conversion email fallback template
     */
    private function get_conversion_fallback($data) {
        return sprintf('
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #11998e 0%%, #38ef7d 100%%); padding: 40px 20px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px;">🎉 New Conversion!</h1>
    </div>
    
    <div style="background: #f9f9f9; padding: 40px 30px;">
        <p style="font-size: 18px;">Hi %s,</p>
        
        <p style="font-size: 16px; margin: 20px 0;">
            Great news! One of your referrals just converted on <strong>%s</strong>.
        </p>
        
        <div style="background: white; padding: 25px; border-radius: 8px; margin: 30px 0;">
            <h3 style="margin-top: 0; color: #11998e;">Conversion Details</h3>
            <p><strong>Lead:</strong> %s</p>
            <p><strong>Date:</strong> %s</p>
        </div>
        
        <p style="font-size: 16px;">
            Keep up the great work! Continue sharing your referral link to earn more conversions.
        </p>
        
        <p style="margin-top: 30px;">
            Best,<br>
            <strong>The %s Team</strong>
        </p>
    </div>
</body>
</html>
        ',
            esc_html($data['partner_name']),
            esc_html($data['conversion_date']),
            esc_html($data['lead_name']),
            esc_html($data['conversion_date']),
            esc_html($data['site_name'])
        );
    }
    
    /**
     * Monthly stats email fallback template
     */
    private function get_stats_fallback($data) {
        return sprintf('
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%%, #764ba2 100%%); padding: 40px 20px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">Your Monthly Referral Stats</h1>
        <p style="color: white; margin: 10px 0; font-size: 16px;">%s</p>
    </div>
    
    <div style="background: #f9f9f9; padding: 40px 30px;">
        <p style="font-size: 18px;">Hi %s,</p>
        
        <p style="font-size: 16px;">Here\'s how your referrals performed this month:</p>
        
        <div style="display: grid; gap: 15px; margin: 30px 0;">
            <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                <h3 style="margin: 0; color: #667eea; font-size: 32px;">%s</h3>
                <p style="margin: 5px 0; color: #666;">Total Clicks</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #38ef7d;">
                <h3 style="margin: 0; color: #38ef7d; font-size: 32px;">%s</h3>
                <p style="margin: 5px 0; color: #666;">Conversions</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107;">
                <h3 style="margin: 0; color: #ffc107; font-size: 32px;">%s</h3>
                <p style="margin: 5px 0; color: #666;">Conversion Rate</p>
            </div>
        </div>
        
        <p style="margin-top: 30px;">
            Thank you for being a valued partner!<br>
            <strong>The %s Team</strong>
        </p>
    </div>
</body>
</html>
        ',
            esc_html($data['month']),
            esc_html($data['partner_name']),
            esc_html($data['total_clicks']),
            esc_html($data['total_conversions']),
            esc_html($data['conversion_rate']),
            esc_html($data['site_name'])
        );
    }
}
