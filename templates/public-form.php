<?php
/**
 * Public Request Form Template
 * 
 * Displayed via [hsr_request_code] shortcode
 * Allows partners to request their own referral link
 * 
 * @package HubSpot_Referrals
 */

if (!defined('ABSPATH')) exit;

// Check if API is configured
$api = HSR_API::instance();
if (!$api->is_configured()) {
    echo '<div class="hsr-public-error">' . 
         esc_html__('Referral system is not configured yet. Please contact the site administrator.', 'hubspot-referrals') . 
         '</div>';
    return;
}

// Prepare inline styles from shortcode attributes
$field_radius = '8px';
if ($atts['field_style'] === 'square') {
    $field_radius = '2px';
} elseif ($atts['field_style'] === 'pill') {
    $field_radius = '25px';
}

$custom_styles = sprintf(
    '.hsr-public-form-wrapper { 
        --hsr-button-color: %s; 
        --hsr-button-hover-color: %s;
        --hsr-accent-color: %s; 
        --hsr-text-color: %s;
        --hsr-border-color: %s;
        --hsr-background-color: %s;
        --hsr-form-width: %s; 
        --hsr-border-radius: %s;
        --hsr-padding: %s;
        --hsr-field-radius: %s;
        --hsr-title-size: %s;
        --hsr-title-weight: %s;
        --hsr-button-size: %s;
        --hsr-button-weight: %s;
    }',
    esc_attr($atts['button_color']),
    esc_attr($atts['button_hover_color']),
    esc_attr($atts['accent_color']),
    esc_attr($atts['text_color']),
    esc_attr($atts['border_color']),
    esc_attr($atts['background_color']),
    esc_attr($atts['form_width']),
    esc_attr($atts['border_radius']),
    esc_attr($atts['padding']),
    esc_attr($field_radius),
    esc_attr($atts['title_size']),
    esc_attr($atts['title_weight']),
    esc_attr($atts['button_size']),
    esc_attr($atts['button_weight'])
);
?>

<style><?php echo $custom_styles; ?></style>

<div class="hsr-public-form-wrapper">
    <div class="hsr-public-form-container">
        
        <!-- Header -->
        <div class="hsr-public-header">
            <h2 class="hsr-public-title"><?php echo esc_html($atts['title']); ?></h2>
            <p class="hsr-public-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
        </div>
        
        <!-- Form -->
        <form id="hsr-public-request-form" class="hsr-public-form">
            <?php wp_nonce_field('hsr_public', 'hsr_public_nonce'); ?>
            
            <div class="hsr-form-row">
                <div class="hsr-form-field">
                    <label for="hsr_first_name">
                        <?php esc_html_e('First Name', 'hubspot-referrals'); ?> <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="hsr_first_name" 
                        name="first_name" 
                        required 
                        placeholder="<?php esc_attr_e('John', 'hubspot-referrals'); ?>"
                    >
                </div>
                
                <div class="hsr-form-field">
                    <label for="hsr_last_name">
                        <?php esc_html_e('Last Name', 'hubspot-referrals'); ?> <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="hsr_last_name" 
                        name="last_name" 
                        required 
                        placeholder="<?php esc_attr_e('Smith', 'hubspot-referrals'); ?>"
                    >
                </div>
            </div>
            
            <div class="hsr-form-row">
                <div class="hsr-form-field">
                    <label for="hsr_email">
                        <?php esc_html_e('Email Address', 'hubspot-referrals'); ?> <span class="required">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="hsr_email" 
                        name="email" 
                        required 
                        placeholder="<?php esc_attr_e('john@example.com', 'hubspot-referrals'); ?>"
                    >
                </div>
                
                <?php if ($atts['show_organization']) : ?>
                <div class="hsr-form-field">
                    <label for="hsr_organization">
                        <?php esc_html_e('Organization', 'hubspot-referrals'); ?> <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="hsr_organization" 
                        name="organization" 
                        required 
                        placeholder="<?php esc_attr_e('Your Company', 'hubspot-referrals'); ?>"
                    >
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!$atts['hide_custom_code']) : ?>
            <div class="hsr-form-row">
                <div class="hsr-form-field hsr-full-width">
                    <label for="hsr_custom_code">
                        <?php esc_html_e('Preferred Referral Code (optional)', 'hubspot-referrals'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="hsr_custom_code" 
                        name="custom_code" 
                        placeholder="<?php esc_attr_e('e.g., johnsmith (6-20 characters)', 'hubspot-referrals'); ?>"
                        pattern="[a-zA-Z0-9]{6,20}"
                        title="<?php esc_attr_e('6-20 alphanumeric characters only', 'hubspot-referrals'); ?>"
                    >
                    <small class="hsr-field-help">
                        <?php esc_html_e('Leave blank to auto-generate a code based on your name', 'hubspot-referrals'); ?>
                    </small>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_info_box']) : ?>
            <!-- Info Box -->
            <div class="hsr-info-box">
                <div class="hsr-info-icon">ℹ️</div>
                <div class="hsr-info-content">
                    <p><?php esc_html_e('You will receive your unique referral link via email within moments of submitting this form.', 'hubspot-referrals'); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="hsr-submit-btn">
                <span class="hsr-btn-text"><?php echo esc_html($atts['button_text']); ?></span>
                <span class="hsr-btn-loading" style="display: none;">
                    <span class="hsr-spinner"></span>
                    <?php esc_html_e('Processing...', 'hubspot-referrals'); ?>
                </span>
            </button>
            
            <div class="hsr-form-message" style="display: none;"></div>
        </form>
        
        <!-- Success View (hidden by default) -->
        <div id="hsr-success-view" class="hsr-success-view" style="display: none;">
            <div class="hsr-success-icon">✅</div>
            <h3><?php esc_html_e('Success!', 'hubspot-referrals'); ?></h3>
            <p><?php echo esc_html($atts['success_message']); ?></p>
            
            <div class="hsr-success-details">
                <p><strong><?php esc_html_e('Your Referral Code:', 'hubspot-referrals'); ?></strong></p>
                <div class="hsr-code-display">
                    <code id="hsr-display-code"></code>
                </div>
                
                <p style="margin-top: 20px;">
                    <strong><?php esc_html_e('Your Referral Link:', 'hubspot-referrals'); ?></strong>
                </p>
                <div class="hsr-link-display">
                    <input type="text" id="hsr-display-link" readonly>
                    <button type="button" class="hsr-copy-btn" onclick="hsrCopyPublicLink()">
                        📋 <?php esc_html_e('Copy', 'hubspot-referrals'); ?>
                    </button>
                </div>
            </div>
            
            <p class="hsr-email-notice">
                📧 <?php esc_html_e('A detailed welcome email has been sent to your inbox with instructions on how to use your referral link.', 'hubspot-referrals'); ?>
            </p>
        </div>
        
    </div>
</div>

<script>
// Handle public form submission
(function() {
    const form = document.getElementById('hsr-public-request-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = form.querySelector('.hsr-submit-btn');
        const btnText = btn.querySelector('.hsr-btn-text');
        const btnLoading = btn.querySelector('.hsr-btn-loading');
        const messageDiv = form.querySelector('.hsr-form-message');
        
        // Show loading state
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        messageDiv.style.display = 'none';
        
        // Prepare form data
        const formData = new FormData(form);
        formData.append('action', 'hsr_generate_referral_link');
        formData.append('send_email', '1'); // Always send email for public requests
        
        // Submit via AJAX
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success view
                document.getElementById('hsr-display-code').textContent = data.data.referral_code;
                document.getElementById('hsr-display-link').value = data.data.referral_link;
                
                form.style.display = 'none';
                document.querySelector('.hsr-public-header').style.display = 'none';
                document.getElementById('hsr-success-view').style.display = 'block';
            } else {
                // Show error message
                messageDiv.className = 'hsr-form-message hsr-error';
                messageDiv.textContent = data.data.message || '<?php esc_html_e('An error occurred. Please try again.', 'hubspot-referrals'); ?>';
                messageDiv.style.display = 'block';
                
                // Reset button
                btn.disabled = false;
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageDiv.className = 'hsr-form-message hsr-error';
            messageDiv.textContent = '<?php esc_html_e('Network error. Please check your connection and try again.', 'hubspot-referrals'); ?>';
            messageDiv.style.display = 'block';
            
            // Reset button
            btn.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
        });
    });
})();

// Copy link function
function hsrCopyPublicLink() {
    const input = document.getElementById('hsr-display-link');
    input.select();
    input.setSelectionRange(0, 99999); // For mobile
    
    try {
        document.execCommand('copy');
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '✓ <?php esc_html_e('Copied!', 'hubspot-referrals'); ?>';
        setTimeout(() => {
            btn.innerHTML = originalText;
        }, 2000);
    } catch (err) {
        alert('<?php esc_html_e('Failed to copy. Please select and copy manually.', 'hubspot-referrals'); ?>');
    }
}
</script>
