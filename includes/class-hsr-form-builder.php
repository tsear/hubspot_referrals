<?php
/**
 * Form Builder
 * 
 * Handles the form customization UI
 *
 * @package HubSpot_Referrals
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSR_Form_Builder {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Option name
     */
    private $option_name = 'hsr_form_builder';
    
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
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'hsr_form_builder_group',
            $this->option_name,
            array($this, 'sanitize_settings')
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Colors
        $sanitized['button_color'] = sanitize_hex_color($input['button_color'] ?? '#667eea');
        $sanitized['button_hover_color'] = sanitize_hex_color($input['button_hover_color'] ?? '#5568d3');
        $sanitized['accent_color'] = sanitize_hex_color($input['accent_color'] ?? '#764ba2');
        $sanitized['text_color'] = sanitize_hex_color($input['text_color'] ?? '#2c3e50');
        $sanitized['border_color'] = sanitize_hex_color($input['border_color'] ?? '#e0e0e0');
        $sanitized['background_color'] = sanitize_hex_color($input['background_color'] ?? '#ffffff');
        
        // Layout
        $sanitized['form_width'] = sanitize_text_field($input['form_width'] ?? '700px');
        $sanitized['border_radius'] = sanitize_text_field($input['border_radius'] ?? '12px');
        $sanitized['padding'] = sanitize_text_field($input['padding'] ?? '40px');
        $sanitized['field_style'] = sanitize_text_field($input['field_style'] ?? 'rounded');
        
        // Typography
        $sanitized['title_size'] = sanitize_text_field($input['title_size'] ?? '28px');
        $sanitized['title_weight'] = sanitize_text_field($input['title_weight'] ?? '700');
        $sanitized['button_size'] = sanitize_text_field($input['button_size'] ?? '16px');
        $sanitized['button_weight'] = sanitize_text_field($input['button_weight'] ?? '600');
        
        // Field Visibility
        $sanitized['hide_custom_code'] = isset($input['hide_custom_code']) ? '1' : '0';
        $sanitized['show_organization'] = isset($input['show_organization']) ? '1' : '0';
        $sanitized['show_info_box'] = isset($input['show_info_box']) ? '1' : '0';
        
        // Text Customization
        $sanitized['default_title'] = sanitize_text_field($input['default_title'] ?? '');
        $sanitized['default_subtitle'] = sanitize_text_field($input['default_subtitle'] ?? '');
        $sanitized['default_button_text'] = sanitize_text_field($input['default_button_text'] ?? '');
        $sanitized['default_success_message'] = sanitize_textarea_field($input['default_success_message'] ?? '');
        
        return $sanitized;
    }
    
    /**
     * Render form builder page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get current settings
        $settings = get_option($this->option_name, array());
        
        // Defaults
        $defaults = array(
            'button_color' => '#667eea',
            'button_hover_color' => '#5568d3',
            'accent_color' => '#764ba2',
            'text_color' => '#2c3e50',
            'border_color' => '#e0e0e0',
            'background_color' => '#ffffff',
            'form_width' => '700px',
            'border_radius' => '12px',
            'padding' => '40px',
            'field_style' => 'rounded',
            'title_size' => '28px',
            'title_weight' => '700',
            'button_size' => '16px',
            'button_weight' => '600',
            'hide_custom_code' => '0',
            'show_organization' => '1',
            'show_info_box' => '1',
            'default_title' => '',
            'default_subtitle' => '',
            'default_button_text' => '',
            'default_success_message' => ''
        );
        
        $settings = wp_parse_args($settings, $defaults);
        
        // Show save message
        if (isset($_GET['settings-updated'])) {
            add_settings_error('hsr_messages', 'hsr_message', __('Form design saved.', 'hubspot-referrals'), 'updated');
        }
        ?>
        
        <div class="wrap hsr-form-builder-wrap">
            <h1><?php esc_html_e('Form Builder', 'hubspot-referrals'); ?></h1>
            
            <?php settings_errors('hsr_messages'); ?>
            
            <div class="hsr-builder-container">
                <!-- Settings Panel -->
                <div class="hsr-builder-panel">
                    <form method="post" action="options.php" id="hsr-form-builder-form">
                        <?php settings_fields('hsr_form_builder_group'); ?>
                        
                        <!-- Colors Section -->
                        <div class="hsr-builder-section">
                            <h2><?php esc_html_e('🎨 Colors & Branding', 'hubspot-referrals'); ?></h2>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Button Color', 'hubspot-referrals'); ?></label>
                                <input type="text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[button_color]" 
                                       value="<?php echo esc_attr($settings['button_color']); ?>" 
                                       class="hsr-color-picker"
                                       data-default-color="#667eea">
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Button Hover Color', 'hubspot-referrals'); ?></label>
                                <input type="text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[button_hover_color]" 
                                       value="<?php echo esc_attr($settings['button_hover_color']); ?>" 
                                       class="hsr-color-picker"
                                       data-default-color="#5568d3">
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Accent Color', 'hubspot-referrals'); ?></label>
                                <input type="text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[accent_color]" 
                                       value="<?php echo esc_attr($settings['accent_color']); ?>" 
                                       class="hsr-color-picker"
                                       data-default-color="#764ba2">
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Text Color', 'hubspot-referrals'); ?></label>
                                <input type="text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[text_color]" 
                                       value="<?php echo esc_attr($settings['text_color']); ?>" 
                                       class="hsr-color-picker"
                                       data-default-color="#2c3e50">
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Border Color', 'hubspot-referrals'); ?></label>
                                <input type="text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[border_color]" 
                                       value="<?php echo esc_attr($settings['border_color']); ?>" 
                                       class="hsr-color-picker"
                                       data-default-color="#e0e0e0">
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Background Color', 'hubspot-referrals'); ?></label>
                                <input type="text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[background_color]" 
                                       value="<?php echo esc_attr($settings['background_color']); ?>" 
                                       class="hsr-color-picker"
                                       data-default-color="#ffffff">
                            </div>
                        </div>
                        
                        <!-- Layout Section -->
                        <div class="hsr-builder-section">
                            <h2><?php esc_html_e('📐 Layout & Spacing', 'hubspot-referrals'); ?></h2>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Form Width', 'hubspot-referrals'); ?></label>
                                <input type="text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[form_width]" 
                                       value="<?php echo esc_attr($settings['form_width']); ?>" 
                                       placeholder="700px">
                                <small>e.g., 700px, 90%, 100%</small>
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Border Radius', 'hubspot-referrals'); ?></label>
                                <input type="text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[border_radius]" 
                                       value="<?php echo esc_attr($settings['border_radius']); ?>" 
                                       placeholder="12px">
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Padding', 'hubspot-referrals'); ?></label>
                                <input type="text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[padding]" 
                                       value="<?php echo esc_attr($settings['padding']); ?>" 
                                       placeholder="40px">
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Field Style', 'hubspot-referrals'); ?></label>
                                <select name="<?php echo esc_attr($this->option_name); ?>[field_style]">
                                    <option value="rounded" <?php selected($settings['field_style'], 'rounded'); ?>>Rounded</option>
                                    <option value="square" <?php selected($settings['field_style'], 'square'); ?>>Square</option>
                                    <option value="pill" <?php selected($settings['field_style'], 'pill'); ?>>Pill</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Typography Section -->
                        <div class="hsr-builder-section">
                            <h2><?php esc_html_e('✍️ Typography', 'hubspot-referrals'); ?></h2>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Title Font Size', 'hubspot-referrals'); ?></label>
                                <input type="text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[title_size]" 
                                       value="<?php echo esc_attr($settings['title_size']); ?>" 
                                       placeholder="28px">
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Title Font Weight', 'hubspot-referrals'); ?></label>
                                <select name="<?php echo esc_attr($this->option_name); ?>[title_weight]">
                                    <option value="400" <?php selected($settings['title_weight'], '400'); ?>>Normal</option>
                                    <option value="600" <?php selected($settings['title_weight'], '600'); ?>>Semi-Bold</option>
                                    <option value="700" <?php selected($settings['title_weight'], '700'); ?>>Bold</option>
                                    <option value="800" <?php selected($settings['title_weight'], '800'); ?>>Extra Bold</option>
                                </select>
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Button Font Size', 'hubspot-referrals'); ?></label>
                                <input type="text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[button_size]" 
                                       value="<?php echo esc_attr($settings['button_size']); ?>" 
                                       placeholder="16px">
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Button Font Weight', 'hubspot-referrals'); ?></label>
                                <select name="<?php echo esc_attr($this->option_name); ?>[button_weight]">
                                    <option value="400" <?php selected($settings['button_weight'], '400'); ?>>Normal</option>
                                    <option value="500" <?php selected($settings['button_weight'], '500'); ?>>Medium</option>
                                    <option value="600" <?php selected($settings['button_weight'], '600'); ?>>Semi-Bold</option>
                                    <option value="700" <?php selected($settings['button_weight'], '700'); ?>>Bold</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Fields Section -->
                        <div class="hsr-builder-section">
                            <h2><?php esc_html_e('📝 Form Fields', 'hubspot-referrals'); ?></h2>
                            
                            <div class="hsr-builder-field">
                                <label>
                                    <input type="checkbox" 
                                           name="<?php echo esc_attr($this->option_name); ?>[show_organization]" 
                                           value="1" 
                                           <?php checked($settings['show_organization'], '1'); ?>>
                                    <?php esc_html_e('Show Organization Field', 'hubspot-referrals'); ?>
                                </label>
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label>
                                    <input type="checkbox" 
                                           name="<?php echo esc_attr($this->option_name); ?>[hide_custom_code]" 
                                           value="1" 
                                           <?php checked($settings['hide_custom_code'], '1'); ?>>
                                    <?php esc_html_e('Hide Custom Code Field', 'hubspot-referrals'); ?>
                                </label>
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label>
                                    <input type="checkbox" 
                                           name="<?php echo esc_attr($this->option_name); ?>[show_info_box]" 
                                           value="1" 
                                           <?php checked($settings['show_info_box'], '1'); ?>>
                                    <?php esc_html_e('Show Info Box', 'hubspot-referrals'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Text Defaults Section -->
                        <div class="hsr-builder-section">
                            <h2><?php esc_html_e('💬 Default Text', 'hubspot-referrals'); ?></h2>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Default Title', 'hubspot-referrals'); ?></label>
                                <input type="text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[default_title]" 
                                       value="<?php echo esc_attr($settings['default_title']); ?>" 
                                       placeholder="Request Your Referral Link"
                                       class="widefat">
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Default Subtitle', 'hubspot-referrals'); ?></label>
                                <input type="text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[default_subtitle]" 
                                       value="<?php echo esc_attr($settings['default_subtitle']); ?>" 
                                       placeholder="Join our referral program and start earning rewards"
                                       class="widefat">
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Default Button Text', 'hubspot-referrals'); ?></label>
                                <input type="text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[default_button_text]" 
                                       value="<?php echo esc_attr($settings['default_button_text']); ?>" 
                                       placeholder="Get My Referral Link"
                                       class="widefat">
                            </div>
                            
                            <div class="hsr-builder-field">
                                <label><?php esc_html_e('Default Success Message', 'hubspot-referrals'); ?></label>
                                <textarea name="<?php echo esc_attr($this->option_name); ?>[default_success_message]" 
                                          rows="3"
                                          class="widefat"
                                          placeholder="Success! Check your email for your unique referral link."><?php echo esc_textarea($settings['default_success_message']); ?></textarea>
                            </div>
                        </div>
                        
                        <?php submit_button(__('Save Design', 'hubspot-referrals'), 'primary large'); ?>
                    </form>
                    
                    <!-- Shortcode Help -->
                    <div class="hsr-builder-section">
                        <h2><?php esc_html_e('📋 Usage', 'hubspot-referrals'); ?></h2>
                        <p><?php esc_html_e('Add this shortcode to any page or post:', 'hubspot-referrals'); ?></p>
                        <code class="hsr-shortcode-display">[hsr_request_code]</code>
                        <p style="margin-top: 15px;">
                            <?php esc_html_e('Override settings on specific pages:', 'hubspot-referrals'); ?>
                        </p>
                        <code class="hsr-shortcode-display">[hsr_request_code button_color="#ff0000" title="Custom Title"]</code>
                    </div>
                </div>
                
                <!-- Live Preview -->
                <div class="hsr-builder-preview">
                    <div class="hsr-preview-header">
                        <h3><?php esc_html_e('Live Preview', 'hubspot-referrals'); ?></h3>
                        <button type="button" class="button" id="hsr-refresh-preview">
                            <?php esc_html_e('Refresh Preview', 'hubspot-referrals'); ?>
                        </button>
                    </div>
                    <div class="hsr-preview-frame" id="hsr-preview-content">
                        <iframe src="<?php echo admin_url('admin-ajax.php?action=hsr_preview_form'); ?>" 
                                frameborder="0" 
                                id="hsr-preview-iframe"></iframe>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .hsr-builder-container {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .hsr-builder-panel {
            background: #fff;
            padding: 0;
        }
        
        .hsr-builder-section {
            background: #fff;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .hsr-builder-section h2 {
            margin: 0 0 20px 0;
            font-size: 16px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .hsr-builder-field {
            margin-bottom: 20px;
        }
        
        .hsr-builder-field:last-child {
            margin-bottom: 0;
        }
        
        .hsr-builder-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #23282d;
        }
        
        .hsr-builder-field input[type="text"],
        .hsr-builder-field select,
        .hsr-builder-field textarea {
            width: 100%;
            max-width: 100%;
        }
        
        .hsr-builder-field small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-style: italic;
        }
        
        .hsr-builder-preview {
            background: #f5f5f5;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            position: sticky;
            top: 32px;
            max-height: calc(100vh - 64px);
            overflow: hidden;
        }
        
        .hsr-preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .hsr-preview-header h3 {
            margin: 0;
            font-size: 16px;
        }
        
        .hsr-preview-frame {
            background: #fff;
            border-radius: 4px;
            overflow: hidden;
            height: calc(100vh - 180px);
        }
        
        #hsr-preview-iframe {
            width: 100%;
            height: 100%;
        }
        
        .hsr-shortcode-display {
            display: block;
            padding: 12px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
        }
        
        @media (max-width: 1400px) {
            .hsr-builder-container {
                grid-template-columns: 1fr;
            }
            
            .hsr-builder-preview {
                position: static;
                max-height: 600px;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Refresh preview
            $('#hsr-refresh-preview').on('click', function() {
                $('#hsr-preview-iframe').attr('src', $('#hsr-preview-iframe').attr('src'));
            });
            
            // Auto-refresh on form change (debounced)
            let refreshTimeout;
            $('#hsr-form-builder-form input, #hsr-form-builder-form select, #hsr-form-builder-form textarea').on('change', function() {
                clearTimeout(refreshTimeout);
                refreshTimeout = setTimeout(function() {
                    $('#hsr-preview-iframe').attr('src', $('#hsr-preview-iframe').attr('src'));
                }, 1000);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Static wrapper for render_page
     */
    public static function render_page_static() {
        self::instance()->render_page();
    }
}
