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

class EZIT_Company_Info {
    
    private static $instance = null;
    private static $api_url = 'https://www.ez-it-solutions.com/api/v1/company-info';
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
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
     * Get installed Ez IT plugins
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
                
                $ezit_plugins[] = [
                    'name' => $plugin_data['Name'],
                    'version' => $plugin_data['Version'],
                    'description' => $plugin_data['Description'],
                    'author' => $plugin_data['Author'],
                    'active' => is_plugin_active($plugin_file),
                    'file' => $plugin_file
                ];
            }
        }
        
        return $ezit_plugins;
    }
    
    /**
     * Clear cached company info
     */
    public static function clear_cache() {
        delete_transient('ezit_company_info');
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
                                    <div class="ezit-plugin-item <?php echo $plugin['active'] ? 'active' : 'inactive'; ?>">
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
            }
            
            .ezit-dark .ezit-plugin-item {
                background: #0f1419;
                border-color: #2d3748;
            }
            
            .ezit-plugin-item.active {
                border-left: 4px solid #a3e635;
            }
            
            .ezit-plugin-item.inactive {
                opacity: 0.7;
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
                color: #666;
            }
            
            .ezit-plugin-badge {
                padding: 3px 10px;
                border-radius: 3px;
                font-size: 0.85rem;
                font-weight: 600;
            }
            
            .ezit-plugin-badge.active {
                background: #a3e635;
                color: #0b0f12;
            }
            
            .ezit-plugin-badge.inactive {
                background: #ccc;
                color: #666;
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
        jQuery(document).ready(function($) {
            $('#ezit-refresh-info').on('click', function() {
                const $btn = $(this);
                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Refreshing...');
                
                $.post(ajaxurl, {
                    action: 'ezit_refresh_company_info',
                    nonce: '<?php echo wp_create_nonce('ezit_refresh_info'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to refresh information');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Refresh Info');
                    }
                });
            });
        });
        </script>
        <?php
        echo '</div>'; // Close ezit-fullpage wrapper
    }
}

// Function is defined at the bottom of this file after class definition

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
