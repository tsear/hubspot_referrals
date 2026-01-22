<?php
/**
 * Welcome Email Template
 * 
 * Sent to new partners when their referral code is generated
 * 
 * Available variables:
 * @var string $first_name
 * @var string $last_name
 * @var string $organization
 * @var string $referral_code
 * @var string $referral_link
 * @var string $site_name
 * @var string $site_url
 */

if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html__('Welcome to Our Referral Program', 'hubspot-referrals'); ?></title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;">
    
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px; font-weight: 600;">🎉 Welcome to Our Referral Program!</h1>
    </div>
    
    <!-- Main Content -->
    <div style="background: white; padding: 40px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        
        <p style="font-size: 18px; margin-bottom: 20px; color: #333;">
            Hi <strong><?php echo esc_html($first_name); ?></strong>,
        </p>
        
        <p style="font-size: 16px; margin-bottom: 20px; color: #555;">
            Thank you for joining the <strong><?php echo esc_html($site_name); ?></strong> referral program! 
            We're excited to partner with <strong><?php echo esc_html($organization); ?></strong>.
        </p>
        
        <!-- Referral Link Box -->
        <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; margin: 30px 0; border-left: 4px solid #667eea;">
            <h2 style="margin-top: 0; color: #667eea; font-size: 20px; font-weight: 600;">
                🔗 Your Unique Referral Link
            </h2>
            
            <p style="margin: 15px 0;">
                <strong>Referral Code:</strong> 
                <code style="background: #e9ecef; padding: 5px 12px; border-radius: 4px; font-size: 16px; color: #667eea; font-weight: 600;">
                    <?php echo esc_html($referral_code); ?>
                </code>
            </p>
            
            <p style="margin: 15px 0;"><strong>Your Link:</strong></p>
            <div style="background: white; padding: 15px; border-radius: 5px; word-break: break-all; border: 1px solid #dee2e6;">
                <a href="<?php echo esc_url($referral_link); ?>" style="color: #667eea; text-decoration: none; font-size: 14px;">
                    <?php echo esc_url($referral_link); ?>
                </a>
            </div>
        </div>
        
        <!-- How It Works -->
        <div style="background: #fff3cd; padding: 25px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 30px 0;">
            <h3 style="margin-top: 0; color: #856404; font-size: 18px; font-weight: 600;">
                📋 How It Works
            </h3>
            <ol style="margin: 10px 0; padding-left: 20px; color: #856404;">
                <li style="margin-bottom: 12px; line-height: 1.6;">
                    <strong>Share</strong> your unique referral link with potential clients
                </li>
                <li style="margin-bottom: 12px; line-height: 1.6;">
                    They <strong>click your link</strong> and visit our contact page
                </li>
                <li style="margin-bottom: 12px; line-height: 1.6;">
                    When they <strong>submit the form</strong>, you automatically get credit for the referral
                </li>
                <li style="margin-bottom: 12px; line-height: 1.6;">
                    <strong>Track your conversions</strong> in HubSpot or contact us for a report
                </li>
            </ol>
        </div>
        
        <!-- Tips Section -->
        <div style="background: #d1ecf1; padding: 25px; border-radius: 8px; border-left: 4px solid #17a2b8; margin: 30px 0;">
            <h3 style="margin-top: 0; color: #0c5460; font-size: 18px; font-weight: 600;">
                💡 Pro Tips
            </h3>
            <ul style="margin: 10px 0; padding-left: 20px; color: #0c5460;">
                <li style="margin-bottom: 10px;">Add your link to your email signature</li>
                <li style="margin-bottom: 10px;">Share it in your social media bios</li>
                <li style="margin-bottom: 10px;">Include it in newsletters or blog posts</li>
                <li>Mention it during client conversations</li>
            </ul>
        </div>
        
        <p style="font-size: 16px; margin-top: 30px; color: #555;">
            If you have any questions or need assistance, feel free to reply to this email. 
            We're here to help you succeed!
        </p>
        
        <p style="font-size: 16px; margin-top: 25px; color: #333;">
            Best regards,<br>
            <strong style="color: #667eea;">The <?php echo esc_html($site_name); ?> Team</strong>
        </p>
        
    </div>
    
    <!-- Footer -->
    <div style="text-align: center; padding: 25px 20px; color: #999; font-size: 13px;">
        <p style="margin: 5px 0;">© <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. All rights reserved.</p>
        <p style="margin: 15px 0;">
            <a href="<?php echo esc_url($site_url); ?>" style="color: #667eea; text-decoration: none;">
                Visit our website
            </a>
        </p>
    </div>
    
</body>
</html>
