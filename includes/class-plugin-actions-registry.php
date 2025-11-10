<?php
/**
 * Plugin Actions Registry
 * 
 * Allows plugins to dynamically register custom actions/buttons
 * for the Company Info page with full customization options
 * 
 * @package Ez_IT_Solutions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('EZIT_Plugin_Actions_Registry')) {
class EZIT_Plugin_Actions_Registry {
    
    /**
     * Registered plugin actions
     * @var array
     */
    private static $actions = [];
    
    /**
     * Register a plugin action
     * 
     * @param string $plugin_slug Plugin slug
     * @param array $action Action configuration
     * @return bool Success
     */
    public static function register_action($plugin_slug, $action) {
        // Validate required fields
        if (empty($action['id']) || empty($action['label'])) {
            return false;
        }
        
        // Set defaults
        $defaults = [
            'type' => 'button',           // button, link, separator, custom
            'callback' => null,           // PHP callback for action
            'url' => '#',                 // URL for link type
            'onclick' => null,            // JavaScript onclick handler
            'icon' => '',                 // Dashicon class
            'color' => '#a3e635',         // Border/text color
            'bg_color' => 'transparent',  // Background color
            'hover_color' => '#ffffff',   // Hover text color
            'hover_bg' => '#a3e635',      // Hover background
            'font_weight' => '600',       // Font weight
            'font_size' => '13px',        // Font size
            'padding' => '6px 12px',      // Padding
            'border_width' => '1px',      // Border width
            'border_radius' => '4px',     // Border radius
            'custom_css' => '',           // Custom CSS classes
            'custom_style' => '',         // Inline styles
            'position' => 10,             // Display order
            'show_if' => null,            // Callback to determine if shown
            'ajax_action' => null,        // AJAX action name
            'ajax_nonce' => null,         // AJAX nonce action
            'confirm_message' => null,    // Confirmation dialog
            'success_message' => null,    // Success message
            'error_message' => null,      // Error message
        ];
        
        $action = wp_parse_args($action, $defaults);
        
        // Store action
        if (!isset(self::$actions[$plugin_slug])) {
            self::$actions[$plugin_slug] = [];
        }
        
        self::$actions[$plugin_slug][$action['id']] = $action;
        
        return true;
    }
    
    /**
     * Get all registered actions for a plugin
     * 
     * @param string $plugin_slug Plugin slug
     * @return array Actions
     */
    public static function get_actions($plugin_slug) {
        if (!isset(self::$actions[$plugin_slug])) {
            return [];
        }
        
        $actions = self::$actions[$plugin_slug];
        
        // Filter by show_if callback
        $actions = array_filter($actions, function($action) {
            if (is_callable($action['show_if'])) {
                return call_user_func($action['show_if']);
            }
            return true;
        });
        
        // Sort by position
        uasort($actions, function($a, $b) {
            return $a['position'] - $b['position'];
        });
        
        return $actions;
    }
    
    /**
     * Get all registered actions for all plugins
     * 
     * @return array All actions grouped by plugin
     */
    public static function get_all_actions() {
        return self::$actions;
    }
    
    /**
     * Render action button/element
     * 
     * @param array $action Action configuration
     * @param string $plugin_slug Plugin slug
     */
    public static function render_action($action, $plugin_slug) {
        // Handle different types
        switch ($action['type']) {
            case 'separator':
                echo '<div class="ezit-action-separator"></div>';
                break;
                
            case 'custom':
                if (is_callable($action['callback'])) {
                    call_user_func($action['callback'], $plugin_slug);
                }
                break;
                
            case 'link':
            case 'button':
            default:
                self::render_button($action, $plugin_slug);
                break;
        }
    }
    
    /**
     * Render button element
     * 
     * @param array $action Action configuration
     * @param string $plugin_slug Plugin slug
     */
    private static function render_button($action, $plugin_slug) {
        $classes = ['ezit-plugin-link', 'ezit-custom-action'];
        if (!empty($action['custom_css'])) {
            $classes[] = $action['custom_css'];
        }
        
        // Build inline styles
        $styles = [
            'border-color: ' . $action['color'] . ' !important',
            'color: ' . $action['color'] . ' !important',
            'background: ' . $action['bg_color'] . ' !important',
            'font-weight: ' . $action['font_weight'] . ' !important',
            'font-size: ' . $action['font_size'] . ' !important',
            'padding: ' . $action['padding'] . ' !important',
            'border-width: ' . $action['border_width'] . ' !important',
            'border-radius: ' . $action['border_radius'] . ' !important',
            'transition: background 0.2s ease, color 0.2s ease !important',
            'transform: none !important',
        ];
        
        if (!empty($action['custom_style'])) {
            $styles[] = $action['custom_style'];
        }
        
        $style_attr = implode('; ', $styles);
        
        // Build onclick handler
        $onclick = '';
        if (!empty($action['onclick'])) {
            $onclick = $action['onclick'];
        } elseif (!empty($action['ajax_action'])) {
            $onclick = sprintf(
                "ezitCustomAction('%s', '%s', '%s', '%s'); return false;",
                esc_js($plugin_slug),
                esc_js($action['id']),
                esc_js($action['ajax_action']),
                esc_js($action['ajax_nonce'] ?? 'ezit_custom_action')
            );
        }
        
        // Add confirmation if needed
        if (!empty($action['confirm_message'])) {
            $onclick = sprintf(
                "if (!confirm('%s')) return false; %s",
                esc_js($action['confirm_message']),
                $onclick
            );
        }
        
        ?>
        <a href="<?php echo esc_url($action['url']); ?>" 
           class="<?php echo esc_attr(implode(' ', $classes)); ?>" 
           style="<?php echo esc_attr($style_attr); ?>"
           <?php if ($onclick): ?>onclick="<?php echo $onclick; ?>"<?php endif; ?>
           data-hover-bg="<?php echo esc_attr($action['hover_bg']); ?>"
           data-hover-color="<?php echo esc_attr($action['hover_color']); ?>"
           data-color="<?php echo esc_attr($action['color']); ?>"
           data-bg="<?php echo esc_attr($action['bg_color']); ?>">
            <?php if (!empty($action['icon'])): ?>
                <span class="dashicons <?php echo esc_attr($action['icon']); ?>"></span>
            <?php endif; ?>
            <?php echo esc_html($action['label']); ?>
        </a>
        <?php
    }
    
    /**
     * Enqueue scripts for custom actions
     */
    public static function enqueue_scripts() {
        ?>
        <script>
        function ezitCustomAction(pluginSlug, actionId, ajaxAction, nonceAction) {
            // Special handling for license activation
            if (ajaxAction === 'ezit_activate_license') {
                var key = prompt('Enter license key:');
                if (key && key.trim()) {
                    jQuery.post(ajaxurl, {
                        action: 'ezit_submit_license',
                        plugin_slug: pluginSlug,
                        license_key: key.trim(),
                        nonce: '<?php echo wp_create_nonce('ezit_license_action'); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('License activated successfully!');
                            location.reload();
                        } else {
                            alert('License activation failed: ' + (response.data || 'Unknown error'));
                        }
                    });
                }
                return;
            }
            
            // Regular AJAX action
            jQuery.post(ajaxurl, {
                action: ajaxAction,
                plugin_slug: pluginSlug,
                action_id: actionId,
                nonce: '<?php echo wp_create_nonce('ezit_custom_action'); ?>'
            }, function(response) {
                if (response.success) {
                    alert(response.data || 'Action completed successfully!');
                    if (response.reload) {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + (response.data || 'Unknown error'));
                }
            });
        }
        
        // Hover effects for custom actions
        jQuery(document).ready(function($) {
            $('.ezit-custom-action').on('mouseenter', function() {
                var $this = $(this);
                var hoverBg = $this.attr('data-hover-bg');
                var hoverColor = $this.attr('data-hover-color');
                
                console.log('Hover BG:', hoverBg, 'Hover Color:', hoverColor);
                
                $this.css({
                    'background-color': hoverBg + ' !important',
                    'color': hoverColor + ' !important',
                    'transform': 'none !important'
                });
            }).on('mouseleave', function() {
                var $this = $(this);
                var bg = $this.attr('data-bg');
                var color = $this.attr('data-color');
                
                console.log('Leave BG:', bg, 'Leave Color:', color);
                
                $this.css({
                    'background-color': bg,
                    'background': bg,
                    'color': color,
                    'transform': 'none'
                });
            });
        });
        </script>
        <style>
        .ezit-action-separator {
            width: 100%;
            height: 1px;
            background: rgba(163, 230, 53, 0.2);
            margin: 10px 0;
        }
        
        .ezit-custom-action {
            transition: background 0.2s ease, color 0.2s ease !important;
            transform: none !important;
            border-style: solid !important;
        }
        
        .ezit-custom-action:hover {
            transform: none !important;
            text-decoration: none !important;
        }
        </style>
        <?php
    }
    
    /**
     * Remove a registered action
     * 
     * @param string $plugin_slug Plugin slug
     * @param string $action_id Action ID
     * @return bool Success
     */
    public static function remove_action($plugin_slug, $action_id) {
        if (isset(self::$actions[$plugin_slug][$action_id])) {
            unset(self::$actions[$plugin_slug][$action_id]);
            return true;
        }
        return false;
    }
    
    /**
     * Clear all actions for a plugin
     * 
     * @param string $plugin_slug Plugin slug
     */
    public static function clear_actions($plugin_slug) {
        if (isset(self::$actions[$plugin_slug])) {
            unset(self::$actions[$plugin_slug]);
        }
    }
}
}
