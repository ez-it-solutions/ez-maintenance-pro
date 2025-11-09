<?php
/**
 * Template Management
 * 
 * Handles template registration, loading, and customization
 */

if (!defined('ABSPATH')) {
    exit;
}

class EZMP_Templates {
    
    private static $instance = null;
    private $templates = [];
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->register_default_templates();
        add_action('wp_ajax_ezmp_preview_template', [$this, 'ajax_preview_template']);
    }
    
    /**
     * Register default templates
     */
    private function register_default_templates() {
        $this->templates = [
            'modern' => [
                'name' => 'Modern',
                'description' => 'Clean and modern design with gradient backgrounds',
                'thumbnail' => EZMP_PLUGIN_URL . 'assets/images/modern-thumb.png',
                'pro' => false
            ],
            'minimal' => [
                'name' => 'Minimal',
                'description' => 'Simple and elegant minimalist design',
                'thumbnail' => EZMP_PLUGIN_URL . 'assets/images/minimal-thumb.png',
                'pro' => false
            ],
            'corporate' => [
                'name' => 'Corporate',
                'description' => 'Professional corporate style',
                'thumbnail' => EZMP_PLUGIN_URL . 'assets/images/corporate-thumb.png',
                'pro' => false
            ],
            'payment-required' => [
                'name' => 'Payment Required',
                'description' => 'Special template for non-payment situations',
                'thumbnail' => EZMP_PLUGIN_URL . 'assets/images/payment-thumb.png',
                'pro' => false
            ]
        ];
        
        $this->templates = apply_filters('ezmp_register_templates', $this->templates);
    }
    
    /**
     * Get all templates
     */
    public function get_templates() {
        return $this->templates;
    }
    
    /**
     * Get template data
     */
    public function get_template($template_id) {
        return isset($this->templates[$template_id]) ? $this->templates[$template_id] : null;
    }
    
    /**
     * Get template variables for rendering
     */
    public static function get_template_vars() {
        return [
            'mode' => get_option('ezmp_mode', 'maintenance'),
            'title' => get_option('ezmp_title', 'Under Maintenance'),
            'message' => get_option('ezmp_message', 'We\'re currently performing scheduled maintenance.'),
            'theme_mode' => get_option('ezmp_theme_mode', 'dark'),
            'bg_color' => get_option('ezmp_bg_color', '#0b0f12'),
            'text_color' => get_option('ezmp_text_color', '#ffffff'),
            'accent_color' => get_option('ezmp_accent_color', '#a3e635'),
            'logo_url' => get_option('ezmp_logo_url', ''),
            'show_logo' => get_option('ezmp_show_logo', true),
            'show_social' => get_option('ezmp_show_social', false),
            'show_contact' => get_option('ezmp_show_contact', false),
            'contact_email' => get_option('ezmp_contact_email', get_option('admin_email')),
            'contact_phone' => get_option('ezmp_contact_phone', ''),
            'social_facebook' => get_option('ezmp_social_facebook', ''),
            'social_twitter' => get_option('ezmp_social_twitter', ''),
            'social_instagram' => get_option('ezmp_social_instagram', ''),
            'countdown_enabled' => get_option('ezmp_countdown_enabled', false),
            'countdown_date' => get_option('ezmp_countdown_date', ''),
            'custom_css' => get_option('ezmp_custom_css', ''),
            'site_name' => get_bloginfo('name'),
        ];
    }
    
    /**
     * AJAX: Preview template
     */
    public function ajax_preview_template() {
        check_ajax_referer('ezmp_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $template = sanitize_text_field($_POST['template'] ?? 'modern');
        $template_file = EZMP_PLUGIN_DIR . 'templates/' . $template . '.php';
        
        if (file_exists($template_file)) {
            ob_start();
            include $template_file;
            $html = ob_get_clean();
            
            wp_send_json_success(['html' => $html]);
        } else {
            wp_send_json_error(['message' => 'Template not found']);
        }
    }
}
