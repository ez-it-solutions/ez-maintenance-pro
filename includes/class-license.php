<?php
/**
 * License Management System
 * 
 * Reusable licensing class for Ez IT Solutions plugins
 * Handles license activation, validation, and feature protection
 */

if (!defined('ABSPATH')) {
    exit;
}

class EZMP_License {
    
    private static $instance = null;
    private $api_url = 'https://licensing.ez-it-solutions.com/api/v1';
    private $product_id = 'ez-maintenance-pro';
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_init', [$this, 'check_license_status']);
        add_action('wp_ajax_ezmp_activate_license', [$this, 'ajax_activate_license']);
        add_action('wp_ajax_ezmp_deactivate_license', [$this, 'ajax_deactivate_license']);
        add_action('wp_ajax_ezmp_check_license', [$this, 'ajax_check_license']);
        
        // Check license daily
        add_action('ezmp_daily_license_check', [$this, 'verify_license']);
        if (!wp_next_scheduled('ezmp_daily_license_check')) {
            wp_schedule_event(time(), 'daily', 'ezmp_daily_license_check');
        }
    }
    
    /**
     * Activate license key
     */
    public function activate_license($license_key, $email = '') {
        $site_url = home_url();
        
        $response = wp_remote_post($this->api_url . '/activate', [
            'timeout' => 15,
            'body' => [
                'license_key' => sanitize_text_field($license_key),
                'email' => sanitize_email($email),
                'site_url' => $site_url,
                'product_id' => $this->product_id,
                'wp_version' => get_bloginfo('version'),
                'plugin_version' => EZMP_VERSION,
                'php_version' => PHP_VERSION,
            ]
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $response->get_error_message()
            ];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['success']) && $body['success']) {
            // Store license data
            update_option('ezmp_license_key', $license_key);
            update_option('ezmp_license_email', $email);
            update_option('ezmp_license_status', 'active');
            update_option('ezmp_license_expires', $body['expires_at'] ?? '');
            update_option('ezmp_license_plan', $body['plan'] ?? 'free');
            update_option('ezmp_license_verified', time());
            
            // Log activation
            $this->log_action('license_activated', [
                'license_key' => substr($license_key, 0, 8) . '...',
                'plan' => $body['plan'] ?? 'free'
            ]);
            
            return [
                'success' => true,
                'message' => 'License activated successfully!',
                'data' => $body
            ];
        }
        
        return [
            'success' => false,
            'message' => $body['message'] ?? 'License activation failed'
        ];
    }
    
    /**
     * Deactivate license
     */
    public function deactivate_license() {
        $license_key = get_option('ezmp_license_key', '');
        
        if (empty($license_key)) {
            return [
                'success' => false,
                'message' => 'No license key found'
            ];
        }
        
        $response = wp_remote_post($this->api_url . '/deactivate', [
            'timeout' => 15,
            'body' => [
                'license_key' => $license_key,
                'site_url' => home_url(),
                'product_id' => $this->product_id,
            ]
        ]);
        
        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
        }
        
        // Clear license data locally regardless of API response
        delete_option('ezmp_license_key');
        delete_option('ezmp_license_email');
        delete_option('ezmp_license_status');
        delete_option('ezmp_license_expires');
        delete_option('ezmp_license_plan');
        delete_option('ezmp_license_verified');
        
        $this->log_action('license_deactivated', []);
        
        return [
            'success' => true,
            'message' => 'License deactivated successfully'
        ];
    }
    
    /**
     * Verify license with server
     */
    public function verify_license() {
        $license_key = get_option('ezmp_license_key', '');
        
        if (empty($license_key)) {
            return false;
        }
        
        $response = wp_remote_post($this->api_url . '/verify', [
            'timeout' => 15,
            'body' => [
                'license_key' => $license_key,
                'site_url' => home_url(),
                'product_id' => $this->product_id,
            ]
        ]);
        
        if (is_wp_error($response)) {
            // If we can't reach server, use cached status for grace period (7 days)
            $last_verified = get_option('ezmp_license_verified', 0);
            if (time() - $last_verified < (7 * DAY_IN_SECONDS)) {
                return get_option('ezmp_license_status') === 'active';
            }
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['success']) && $body['success']) {
            update_option('ezmp_license_status', $body['status'] ?? 'active');
            update_option('ezmp_license_expires', $body['expires_at'] ?? '');
            update_option('ezmp_license_plan', $body['plan'] ?? 'free');
            update_option('ezmp_license_verified', time());
            
            return $body['status'] === 'active';
        }
        
        // License invalid
        update_option('ezmp_license_status', 'invalid');
        return false;
    }
    
    /**
     * Check if license is active
     */
    public function is_active() {
        $status = get_option('ezmp_license_status', '');
        return $status === 'active';
    }
    
    /**
     * Get license plan
     */
    public function get_plan() {
        return get_option('ezmp_license_plan', 'free');
    }
    
    /**
     * Check if feature is available for current plan
     */
    public function has_feature($feature) {
        $plan = $this->get_plan();
        
        $features = [
            'free' => ['basic_templates', 'color_customization', 'basic_access_control'],
            'pro' => ['basic_templates', 'color_customization', 'basic_access_control', 'premium_templates', 'countdown_timer', 'social_links', 'custom_css', 'api_access'],
            'business' => ['basic_templates', 'color_customization', 'basic_access_control', 'premium_templates', 'countdown_timer', 'social_links', 'custom_css', 'api_access', 'white_label', 'priority_support', 'multisite']
        ];
        
        if (!isset($features[$plan])) {
            return false;
        }
        
        return in_array($feature, $features[$plan]);
    }
    
    /**
     * Get license info
     */
    public function get_license_info() {
        return [
            'key' => get_option('ezmp_license_key', ''),
            'email' => get_option('ezmp_license_email', ''),
            'status' => get_option('ezmp_license_status', ''),
            'plan' => get_option('ezmp_license_plan', 'free'),
            'expires' => get_option('ezmp_license_expires', ''),
            'verified' => get_option('ezmp_license_verified', 0),
        ];
    }
    
    /**
     * Check license status on admin init
     */
    public function check_license_status() {
        // Only check once per day
        $last_check = get_option('ezmp_license_last_check', 0);
        if (time() - $last_check < DAY_IN_SECONDS) {
            return;
        }
        
        $this->verify_license();
        update_option('ezmp_license_last_check', time());
    }
    
    /**
     * AJAX: Activate license
     */
    public function ajax_activate_license() {
        check_ajax_referer('ezmp_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $license_key = sanitize_text_field($_POST['license_key'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        
        if (empty($license_key)) {
            wp_send_json_error(['message' => 'License key is required']);
        }
        
        $result = $this->activate_license($license_key, $email);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Deactivate license
     */
    public function ajax_deactivate_license() {
        check_ajax_referer('ezmp_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $result = $this->deactivate_license();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Check license
     */
    public function ajax_check_license() {
        check_ajax_referer('ezmp_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $is_valid = $this->verify_license();
        $info = $this->get_license_info();
        
        wp_send_json_success([
            'valid' => $is_valid,
            'info' => $info
        ]);
    }
    
    /**
     * Log license action
     */
    private function log_action($action, $details) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ezmp_logs';
        
        $wpdb->insert($table_name, [
            'action' => $action,
            'details' => json_encode($details),
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Display upgrade notice
     */
    public static function upgrade_notice($feature_name) {
        ?>
        <div class="ezmp-upgrade-notice" style="background: rgba(163, 230, 53, 0.1); border: 2px solid rgba(163, 230, 53, 0.3); border-radius: 8px; padding: 20px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #a3e635; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-lock" style="font-size: 24px;"></span>
                Premium Feature
            </h3>
            <p style="font-size: 1.05rem; margin-bottom: 15px;">
                <strong><?php echo esc_html($feature_name); ?></strong> is available in Pro and Business plans.
            </p>
            <p style="margin-bottom: 20px;">
                Upgrade to unlock this feature along with premium templates, advanced customization, API access, and more!
            </p>
            <a href="https://www.ez-it-solutions.com/ez-maintenance-pro/pricing" target="_blank" class="button button-primary" style="background: #a3e635 !important; border-color: #a3e635 !important; color: #0b0f12 !important;">
                View Pricing & Upgrade →
            </a>
        </div>
        <?php
    }
}
