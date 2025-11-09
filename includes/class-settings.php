<?php
/**
 * Settings Management
 * 
 * Handles plugin settings and options
 */

if (!defined('ABSPATH')) {
    exit;
}

class EZMP_Settings {
    
    private static $instance = null;
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_ezmp_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_ezmp_reset_settings', [$this, 'ajax_reset_settings']);
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // General settings
        register_setting('ezmp_general', 'ezmp_enabled');
        register_setting('ezmp_general', 'ezmp_mode');
        register_setting('ezmp_general', 'ezmp_template');
        register_setting('ezmp_general', 'ezmp_title');
        register_setting('ezmp_general', 'ezmp_message');
        
        // Design settings
        register_setting('ezmp_design', 'ezmp_theme_mode');
        register_setting('ezmp_design', 'ezmp_bg_color');
        register_setting('ezmp_design', 'ezmp_text_color');
        register_setting('ezmp_design', 'ezmp_accent_color');
        register_setting('ezmp_design', 'ezmp_logo_url');
        register_setting('ezmp_design', 'ezmp_show_logo');
        register_setting('ezmp_design', 'ezmp_custom_css');
        
        // Access settings
        register_setting('ezmp_access', 'ezmp_bypass_roles');
        register_setting('ezmp_access', 'ezmp_bypass_ips');
        
        // Contact settings
        register_setting('ezmp_contact', 'ezmp_show_contact');
        register_setting('ezmp_contact', 'ezmp_contact_email');
        register_setting('ezmp_contact', 'ezmp_contact_phone');
        register_setting('ezmp_contact', 'ezmp_show_social');
        register_setting('ezmp_contact', 'ezmp_social_facebook');
        register_setting('ezmp_contact', 'ezmp_social_twitter');
        register_setting('ezmp_contact', 'ezmp_social_instagram');
        
        // Countdown settings
        register_setting('ezmp_countdown', 'ezmp_countdown_enabled');
        register_setting('ezmp_countdown', 'ezmp_countdown_date');
    }
    
    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('ezmp_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $settings = $_POST['settings'] ?? [];
        
        foreach ($settings as $key => $value) {
            // Sanitize based on setting type
            if (strpos($key, '_color') !== false) {
                $value = sanitize_hex_color($value);
            } elseif (strpos($key, '_url') !== false) {
                $value = esc_url_raw($value);
            } elseif (strpos($key, '_email') !== false) {
                $value = sanitize_email($value);
            } elseif (is_array($value)) {
                $value = array_map('sanitize_text_field', $value);
            } else {
                $value = sanitize_text_field($value);
            }
            
            update_option($key, $value);
        }
        
        wp_send_json_success(['message' => 'Settings saved successfully']);
    }
    
    /**
     * AJAX: Reset settings
     */
    public function ajax_reset_settings() {
        check_ajax_referer('ezmp_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        // Get all ezmp options
        global $wpdb;
        $options = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'ezmp_%'");
        
        foreach ($options as $option) {
            delete_option($option->option_name);
        }
        
        // Re-activate to set defaults
        Ez_Maintenance_Pro::get_instance()->activate();
        
        wp_send_json_success(['message' => 'Settings reset to defaults']);
    }
    
    /**
     * Get setting value
     */
    public static function get($key, $default = null) {
        return get_option('ezmp_' . $key, $default);
    }
    
    /**
     * Update setting value
     */
    public static function update($key, $value) {
        return update_option('ezmp_' . $key, $value);
    }
}
