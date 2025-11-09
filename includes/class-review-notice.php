<?php
/**
 * Ez IT Solutions - Review Notice
 * 
 * Displays a friendly review request notice after plugin activation
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('EZIT_Review_Notice')) {
class EZIT_Review_Notice {
    
    private $plugin_slug;
    private $plugin_name;
    private $days_before_notice = 7; // Show notice after 7 days
    
    public function __construct($plugin_slug, $plugin_name) {
        $this->plugin_slug = $plugin_slug;
        $this->plugin_name = $plugin_name;
        
        add_action('admin_notices', [$this, 'show_review_notice']);
        add_action('wp_ajax_ezit_dismiss_review_notice', [$this, 'dismiss_notice']);
    }
    
    /**
     * Show review notice
     */
    public function show_review_notice() {
        // Don't show on Ez IT pages (they have notices hidden)
        $screen = get_current_screen();
        if ($screen && (
            strpos($screen->id, 'ez-it-solutions') !== false ||
            strpos($screen->id, 'ez-it-client-manager') !== false ||
            strpos($screen->id, 'ez-maintenance-pro') !== false
        )) {
            return;
        }
        
        // Check if notice was dismissed
        $dismissed = get_option('ezit_review_notice_dismissed_' . $this->plugin_slug, false);
        if ($dismissed) {
            return;
        }
        
        // Check if enough time has passed since activation
        $activation_time = get_option('ezit_activation_time_' . $this->plugin_slug, 0);
        if (!$activation_time) {
            // Set activation time if not set
            update_option('ezit_activation_time_' . $this->plugin_slug, time());
            return;
        }
        
        $days_since_activation = (time() - $activation_time) / DAY_IN_SECONDS;
        if ($days_since_activation < $this->days_before_notice) {
            return;
        }
        
        // Show the notice
        ?>
        <div class="notice notice-info is-dismissible ezit-review-notice" data-plugin="<?php echo esc_attr($this->plugin_slug); ?>" style="border-left: 4px solid #a3e635; padding: 15px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="font-size: 48px;">⭐</div>
                <div>
                    <p style="margin: 0 0 8px; font-size: 14px; font-weight: 600;">
                        We've worked tirelessly to develop <strong><?php echo esc_html($this->plugin_name); ?></strong> and it would really appreciate us if you dropped a short review about the plugin.
                    </p>
                    <p style="margin: 0; font-size: 13px; color: #666;">
                        Your review means a lot to us and we are working to make the plugin more awesome. Thanks for using <?php echo esc_html($this->plugin_name); ?>!
                    </p>
                    <p style="margin: 10px 0 0;">
                        <a href="https://wordpress.org/support/plugin/<?php echo esc_attr($this->plugin_slug); ?>/reviews/#new-post" class="button button-primary" target="_blank" style="background: #a3e635; border-color: #84cc16; color: #0b0f12; font-weight: 600;">
                            ⭐ Remind me later
                        </a>
                        <a href="https://wordpress.org/support/plugin/<?php echo esc_attr($this->plugin_slug); ?>/reviews/#new-post" class="button button-primary" target="_blank" style="background: #a3e635; border-color: #84cc16; color: #0b0f12; font-weight: 600;">
                            ⭐ Review Here
                        </a>
                        <button type="button" class="button ezit-dismiss-review" data-plugin="<?php echo esc_attr($this->plugin_slug); ?>" style="margin-left: 10px;">
                            I already did
                        </button>
                    </p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.ezit-dismiss-review, .ezit-review-notice .notice-dismiss').on('click', function(e) {
                var plugin = $(this).data('plugin') || $(this).closest('.ezit-review-notice').data('plugin');
                
                $.post(ajaxurl, {
                    action: 'ezit_dismiss_review_notice',
                    plugin: plugin,
                    nonce: '<?php echo wp_create_nonce('ezit_dismiss_review'); ?>'
                });
                
                $(this).closest('.ezit-review-notice').fadeOut();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Dismiss notice via AJAX
     */
    public function dismiss_notice() {
        check_ajax_referer('ezit_dismiss_review', 'nonce');
        
        $plugin = isset($_POST['plugin']) ? sanitize_text_field($_POST['plugin']) : '';
        if ($plugin) {
            update_option('ezit_review_notice_dismissed_' . $plugin, true);
        }
        
        wp_send_json_success();
    }
}
} // End class_exists check
