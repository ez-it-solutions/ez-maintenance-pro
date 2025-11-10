<?php
/**
 * Ez IT Solutions - Company Info Manager
 * 
 * Shared class for fetching and displaying company information
 * across all Ez IT Solutions plugins
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load plugin actions registry
if (file_exists(dirname(__FILE__) . '/class-plugin-actions-registry.php')) {
    require_once dirname(__FILE__) . '/class-plugin-actions-registry.php';
}

if (!class_exists('EZIT_Company_Info')) {
class EZIT_Company_Info {
    
    private static $instance = null;
    private static $api_url = 'https://www.ez-it-solutions.com/api/v1/company-info';
    
    /**
     * Initialize hooks
     */
    public static function init() {
        add_action('wp_ajax_ezit_activate_license', [__CLASS__, 'ajax_activate_license']);
        add_action('wp_ajax_ezit_submit_license', [__CLASS__, 'ajax_submit_license']);
        add_action('wp_ajax_ezit_backup_now', [__CLASS__, 'ajax_backup_now']);
        add_action('wp_ajax_ezit_plugin_action', [__CLASS__, 'ajax_plugin_action']);
    }
    
    /**
     * AJAX handler for plugin activation/deactivation
     */
    public static function ajax_plugin_action() {
        check_ajax_referer('ezit_plugin_action', 'nonce');
        
        if (!current_user_can('activate_plugins')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $plugin_file = sanitize_text_field($_POST['plugin_file']);
        $plugin_action = sanitize_text_field($_POST['plugin_action']);
        
        if ($plugin_action === 'activate') {
            $result = activate_plugin($plugin_file);
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            wp_send_json_success('Plugin activated successfully');
        } elseif ($plugin_action === 'deactivate') {
            deactivate_plugins($plugin_file);
            wp_send_json_success('Plugin deactivated successfully');
        }
        
        wp_send_json_error('Invalid action');
    }
    
    /**
     * Get company information
     * Fetches from API with 24-hour cache
     */
    public static function get_info() {
        // Check cache first
        $cached = get_transient('ezit_company_info');
        if ($cached !== false) {
            return $cached;
        }
        
        // Fetch from API
        $response = wp_remote_get(self::$api_url, [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        
        // If API fails, use defaults
        if (is_wp_error($response)) {
            return self::get_default_info();
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['success']) || !$body['success']) {
            return self::get_default_info();
        }
        
        $info = $body['data'];
        
        // Cache for 24 hours
        set_transient('ezit_company_info', $info, DAY_IN_SECONDS);
        
        return $info;
    }
    
    /**
     * Get default company information
     */
    private static function get_default_info() {
        return [
            'name' => 'Ez IT Solutions',
            'tagline' => 'Professional WordPress Solutions',
            'description' => 'We build premium WordPress plugins and provide comprehensive IT solutions for businesses.',
            'website' => 'https://www.ez-it-solutions.com',
            'email' => 'chrishultberg@ez-it-solutions.com',
            'phone' => '',
            'logo' => '',
            'social' => [
                'facebook' => '',
                'twitter' => '',
                'linkedin' => '',
                'github' => 'https://github.com/ez-it-solutions'
            ],
            'support_url' => 'https://www.ez-it-solutions.com/support',
            'docs_url' => 'https://www.ez-it-solutions.com/docs',
            'products' => []
        ];
    }
    
    /**
     * Get installed Ez IT plugins with auto-detected admin pages
     */
    public static function get_installed_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $ezit_plugins = [];
        
        foreach ($all_plugins as $plugin_file => $plugin_data) {
            // Check if it's an Ez IT plugin
            if (stripos($plugin_data['Name'], 'Ez') === 0 || 
                stripos($plugin_data['Author'], 'Ez IT Solutions') !== false ||
                stripos($plugin_data['AuthorURI'], 'ez-it-solutions.com') !== false) {
                
                // Auto-detect admin page slug from plugin file
                $plugin_slug = dirname($plugin_file);
                if ($plugin_slug === '.') {
                    $plugin_slug = basename($plugin_file, '.php');
                }
                
                // Try to find registered admin pages for this plugin
                $admin_pages = self::find_plugin_admin_pages($plugin_slug, $plugin_data['Name']);
                
                $ezit_plugins[] = [
                    'name' => $plugin_data['Name'],
                    'version' => $plugin_data['Version'],
                    'description' => $plugin_data['Description'],
                    'author' => $plugin_data['Author'],
                    'active' => is_plugin_active($plugin_file),
                    'file' => $plugin_file,
                    'slug' => $plugin_slug,
                    'dashboard_url' => $admin_pages['dashboard'] ?? '',
                    'settings_url' => $admin_pages['settings'] ?? ''
                ];
            }
        }
        
        return $ezit_plugins;
    }
    
    /**
     * Find admin pages registered by a plugin
     */
    private static function find_plugin_admin_pages($plugin_slug, $plugin_name) {
        global $menu, $submenu;
        
        $pages = [
            'dashboard' => '',
            'settings' => ''
        ];
        
        // Common page slug patterns to check
        $slug_patterns = [
            $plugin_slug,
            str_replace('_', '-', $plugin_slug),
            str_replace('-', '_', $plugin_slug),
            sanitize_title($plugin_name)
        ];
        
        // Check main menu
        if (!empty($menu)) {
            foreach ($menu as $item) {
                if (isset($item[2])) {
                    foreach ($slug_patterns as $pattern) {
                        if (stripos($item[2], $pattern) !== false) {
                            $pages['dashboard'] = admin_url('admin.php?page=' . $item[2]);
                            break 2;
                        }
                    }
                }
            }
        }
        
        // Check submenus under Ez IT Solutions parent
        if (!empty($submenu['ez-it-solutions'])) {
            foreach ($submenu['ez-it-solutions'] as $item) {
                if (isset($item[2])) {
                    foreach ($slug_patterns as $pattern) {
                        if (stripos($item[2], $pattern) !== false && stripos($item[2], 'ez-it-solutions') === false) {
                            $pages['dashboard'] = admin_url('admin.php?page=' . $item[2]);
                            // Settings is typically dashboard + &tab=settings
                            $pages['settings'] = admin_url('admin.php?page=' . $item[2] . '&tab=settings');
                            break 2;
                        }
                    }
                }
            }
        }
        
        // Check all submenus if not found yet
        if (empty($pages['dashboard']) && !empty($submenu)) {
            foreach ($submenu as $parent => $items) {
                foreach ($items as $item) {
                    if (isset($item[2])) {
                        foreach ($slug_patterns as $pattern) {
                            if (stripos($item[2], $pattern) !== false) {
                                $pages['dashboard'] = admin_url('admin.php?page=' . $item[2]);
                                $pages['settings'] = admin_url('admin.php?page=' . $item[2] . '&tab=settings');
                                break 3;
                            }
                        }
                    }
                }
            }
        }
        
        return $pages;
    }
    
    /**
     * Clear cached company info
     */
    public static function clear_cache() {
        delete_transient('ezit_company_info');
    }
    
    /**
     * Handle license activation via AJAX
     */
    public static function ajax_activate_license() {
        check_ajax_referer('ezit_license_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $plugin_slug = isset($_POST['plugin_slug']) ? sanitize_text_field($_POST['plugin_slug']) : '';
        $action_id = isset($_POST['action_id']) ? sanitize_text_field($_POST['action_id']) : '';
        
        // Prompt for license key via JavaScript
        wp_send_json_success([
            'prompt' => true,
            'message' => 'Please enter your license key'
        ]);
    }
    
    /**
     * Handle license key submission via AJAX
     */
    public static function ajax_submit_license() {
        check_ajax_referer('ezit_license_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $plugin_slug = isset($_POST['plugin_slug']) ? sanitize_text_field($_POST['plugin_slug']) : '';
        $license_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
        
        if (empty($plugin_slug) || empty($license_key)) {
            wp_send_json_error('Missing required fields');
        }
        
        // Store license key based on plugin
        if ($plugin_slug === 'ez-maintenance-pro') {
            update_option('ezmp_license_key', $license_key);
        } else {
            update_option('ezit_license_' . $plugin_slug, $license_key);
        }
        
        // TODO: Validate with license server
        // For now, just store it
        
        wp_send_json_success('License activated');
    }
    
    /**
     * Handle backup creation via AJAX
     */
    public static function ajax_backup_now() {
        check_ajax_referer('ezit_backup_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $plugin_slug = isset($_POST['plugin_slug']) ? sanitize_text_field($_POST['plugin_slug']) : '';
        
        // Check if backup system is available
        if (class_exists('EZIT_Backup_Core')) {
            $result = EZIT_Backup_Core::create_backup([
                'type' => 'full',
                'compression' => 'zip',
                'storage' => ['local'],
                'description' => 'Manual backup from Company Info page'
            ]);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success('Backup created successfully');
            }
        } else {
            wp_send_json_error('Backup system not available. Please install Ez IT Backup System plugin.');
        }
    }
    
    /**
     * Render company info page
     * Made public static so it can be called as callback
     */
    public static function render_page() {
        // Get theme mode from either plugin
        $theme_mode = get_option('ezit_cm_theme', get_option('ezmp_theme_mode', 'dark'));
        $theme_class = $theme_mode === 'light' ? 'ezit-light' : 'ezit-dark';
        
        $info = self::get_info();
        $plugins = self::get_installed_plugins();
        ?>
        <div class="ezit-fullpage <?php echo esc_attr($theme_class); ?>" id="ezit-company-wrap" data-theme="<?php echo esc_attr($theme_mode); ?>">
        <div class="ezit-company-info-page">
            <div class="ezit-company-header">
                <?php if (!empty($info['logo'])): ?>
                    <img src="<?php echo esc_url($info['logo']); ?>" alt="<?php echo esc_attr($info['name']); ?>" class="ezit-company-logo">
                <?php endif; ?>
                <h1><?php echo esc_html($info['name']); ?></h1>
                <p class="ezit-tagline"><?php echo esc_html($info['tagline']); ?></p>
            </div>
            
            <div class="ezit-company-content">
                <div class="ezit-main-content">
                    <div class="ezit-card">
                        <h2>About Us</h2>
                        <p><?php echo esc_html($info['description']); ?></p>
                        
                        <div class="ezit-contact-info">
                            <p><strong>Website:</strong> <a href="<?php echo esc_url($info['website']); ?>" target="_blank"><?php echo esc_html($info['website']); ?></a></p>
                            <?php if (!empty($info['email'])): ?>
                                <p><strong>Email:</strong> <a href="mailto:<?php echo esc_attr($info['email']); ?>"><?php echo esc_html($info['email']); ?></a></p>
                            <?php endif; ?>
                            <?php if (!empty($info['phone'])): ?>
                                <p><strong>Phone:</strong> <?php echo esc_html($info['phone']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="ezit-card">
                        <h2>Installed Plugins</h2>
                        <?php if (empty($plugins)): ?>
                            <p>No Ez IT Solutions plugins detected.</p>
                        <?php else: ?>
                            <div class="ezit-plugins-list">
                                <?php foreach ($plugins as $plugin): ?>
                                    <div class="ezit-plugin-item <?php echo $plugin['active'] ? 'active' : 'inactive'; ?>" 
                                         <?php if (!empty($plugin['dashboard_url'])): ?>
                                         onclick="window.location.href='<?php echo esc_url($plugin['dashboard_url']); ?>'" 
                                         style="cursor: pointer;"
                                         <?php endif; ?>>
                                        <div class="ezit-plugin-header">
                                            <h3><?php echo esc_html($plugin['name']); ?></h3>
                                            <span class="ezit-plugin-version">v<?php echo esc_html($plugin['version']); ?></span>
                                            <?php if ($plugin['active']): ?>
                                                <span class="ezit-plugin-badge active">Active</span>
                                            <?php else: ?>
                                                <span class="ezit-plugin-badge inactive">Inactive</span>
                                            <?php endif; ?>
                                        </div>
                                        <p><?php echo esc_html($plugin['description']); ?></p>
                                        
                                        <div class="ezit-plugin-actions" onclick="event.stopPropagation();">
                                            <?php if (!empty($plugin['dashboard_url'])): ?>
                                                <a href="<?php echo esc_url($plugin['dashboard_url']); ?>" class="ezit-plugin-link">
                                                    <span class="dashicons dashicons-dashboard"></span> Dashboard
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($plugin['settings_url'])): ?>
                                                <a href="<?php echo esc_url($plugin['settings_url']); ?>" class="ezit-plugin-link">
                                                    <span class="dashicons dashicons-admin-settings"></span> Settings
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php
                                            // Fire registration hook and render custom plugin actions from registry
                                            if (class_exists('EZIT_Plugin_Actions_Registry')) {
                                                // Fire the registration hook to ensure all plugins have registered their actions
                                                do_action('ezit_register_plugin_actions');
                                                
                                                $custom_actions = EZIT_Plugin_Actions_Registry::get_actions($plugin['slug']);
                                                foreach ($custom_actions as $action) {
                                                    EZIT_Plugin_Actions_Registry::render_action($action, $plugin['slug']);
                                                }
                                            }
                                            ?>
                                            
                                            <?php if ($plugin['active']): ?>
                                                <a href="#" class="ezit-plugin-link ezit-plugin-deactivate" data-plugin-file="<?php echo esc_attr($plugin['file']); ?>" data-action="deactivate" onclick="return ezitPluginAction(this, 'Are you sure you want to deactivate <?php echo esc_js($plugin['name']); ?>?');">
                                                    <span class="dashicons dashicons-dismiss"></span> Deactivate
                                                </a>
                                            <?php else: ?>
                                                <a href="#" class="ezit-plugin-link ezit-plugin-activate" data-plugin-file="<?php echo esc_attr($plugin['file']); ?>" data-action="activate" onclick="return ezitPluginAction(this, 'Activate <?php echo esc_js($plugin['name']); ?>?');">
                                                    <span class="dashicons dashicons-yes"></span> Activate
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <aside class="ezit-sidebar">
                    <div class="ezit-card">
                        <h3>Quick Links</h3>
                        <ul class="ezit-quick-links">
                            <li><a href="<?php echo esc_url($info['website']); ?>" target="_blank">
                                <span class="dashicons dashicons-admin-site-alt3"></span> Visit Website
                            </a></li>
                            <li><a href="<?php echo esc_url($info['support_url']); ?>" target="_blank">
                                <span class="dashicons dashicons-sos"></span> Get Support
                            </a></li>
                            <li><a href="<?php echo esc_url($info['docs_url']); ?>" target="_blank">
                                <span class="dashicons dashicons-book"></span> Documentation
                            </a></li>
                        </ul>
                    </div>
                    
                    <?php if (!empty(array_filter($info['social']))): ?>
                    <div class="ezit-card">
                        <h3>Connect With Us</h3>
                        <div class="ezit-social-links">
                            <?php if (!empty($info['social']['facebook'])): ?>
                                <a href="<?php echo esc_url($info['social']['facebook']); ?>" target="_blank" class="ezit-social-link">
                                    <span class="dashicons dashicons-facebook"></span>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($info['social']['twitter'])): ?>
                                <a href="<?php echo esc_url($info['social']['twitter']); ?>" target="_blank" class="ezit-social-link">
                                    <span class="dashicons dashicons-twitter"></span>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($info['social']['linkedin'])): ?>
                                <a href="<?php echo esc_url($info['social']['linkedin']); ?>" target="_blank" class="ezit-social-link">
                                    <span class="dashicons dashicons-linkedin"></span>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($info['social']['github'])): ?>
                                <a href="<?php echo esc_url($info['social']['github']); ?>" target="_blank" class="ezit-social-link">
                                    <span class="dashicons dashicons-github"></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="ezit-card">
                        <h3>System Info</h3>
                        <table class="ezit-system-info">
                            <tr>
                                <td><strong>WordPress:</strong></td>
                                <td><?php echo get_bloginfo('version'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>PHP:</strong></td>
                                <td><?php echo PHP_VERSION; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Plugins:</strong></td>
                                <td><?php echo count($plugins); ?> Ez IT plugins</td>
                            </tr>
                        </table>
                        
                        <button type="button" class="ezit-refresh-btn" id="ezit-refresh-info">
                            <span class="dashicons dashicons-update"></span> Refresh Info
                        </button>
                    </div>
                </aside>
            </div>
        </div>
        
        <style>
            /* Hide all admin notices on company info page */
            #ezit-company-wrap ~ .notice,
            #ezit-company-wrap ~ .updated,
            #ezit-company-wrap ~ .error,
            #ezit-company-wrap ~ div[class*="notice"],
            .ezit-fullpage .notice,
            .ezit-fullpage .updated,
            .ezit-fullpage .error {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                z-index: -9999 !important;
                pointer-events: none !important;
            }
            
            /* Full page wrapper */
            .ezit-fullpage {
                position: fixed;
                top: 32px;
                left: 160px;
                right: 0;
                bottom: 0;
                overflow-y: auto;
                padding: 20px;
                transition: background-color 0.3s ease;
                z-index: 9999;
            }
            
            .ezit-dark {
                background-color: #0b0f12;
                color: #e2e8f0;
            }
            
            .ezit-light {
                background-color: #f9fafb;
                color: #1f2937;
            }
            
            @media screen and (max-width: 960px) {
                .ezit-fullpage {
                    left: 36px;
                }
            }
            
            @media screen and (max-width: 782px) {
                .ezit-fullpage {
                    top: 46px;
                    left: 0;
                }
            }
            
            .ezit-company-info-page {
                max-width: 1400px;
                margin: 0 auto;
                padding: 0;
            }
            
            .ezit-company-header {
                text-align: center;
                padding: 40px 20px;
                background: linear-gradient(135deg, #0b0f12 0%, #1a2332 100%);
                border-radius: 12px;
                margin-bottom: 30px;
                color: white;
            }
            
            .ezit-light .ezit-company-header {
                background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                color: #0c4a6e;
            }
            
            .ezit-company-logo {
                max-width: 200px;
                margin-bottom: 20px;
            }
            
            .ezit-company-header h1 {
                margin: 0 0 10px;
                font-size: 2.5rem;
                color: #a3e635;
            }
            
            .ezit-light .ezit-company-header h1 {
                color: #16a34a;
            }
            
            .ezit-tagline {
                font-size: 1.2rem;
                opacity: 0.9;
                margin: 0;
            }
            
            .ezit-company-content {
                display: flex;
                gap: 30px;
            }
            
            .ezit-main-content {
                flex: 1;
            }
            
            .ezit-sidebar {
                width: 320px;
                flex-shrink: 0;
            }
            
            .ezit-card {
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 25px;
                margin-bottom: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            
            .ezit-dark .ezit-card {
                background: #1a2332;
                border-color: #2d3748;
                color: #e2e8f0;
            }
            
            .ezit-card h2 {
                margin-top: 0;
                color: #0b0f12;
                border-bottom: 2px solid #a3e635;
                padding-bottom: 10px;
            }
            
            .ezit-dark .ezit-card h2 {
                color: #f1f5f9;
                border-bottom-color: #a3e635;
            }
            
            .ezit-card h3 {
                margin-top: 0;
                color: #0b0f12;
            }
            
            .ezit-dark .ezit-card h3 {
                color: #f1f5f9;
            }
            
            .ezit-contact-info p {
                margin: 10px 0;
            }
            
            .ezit-contact-info a {
                color: #a3e635;
                text-decoration: none;
                transition: color 0.2s;
            }
            
            .ezit-contact-info a:hover {
                color: #84cc16;
            }
            
            .ezit-light .ezit-contact-info a {
                color: #16a34a;
            }
            
            .ezit-light .ezit-contact-info a:hover {
                color: #15803d;
            }
            
            .ezit-plugins-list {
                margin-top: 20px;
            }
            
            .ezit-plugin-item {
                border: 1px solid #ddd;
                border-radius: 6px;
                padding: 15px;
                margin-bottom: 15px;
                background: #f9f9f9;
                border-left: 4px solid #a3e635;
                transition: all 0.2s ease;
            }
            
            .ezit-plugin-item[style*="cursor: pointer"]:hover {
                background: rgba(163, 230, 53, 0.05);
                border-color: #a3e635;
            }
            
            .ezit-dark .ezit-plugin-item[style*="cursor: pointer"]:hover {
                background: rgba(163, 230, 53, 0.08);
                border-color: #a3e635;
            }
            
            .ezit-light .ezit-plugin-item[style*="cursor: pointer"]:hover {
                background: rgba(22, 163, 74, 0.05);
                border-color: #16a34a;
            }
            
            .ezit-dark .ezit-plugin-item {
                background: #0f1419;
                border-color: #2d3748;
            }
            
            .ezit-plugin-actions {
                display: flex;
                gap: 10px;
                margin-top: 12px;
                padding-top: 12px;
                border-top: 1px solid rgba(163, 230, 53, 0.2);
            }
            
            .ezit-plugin-link {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 6px 12px;
                border: 1px solid #a3e635;
                border-radius: 4px;
                color: #a3e635;
                text-decoration: none;
                font-size: 13px;
                font-weight: 600;
                transition: background 0.2s ease, color 0.2s ease;
                background: transparent;
                transform: none;
            }
            
            .ezit-plugin-link:hover {
                background: #a3e635;
                color: #0b0f12;
                text-decoration: none;
                transform: none;
            }
            
            .ezit-plugin-link .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }
            
            .ezit-light .ezit-plugin-link {
                border-color: #16a34a;
                color: #16a34a;
            }
            
            .ezit-light .ezit-plugin-link:hover {
                background: #16a34a;
                color: white;
            }
            
            .ezit-plugin-link.ezit-plugin-license {
                border-color: #3b82f6;
                color: #3b82f6;
            }
            
            .ezit-plugin-link.ezit-plugin-license:hover {
                background: #3b82f6;
                color: white;
            }
            
            .ezit-plugin-link.ezit-plugin-backup {
                border-color: #f59e0b;
                color: #f59e0b;
            }
            
            .ezit-plugin-link.ezit-plugin-backup:hover {
                background: #f59e0b;
                color: white;
            }
            
            .ezit-plugin-link.ezit-plugin-deactivate {
                border-color: #ef4444;
                color: #ef4444;
            }
            
            .ezit-plugin-link.ezit-plugin-deactivate:hover {
                background: #ef4444;
                color: white;
            }
            
            .ezit-plugin-link.ezit-plugin-activate {
                border-color: #3b82f6 !important;
                color: #3b82f6 !important;
                background: transparent !important;
                font-weight: 600 !important;
            }
            
            .ezit-plugin-link.ezit-plugin-activate:hover {
                background: #3b82f6 !important;
                color: white !important;
                border-color: #3b82f6 !important;
            }
            
            .ezit-plugin-item.active {
                border-left: 4px solid #a3e635;
            }
            
            .ezit-plugin-item.inactive {
                opacity: 0.7;
                border-left: 4px solid #ef4444;
            }
            
            .ezit-plugin-item.inactive .ezit-plugin-badge {
                opacity: 1;
            }
            
            .ezit-plugin-item.inactive .ezit-plugin-version {
                opacity: 1;
            }
            
            .ezit-plugin-item.inactive .ezit-plugin-link {
                opacity: 1;
            }
            
            .ezit-plugin-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 10px;
            }
            
            .ezit-plugin-header h3 {
                margin: 0;
                flex: 1;
                font-size: 1.1rem;
            }
            
            .ezit-plugin-version {
                font-size: 0.9rem;
                color: #d1d5db;
            }
            
            .ezit-plugin-badge {
                padding: 3px 10px;
                border-radius: 3px;
                font-size: 0.85rem;
                font-weight: 600;
            }
            
            .ezit-plugin-badge.active {
                background: #a3e635;
                color: #000000;
            }
            
            .ezit-plugin-badge.inactive {
                background: #ef4444;
                color: #ffffff;
            }
            
            .ezit-quick-links {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            
            .ezit-quick-links li {
                margin: 10px 0;
            }
            
            .ezit-quick-links a {
                display: flex;
                align-items: center;
                gap: 8px;
                text-decoration: none;
                color: #a3e635;
                padding: 8px;
                border-radius: 4px;
                transition: all 0.2s;
            }
            
            .ezit-quick-links a:hover {
                background: rgba(163, 230, 53, 0.1);
                color: #84cc16;
            }
            
            .ezit-light .ezit-quick-links a {
                color: #16a34a;
            }
            
            .ezit-light .ezit-quick-links a:hover {
                background: rgba(22, 163, 74, 0.1);
                color: #15803d;
            }
            
            .ezit-social-links {
                display: flex;
                gap: 10px;
                margin-top: 15px;
            }
            
            .ezit-social-link {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                background: #a3e635;
                color: #0b0f12;
                border-radius: 50%;
                text-decoration: none;
                transition: transform 0.2s, background 0.2s;
            }
            
            .ezit-social-link:hover {
                transform: scale(1.1);
                background: #84cc16;
            }
            
            .ezit-light .ezit-social-link {
                background: #16a34a;
                color: white;
            }
            
            .ezit-light .ezit-social-link:hover {
                background: #15803d;
            }
            
            .ezit-system-info {
                width: 100%;
                border-collapse: collapse;
            }
            
            .ezit-system-info td {
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }
            
            .ezit-dark .ezit-system-info td {
                border-bottom-color: #2d3748;
            }
            
            .ezit-refresh-btn {
                margin-top: 15px;
                width: 100%;
                background: #a3e635;
                color: #0b0f12;
                border: none;
                padding: 10px 16px;
                border-radius: 6px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }
            
            .ezit-refresh-btn:hover {
                background: #84cc16;
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(163, 230, 53, 0.3);
            }
            
            .ezit-refresh-btn:active {
                transform: translateY(0);
            }
            
            .ezit-refresh-btn .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
            }
            
            .ezit-light .ezit-refresh-btn {
                background: #16a34a;
                color: white;
            }
            
            .ezit-light .ezit-refresh-btn:hover {
                background: #15803d;
                box-shadow: 0 4px 8px rgba(22, 163, 74, 0.3);
            }
            
            @media screen and (max-width: 1200px) {
                .ezit-company-content {
                    flex-direction: column;
                }
                
                .ezit-sidebar {
                    width: 100%;
                }
            }
        </style>
        
        <script>
        // Handle plugin activation/deactivation with AJAX
        function ezitPluginAction(element, message) {
            var $element = jQuery(element);
            var pluginFile = $element.data('plugin-file');
            var action = $element.data('action');
            
            // Show confirmation modal
            ezitConfirm(message, function() {
                // Show loading state
                $element.css('opacity', '0.5').css('pointer-events', 'none');
                
                // Use AJAX to activate/deactivate
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ezit_plugin_action',
                        plugin_action: action,
                        plugin_file: pluginFile,
                        nonce: '<?php echo wp_create_nonce('ezit_plugin_action'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Reload the page to show updated status
                            window.location.reload();
                        } else {
                            alert('Error: ' + (response.data || 'Unknown error'));
                            $element.css('opacity', '1').css('pointer-events', 'auto');
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                        $element.css('opacity', '1').css('pointer-events', 'auto');
                    }
                });
            });
            
            return false;
        }
        
        // Custom confirmation modal
        function ezitConfirm(message, callback) {
            // Create modal overlay
            var overlay = jQuery('<div class="ezit-modal-overlay"></div>');
            var modal = jQuery('<div class="ezit-modal"></div>');
            var content = jQuery('<div class="ezit-modal-content"></div>');
            var icon = jQuery('<div class="ezit-modal-icon"><span class="dashicons dashicons-warning"></span></div>');
            var text = jQuery('<p class="ezit-modal-text"></p>').text(message);
            var buttons = jQuery('<div class="ezit-modal-buttons"></div>');
            var confirmBtn = jQuery('<button class="ezit-modal-btn ezit-modal-confirm">Confirm</button>');
            var cancelBtn = jQuery('<button class="ezit-modal-btn ezit-modal-cancel">Cancel</button>');
            
            buttons.append(confirmBtn).append(cancelBtn);
            content.append(icon).append(text).append(buttons);
            modal.append(content);
            overlay.append(modal);
            jQuery('body').append(overlay);
            
            // Animate in
            setTimeout(function() {
                overlay.addClass('ezit-modal-active');
            }, 10);
            
            // Handle confirm
            confirmBtn.on('click', function() {
                overlay.removeClass('ezit-modal-active');
                setTimeout(function() {
                    overlay.remove();
                    if (typeof callback === 'function') {
                        callback();
                    } else {
                        window.location.href = callback;
                    }
                }, 200);
            });
            
            // Handle cancel
            cancelBtn.on('click', function() {
                overlay.removeClass('ezit-modal-active');
                setTimeout(function() {
                    overlay.remove();
                }, 200);
            });
            
            // Handle overlay click
            overlay.on('click', function(e) {
                if (e.target === overlay[0]) {
                    cancelBtn.click();
                }
            });
            
            return false;
        }
        </script>
        
        <style>
        .ezit-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .ezit-modal-overlay.ezit-modal-active {
            opacity: 1;
        }
        
        .ezit-modal {
            background: #1a1f26;
            border-radius: 8px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            transform: scale(0.9);
            transition: transform 0.2s ease;
        }
        
        .ezit-modal-overlay.ezit-modal-active .ezit-modal {
            transform: scale(1);
        }
        
        .ezit-modal-content {
            text-align: center;
        }
        
        .ezit-modal-icon {
            margin-bottom: 20px;
        }
        
        .ezit-modal-icon .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: #f59e0b;
        }
        
        .ezit-modal-text {
            color: #e5e7eb;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .ezit-modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .ezit-modal-btn {
            padding: 10px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .ezit-modal-confirm {
            background: #a3e635;
            color: #0b0f12;
        }
        
        .ezit-modal-confirm:hover {
            background: #bef264;
        }
        
        .ezit-modal-cancel {
            background: #374151;
            color: #e5e7eb;
        }
        
        .ezit-modal-cancel:hover {
            background: #4b5563;
        }
        </style>
        
        <?php
        // Enqueue custom action scripts
        if (class_exists('EZIT_Plugin_Actions_Registry')) {
            EZIT_Plugin_Actions_Registry::enqueue_scripts();
        }
        ?>
        
        <?php
        echo '</div>'; // Close ezit-fullpage wrapper
    }
}


/**
 * Global function to render company info page
 * Must be defined outside class so WordPress can call it
 */
if (!function_exists('ezit_render_company_info_page')) {
    function ezit_render_company_info_page() {
        EZIT_Company_Info::render_page();
    }
}

/**
 * AJAX handler to refresh company info
 */
add_action('wp_ajax_ezit_refresh_company_info', function() {
    check_ajax_referer('ezit_refresh_info', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    EZIT_Company_Info::clear_cache();
    wp_send_json_success(['message' => 'Company information refreshed']);
});
} // End class_exists check
