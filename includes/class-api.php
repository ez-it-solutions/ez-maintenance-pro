<?php
/**
 * REST API
 * 
 * Provides REST API endpoints for external control (Ez IT Client Manager integration)
 */

if (!defined('ABSPATH')) {
    exit;
}

class EZMP_API {
    
    private static $instance = null;
    private $namespace = 'ezmp/v1';
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Get status
        register_rest_route($this->namespace, '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_status'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        // Activate maintenance mode
        register_rest_route($this->namespace, '/activate', [
            'methods' => 'POST',
            'callback' => [$this, 'activate_maintenance'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'mode' => [
                    'required' => false,
                    'type' => 'string',
                    'enum' => ['maintenance', 'construction', 'payment_overdue']
                ],
                'template' => [
                    'required' => false,
                    'type' => 'string'
                ],
                'message' => [
                    'required' => false,
                    'type' => 'string'
                ]
            ]
        ]);
        
        // Deactivate maintenance mode
        register_rest_route($this->namespace, '/deactivate', [
            'methods' => 'POST',
            'callback' => [$this, 'deactivate_maintenance'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        // Update template
        register_rest_route($this->namespace, '/template', [
            'methods' => 'POST',
            'callback' => [$this, 'update_template'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'template' => [
                    'required' => true,
                    'type' => 'string'
                ]
            ]
        ]);
        
        // Update settings
        register_rest_route($this->namespace, '/settings', [
            'methods' => 'POST',
            'callback' => [$this, 'update_settings'],
            'permission_callback' => [$this, 'check_permission']
        ]);
    }
    
    /**
     * Check API permission
     */
    public function check_permission($request) {
        // Check if user has manage_options capability
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Check for API key in header (for external integrations)
        $api_key = $request->get_header('X-EZMP-API-Key');
        $stored_key = get_option('ezmp_api_key');
        
        if ($api_key && $stored_key && hash_equals($stored_key, $api_key)) {
            return true;
        }
        
        return new WP_Error('rest_forbidden', 'Unauthorized', ['status' => 401]);
    }
    
    /**
     * Get maintenance mode status
     */
    public function get_status($request) {
        return rest_ensure_response([
            'enabled' => EZMP_Core::is_active(),
            'mode' => EZMP_Core::get_mode(),
            'template' => get_option('ezmp_template', 'modern'),
            'title' => get_option('ezmp_title', ''),
            'message' => get_option('ezmp_message', '')
        ]);
    }
    
    /**
     * Activate maintenance mode
     */
    public function activate_maintenance($request) {
        $params = $request->get_params();
        
        // Update mode if provided
        if (isset($params['mode'])) {
            update_option('ezmp_mode', sanitize_text_field($params['mode']));
        }
        
        // Update template if provided
        if (isset($params['template'])) {
            update_option('ezmp_template', sanitize_text_field($params['template']));
        }
        
        // Update message if provided
        if (isset($params['message'])) {
            update_option('ezmp_message', sanitize_textarea_field($params['message']));
        }
        
        // Activate
        update_option('ezmp_enabled', true);
        
        do_action('ezmp_api_activated', $params);
        
        return rest_ensure_response([
            'success' => true,
            'message' => 'Maintenance mode activated',
            'status' => $this->get_status($request)->data
        ]);
    }
    
    /**
     * Deactivate maintenance mode
     */
    public function deactivate_maintenance($request) {
        update_option('ezmp_enabled', false);
        
        do_action('ezmp_api_deactivated');
        
        return rest_ensure_response([
            'success' => true,
            'message' => 'Maintenance mode deactivated'
        ]);
    }
    
    /**
     * Update template
     */
    public function update_template($request) {
        $template = sanitize_text_field($request->get_param('template'));
        update_option('ezmp_template', $template);
        
        return rest_ensure_response([
            'success' => true,
            'message' => 'Template updated',
            'template' => $template
        ]);
    }
    
    /**
     * Update settings
     */
    public function update_settings($request) {
        $settings = $request->get_params();
        
        foreach ($settings as $key => $value) {
            if (strpos($key, 'ezmp_') === 0) {
                update_option($key, $value);
            }
        }
        
        return rest_ensure_response([
            'success' => true,
            'message' => 'Settings updated'
        ]);
    }
}
