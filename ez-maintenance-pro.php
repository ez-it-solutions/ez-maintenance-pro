<?php
/**
 * Plugin Name: Ez Maintenance Pro
 * Plugin URI: https://www.ez-it-solutions.com/ez-maintenance-pro
 * Description: Professional maintenance mode, under construction, and coming soon page plugin with beautiful templates and advanced customization options.
 * Version: 1.0.0
 * Author: Chris Hultberg | Ez IT Solutions
 * Author URI: https://www.ez-it-solutions.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ez-maintenance-pro
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('EZMP_VERSION', '1.0.0');
define('EZMP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EZMP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EZMP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Ez_Maintenance_Pro {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once EZMP_PLUGIN_DIR . 'includes/class-core.php';
        require_once EZMP_PLUGIN_DIR . 'includes/class-templates.php';
        require_once EZMP_PLUGIN_DIR . 'includes/class-settings.php';
        require_once EZMP_PLUGIN_DIR . 'includes/class-api.php';
        require_once EZMP_PLUGIN_DIR . 'includes/class-license.php';
        require_once EZMP_PLUGIN_DIR . 'includes/class-company-info.php';
        
        if (is_admin()) {
            require_once EZMP_PLUGIN_DIR . 'admin/class-admin.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        add_action('plugins_loaded', [$this, 'init']);
        add_action('admin_init', [$this, 'activation_redirect']);
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize core components
        EZMP_Core::init();
        EZMP_Templates::init();
        EZMP_Settings::init();
        EZMP_API::init();
        EZMP_License::init();
        EZIT_Company_Info::init();
        
        if (is_admin()) {
            EZMP_Admin::init();
        }
        
        // Load text domain
        load_plugin_textdomain('ez-maintenance-pro', false, dirname(EZMP_PLUGIN_BASENAME) . '/languages');
        
        do_action('ezmp_loaded');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $defaults = [
            'ezmp_enabled' => false,
            'ezmp_mode' => 'maintenance',
            'ezmp_template' => 'modern',
            'ezmp_title' => 'Under Maintenance',
            'ezmp_message' => 'We\'re currently performing scheduled maintenance. We\'ll be back shortly!',
            'ezmp_theme_mode' => 'dark',
            'ezmp_bg_color' => '#0b0f12',
            'ezmp_text_color' => '#ffffff',
            'ezmp_accent_color' => '#a3e635',
            'ezmp_bypass_roles' => ['administrator'],
            'ezmp_bypass_ips' => [],
            'ezmp_show_logo' => true,
            'ezmp_show_social' => false,
            'ezmp_show_contact' => false,
        ];
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
        
        // Create custom database tables if needed
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation redirect flag
        set_transient('ezmp_activation_redirect', true, 30);
        
        do_action('ezmp_activated');
    }
    
    /**
     * Redirect to settings page on activation
     */
    public function activation_redirect() {
        if (get_transient('ezmp_activation_redirect')) {
            delete_transient('ezmp_activation_redirect');
            
            // Don't redirect if activating multiple plugins or doing bulk activation
            if (isset($_GET['activate-multi'])) {
                return;
            }
            
            wp_safe_redirect(admin_url('admin.php?page=ez-maintenance-pro&tab=settings&welcome=1'));
            exit;
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Disable maintenance mode on deactivation
        update_option('ezmp_enabled', false);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        do_action('ezmp_deactivated');
    }
    
    /**
     * Create custom database tables
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Subscribers table (for email collection)
        $table_name = $wpdb->prefix . 'ezmp_subscribers';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            name varchar(255) DEFAULT NULL,
            subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Logs table
        $table_name = $wpdb->prefix . 'ezmp_logs';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            action varchar(50) NOT NULL,
            details text DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
}

/**
 * Initialize the plugin
 */
function ezmp() {
    return Ez_Maintenance_Pro::get_instance();
}

// Start the plugin
ezmp();
