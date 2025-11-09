<?php
/**
 * Core Functionality
 * 
 * Handles the main maintenance mode logic and display
 */

if (!defined('ABSPATH')) {
    exit;
}

class EZMP_Core {
    
    private static $instance = null;
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('template_redirect', [$this, 'check_maintenance_mode'], 1);
        add_action('wp_ajax_ezmp_toggle_mode', [$this, 'ajax_toggle_mode']);
    }
    
    /**
     * Check if maintenance mode should be displayed
     */
    public function check_maintenance_mode() {
        // Check if maintenance mode is enabled
        if (!get_option('ezmp_enabled', false)) {
            return;
        }
        
        // Allow administrators to bypass
        if ($this->should_bypass()) {
            return;
        }
        
        // Display maintenance page
        $this->display_maintenance_page();
        exit;
    }
    
    /**
     * Check if current user should bypass maintenance mode
     */
    private function should_bypass() {
        // Check user roles
        $bypass_roles = get_option('ezmp_bypass_roles', ['administrator']);
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            foreach ($bypass_roles as $role) {
                if (in_array($role, $user->roles)) {
                    return true;
                }
            }
        }
        
        // Check IP whitelist
        $bypass_ips = get_option('ezmp_bypass_ips', []);
        $user_ip = $this->get_user_ip();
        if (in_array($user_ip, $bypass_ips)) {
            return true;
        }
        
        // Allow plugins to add custom bypass logic
        return apply_filters('ezmp_should_bypass', false);
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    /**
     * Display maintenance page
     */
    private function display_maintenance_page() {
        // Set HTTP status
        status_header(503);
        nocache_headers();
        
        // Get template
        $template = get_option('ezmp_template', 'modern');
        $template_file = EZMP_PLUGIN_DIR . 'templates/' . sanitize_file_name($template) . '.php';
        
        // Fallback to modern template if custom doesn't exist
        if (!file_exists($template_file)) {
            $template_file = EZMP_PLUGIN_DIR . 'templates/modern.php';
        }
        
        // Allow plugins to filter template
        $template_file = apply_filters('ezmp_template_file', $template_file, $template);
        
        // Load template
        if (file_exists($template_file)) {
            do_action('ezmp_before_display');
            include $template_file;
            do_action('ezmp_after_display');
        }
    }
    
    /**
     * AJAX: Toggle maintenance mode
     */
    public function ajax_toggle_mode() {
        check_ajax_referer('ezmp_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $enabled = get_option('ezmp_enabled', false);
        $new_status = !$enabled;
        
        update_option('ezmp_enabled', $new_status);
        
        // Log the action
        $this->log_action($new_status ? 'activated' : 'deactivated');
        
        wp_send_json_success([
            'enabled' => $new_status,
            'message' => $new_status ? 'Maintenance mode activated' : 'Maintenance mode deactivated'
        ]);
    }
    
    /**
     * Log action to database
     */
    private function log_action($action, $details = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'ezmp_logs';
        
        $wpdb->insert($table, [
            'action' => $action,
            'details' => $details,
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ]);
        
        do_action('ezmp_action_logged', $action, $details);
    }
    
    /**
     * Get maintenance mode status
     */
    public static function is_active() {
        return (bool) get_option('ezmp_enabled', false);
    }
    
    /**
     * Get current mode
     */
    public static function get_mode() {
        return get_option('ezmp_mode', 'maintenance');
    }
}
