/**
 * Ez Maintenance Pro - Admin Bar JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle admin bar toggle
        $('#ezmp-admin-bar-toggle').on('change', function(e) {
            e.stopPropagation();
            
            const enabled = $(this).is(':checked');
            const $toggle = $(this);
            
            $.post(ezmpAdminBar.ajaxurl, {
                action: 'ezmp_toggle_mode',
                nonce: ezmpAdminBar.nonce
            }, function(response) {
                if (response.success) {
                    // Update badge
                    if (enabled) {
                        if ($('#wp-admin-bar-ez-maintenance-pro .ezmp-admin-bar-badge').length === 0) {
                            $('#wp-admin-bar-ez-maintenance-pro .ab-item').append(' <span class="ezmp-admin-bar-badge">ON</span>');
                        }
                    } else {
                        $('#wp-admin-bar-ez-maintenance-pro .ezmp-admin-bar-badge').remove();
                    }
                    
                    // Show notification
                    if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                        wp.data.dispatch('core/notices').createNotice(
                            'success',
                            response.data.message,
                            {
                                isDismissible: true,
                                type: 'snackbar'
                            }
                        );
                    }
                } else {
                    // Revert toggle on error
                    $toggle.prop('checked', !enabled);
                    
                    if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                        wp.data.dispatch('core/notices').createNotice(
                            'error',
                            response.data.message || 'Error toggling maintenance mode',
                            {
                                isDismissible: true,
                                type: 'snackbar'
                            }
                        );
                    } else {
                        alert(response.data.message || 'Error toggling maintenance mode');
                    }
                }
            }).fail(function() {
                // Revert toggle on connection error
                $toggle.prop('checked', !enabled);
                alert('Connection error. Please try again.');
            });
        });
        
        // Prevent toggle item from closing menu
        $('#wp-admin-bar-ezmp-toggle').on('click', function(e) {
            e.stopPropagation();
        });
    });
    
})(jQuery);
