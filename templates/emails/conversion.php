<?php
/**
 * Conversion Notification Email Template
 * 
 * Sent to partners when one of their referrals converts
 * 
 * Available variables:
 * @var string $partner_name
 * @var string $lead_name
 * @var string $lead_email
 * @var string $conversion_date
 * @var string $site_name
 */

if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html__('New Referral Conversion!', 'hubspot-referrals'); ?></title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;">
    
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 40px 20px; text-align: center; border-radius: 10px 10px 0 0;">
        <div style="font-size: 48px; margin-bottom: 10px;">🎉</div>
        <h1 style="color: white; margin: 0; font-size: 28px; font-weight: 600;">New Conversion!</h1>
        <p style="color: rgba(255,255,255,0.9); margin: 10px 0; font-size: 16px;">One of your referrals just converted</p>
    </div>
    
    <!-- Main Content -->
    <div style="background: white; padding: 40px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        
        <p style="font-size: 18px; margin-bottom: 20px;">
            Hi <strong><?php echo esc_html($partner_name); ?></strong>,
        </p>
        
        <p style="font-size: 16px; margin: 20px 0; color: #555;">
            Great news! One of your referrals just converted on <strong><?php echo esc_html($conversion_date); ?></strong>. 
            Keep up the excellent work! 🚀
        </p>
        
        <!-- Conversion Details -->
        <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; margin: 30px 0; border-left: 4px solid #38ef7d;">
            <h3 style="margin-top: 0; color: #11998e; font-size: 18px; font-weight: 600;">
                📊 Conversion Details
            </h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #dee2e6;">
                        <strong>Lead Name:</strong>
                    </td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #dee2e6; text-align: right;">
                        <?php echo esc_html($lead_name); ?>
                    </td>
                </tr>
                <?php if (!empty($lead_email)): ?>
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #dee2e6;">
                        <strong>Email:</strong>
                    </td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #dee2e6; text-align: right;">
                        <?php echo esc_html($lead_email); ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td style="padding: 10px 0;">
                        <strong>Conversion Date:</strong>
                    </td>
                    <td style="padding: 10px 0; text-align: right;">
                        <?php echo esc_html($conversion_date); ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Encouragement -->
        <div style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); padding: 25px; border-radius: 8px; margin: 30px 0; text-align: center;">
            <p style="font-size: 18px; color: #667eea; font-weight: 600; margin: 0 0 10px 0;">
                Keep sharing your referral link!
            </p>
            <p style="font-size: 14px; color: #666; margin: 0;">
                The more you share, the more conversions you'll earn. Thank you for being a valued partner!
            </p>
        </div>
        
        <p style="font-size: 16px; margin-top: 30px;">
            Best regards,<br>
            <strong style="color: #11998e;">The <?php echo esc_html($site_name); ?> Team</strong>
        </p>
        
    </div>
    
    <!-- Footer -->
    <div style="text-align: center; padding: 25px 20px; color: #999; font-size: 13px;">
        <p style="margin: 5px 0;">© <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. All rights reserved.</p>
    </div>
    
</body>
</html>
