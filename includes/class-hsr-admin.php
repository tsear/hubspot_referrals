<?php
/**
 * Admin Dashboard
 * 
 * Handles the referral management admin interface
 *
 * @package HubSpot_Referrals
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSR_Admin {
    
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Referral Management', 'hubspot-referrals'),
            __('Referrals', 'hubspot-referrals'),
            'manage_options',
            'hubspot-referrals',
            array($this, 'render_dashboard'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'hubspot-referrals',
            __('Dashboard', 'hubspot-referrals'),
            __('Dashboard', 'hubspot-referrals'),
            'manage_options',
            'hubspot-referrals',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'hubspot-referrals',
            __('Settings', 'hubspot-referrals'),
            __('Settings', 'hubspot-referrals'),
            'manage_options',
            'hubspot-referrals-settings',
            array($this, 'render_settings')
        );
        
        add_submenu_page(
            'hubspot-referrals',
            __('Form Builder', 'hubspot-referrals'),
            __('Form Builder', 'hubspot-referrals'),
            'manage_options',
            'hubspot-referrals-form-builder',
            'HSR_Form_Builder::render_page_static'
        );
        
        add_submenu_page(
            'hubspot-referrals',
            __('Partner Directory', 'hubspot-referrals'),
            __('Partner Directory', 'hubspot-referrals'),
            'manage_options',
            'hubspot-referrals-directory',
            array($this, 'render_directory')
        );
        
        add_submenu_page(
            'hubspot-referrals',
            __('Bulk Import', 'hubspot-referrals'),
            __('Bulk Import', 'hubspot-referrals'),
            'manage_options',
            'hubspot-referrals-import',
            array($this, 'render_import')
        );
        
        add_submenu_page(
            'hubspot-referrals',
            __('Webhook Logs', 'hubspot-referrals'),
            __('Webhook Logs', 'hubspot-referrals'),
            'manage_options',
            'hubspot-referrals-logs',
            array($this, 'render_logs')
        );
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        // Check if API is configured
        $api = HSR_API::instance();
        
        if (!$api->is_configured()) {
            $this->render_setup_notice();
            return;
        }
        
        // Fetch referral data
        $referral_codes = $api->get_all_referrals();
        
        // Initialize variables
        if (!is_array($referral_codes)) {
            $referral_codes = array();
        }
        
        // Calculate stats
        $total_referrers = count($referral_codes);
        $total_conversions = 0;
        $active_this_month = 0;
        $current_month = date('Y-m');
        
        foreach ($referral_codes as $data) {
            $total_conversions += $data['conversion_count'] ?? 0;
            if (!empty($data['created_at']) && strpos($data['created_at'], $current_month) === 0) {
                $active_this_month++;
            }
        }
        
        $conversion_rate = $total_referrers > 0 ? round(($total_conversions / $total_referrers) * 100, 1) : 0;
        
        // Get settings for HubSpot link
        $portal_id = HubSpot_Referrals::get_setting('hubspot_portal_id', '');
        $contact_page = HubSpot_Referrals::get_setting('contact_page', '/contact/');
        
        ?>
        <div class="wrap hsr-admin">
            <h1><?php esc_html_e('Referral Management', 'hubspot-referrals'); ?></h1>
            
            <!-- Stats Dashboard -->
            <div class="hsr-stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #FFB03F;">👥</div>
                    <div class="stat-content">
                        <h3><?php esc_html_e('Total Referrers', 'hubspot-referrals'); ?></h3>
                        <p class="stat-number"><?php echo esc_html($total_referrers); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4CAF50;">✓</div>
                    <div class="stat-content">
                        <h3><?php esc_html_e('Total Conversions', 'hubspot-referrals'); ?></h3>
                        <p class="stat-number"><?php echo esc_html($total_conversions); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196F3;">%</div>
                    <div class="stat-content">
                        <h3><?php esc_html_e('Conversion Rate', 'hubspot-referrals'); ?></h3>
                        <p class="stat-number"><?php echo esc_html($conversion_rate); ?>%</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #9C27B0;">📅</div>
                    <div class="stat-content">
                        <h3><?php esc_html_e('New This Month', 'hubspot-referrals'); ?></h3>
                        <p class="stat-number"><?php echo esc_html($active_this_month); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Manual Code Generator -->
            <div class="hsr-generator-card">
                <h2><?php esc_html_e('Generate Referral Code', 'hubspot-referrals'); ?></h2>
                <p><?php esc_html_e('Create a referral link manually to enroll partners.', 'hubspot-referrals'); ?></p>
                
                <form id="hsr-manual-generator" class="hsr-generator-form">
                    <?php wp_nonce_field('hsr_generate_code', 'hsr_nonce'); ?>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="gen_first_name"><?php esc_html_e('First Name', 'hubspot-referrals'); ?> *</label>
                            <input type="text" id="gen_first_name" name="first_name" required>
                        </div>
                        <div class="form-field">
                            <label for="gen_last_name"><?php esc_html_e('Last Name', 'hubspot-referrals'); ?> *</label>
                            <input type="text" id="gen_last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="gen_email"><?php esc_html_e('Email', 'hubspot-referrals'); ?> *</label>
                            <input type="email" id="gen_email" name="email" required>
                        </div>
                        <div class="form-field">
                            <label for="gen_organization"><?php esc_html_e('Organization', 'hubspot-referrals'); ?> *</label>
                            <input type="text" id="gen_organization" name="organization" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="gen_custom_code"><?php esc_html_e('Custom Code (optional)', 'hubspot-referrals'); ?></label>
                            <input type="text" id="gen_custom_code" name="custom_code" placeholder="<?php esc_attr_e('Auto-generated if empty', 'hubspot-referrals'); ?>">
                        </div>
                        <div class="form-field"></div>
                    </div>
                    
                    <div class="form-row" style="margin-top: 20px; background: #f0f6ff; padding: 15px; border-radius: 6px; border-left: 4px solid #667eea;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" id="gen_send_email" name="send_email" value="1" checked style="margin-right: 10px; width: 18px; height: 18px;">
                            <span style="font-weight: 500;">
                                📧 <?php esc_html_e('Send welcome email to partner with their referral link', 'hubspot-referrals'); ?>
                            </span>
                        </label>
                        <p style="margin: 8px 0 0 28px; font-size: 13px; color: #666;">
                            <?php esc_html_e('The partner will receive a professional email with their unique referral code and instructions on how to use it.', 'hubspot-referrals'); ?>
                        </p>
                    </div>
                    
                    <button type="submit" class="button button-primary button-large" style="margin-top: 20px;">
                        <?php esc_html_e('Generate & Send Referral Link', 'hubspot-referrals'); ?>
                    </button>
                </form>
                
                <div id="hsr-generator-result" style="display: none;">
                    <div class="result-success">
                        <h3>✓ <?php esc_html_e('Referral Link Generated', 'hubspot-referrals'); ?></h3>
                        <div class="generated-link-wrapper">
                            <input type="text" id="generated-link" readonly>
                            <button type="button" class="button" onclick="hsrCopyLink()">
                                <?php esc_html_e('Copy Link', 'hubspot-referrals'); ?>
                            </button>
                        </div>
                        <p class="generated-code"><?php esc_html_e('Code:', 'hubspot-referrals'); ?> <strong id="generated-code"></strong></p>
                        <button type="button" class="button" onclick="hsrResetGenerator()">
                            <?php esc_html_e('Create Another', 'hubspot-referrals'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Referrer Table -->
            <div class="hsr-table-card">
                <div class="table-header">
                    <h2><?php esc_html_e('All Referrers', 'hubspot-referrals'); ?></h2>
                    <div class="table-actions">
                        <button type="button" class="button" onclick="hsrRefreshData()">
                            🔄 <?php esc_html_e('Refresh from HubSpot', 'hubspot-referrals'); ?>
                        </button>
                        <button type="button" class="button" onclick="hsrExportCSV()">
                            📥 <?php esc_html_e('Export CSV', 'hubspot-referrals'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="table-filters">
                    <input type="text" id="hsr-search" placeholder="<?php esc_attr_e('Search by name, organization, or code...', 'hubspot-referrals'); ?>" class="hsr-search-input">
                </div>
                
                <table class="wp-list-table widefat fixed striped hsr-referral-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Name', 'hubspot-referrals'); ?></th>
                            <th><?php esc_html_e('Organization', 'hubspot-referrals'); ?></th>
                            <th><?php esc_html_e('Code', 'hubspot-referrals'); ?></th>
                            <th><?php esc_html_e('Conversions', 'hubspot-referrals'); ?></th>
                            <th><?php esc_html_e('Directory', 'hubspot-referrals'); ?></th>
                            <th><?php esc_html_e('Created', 'hubspot-referrals'); ?></th>
                            <th><?php esc_html_e('Status', 'hubspot-referrals'); ?></th>
                            <th><?php esc_html_e('Actions', 'hubspot-referrals'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="hsr-referral-tbody">
                        <?php if (empty($referral_codes)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <?php esc_html_e('No referrers yet. Use the generator above to create your first referral link.', 'hubspot-referrals'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($referral_codes as $code => $data): ?>
                                <?php 
                                $referrals = $data['referrals'] ?? [];
                                $conversion_count = $data['conversion_count'] ?? 0;
                                $is_active = !empty($data['show_in_directory']);
                                ?>
                                <tr data-code="<?php echo esc_attr($code); ?>" data-search="<?php echo esc_attr(strtolower($data['first_name'] . ' ' . $data['last_name'] . ' ' . ($data['organization'] ?? '') . ' ' . $code)); ?>">
                                    <td>
                                        <strong><?php echo esc_html($data['first_name'] . ' ' . $data['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo esc_html($data['organization'] ?? 'N/A'); ?></td>
                                    <td><code class="hsr-code"><?php echo esc_html($code); ?></code></td>
                                    <td>
                                        <span class="hsr-conversion-badge <?php echo $conversion_count > 0 ? 'has-conversions' : ''; ?>">
                                            <?php echo esc_html($conversion_count); ?>
                                        </span>
                                        <?php if ($conversion_count > 0): ?>
                                            <button type="button" class="button button-small hsr-view-btn" onclick="hsrToggleConversions('<?php echo esc_js($code); ?>')">
                                                <?php esc_html_e('View', 'hubspot-referrals'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <label class="hsr-toggle-switch" title="<?php esc_attr_e('Show in Partner Directory', 'hubspot-referrals'); ?>">
                                            <input type="checkbox" 
                                                   class="hsr-directory-toggle" 
                                                   data-contact-id="<?php echo esc_attr($data['hubspot_contact_id']); ?>"
                                                   <?php checked($data['show_in_directory'] ?? false); ?>>
                                            <span class="hsr-toggle-slider"></span>
                                        </label>
                                    </td>
                                    <td><?php echo esc_html(!empty($data['created_at']) ? date('M j, Y', strtotime($data['created_at'])) : 'N/A'); ?></td>
                                    <td>
                                        <span class="hsr-status-badge <?php echo $is_active ? 'active' : 'inactive'; ?>">
                                            <?php echo $is_active ? esc_html__('Active', 'hubspot-referrals') : esc_html__('Inactive', 'hubspot-referrals'); ?>
                                        </span>
                                    </td>
                                    <td class="hsr-actions">
                                        <button type="button" class="button button-small" onclick="hsrEditPartner('<?php echo esc_js($data['hubspot_contact_id']); ?>', '<?php echo esc_js($code); ?>')" title="<?php esc_attr_e('Edit Directory Info', 'hubspot-referrals'); ?>">
                                            ✏️
                                        </button>
                                        <button type="button" class="button button-small" onclick="hsrCopyReferralLink('<?php echo esc_js($code); ?>')" title="<?php esc_attr_e('Copy Link', 'hubspot-referrals'); ?>">
                                            📋
                                        </button>
                                        <?php if (!empty($data['hubspot_contact_id']) && !empty($portal_id)): ?>
                                            <a href="https://app.hubspot.com/contacts/<?php echo esc_attr($portal_id); ?>/contact/<?php echo esc_attr($data['hubspot_contact_id']); ?>" target="_blank" class="button button-small" title="<?php esc_attr_e('View in HubSpot', 'hubspot-referrals'); ?>">
                                                🔗
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if (!empty($referrals)): ?>
                                    <tr id="conversions-<?php echo esc_attr($code); ?>" class="hsr-conversions-row" style="display: none;">
                                        <td colspan="7">
                                            <div class="hsr-conversions-detail">
                                                <h4><?php printf(esc_html__('Conversions from %s', 'hubspot-referrals'), esc_html($code)); ?></h4>
                                                <table class="hsr-sub-table">
                                                    <thead>
                                                        <tr>
                                                            <th><?php esc_html_e('Name', 'hubspot-referrals'); ?></th>
                                                            <th><?php esc_html_e('Email', 'hubspot-referrals'); ?></th>
                                                            <th><?php esc_html_e('Submitted', 'hubspot-referrals'); ?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($referrals as $referral): ?>
                                                            <tr>
                                                                <td><?php echo esc_html(($referral['first_name'] ?? '') . ' ' . ($referral['last_name'] ?? '')); ?></td>
                                                                <td><a href="mailto:<?php echo esc_attr($referral['email'] ?? ''); ?>"><?php echo esc_html($referral['email'] ?? 'N/A'); ?></a></td>
                                                                <td><?php echo esc_html($referral['created_at'] ?? 'N/A'); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Partner Edit Modal -->
            <div id="hsr-partner-modal" class="hsr-modal" style="display: none;">
                <div class="hsr-modal-content">
                    <div class="hsr-modal-header">
                        <h2><?php esc_html_e('Edit Partner Directory Info', 'hubspot-referrals'); ?></h2>
                        <button type="button" class="hsr-modal-close" onclick="hsrClosePartnerModal()">&times;</button>
                    </div>
                    <form id="hsr-partner-edit-form">
                        <?php wp_nonce_field('hsr_update_partner', 'hsr_partner_nonce'); ?>
                        <input type="hidden" id="partner_contact_id" name="contact_id">
                        <input type="hidden" id="partner_code" name="code">
                        
                        <div class="hsr-form-group">
                            <label for="partner_logo_url"><?php esc_html_e('Logo URL', 'hubspot-referrals'); ?></label>
                            <div style="display: flex; gap: 10px;">
                                <input type="url" id="partner_logo_url" name="logo_url" class="regular-text" placeholder="https://example.com/logo.png" style="flex: 1;">
                                <button type="button" class="button" id="hsr-upload-logo-btn">
                                    <?php esc_html_e('Upload', 'hubspot-referrals'); ?>
                                </button>
                            </div>
                            <p class="description"><?php esc_html_e('Upload or enter the full URL to the partner\'s logo image', 'hubspot-referrals'); ?></p>
                        </div>
                        
                        <div class="hsr-form-group">
                            <label for="partner_description"><?php esc_html_e('Directory Description', 'hubspot-referrals'); ?></label>
                            <textarea id="partner_description" name="directory_description" class="large-text" rows="4" placeholder="<?php esc_attr_e('Brief description of the partner organization...', 'hubspot-referrals'); ?>"></textarea>
                            <p class="description"><?php esc_html_e('This description will appear on the public partner directory', 'hubspot-referrals'); ?></p>
                        </div>
                        
                        <div class="hsr-form-group">
                            <label for="partner_website"><?php esc_html_e('Website URL', 'hubspot-referrals'); ?></label>
                            <input type="url" id="partner_website" name="website_url" class="regular-text" placeholder="https://example.com">
                            <p class="description"><?php esc_html_e('Partner\'s website (for "Learn More" button)', 'hubspot-referrals'); ?></p>
                        </div>
                        
                        <div class="hsr-form-group">
                            <label for="partner_directory_order"><?php esc_html_e('Display Order', 'hubspot-referrals'); ?></label>
                            <input type="number" id="partner_directory_order" name="directory_order" class="small-text" value="999" min="0">
                            <p class="description"><?php esc_html_e('Lower numbers appear first (0-999)', 'hubspot-referrals'); ?></p>
                        </div>
                        
                        <div class="hsr-modal-footer">
                            <button type="button" class="button" onclick="hsrClosePartnerModal()"><?php esc_html_e('Cancel', 'hubspot-referrals'); ?></button>
                            <button type="submit" class="button button-primary"><?php esc_html_e('Save Changes', 'hubspot-referrals'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render partner directory management page
     */
    public function render_directory() {
        // Save settings if posted
        if (isset($_POST['hsr_directory_settings_nonce']) && 
            wp_verify_nonce($_POST['hsr_directory_settings_nonce'], 'hsr_directory_settings')) {
            
            $directory_settings = array(
                'columns' => sanitize_text_field($_POST['directory_columns'] ?? '3'),
                'show_description' => isset($_POST['directory_show_description']) ? '1' : '0',
                'card_bg_color' => sanitize_hex_color($_POST['directory_card_bg'] ?? '#ffffff'),
                'card_border_color' => sanitize_hex_color($_POST['directory_card_border'] ?? '#e5e7eb'),
                'card_hover_border' => sanitize_hex_color($_POST['directory_card_hover_border'] ?? '#3b82f6'),
                'button_bg_color' => sanitize_hex_color($_POST['directory_button_bg'] ?? '#3b82f6'),
                'button_hover_color' => sanitize_hex_color($_POST['directory_button_hover'] ?? '#2563eb'),
                'title_color' => sanitize_hex_color($_POST['directory_title_color'] ?? '#111827'),
                'description_color' => sanitize_hex_color($_POST['directory_description_color'] ?? '#6b7280'),
                'logo_bg_color' => sanitize_hex_color($_POST['directory_logo_bg'] ?? '#f9fafb'),
                'card_radius' => sanitize_text_field($_POST['directory_card_radius'] ?? '8px'),
                'gap' => sanitize_text_field($_POST['directory_gap'] ?? '24px'),
            );
            
            update_option('hsr_directory_settings', $directory_settings);
            
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 esc_html__('Directory settings saved successfully!', 'hubspot-referrals') . 
                 '</p></div>';
        }
        
        // Get current settings
        $settings = get_option('hsr_directory_settings', array(
            'columns' => '3',
            'show_description' => '1',
            'card_bg_color' => '#ffffff',
            'card_border_color' => '#e5e7eb',
            'card_hover_border' => '#3b82f6',
            'button_bg_color' => '#3b82f6',
            'button_hover_color' => '#2563eb',
            'title_color' => '#111827',
            'description_color' => '#6b7280',
            'logo_bg_color' => '#f9fafb',
            'card_radius' => '8px',
            'gap' => '24px',
        ));
        
        // Get partners for preview
        $api = HSR_API::instance();
        $all_partners = array();
        $directory_count = 0;
        
        if ($api->is_configured()) {
            $all_partners = $api->get_all_referrals();
            $directory_count = count(array_filter($all_partners, function($p) {
                return !empty($p['show_in_directory']);
            }));
        }
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Partner Directory', 'hubspot-referrals'); ?></h1>
            
            <div class="hsr-directory-admin">
                <div class="hsr-directory-grid-admin">
                    <!-- Left Column: Shortcode & Preview -->
                    <div class="hsr-directory-main">
                        <!-- Shortcode Box -->
                        <div class="hsr-card">
                            <h2><?php esc_html_e('Shortcode', 'hubspot-referrals'); ?></h2>
                            <p><?php esc_html_e('Copy and paste this shortcode into any page to display the partner directory:', 'hubspot-referrals'); ?></p>
                            
                            <div class="hsr-shortcode-box">
                                <code id="hsr-directory-shortcode">[hsr_partner_directory]</code>
                                <button type="button" class="button" onclick="hsrCopyDirectoryShortcode()">
                                    📋 <?php esc_html_e('Copy', 'hubspot-referrals'); ?>
                                </button>
                            </div>
                            
                            <details style="margin-top: 15px;">
                                <summary style="cursor: pointer; font-weight: 600;">
                                    <?php esc_html_e('Advanced Options', 'hubspot-referrals'); ?>
                                </summary>
                                <div style="margin-top: 10px; padding: 10px; background: #f9fafb; border-radius: 4px;">
                                    <p><strong><?php esc_html_e('Customize per page:', 'hubspot-referrals'); ?></strong></p>
                                    <code>[hsr_partner_directory columns="3" show_description="true" max_partners="12"]</code>
                                    <ul style="margin-top: 10px; list-style: disc; margin-left: 20px; font-size: 13px;">
                                        <li><strong>columns</strong>: Number of columns (2, 3, or 4)</li>
                                        <li><strong>show_description</strong>: Show descriptions (true/false)</li>
                                        <li><strong>max_partners</strong>: Maximum number to display</li>
                                    </ul>
                                </div>
                            </details>
                        </div>
                        
                        <!-- Stats Box -->
                        <div class="hsr-card">
                            <h2><?php esc_html_e('Directory Status', 'hubspot-referrals'); ?></h2>
                            <div class="hsr-directory-stats">
                                <div class="hsr-stat-item">
                                    <div class="hsr-stat-number"><?php echo esc_html($directory_count); ?></div>
                                    <div class="hsr-stat-label"><?php esc_html_e('Partners in Directory', 'hubspot-referrals'); ?></div>
                                </div>
                                <div class="hsr-stat-item">
                                    <div class="hsr-stat-number"><?php echo esc_html(count($all_partners)); ?></div>
                                    <div class="hsr-stat-label"><?php esc_html_e('Total Partners', 'hubspot-referrals'); ?></div>
                                </div>
                            </div>
                            
                            <p style="margin-top: 15px;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=hubspot-referrals')); ?>" class="button">
                                    <?php esc_html_e('Manage Partners →', 'hubspot-referrals'); ?>
                                </a>
                            </p>
                        </div>
                        
                        <!-- Instructions -->
                        <div class="hsr-card">
                            <h2><?php esc_html_e('How to Add Partners to Directory', 'hubspot-referrals'); ?></h2>
                            <ol style="margin-left: 20px; line-height: 1.8;">
                                <li><?php esc_html_e('Go to the main Referrals Dashboard', 'hubspot-referrals'); ?></li>
                                <li><?php esc_html_e('Find the partner you want to feature', 'hubspot-referrals'); ?></li>
                                <li><?php esc_html_e('Toggle the "Directory" switch to ON', 'hubspot-referrals'); ?></li>
                                <li><?php esc_html_e('Click the ✏️ edit icon to add:', 'hubspot-referrals'); ?>
                                    <ul style="margin-left: 20px; list-style: circle;">
                                        <li><?php esc_html_e('Partner logo URL', 'hubspot-referrals'); ?></li>
                                        <li><?php esc_html_e('Description for directory', 'hubspot-referrals'); ?></li>
                                        <li><?php esc_html_e('Website URL', 'hubspot-referrals'); ?></li>
                                        <li><?php esc_html_e('Display order (0-999)', 'hubspot-referrals'); ?></li>
                                    </ul>
                                </li>
                                <li><?php esc_html_e('Partner appears instantly in your directory!', 'hubspot-referrals'); ?></li>
                            </ol>
                        </div>
                    </div>
                    
                    <!-- Right Column: Settings -->
                    <div class="hsr-directory-sidebar">
                        <div class="hsr-card">
                            <h2><?php esc_html_e('Directory Appearance', 'hubspot-referrals'); ?></h2>
                            <p><?php esc_html_e('Customize how the partner directory looks on your site.', 'hubspot-referrals'); ?></p>
                            
                            <form method="post" action="">
                                <?php wp_nonce_field('hsr_directory_settings', 'hsr_directory_settings_nonce'); ?>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="directory_columns"><?php esc_html_e('Columns', 'hubspot-referrals'); ?></label>
                                        </th>
                                        <td>
                                            <select name="directory_columns" id="directory_columns">
                                                <option value="2" <?php selected($settings['columns'], '2'); ?>>2 <?php esc_html_e('columns', 'hubspot-referrals'); ?></option>
                                                <option value="3" <?php selected($settings['columns'], '3'); ?>>3 <?php esc_html_e('columns', 'hubspot-referrals'); ?></option>
                                                <option value="4" <?php selected($settings['columns'], '4'); ?>>4 <?php esc_html_e('columns', 'hubspot-referrals'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <?php esc_html_e('Show Descriptions', 'hubspot-referrals'); ?>
                                        </th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="directory_show_description" value="1" <?php checked($settings['show_description'], '1'); ?>>
                                                <?php esc_html_e('Display partner descriptions', 'hubspot-referrals'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th colspan="2" style="padding-top: 20px;">
                                            <h3 style="margin: 0;"><?php esc_html_e('Colors', 'hubspot-referrals'); ?></h3>
                                        </th>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="directory_card_bg"><?php esc_html_e('Card Background', 'hubspot-referrals'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="directory_card_bg" id="directory_card_bg" value="<?php echo esc_attr($settings['card_bg_color']); ?>" class="hsr-color-picker">
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="directory_card_border"><?php esc_html_e('Card Border', 'hubspot-referrals'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="directory_card_border" id="directory_card_border" value="<?php echo esc_attr($settings['card_border_color']); ?>" class="hsr-color-picker">
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="directory_card_hover_border"><?php esc_html_e('Card Hover Border', 'hubspot-referrals'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="directory_card_hover_border" id="directory_card_hover_border" value="<?php echo esc_attr($settings['card_hover_border']); ?>" class="hsr-color-picker">
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="directory_button_bg"><?php esc_html_e('Button Background', 'hubspot-referrals'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="directory_button_bg" id="directory_button_bg" value="<?php echo esc_attr($settings['button_bg_color']); ?>" class="hsr-color-picker">
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="directory_button_hover"><?php esc_html_e('Button Hover', 'hubspot-referrals'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="directory_button_hover" id="directory_button_hover" value="<?php echo esc_attr($settings['button_hover_color']); ?>" class="hsr-color-picker">
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="directory_title_color"><?php esc_html_e('Title Color', 'hubspot-referrals'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="directory_title_color" id="directory_title_color" value="<?php echo esc_attr($settings['title_color']); ?>" class="hsr-color-picker">
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="directory_description_color"><?php esc_html_e('Description Color', 'hubspot-referrals'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="directory_description_color" id="directory_description_color" value="<?php echo esc_attr($settings['description_color']); ?>" class="hsr-color-picker">
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th colspan="2" style="padding-top: 20px;">
                                            <h3 style="margin: 0;"><?php esc_html_e('Layout', 'hubspot-referrals'); ?></h3>
                                        </th>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="directory_card_radius"><?php esc_html_e('Card Border Radius', 'hubspot-referrals'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="directory_card_radius" id="directory_card_radius" value="<?php echo esc_attr($settings['card_radius']); ?>" class="small-text">
                                            <p class="description"><?php esc_html_e('e.g., 8px, 12px, 0px', 'hubspot-referrals'); ?></p>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="directory_gap"><?php esc_html_e('Card Gap', 'hubspot-referrals'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="directory_gap" id="directory_gap" value="<?php echo esc_attr($settings['gap']); ?>" class="small-text">
                                            <p class="description"><?php esc_html_e('Spacing between cards (e.g., 24px)', 'hubspot-referrals'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p class="submit">
                                    <button type="submit" class="button button-primary button-large">
                                        <?php esc_html_e('Save Appearance Settings', 'hubspot-referrals'); ?>
                                    </button>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function hsrCopyDirectoryShortcode() {
            const shortcode = document.getElementById('hsr-directory-shortcode');
            const range = document.createRange();
            range.selectNode(shortcode);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand('copy');
            window.getSelection().removeAllRanges();
            alert('<?php esc_html_e('Shortcode copied to clipboard!', 'hubspot-referrals'); ?>');
        }
        </script>
        
        <style>
        .hsr-directory-admin {
            margin-top: 20px;
        }
        
        .hsr-directory-grid-admin {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 20px;
        }
        
        .hsr-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .hsr-card h2 {
            margin-top: 0;
            font-size: 18px;
        }
        
        .hsr-shortcode-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f9fafb;
            border: 2px dashed #3b82f6;
            border-radius: 8px;
        }
        
        .hsr-shortcode-box code {
            flex: 1;
            font-size: 16px;
            background: transparent;
            padding: 0;
        }
        
        .hsr-directory-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        
        .hsr-stat-item {
            text-align: center;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .hsr-stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #3b82f6;
        }
        
        .hsr-stat-label {
            font-size: 13px;
            color: #6b7280;
            margin-top: 5px;
        }
        
        @media (max-width: 1200px) {
            .hsr-directory-grid-admin {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        // Delegate to settings class
        HSR_Settings::instance()->render_page();
    }
    
    /**
     * Render bulk import page
     */
    public function render_import() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Bulk Import Partners', 'hubspot-referrals'); ?></h1>
            
            <div class="card" style="max-width: 800px;">
                <h2><?php esc_html_e('Import from CSV', 'hubspot-referrals'); ?></h2>
                <p><?php esc_html_e('Upload a CSV file with partner information to create multiple referral links at once.', 'hubspot-referrals'); ?></p>
                
                <div class="hsr-import-instructions" style="background: #f0f6ff; padding: 15px; border-left: 4px solid #2271b1; margin: 20px 0;">
                    <h3 style="margin-top: 0;"><?php esc_html_e('CSV Format Requirements:', 'hubspot-referrals'); ?></h3>
                    <p><?php esc_html_e('Your CSV file should have these columns (header row required):', 'hubspot-referrals'); ?></p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><strong>first_name</strong> - <?php esc_html_e('Required', 'hubspot-referrals'); ?></li>
                        <li><strong>last_name</strong> - <?php esc_html_e('Required', 'hubspot-referrals'); ?></li>
                        <li><strong>email</strong> - <?php esc_html_e('Required', 'hubspot-referrals'); ?></li>
                        <li><strong>organization</strong> <?php esc_html_e('or', 'hubspot-referrals'); ?> <strong>company</strong> - <?php esc_html_e('Optional', 'hubspot-referrals'); ?></li>
                        <li><strong>referral_code</strong> - <?php esc_html_e('Optional (will auto-generate if empty)', 'hubspot-referrals'); ?></li>
                    </ul>
                    <p><a href="#" onclick="hsrDownloadTemplate(); return false;"><?php esc_html_e('Download CSV Template →', 'hubspot-referrals'); ?></a></p>
                </div>
                
                <form id="hsr-bulk-import-form">
                    <?php wp_nonce_field('hsr_bulk_import', 'hsr_import_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="hsr_csv_file"><?php esc_html_e('CSV File', 'hubspot-referrals'); ?></label>
                            </th>
                            <td>
                                <input type="file" id="hsr_csv_file" accept=".csv" required>
                                <p class="description"><?php esc_html_e('Select a CSV file to upload', 'hubspot-referrals'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="hsr_send_welcome"><?php esc_html_e('Send Emails', 'hubspot-referrals'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="hsr_send_welcome" name="send_emails" value="1" checked>
                                    <?php esc_html_e('Send welcome emails to all imported partners', 'hubspot-referrals'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Partners will receive their referral link via email', 'hubspot-referrals'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            <?php esc_html_e('Import Partners', 'hubspot-referrals'); ?>
                        </button>
                    </p>
                    
                    <div id="hsr-import-progress" style="display: none; margin-top: 20px;">
                        <div class="notice notice-info">
                            <p><span class="spinner is-active" style="float: none;"></span> <?php esc_html_e('Importing...', 'hubspot-referrals'); ?></p>
                        </div>
                    </div>
                    
                    <div id="hsr-import-result" style="display: none; margin-top: 20px;"></div>
                </form>
            </div>
        </div>
        
        <script>
        function hsrDownloadTemplate() {
            const csv = 'first_name,last_name,email,organization,referral_code\nJohn,Smith,john@example.com,Acme Corp,\nJane,Doe,jane@example.com,Example Inc,janedoe';
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'referral-import-template.csv';
            a.click();
        }
        
        jQuery(document).ready(function($) {
            $('#hsr-bulk-import-form').on('submit', function(e) {
                e.preventDefault();
                
                const fileInput = document.getElementById('hsr_csv_file');
                const file = fileInput.files[0];
                
                if (!file) {
                    alert('<?php esc_html_e('Please select a CSV file', 'hubspot-referrals'); ?>');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const csvData = e.target.result;
                    
                    $('#hsr-import-progress').show();
                    $('#hsr-import-result').hide();
                    $('button[type="submit"]').prop('disabled', true);
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'hsr_bulk_import',
                            nonce: $('#hsr_import_nonce').val(),
                            csv_data: csvData,
                            send_emails: $('#hsr_send_welcome').is(':checked') ? '1' : '0'
                        },
                        success: function(response) {
                            $('#hsr-import-progress').hide();
                            $('button[type="submit"]').prop('disabled', false);
                            
                            if (response.success) {
                                let html = '<div class="notice notice-success"><p><strong>' + response.data.message + '</strong></p>';
                                if (response.data.errors && response.data.errors.length > 0) {
                                    html += '<p><?php esc_html_e('Errors:', 'hubspot-referrals'); ?></p><ul style="list-style: disc; margin-left: 20px;">';
                                    response.data.errors.forEach(function(error) {
                                        html += '<li>' + error + '</li>';
                                    });
                                    html += '</ul>';
                                }
                                html += '</div>';
                                $('#hsr-import-result').html(html).show();
                                
                                // Reset form
                                fileInput.value = '';
                            } else {
                                $('#hsr-import-result').html('<div class="notice notice-error"><p>' + (response.data.message || '<?php esc_html_e('Import failed', 'hubspot-referrals'); ?>') + '</p></div>').show();
                            }
                        },
                        error: function() {
                            $('#hsr-import-progress').hide();
                            $('button[type="submit"]').prop('disabled', false);
                            $('#hsr-import-result').html('<div class="notice notice-error"><p><?php esc_html_e('Network error. Please try again.', 'hubspot-referrals'); ?></p></div>').show();
                        }
                    });
                };
                reader.readAsText(file);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render webhook logs page
     */
    public function render_logs() {
        $logs = HSR_Webhook::get_logs();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Webhook Activity Logs', 'hubspot-referrals'); ?></h1>
            
            <div class="card" style="max-width: 900px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h2 style="margin: 0;"><?php esc_html_e('Recent Webhook Events', 'hubspot-referrals'); ?></h2>
                        <p style="margin: 5px 0 0 0;"><?php esc_html_e('Last 100 webhook requests received', 'hubspot-referrals'); ?></p>
                    </div>
                    <div>
                        <button type="button" class="button" onclick="location.reload()">
                            🔄 <?php esc_html_e('Refresh', 'hubspot-referrals'); ?>
                        </button>
                        <button type="button" class="button" onclick="hsrClearLogs()">
                            🗑️ <?php esc_html_e('Clear Logs', 'hubspot-referrals'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="notice notice-info">
                    <p><strong><?php esc_html_e('Your Webhook URL:', 'hubspot-referrals'); ?></strong></p>
                    <code><?php echo esc_html(rest_url('hubspot-referrals/v1/webhook')); ?></code>
                    <p style="margin-top: 10px;">
                        <a href="https://app.hubspot.com/integrations-settings" target="_blank">
                            <?php esc_html_e('Configure in HubSpot →', 'hubspot-referrals'); ?>
                        </a>
                    </p>
                </div>
                
                <?php if (empty($logs)): ?>
                    <p><?php esc_html_e('No webhook events received yet.', 'hubspot-referrals'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 150px;"><?php esc_html_e('Time', 'hubspot-referrals'); ?></th>
                                <th style="width: 100px;"><?php esc_html_e('Type', 'hubspot-referrals'); ?></th>
                                <th><?php esc_html_e('Details', 'hubspot-referrals'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo esc_html($log['timestamp']); ?></td>
                                    <td>
                                        <?php 
                                        $badge_color = $log['type'] === 'success' ? '#28a745' : ($log['type'] === 'error' ? '#dc3545' : '#6c757d');
                                        ?>
                                        <span style="background: <?php echo esc_attr($badge_color); ?>; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; text-transform: uppercase;">
                                            <?php echo esc_html($log['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <details>
                                            <summary style="cursor: pointer;">
                                                <?php 
                                                if (is_array($log['data'])) {
                                                    echo esc_html(wp_json_encode($log['data'], JSON_PRETTY_PRINT));
                                                } else {
                                                    echo esc_html($log['data']);
                                                }
                                                ?>
                                            </summary>
                                            <pre style="background: #f5f5f5; padding: 10px; margin-top: 10px; overflow: auto;"><?php echo esc_html(wp_json_encode($log['data'], JSON_PRETTY_PRINT)); ?></pre>
                                        </details>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        function hsrClearLogs() {
            if (confirm('<?php esc_html_e('Are you sure you want to clear all webhook logs?', 'hubspot-referrals'); ?>')) {
                jQuery.post(ajaxurl, {
                    action: 'hsr_clear_logs',
                    nonce: '<?php echo wp_create_nonce('hsr_clear_logs'); ?>'
                }, function() {
                    location.reload();
                });
            }
        }
        </script>
        <?php
    }
    
    /**
     * Render setup notice
     */
    private function render_setup_notice() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Referral Management', 'hubspot-referrals'); ?></h1>
            
            <div class="notice notice-warning" style="padding: 20px;">
                <h2><?php esc_html_e('Setup Required', 'hubspot-referrals'); ?></h2>
                <p><?php esc_html_e('Please configure your HubSpot API key to start using the referral system.', 'hubspot-referrals'); ?></p>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=hubspot-referrals-settings')); ?>" class="button button-primary">
                        <?php esc_html_e('Go to Settings', 'hubspot-referrals'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
}
