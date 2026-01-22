<?php
/**
 * Monthly Stats Email Template
 * 
 * Sent monthly to partners with their referral performance stats
 * 
 * Available variables:
 * @var string $partner_name
 * @var string $month
 * @var int $total_clicks
 * @var int $total_conversions
 * @var string $conversion_rate
 * @var string $site_name
 * @var string $dashboard_url
 */

if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html__('Your Monthly Referral Stats', 'hubspot-referrals'); ?></title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;">
    
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; text-align: center; border-radius: 10px 10px 0 0;">
        <div style="font-size: 40px; margin-bottom: 10px;">📊</div>
        <h1 style="color: white; margin: 0; font-size: 24px; font-weight: 600;">Your Monthly Referral Stats</h1>
        <p style="color: rgba(255,255,255,0.9); margin: 10px 0; font-size: 16px;"><?php echo esc_html($month); ?></p>
    </div>
    
    <!-- Main Content -->
    <div style="background: white; padding: 40px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        
        <p style="font-size: 18px; margin-bottom: 20px;">
            Hi <strong><?php echo esc_html($partner_name); ?></strong>,
        </p>
        
        <p style="font-size: 16px; margin-bottom: 30px; color: #555;">
            Here's how your referrals performed this month. Thank you for being a valued partner!
        </p>
        
        <!-- Stats Grid -->
        <div style="margin: 30px 0;">
            
            <!-- Total Clicks -->
            <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #667eea;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="margin: 0; color: #666; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Total Clicks</p>
                        <h3 style="margin: 5px 0 0 0; color: #667eea; font-size: 36px; font-weight: 700;">
                            <?php echo esc_html($total_clicks); ?>
                        </h3>
                    </div>
                    <div style="font-size: 40px; opacity: 0.3;">👆</div>
                </div>
            </div>
            
            <!-- Conversions -->
            <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #38ef7d;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="margin: 0; color: #666; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Conversions</p>
                        <h3 style="margin: 5px 0 0 0; color: #38ef7d; font-size: 36px; font-weight: 700;">
                            <?php echo esc_html($total_conversions); ?>
                        </h3>
                    </div>
                    <div style="font-size: 40px; opacity: 0.3;">✅</div>
                </div>
            </div>
            
            <!-- Conversion Rate -->
            <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 4px solid #ffc107;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="margin: 0; color: #666; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Conversion Rate</p>
                        <h3 style="margin: 5px 0 0 0; color: #ffc107; font-size: 36px; font-weight: 700;">
                            <?php echo esc_html($conversion_rate); ?>
                        </h3>
                    </div>
                    <div style="font-size: 40px; opacity: 0.3;">📈</div>
                </div>
            </div>
            
        </div>
        
        <?php if ($total_conversions > 0): ?>
        <!-- Congratulations -->
        <div style="background: linear-gradient(135deg, rgba(56, 239, 125, 0.1) 0%, rgba(17, 153, 142, 0.1) 100%); padding: 25px; border-radius: 8px; margin: 30px 0; text-align: center;">
            <p style="font-size: 20px; margin: 0 0 10px 0;">🎉</p>
            <p style="font-size: 16px; color: #11998e; font-weight: 600; margin: 0;">
                Excellent work this month!
            </p>
        </div>
        <?php else: ?>
        <!-- Encouragement -->
        <div style="background: rgba(102, 126, 234, 0.1); padding: 25px; border-radius: 8px; margin: 30px 0; text-align: center;">
            <p style="font-size: 16px; color: #667eea; margin: 0;">
                Keep sharing your referral link to earn conversions!
            </p>
        </div>
        <?php endif; ?>
        
        <p style="font-size: 16px; margin-top: 30px; color: #555;">
            Continue sharing your referral link to grow your impact. Every referral counts!
        </p>
        
        <p style="font-size: 16px; margin-top: 25px;">
            Best regards,<br>
            <strong style="color: #667eea;">The <?php echo esc_html($site_name); ?> Team</strong>
        </p>
        
    </div>
    
    <!-- Footer -->
    <div style="text-align: center; padding: 25px 20px; color: #999; font-size: 13px;">
        <p style="margin: 5px 0;">© <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. All rights reserved.</p>
    </div>
    
</body>
</html>
