<?php
/**
 * Partner Directory Template
 * 
 * Displays public partner directory using Flowbite card grid
 * 
 * @package HubSpot_Referrals
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Variables available:
// $directory_partners - array of partners with show_in_directory = true
// $atts - shortcode attributes

// Get saved directory settings for styling
$directory_settings = get_option('hsr_directory_settings', array(
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
?>

<div class="hsr-partner-directory">
    <?php if (empty($directory_partners)): ?>
        <div class="hsr-directory-empty" style="border-radius: <?php echo esc_attr($directory_settings['card_radius']); ?>;">
            <p><?php esc_html_e('No partners are currently listed in the directory.', 'hubspot-referrals'); ?></p>
        </div>
    <?php else: ?>
        <div class="hsr-directory-grid hsr-grid-cols-<?php echo esc_attr($atts['columns']); ?>" style="gap: <?php echo esc_attr($directory_settings['gap']); ?>;">
            <?php foreach ($directory_partners as $code => $partner): ?>
                <div class="hsr-partner-card" 
                     style="background: <?php echo esc_attr($directory_settings['card_bg_color']); ?>; 
                            border: 1px solid <?php echo esc_attr($directory_settings['card_border_color']); ?>; 
                            border-radius: <?php echo esc_attr($directory_settings['card_radius']); ?>;"
                     data-hover-border="<?php echo esc_attr($directory_settings['card_hover_border']); ?>">
                    
                    <?php if (!empty($partner['logo_url'])): ?>
                        <div class="hsr-partner-logo" 
                             style="background: <?php echo esc_attr($directory_settings['logo_bg_color']); ?>; 
                                    border-radius: calc(<?php echo esc_attr($directory_settings['card_radius']); ?> - 4px);">
                            <img src="<?php echo esc_url($partner['logo_url']); ?>" 
                                 alt="<?php echo esc_attr($partner['organization']); ?>">
                        </div>
                    <?php else: ?>
                        <div class="hsr-partner-logo hsr-partner-logo-placeholder" 
                             style="border-radius: calc(<?php echo esc_attr($directory_settings['card_radius']); ?> - 4px);">
                            <div class="hsr-logo-text">
                                <?php echo esc_html(substr($partner['organization'], 0, 2)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="hsr-partner-content">
                        <h3 class="hsr-partner-name" style="color: <?php echo esc_attr($directory_settings['title_color']); ?>;">
                            <?php echo esc_html($partner['organization']); ?>
                        </h3>
                        
                        <?php if (filter_var($atts['show_description'] ?? '1', FILTER_VALIDATE_BOOLEAN) && !empty($partner['directory_description'])): ?>
                            <p class="hsr-partner-description" style="color: <?php echo esc_attr($directory_settings['description_color']); ?>;">
                                <?php echo esc_html($partner['directory_description']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($partner['website_url'])): ?>
                            <a href="<?php echo esc_url($partner['website_url']); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="hsr-partner-button"
                               style="background: <?php echo esc_attr($directory_settings['button_bg_color']); ?>; 
                                      border-radius: calc(<?php echo esc_attr($directory_settings['card_radius']); ?> - 2px);"
                               data-hover-bg="<?php echo esc_attr($directory_settings['button_hover_color']); ?>">
                                <?php esc_html_e('Learn More', 'hubspot-referrals'); ?>
                                <svg class="hsr-button-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Partner Directory Styles - Flowbite Design System */
.hsr-partner-directory {
    margin: 2rem 0;
}

.hsr-directory-empty {
    text-align: center;
    padding: 3rem 1rem;
    background: #f9fafb;
}

.hsr-directory-grid {
    display: grid;
    margin: 0;
    padding: 0;
}

.hsr-grid-cols-2 {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
}

.hsr-grid-cols-3 {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

.hsr-grid-cols-4 {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
}

@media (min-width: 768px) {
    .hsr-grid-cols-2 {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .hsr-grid-cols-3 {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .hsr-grid-cols-4 {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* Flowbite Card Styling */
.hsr-partner-card {
    padding: 1.5rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    height: 100%;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.hsr-partner-card:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
    border-color: var(--hover-border) !important;
}

/* Partner Logo */
.hsr-partner-logo {
    width: 100%;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    overflow: hidden;
}

.hsr-partner-logo img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.hsr-partner-logo-placeholder {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.hsr-logo-text {
    font-size: 2.5rem;
    font-weight: 700;
    color: #ffffff;
    text-transform: uppercase;
}

/* Partner Content */
.hsr-partner-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.hsr-partner-name {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.75rem 0;
    line-height: 1.4;
}

.hsr-partner-description {
    font-size: 0.875rem;
    line-height: 1.6;
    margin: 0 0 1.25rem 0;
    flex: 1;
}

/* Flowbite Button */
.hsr-partner-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #ffffff;
    text-decoration: none;
    transition: all 0.2s;
    margin-top: auto;
    border: none;
}

.hsr-partner-button:hover {
    background: var(--hover-bg) !important;
    color: #ffffff;
    transform: translateX(2px);
}

.hsr-partner-button:focus {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

.hsr-button-arrow {
    width: 1rem;
    height: 1rem;
    transition: transform 0.2s;
}

.hsr-partner-button:hover .hsr-button-arrow {
    transform: translateX(2px);
}

/* Responsive adjustments */
@media (max-width: 767px) {
    .hsr-directory-grid {
        grid-template-columns: 1fr;
    }
    
    .hsr-partner-card {
        padding: 1.25rem;
    }
    
    .hsr-partner-logo {
        height: 100px;
    }
}
</style>

<script>
// Set CSS variables for hover effects using data attributes
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.hsr-partner-card').forEach(card => {
        const hoverBorder = card.getAttribute('data-hover-border');
        if (hoverBorder) {
            card.style.setProperty('--hover-border', hoverBorder);
        }
    });
    
    document.querySelectorAll('.hsr-partner-button').forEach(btn => {
        const hoverBg = btn.getAttribute('data-hover-bg');
        if (hoverBg) {
            btn.style.setProperty('--hover-bg', hoverBg);
        }
    });
});
</script>
