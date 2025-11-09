<?php
/**
 * Admin Interface
 * 
 * Handles the WordPress admin dashboard interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class EZMP_Admin {
    
    private static $instance = null;
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('in_admin_header', [$this, 'hide_admin_notices'], 1000);
        add_filter('plugin_action_links_' . EZMP_PLUGIN_BASENAME, [$this, 'add_action_links']);
        add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 100);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Use the same parent slug as Ez IT Client Manager
        $parent_slug = 'ez-it-solutions';
        
        // Check if parent menu already exists (from Ez IT Client Manager or other Ez IT plugins)
        global $menu;
        $parent_exists = false;
        
        foreach ($menu as $item) {
            if (isset($item[2]) && $item[2] === $parent_slug) {
                $parent_exists = true;
                break;
            }
        }
        
        // Create parent menu if it doesn't exist
        if (!$parent_exists) {
            add_menu_page(
                'Ez IT Solutions',
                'Ez IT Solutions',
                'manage_options',
                $parent_slug,
                ['EZIT_Company_Info', 'render_page'],
                'dashicons-admin-site-alt3',
                3
            );
            
            // Add Company Info as first submenu (replaces duplicate parent)
            add_submenu_page(
                $parent_slug,
                'Company Info',
                'Company Info',
                'manage_options',
                $parent_slug,
                ['EZIT_Company_Info', 'render_page']
            );
        }
        
        // Add as submenu under Ez IT Solutions
        add_submenu_page(
            $parent_slug,
            'Ez Maintenance Pro',
            'Maintenance Pro',
            'manage_options',
            'ez-maintenance-pro',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Add admin bar menu
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $is_active = EZMP_Core::is_active();
        
        // Add parent menu
        $wp_admin_bar->add_node([
            'id' => 'ez-maintenance-pro',
            'title' => '<span class="ab-icon dashicons dashicons-admin-settings"></span><span class="ab-label">Ez Maintenance</span>' . 
                       ($is_active ? ' <span class="ezmp-admin-bar-badge">ON</span>' : ''),
            'href' => admin_url('admin.php?page=ez-maintenance-pro'),
            'meta' => [
                'class' => 'ezmp-admin-bar-menu',
            ]
        ]);
        
        // Add toggle submenu
        $wp_admin_bar->add_node([
            'id' => 'ezmp-toggle',
            'parent' => 'ez-maintenance-pro',
            'title' => '<span class="ezmp-admin-bar-toggle">' .
                       '<span class="ezmp-toggle-label">Maintenance Mode</span>' .
                       '<label class="ezmp-admin-bar-switch">' .
                       '<input type="checkbox" id="ezmp-admin-bar-toggle" ' . checked($is_active, true, false) . '>' .
                       '<span class="ezmp-admin-bar-slider"></span>' .
                       '</label>' .
                       '</span>',
            'href' => '#',
            'meta' => [
                'onclick' => 'return false;',
                'class' => 'ezmp-admin-bar-toggle-item'
            ]
        ]);
        
        // Add preview submenu
        $wp_admin_bar->add_node([
            'id' => 'ezmp-preview',
            'parent' => 'ez-maintenance-pro',
            'title' => 'Preview',
            'href' => home_url('?ezmp_preview=1&nonce=' . wp_create_nonce('ezmp_preview')),
            'meta' => [
                'target' => '_blank'
            ]
        ]);
        
        // Add settings submenu
        $wp_admin_bar->add_node([
            'id' => 'ezmp-settings',
            'parent' => 'ez-maintenance-pro',
            'title' => 'Settings',
            'href' => admin_url('admin.php?page=ez-maintenance-pro&tab=settings'),
        ]);
    }
    
    /**
     * Add action links to plugins page
     */
    public function add_action_links($links) {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=ez-maintenance-pro') . '">Dashboard</a>',
            '<a href="' . admin_url('admin.php?page=ez-maintenance-pro&tab=settings') . '">Settings</a>',
        ];
        
        // Add activation link if not activated
        $license_key = get_option('ezmp_license_key', '');
        if (empty($license_key)) {
            $plugin_links[] = '<a href="' . admin_url('admin.php?page=ez-maintenance-pro&tab=settings#license') . '" style="color: #a3e635; font-weight: 600;">Activate License</a>';
        } else {
            $plugin_links[] = '<span style="color: #a3e635;">✓ Licensed</span>';
        }
        
        return array_merge($plugin_links, $links);
    }
    
    /**
     * Hide admin notices on plugin pages
     */
    public function hide_admin_notices() {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'ez-maintenance-pro') !== false) {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Always enqueue admin bar styles
        wp_enqueue_style(
            'ezmp-admin-bar',
            EZMP_PLUGIN_URL . 'assets/css/admin-bar.css',
            [],
            EZMP_VERSION
        );
        
        wp_enqueue_script(
            'ezmp-admin-bar',
            EZMP_PLUGIN_URL . 'assets/js/admin-bar.js',
            ['jquery'],
            EZMP_VERSION,
            true
        );
        
        wp_localize_script('ezmp-admin-bar', 'ezmpAdminBar', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ezmp_admin'),
        ]);
        
        // Only enqueue full admin assets on plugin pages
        if (strpos($hook, 'ez-maintenance-pro') === false) {
            return;
        }
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_media();
        
        wp_enqueue_style(
            'ezmp-admin',
            EZMP_PLUGIN_URL . 'assets/css/admin.css',
            [],
            EZMP_VERSION
        );
        
        wp_enqueue_script(
            'ezmp-admin',
            EZMP_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-color-picker'],
            EZMP_VERSION,
            true
        );
        
        wp_localize_script('ezmp-admin', 'ezmpAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ezmp_admin'),
            'siteUrl' => home_url(),
            'pluginUrl' => EZMP_PLUGIN_URL
        ]);
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $current_tab = $_GET['tab'] ?? 'dashboard';
        $theme_mode = get_option('ezmp_theme_mode', 'dark');
        ?>
        <div class="ezmp-fullpage ezmp-<?php echo esc_attr($theme_mode); ?>" id="ezmp-main-wrap" data-theme="<?php echo esc_attr($theme_mode); ?>">
            <div class="ezmp-header">
                <div class="ezmp-header-content">
                    <h1 class="ezmp-title">
                        <span class="dashicons dashicons-admin-tools"></span>
                        Ez Maintenance Pro
                    </h1>
                    <div class="ezmp-header-actions">
                        <button type="button" class="ezmp-theme-toggle" id="ezmp-theme-toggle">
                            <span class="dashicons dashicons-admin-appearance"></span>
                            <span class="theme-label"><?php echo $theme_mode === 'dark' ? 'Light' : 'Dark'; ?> Mode</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="ezmp-tabs">
                <a href="?page=ez-maintenance-pro&tab=dashboard" 
                   class="ezmp-tab <?php echo $current_tab === 'dashboard' ? 'ezmp-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-dashboard"></span>
                    Dashboard
                </a>
                <a href="?page=ez-maintenance-pro&tab=templates" 
                   class="ezmp-tab <?php echo $current_tab === 'templates' ? 'ezmp-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-layout"></span>
                    Templates
                </a>
                <a href="?page=ez-maintenance-pro&tab=design" 
                   class="ezmp-tab <?php echo $current_tab === 'design' ? 'ezmp-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-customizer"></span>
                    Design
                </a>
                <a href="?page=ez-maintenance-pro&tab=content" 
                   class="ezmp-tab <?php echo $current_tab === 'content' ? 'ezmp-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-edit"></span>
                    Content
                </a>
                <a href="?page=ez-maintenance-pro&tab=access" 
                   class="ezmp-tab <?php echo $current_tab === 'access' ? 'ezmp-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-lock"></span>
                    Access
                </a>
                <a href="?page=ez-maintenance-pro&tab=settings" 
                   class="ezmp-tab <?php echo $current_tab === 'settings' ? 'ezmp-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-settings"></span>
                    Settings
                </a>
            </div>
            
            <!-- Loading Modal -->
            <div id="ezmp-loading-modal" class="ezmp-loading-modal">
                <div class="ezmp-loading-content">
                    <div class="ezmp-loading-spinner"></div>
                    <p>Loading...</p>
                </div>
            </div>
            
            <div class="ezmp-content-wrapper">
                <div class="ezmp-main-content">
                    <?php
                    switch ($current_tab) {
                        case 'templates':
                            $this->render_templates_tab();
                            break;
                        case 'design':
                            $this->render_design_tab();
                            break;
                        case 'content':
                            $this->render_content_tab();
                            break;
                        case 'access':
                            $this->render_access_tab();
                            break;
                        case 'settings':
                            $this->render_settings_tab();
                            break;
                        case 'dashboard':
                        default:
                            $this->render_dashboard_tab();
                            break;
                    }
                    ?>
                </div>
                
                <aside class="ezmp-sidebar">
                    <?php $this->render_sidebar($current_tab); ?>
                </aside>
            </div>
            
            <footer class="ezmp-footer">
                <p>
                    <span class="dashicons dashicons-heart"></span>
                    Built by <a href="https://www.Ez-IT-Solutions.com" target="_blank">Ez IT Solutions</a> | Chris Hultberg
                </p>
            </footer>
        </div>
        <?php
    }
    
    /**
     * Render Dashboard Tab
     */
    private function render_dashboard_tab() {
        $is_active = EZMP_Core::is_active();
        $mode = EZMP_Core::get_mode();
        $template = get_option('ezmp_template', 'modern');
        ?>
        <h2 class="ezmp-section-title">Dashboard</h2>
        <p class="ezmp-intro">Manage your maintenance mode settings and monitor your site status.</p>
        
        <div class="ezmp-dashboard-stats">
            <div class="ezmp-stat-card">
                <div class="ezmp-stat-icon">
                    <span class="dashicons dashicons-admin-tools"></span>
                </div>
                <h3>Status</h3>
                <div class="ezmp-stat-value" style="color: <?php echo $is_active ? '#fbbf24' : '#a3e635'; ?>;">
                    <?php echo $is_active ? 'ACTIVE' : 'INACTIVE'; ?>
                </div>
                <p class="ezmp-stat-label">Maintenance Mode</p>
            </div>
            
            <div class="ezmp-stat-card">
                <div class="ezmp-stat-icon">
                    <span class="dashicons dashicons-layout"></span>
                </div>
                <h3>Template</h3>
                <div class="ezmp-stat-value" style="font-size: 1.5rem;">
                    <?php echo ucfirst($template); ?>
                </div>
                <p class="ezmp-stat-label">Current Template</p>
            </div>
            
            <div class="ezmp-stat-card">
                <div class="ezmp-stat-icon">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
                <h3>Mode</h3>
                <div class="ezmp-stat-value" style="font-size: 1.5rem;">
                    <?php echo ucfirst(str_replace('_', ' ', $mode)); ?>
                </div>
                <p class="ezmp-stat-label">Display Mode</p>
            </div>
        </div>
        
        <div class="ezmp-dashboard-grid">
            <div class="ezmp-card">
                <div class="ezmp-card-icon ezmp-icon-success">
                    <span class="dashicons dashicons-admin-tools"></span>
                </div>
                <h3>Quick Toggle</h3>
                <p>Enable or disable maintenance mode with one click.</p>
                <div class="ezmp-setting-header" style="margin-top: 20px;">
                    <span class="ezmp-setting-label">
                        <span class="dashicons dashicons-admin-tools"></span>
                        Maintenance Mode
                    </span>
                    <label class="ezmp-toggle">
                        <input type="checkbox" id="ezmp-quick-toggle" <?php checked($is_active); ?>>
                        <span class="ezmp-toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="ezmp-card">
                <div class="ezmp-card-icon ezmp-icon-primary">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
                <h3>Preview Page</h3>
                <p>See how your maintenance page looks to visitors.</p>
                <a href="<?php echo home_url('?ezmp_preview=1&nonce=' . wp_create_nonce('ezmp_preview')); ?>" 
                   target="_blank" 
                   class="button button-primary" 
                   style="margin-top: 20px;">
                    Preview Page →
                </a>
            </div>
            
            <div class="ezmp-card">
                <div class="ezmp-card-icon ezmp-icon-info">
                    <span class="dashicons dashicons-admin-generic"></span>
                </div>
                <h3>Quick Settings</h3>
                <p class="ezmp-status-item">
                    <span class="ezmp-label">Bypass Admins:</span>
                    <span>Yes</span>
                </p>
                <p class="ezmp-status-item">
                    <span class="ezmp-label">Show Logo:</span>
                    <span><?php echo get_option('ezmp_show_logo') ? 'Yes' : 'No'; ?></span>
                </p>
                <a href="?page=ez-maintenance-pro&tab=settings" class="button button-primary" style="margin-top: 20px;">
                    All Settings →
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Templates Tab
     */
    private function render_templates_tab() {
        $templates = EZMP_Templates::init()->get_templates();
        $current_template = get_option('ezmp_template', 'modern');
        ?>
        <h2 class="ezmp-section-title">Choose Template</h2>
        <p class="ezmp-intro">Select a template for your maintenance page.</p>
        
        <div class="ezmp-templates-grid">
            <?php foreach ($templates as $id => $template): ?>
                <div class="ezmp-template-card <?php echo $current_template === $id ? 'ezmp-template-active' : ''; ?>" 
                     data-template="<?php echo esc_attr($id); ?>">
                    <div class="ezmp-template-preview">
                        <img src="<?php echo esc_url($template['thumbnail']); ?>" alt="<?php echo esc_attr($template['name']); ?>">
                        <?php if ($current_template === $id): ?>
                            <div class="ezmp-template-badge">Active</div>
                        <?php endif; ?>
                    </div>
                    <div class="ezmp-template-info">
                        <h3><?php echo esc_html($template['name']); ?></h3>
                        <p><?php echo esc_html($template['description']); ?></p>
                        <div class="ezmp-template-actions">
                            <button class="button button-primary ezmp-select-template" data-template="<?php echo esc_attr($id); ?>">
                                <?php echo $current_template === $id ? 'Selected' : 'Select'; ?>
                            </button>
                            <button class="button ezmp-preview-template" data-template="<?php echo esc_attr($id); ?>">
                                Preview
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render Design Tab
     */
    private function render_design_tab() {
        ?>
        <h2 class="ezmp-section-title">Design Customization</h2>
        <p class="ezmp-intro">Customize colors, logo, and styling.</p>
        
        <form method="post" id="ezmp-design-form" class="ezmp-settings-form">
            <div class="ezmp-card">
                <h3>Colors</h3>
                <div class="ezmp-settings-grid">
                    <div class="ezmp-setting-item">
                        <label>Background Color</label>
                        <input type="text" name="ezmp_bg_color" value="<?php echo esc_attr(get_option('ezmp_bg_color', '#0b0f12')); ?>" class="ezmp-color-picker">
                    </div>
                    <div class="ezmp-setting-item">
                        <label>Text Color</label>
                        <input type="text" name="ezmp_text_color" value="<?php echo esc_attr(get_option('ezmp_text_color', '#ffffff')); ?>" class="ezmp-color-picker">
                    </div>
                    <div class="ezmp-setting-item">
                        <label>Accent Color</label>
                        <input type="text" name="ezmp_accent_color" value="<?php echo esc_attr(get_option('ezmp_accent_color', '#a3e635')); ?>" class="ezmp-color-picker">
                    </div>
                </div>
            </div>
            
            <div class="ezmp-card">
                <h3>Logo</h3>
                <div class="ezmp-setting-item">
                    <label>Logo URL</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="url" name="ezmp_logo_url" id="ezmp-logo-url" value="<?php echo esc_url(get_option('ezmp_logo_url', '')); ?>" class="regular-text">
                        <button type="button" class="button" id="ezmp-upload-logo">Upload Logo</button>
                    </div>
                </div>
                <div class="ezmp-setting-item">
                    <label class="ezmp-setting-header">
                        <span>Show Logo</span>
                        <label class="ezmp-toggle">
                            <input type="checkbox" name="ezmp_show_logo" <?php checked(get_option('ezmp_show_logo', true)); ?>>
                            <span class="ezmp-toggle-slider"></span>
                        </label>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="button button-primary button-large">Save Design Settings</button>
        </form>
        <?php
    }
    
    /**
     * Render Content Tab
     */
    private function render_content_tab() {
        ?>
        <h2 class="ezmp-section-title">Content Settings</h2>
        <p class="ezmp-intro">Customize the text and messaging on your maintenance page.</p>
        
        <form method="post" id="ezmp-content-form" class="ezmp-settings-form">
            <div class="ezmp-card">
                <h3>Main Content</h3>
                <div class="ezmp-setting-item">
                    <label>Page Title</label>
                    <input type="text" name="ezmp_title" value="<?php echo esc_attr(get_option('ezmp_title', 'Under Maintenance')); ?>" class="regular-text">
                </div>
                <div class="ezmp-setting-item">
                    <label>Message</label>
                    <textarea name="ezmp_message" rows="4" class="large-text"><?php echo esc_textarea(get_option('ezmp_message', '')); ?></textarea>
                </div>
            </div>
            
            <div class="ezmp-card">
                <h3>Mode</h3>
                <div class="ezmp-setting-item">
                    <label>Display Mode</label>
                    <select name="ezmp_mode" class="regular-text">
                        <option value="maintenance" <?php selected(get_option('ezmp_mode'), 'maintenance'); ?>>Maintenance</option>
                        <option value="construction" <?php selected(get_option('ezmp_mode'), 'construction'); ?>>Under Construction</option>
                        <option value="payment_overdue" <?php selected(get_option('ezmp_mode'), 'payment_overdue'); ?>>Payment Required</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="button button-primary button-large">Save Content Settings</button>
        </form>
        <?php
    }
    
    /**
     * Render Access Tab
     */
    private function render_access_tab() {
        ?>
        <h2 class="ezmp-section-title">Access Control</h2>
        <p class="ezmp-intro">Control who can bypass the maintenance page.</p>
        
        <form method="post" id="ezmp-access-form" class="ezmp-settings-form">
            <div class="ezmp-card">
                <h3>User Roles</h3>
                <p>Select which user roles can bypass maintenance mode:</p>
                <?php
                $bypass_roles = get_option('ezmp_bypass_roles', ['administrator']);
                $roles = wp_roles()->get_names();
                foreach ($roles as $role_key => $role_name):
                ?>
                    <label style="display: block; margin: 10px 0;">
                        <input type="checkbox" name="ezmp_bypass_roles[]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $bypass_roles)); ?>>
                        <?php echo esc_html($role_name); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <div class="ezmp-card">
                <h3>IP Whitelist</h3>
                <p>Enter IP addresses that can bypass maintenance mode (one per line):</p>
                <textarea name="ezmp_bypass_ips" rows="6" class="large-text"><?php echo esc_textarea(implode("\n", get_option('ezmp_bypass_ips', []))); ?></textarea>
            </div>
            
            <button type="submit" class="button button-primary button-large">Save Access Settings</button>
        </form>
        <?php
    }
    
    /**
     * Render Settings Tab
     */
    private function render_settings_tab() {
        $is_welcome = isset($_GET['welcome']) && $_GET['welcome'] === '1';
        ?>
        <h2 class="ezmp-section-title">Advanced Settings</h2>
        <p class="ezmp-intro">Configure advanced options and integrations.</p>
        
        <?php if ($is_welcome): ?>
            <div class="ezmp-card" style="border: 2px solid #a3e635; background: rgba(163, 230, 53, 0.1);">
                <h3 style="color: #a3e635; display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 24px;"></span>
                    Welcome to Ez Maintenance Pro!
                </h3>
                <p style="font-size: 1.1rem; margin-bottom: 20px;">Thank you for installing Ez Maintenance Pro. Here's how to get started:</p>
                <ol style="line-height: 2; margin-left: 20px;">
                    <li><strong>Choose a Template:</strong> Go to the Templates tab and select your preferred design</li>
                    <li><strong>Customize Design:</strong> Set your brand colors and upload your logo in the Design tab</li>
                    <li><strong>Edit Content:</strong> Customize the message your visitors will see in the Content tab</li>
                    <li><strong>Configure Access:</strong> Set who can bypass maintenance mode in the Access tab</li>
                    <li><strong>Activate:</strong> Toggle maintenance mode on the Dashboard when ready</li>
                </ol>
                <p style="margin-top: 20px;">
                    <a href="?page=ez-maintenance-pro&tab=templates" class="button button-primary" style="margin-right: 10px;">Choose Template →</a>
                    <a href="?page=ez-maintenance-pro&tab=dashboard" class="button">Go to Dashboard</a>
                </p>
            </div>
        <?php endif; ?>
        
        <div class="ezmp-card" id="license">
            <?php
            $license = EZMP_License::init();
            $license_info = $license->get_license_info();
            $is_active = $license->is_active();
            ?>
            <h3>License Activation</h3>
            
            <?php if ($is_active): ?>
                <div style="background: rgba(163, 230, 53, 0.1); border: 1px solid rgba(163, 230, 53, 0.3); border-radius: 6px; padding: 20px; margin-bottom: 20px;">
                    <p style="margin: 0; display: flex; align-items: center; gap: 10px; color: #a3e635; font-weight: 600;">
                        <span class="dashicons dashicons-yes-alt"></span>
                        License Active - <?php echo esc_html(ucfirst($license_info['plan'])); ?> Plan
                    </p>
                </div>
                
                <table class="widefat" style="margin-bottom: 20px;">
                    <tr>
                        <td style="width: 150px;"><strong>License Key:</strong></td>
                        <td><?php echo esc_html(substr($license_info['key'], 0, 8) . '...' . substr($license_info['key'], -4)); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td><?php echo esc_html($license_info['email']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Plan:</strong></td>
                        <td><?php echo esc_html(ucfirst($license_info['plan'])); ?></td>
                    </tr>
                    <?php if (!empty($license_info['expires'])): ?>
                    <tr>
                        <td><strong>Expires:</strong></td>
                        <td><?php echo esc_html(date('F j, Y', strtotime($license_info['expires']))); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><strong>Last Verified:</strong></td>
                        <td><?php echo esc_html(human_time_diff($license_info['verified'])) . ' ago'; ?></td>
                    </tr>
                </table>
                
                <button type="button" class="button" id="ezmp-check-license">Check License Status</button>
                <button type="button" class="button button-secondary" id="ezmp-deactivate-license">Deactivate License</button>
                
            <?php else: ?>
                <p>Activate your license to unlock premium features, templates, and priority support.</p>
                
                <div class="ezmp-setting-item">
                    <label>License Key</label>
                    <input type="text" id="ezmp-license-key" class="regular-text" placeholder="Enter your license key">
                </div>
                
                <div class="ezmp-setting-item">
                    <label>Email Address</label>
                    <input type="email" id="ezmp-license-email" class="regular-text" placeholder="your@email.com" value="<?php echo esc_attr(get_option('admin_email')); ?>">
                </div>
                
                <button type="button" class="button button-primary" id="ezmp-activate-license">Activate License</button>
                
                <p style="margin-top: 20px;">
                    <a href="https://www.ez-it-solutions.com/ez-maintenance-pro/pricing" target="_blank">Don't have a license? Get one here →</a>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="ezmp-card">
            <h3>API Access</h3>
            <p>Enable REST API for external control (e.g., Ez IT Client Manager).</p>
            <div class="ezmp-setting-item">
                <label>API Key</label>
                <input type="text" value="<?php echo esc_attr(get_option('ezmp_api_key', '')); ?>" class="regular-text" readonly>
                <button type="button" class="button" id="ezmp-generate-api-key">Generate New Key</button>
            </div>
            <p style="margin-top: 15px; opacity: 0.8; font-size: 0.95rem;">
                <span class="dashicons dashicons-info" style="color: #a3e635;"></span>
                Use this API key to control maintenance mode remotely via REST API or Ez IT Client Manager.
            </p>
        </div>
        
        <div class="ezmp-card">
            <h3>Reset Settings</h3>
            <p>Reset all settings to default values.</p>
            <button type="button" class="button button-secondary" id="ezmp-reset-settings">Reset to Defaults</button>
        </div>
        <?php
    }
    
    /**
     * Render Sidebar
     */
    private function render_sidebar($current_tab) {
        ?>
        <div class="ezmp-sidebar-card">
            <h3><span class="dashicons dashicons-info"></span> Quick Info</h3>
            <p><strong>Plugin Version:</strong> <?php echo EZMP_VERSION; ?></p>
            <p><strong>WordPress:</strong> <?php echo get_bloginfo('version'); ?></p>
            <p><strong>PHP:</strong> <?php echo PHP_VERSION; ?></p>
        </div>
        
        <div class="ezmp-sidebar-card">
            <h3><span class="dashicons dashicons-sos"></span> Need Help?</h3>
            <p>Contact Ez IT Solutions for support:</p>
            <p><a href="https://www.Ez-IT-Solutions.com" target="_blank" class="button">Visit Website</a></p>
        </div>
        
        <div class="ezmp-sidebar-card ezmp-sidebar-highlight">
            <h3><span class="dashicons dashicons-lightbulb"></span> Quick Tips</h3>
            <ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
                <li>Test your maintenance page before activating</li>
                <li>Admins can always bypass maintenance mode</li>
                <li>Use IP whitelist for development access</li>
                <li>Customize colors to match your brand</li>
            </ul>
        </div>
        <?php
    }
}
