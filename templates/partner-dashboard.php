<?php
/**
 * Partner Dashboard Template
 * 
 * Displayed via [hsr_partner_dashboard] shortcode
 * Allows partners to view their referral statistics
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
?>

<div class="hsr-partner-dashboard-wrapper">
    <div class="hsr-partner-dashboard-container">
        
        <!-- Login Form -->
        <div id="hsr-partner-login" class="hsr-partner-section">
            <div class="hsr-public-header">
                <h2 class="hsr-public-title"><?php echo esc_html($atts['title']); ?></h2>
                <p class="hsr-public-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
            </div>
            
            <form id="hsr-partner-login-form" class="hsr-public-form">
                <?php wp_nonce_field('hsr_partner_login', 'hsr_partner_nonce'); ?>
                
                <div class="hsr-form-row">
                    <div class="hsr-form-field hsr-full-width">
                        <label for="hsr_partner_email">
                            <?php esc_html_e('Enter Your Email', 'hubspot-referrals'); ?> <span class="required">*</span>
                        </label>
                        <input 
                            type="email" 
                            id="hsr_partner_email" 
                            name="email" 
                            required 
                            placeholder="<?php esc_attr_e('your@email.com', 'hubspot-referrals'); ?>"
                        >
                        <small class="hsr-field-help">
                            <?php esc_html_e('Use the email address you registered with', 'hubspot-referrals'); ?>
                        </small>
                    </div>
                </div>
                
                <button type="submit" class="hsr-submit-btn">
                    <span class="hsr-btn-text"><?php esc_html_e('View My Stats', 'hubspot-referrals'); ?></span>
                    <span class="hsr-btn-loading" style="display: none;">
                        <span class="hsr-spinner"></span>
                        <?php esc_html_e('Loading...', 'hubspot-referrals'); ?>
                    </span>
                </button>
                
                <div class="hsr-form-message" style="display: none;"></div>
            </form>
        </div>
        
        <!-- Dashboard View (hidden by default) -->
        <div id="hsr-partner-stats" class="hsr-partner-section" style="display: none;">
            <div class="hsr-dashboard-header">
                <h2><?php esc_html_e('Welcome back,', 'hubspot-referrals'); ?> <span id="hsr-partner-name"></span>!</h2>
                <button type="button" class="button-link" onclick="hsrPartnerLogout()">
                    <?php esc_html_e('← Back to Login', 'hubspot-referrals'); ?>
                </button>
            </div>
            
            <!-- Referral Link Card -->
            <div class="hsr-stats-card hsr-link-card">
                <h3><?php esc_html_e('Your Referral Link', 'hubspot-referrals'); ?></h3>
                <div class="hsr-link-display">
                    <input type="text" id="hsr-partner-link" readonly>
                    <button type="button" class="hsr-copy-btn" onclick="hsrCopyPartnerLink()">
                        📋 <?php esc_html_e('Copy', 'hubspot-referrals'); ?>
                    </button>
                </div>
                <p class="hsr-code-info">
                    <?php esc_html_e('Referral Code:', 'hubspot-referrals'); ?> 
                    <strong id="hsr-partner-code"></strong>
                </p>
            </div>
            
            <!-- Stats Grid -->
            <div class="hsr-stats-grid-partner">
                <div class="hsr-stat-card">
                    <div class="hsr-stat-icon">👆</div>
                    <div class="hsr-stat-content">
                        <h3><?php esc_html_e('Total Clicks', 'hubspot-referrals'); ?></h3>
                        <p class="hsr-stat-number" id="hsr-stat-clicks">-</p>
                    </div>
                </div>
                
                <div class="hsr-stat-card">
                    <div class="hsr-stat-icon">✅</div>
                    <div class="hsr-stat-content">
                        <h3><?php esc_html_e('Conversions', 'hubspot-referrals'); ?></h3>
                        <p class="hsr-stat-number" id="hsr-stat-conversions">-</p>
                    </div>
                </div>
                
                <div class="hsr-stat-card">
                    <div class="hsr-stat-icon">📈</div>
                    <div class="hsr-stat-content">
                        <h3><?php esc_html_e('Conversion Rate', 'hubspot-referrals'); ?></h3>
                        <p class="hsr-stat-number" id="hsr-stat-rate">-</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Conversions -->
            <div class="hsr-stats-card">
                <h3><?php esc_html_e('Recent Conversions', 'hubspot-referrals'); ?></h3>
                <div id="hsr-conversions-list">
                    <p class="hsr-no-data"><?php esc_html_e('Loading...', 'hubspot-referrals'); ?></p>
                </div>
            </div>
            
            <!-- Tips Card -->
            <div class="hsr-tips-card">
                <h3>💡 <?php esc_html_e('Tips to Get More Referrals', 'hubspot-referrals'); ?></h3>
                <ul>
                    <li><?php esc_html_e('Add your referral link to your email signature', 'hubspot-referrals'); ?></li>
                    <li><?php esc_html_e('Share it on your social media profiles', 'hubspot-referrals'); ?></li>
                    <li><?php esc_html_e('Mention it during client conversations', 'hubspot-referrals'); ?></li>
                    <li><?php esc_html_e('Include it in newsletters or blog posts', 'hubspot-referrals'); ?></li>
                </ul>
            </div>
        </div>
        
    </div>
</div>

<script>
(function() {
    const loginForm = document.getElementById('hsr-partner-login-form');
    if (!loginForm) return;
    
    // Handle login
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = loginForm.querySelector('.hsr-submit-btn');
        const btnText = btn.querySelector('.hsr-btn-text');
        const btnLoading = btn.querySelector('.hsr-btn-loading');
        const messageDiv = loginForm.querySelector('.hsr-form-message');
        
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        messageDiv.style.display = 'none';
        
        const formData = new FormData(loginForm);
        formData.append('action', 'hsr_partner_login');
        formData.append('nonce', document.getElementById('hsr_partner_nonce').value);
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPartnerStats(data.data);
            } else {
                messageDiv.className = 'hsr-form-message hsr-error';
                messageDiv.textContent = data.data.message || '<?php esc_html_e('Partner not found. Please check your email.', 'hubspot-referrals'); ?>';
                messageDiv.style.display = 'block';
                btn.disabled = false;
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageDiv.className = 'hsr-form-message hsr-error';
            messageDiv.textContent = '<?php esc_html_e('Network error. Please try again.', 'hubspot-referrals'); ?>';
            messageDiv.style.display = 'block';
            btn.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
        });
    });
    
    function displayPartnerStats(partner) {
        document.getElementById('hsr-partner-name').textContent = partner.first_name;
        document.getElementById('hsr-partner-code').textContent = partner.referral_code;
        document.getElementById('hsr-partner-link').value = partner.referral_link;
        document.getElementById('hsr-stat-clicks').textContent = partner.clicks || '0';
        document.getElementById('hsr-stat-conversions').textContent = partner.conversions || '0';
        document.getElementById('hsr-stat-rate').textContent = partner.conversion_rate || '0%';
        
        // Display conversions
        const conversionsList = document.getElementById('hsr-conversions-list');
        if (partner.recent_conversions && partner.recent_conversions.length > 0) {
            conversionsList.innerHTML = partner.recent_conversions.map(conv => 
                `<div class="hsr-conversion-item">
                    <span class="hsr-conversion-name">${conv.name}</span>
                    <span class="hsr-conversion-date">${conv.date}</span>
                </div>`
            ).join('');
        } else {
            conversionsList.innerHTML = '<p class="hsr-no-data"><?php esc_html_e('No conversions yet. Keep sharing your link!', 'hubspot-referrals'); ?></p>';
        }
        
        // Switch views
        document.getElementById('hsr-partner-login').style.display = 'none';
        document.getElementById('hsr-partner-stats').style.display = 'block';
    }
})();

function hsrPartnerLogout() {
    document.getElementById('hsr-partner-login').style.display = 'block';
    document.getElementById('hsr-partner-stats').style.display = 'none';
    document.getElementById('hsr-partner-login-form').reset();
}

function hsrCopyPartnerLink() {
    const input = document.getElementById('hsr-partner-link');
    input.select();
    input.setSelectionRange(0, 99999);
    
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
