<?php
/**
 * Plugin Uninstall Handler
 * 
 * Fired when the plugin is uninstalled (deleted, not just deactivated)
 * Cleans up all plugin data from WordPress
 *
 * @package HubSpot_Referrals
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove plugin options
 */
function hsr_uninstall_remove_options() {
    delete_option('hsr_settings');
    delete_option('hsr_version');
    delete_option('hsr_activation_redirect');
}

/**
 * Remove transients
 */
function hsr_uninstall_remove_transients() {
    delete_transient('hsr_referral_data');
    delete_transient('hsr_webhook_logs');
    delete_transient('hsr_api_cache_contacts');
    delete_transient('hsr_api_cache_referrals');
    
    // Remove any transients with our prefix
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hsr_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_hsr_%'");
}

/**
 * Unschedule cron jobs
 */
function hsr_uninstall_remove_cron() {
    $timestamp = wp_next_scheduled('hsr_monthly_stats_cron');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'hsr_monthly_stats_cron');
    }
    
    // Clear all cron jobs with our prefix
    wp_clear_scheduled_hook('hsr_monthly_stats_cron');
}

/**
 * Remove custom database tables (if any were created in future)
 */
function hsr_uninstall_remove_tables() {
    global $wpdb;
    
    // Currently we don't create custom tables, but if we add them:
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hsr_conversions");
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hsr_logs");
}

/**
 * Main uninstall function
 */
function hsr_uninstall() {
    // Check if user wants to keep data
    $settings = get_option('hsr_settings', array());
    $keep_data = isset($settings['keep_data_on_uninstall']) && $settings['keep_data_on_uninstall'] === '1';
    
    if ($keep_data) {
        error_log('HSR: Uninstall - keeping data as per settings');
        return;
    }
    
    // Remove all plugin data
    hsr_uninstall_remove_options();
    hsr_uninstall_remove_transients();
    hsr_uninstall_remove_cron();
    hsr_uninstall_remove_tables();
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    error_log('HSR: Plugin data cleaned up successfully');
}

// Run uninstall
hsr_uninstall();
