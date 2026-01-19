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
                    
                    <button type="submit" class="button button-primary button-large">
                        <?php esc_html_e('Generate Referral Link', 'hubspot-referrals'); ?>
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
                            <th><?php esc_html_e('Created', 'hubspot-referrals'); ?></th>
                            <th><?php esc_html_e('Status', 'hubspot-referrals'); ?></th>
                            <th><?php esc_html_e('Actions', 'hubspot-referrals'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="hsr-referral-tbody">
                        <?php if (empty($referral_codes)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px;">
                                    <?php esc_html_e('No referrers yet. Use the generator above to create your first referral link.', 'hubspot-referrals'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($referral_codes as $code => $data): ?>
                                <?php 
                                $referrals = $data['referrals'] ?? [];
                                $conversion_count = $data['conversion_count'] ?? 0;
                                $is_active = $conversion_count > 0 || (!empty($data['created_at']) && strpos($data['created_at'], date('Y-m')) === 0);
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
                                    <td><?php echo esc_html(!empty($data['created_at']) ? date('M j, Y', strtotime($data['created_at'])) : 'N/A'); ?></td>
                                    <td>
                                        <span class="hsr-status-badge <?php echo $is_active ? 'active' : 'inactive'; ?>">
                                            <?php echo $is_active ? esc_html__('Active', 'hubspot-referrals') : esc_html__('Inactive', 'hubspot-referrals'); ?>
                                        </span>
                                    </td>
                                    <td class="hsr-actions">
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
        </div>
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
