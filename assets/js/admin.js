/**
 * Ez Maintenance Pro - Admin JavaScript
 */

(function($) {
    'use strict';

    const EZMP_Admin = {
        
        init: function() {
            this.themeToggle();
            this.quickToggle();
            this.colorPickers();
            this.mediaUploader();
            this.formHandlers();
            this.templateSelection();
        },
        
        /**
         * Show loading spinner
         */
        showLoading: function() {
            $('#ezmp-loading-modal').addClass('active');
        },
        
        /**
         * Hide loading spinner
         */
        hideLoading: function() {
            $('#ezmp-loading-modal').removeClass('active');
        },
        
        /**
         * Theme Toggle (Dark/Light Mode)
         */
        themeToggle: function() {
            $('#ezmp-theme-toggle').on('click', function() {
                const $wrap = $('.ezmp-wrap');
                const currentTheme = $wrap.hasClass('ezmp-dark') ? 'dark' : 'light';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                $wrap.removeClass('ezmp-dark ezmp-light').addClass('ezmp-' + newTheme);
                $(this).find('.theme-label').text(newTheme === 'dark' ? 'Light Mode' : 'Dark Mode');
                
                // Save preference
                $.post(ezmpAdmin.ajaxurl, {
                    action: 'ezmp_save_settings',
                    nonce: ezmpAdmin.nonce,
                    settings: {
                        ezmp_theme_mode: newTheme
                    }
                });
            });
        },
        
        /**
         * Quick Toggle for Maintenance Mode
         */
        quickToggle: function() {
            $('#ezmp-quick-toggle').on('change', function() {
                const enabled = $(this).is(':checked');
                const $toggle = $(this);
                
                $.post(ezmpAdmin.ajaxurl, {
                    action: 'ezmp_toggle_mode',
                    nonce: ezmpAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        EZMP_Admin.showNotice(response.data.message, 'success');
                        // Update stat card
                        $('.ezmp-stat-card .ezmp-stat-value').first().text(enabled ? 'ACTIVE' : 'INACTIVE');
                    } else {
                        $toggle.prop('checked', !enabled);
                        EZMP_Admin.showNotice(response.data.message || 'Error toggling maintenance mode', 'error');
                    }
                });
            });
        },
        
        /**
         * Color Pickers
         */
        colorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.ezmp-color-picker').wpColorPicker({
                    change: function(event, ui) {
                        // Auto-save on color change (optional)
                    }
                });
            }
        },
        
        /**
         * Media Uploader for Logo
         */
        mediaUploader: function() {
            let mediaUploader;
            
            $('#ezmp-upload-logo').on('click', function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: 'Choose Logo',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#ezmp-logo-url').val(attachment.url);
                });
                
                mediaUploader.open();
            });
        },
        
        /**
         * Form Handlers
         */
        formHandlers: function() {
            // Design Form
            $('#ezmp-design-form').on('submit', function(e) {
                e.preventDefault();
                EZMP_Admin.saveForm($(this), 'Design settings saved successfully!');
            });
            
            // Content Form
            $('#ezmp-content-form').on('submit', function(e) {
                e.preventDefault();
                EZMP_Admin.saveForm($(this), 'Content settings saved successfully!');
            });
            
            // Access Form
            $('#ezmp-access-form').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serializeArray();
                const settings = {};
                
                // Handle checkbox arrays
                const bypassRoles = [];
                formData.forEach(function(item) {
                    if (item.name === 'ezmp_bypass_roles[]') {
                        bypassRoles.push(item.value);
                    } else if (item.name === 'ezmp_bypass_ips') {
                        settings[item.name] = item.value.split('\n').filter(ip => ip.trim());
                    } else {
                        settings[item.name] = item.value;
                    }
                });
                settings.ezmp_bypass_roles = bypassRoles;
                
                EZMP_Admin.saveSettings(settings, 'Access settings saved successfully!');
            });
            
            // Generate API Key
            $('#ezmp-generate-api-key').on('click', function() {
                const apiKey = EZMP_Admin.generateApiKey();
                $(this).prev('input').val(apiKey);
                
                $.post(ezmpAdmin.ajaxurl, {
                    action: 'ezmp_save_settings',
                    nonce: ezmpAdmin.nonce,
                    settings: {
                        ezmp_api_key: apiKey
                    }
                }, function(response) {
                    if (response.success) {
                        EZMP_Admin.showNotice('API key generated successfully!', 'success');
                    }
                });
            });
            
            // Reset Settings
            $('#ezmp-reset-settings').on('click', function() {
                if (!confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
                    return;
                }
                
                $.post(ezmpAdmin.ajaxurl, {
                    action: 'ezmp_reset_settings',
                    nonce: ezmpAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        EZMP_Admin.showNotice('Settings reset successfully! Reloading...', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                });
            });
            
            // Activate License
            $('#ezmp-activate-license').on('click', function() {
                const $btn = $(this);
                const licenseKey = $('#ezmp-license-key').val().trim();
                const email = $('#ezmp-license-email').val().trim();
                
                if (!licenseKey) {
                    EZMP_Admin.showNotice('Please enter a license key', 'error');
                    return;
                }
                
                $btn.prop('disabled', true).text('Activating...');
                
                $.post(ezmpAdmin.ajaxurl, {
                    action: 'ezmp_activate_license',
                    nonce: ezmpAdmin.nonce,
                    license_key: licenseKey,
                    email: email
                }, function(response) {
                    if (response.success) {
                        EZMP_Admin.showNotice(response.data.message || 'License activated successfully!', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        EZMP_Admin.showNotice(response.data.message || 'License activation failed', 'error');
                        $btn.prop('disabled', false).text('Activate License');
                    }
                });
            });
            
            // Deactivate License
            $('#ezmp-deactivate-license').on('click', function() {
                if (!confirm('Are you sure you want to deactivate your license?')) {
                    return;
                }
                
                const $btn = $(this);
                $btn.prop('disabled', true).text('Deactivating...');
                
                $.post(ezmpAdmin.ajaxurl, {
                    action: 'ezmp_deactivate_license',
                    nonce: ezmpAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        EZMP_Admin.showNotice('License deactivated successfully', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        EZMP_Admin.showNotice(response.data.message || 'Deactivation failed', 'error');
                        $btn.prop('disabled', false).text('Deactivate License');
                    }
                });
            });
            
            // Check License
            $('#ezmp-check-license').on('click', function() {
                const $btn = $(this);
                $btn.prop('disabled', true).text('Checking...');
                
                $.post(ezmpAdmin.ajaxurl, {
                    action: 'ezmp_check_license',
                    nonce: ezmpAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        if (response.data.valid) {
                            EZMP_Admin.showNotice('License is valid and active!', 'success');
                        } else {
                            EZMP_Admin.showNotice('License verification failed. Please check your license.', 'error');
                        }
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        EZMP_Admin.showNotice('Could not verify license', 'error');
                        $btn.prop('disabled', false).text('Check License Status');
                    }
                });
            });
        },
        
        /**
         * Template Selection
         */
        templateSelection: function() {
            $('.ezmp-select-template').on('click', function() {
                const template = $(this).data('template');
                
                $.post(ezmpAdmin.ajaxurl, {
                    action: 'ezmp_save_settings',
                    nonce: ezmpAdmin.nonce,
                    settings: {
                        ezmp_template: template
                    }
                }, function(response) {
                    if (response.success) {
                        EZMP_Admin.showNotice('Template selected successfully!', 'success');
                        
                        // Update UI
                        $('.ezmp-template-card').removeClass('ezmp-template-active');
                        $('.ezmp-template-card[data-template="' + template + '"]').addClass('ezmp-template-active');
                        $('.ezmp-template-badge').remove();
                        $('.ezmp-template-card[data-template="' + template + '"] .ezmp-template-preview').append('<div class="ezmp-template-badge">Active</div>');
                        $('.ezmp-select-template').text('Select');
                        $('.ezmp-template-card[data-template="' + template + '"] .ezmp-select-template').text('Selected');
                        
                        // Update stat card
                        $('.ezmp-stat-card .ezmp-stat-value').eq(1).text(template.charAt(0).toUpperCase() + template.slice(1));
                    }
                });
            });
            
            $('.ezmp-preview-template').on('click', function() {
                const template = $(this).data('template');
                const previewUrl = ezmpAdmin.siteUrl + '?ezmp_preview=1&template=' + template + '&nonce=' + ezmpAdmin.nonce;
                window.open(previewUrl, '_blank');
            });
        },
        
        /**
         * Save Form Helper
         */
        saveForm: function($form, successMessage) {
            const formData = $form.serializeArray();
            const settings = {};
            
            formData.forEach(function(item) {
                if (item.name.indexOf('[]') === -1) {
                    settings[item.name] = item.value;
                }
            });
            
            // Handle checkboxes
            $form.find('input[type="checkbox"]').each(function() {
                const name = $(this).attr('name');
                if (name && name.indexOf('[]') === -1) {
                    settings[name] = $(this).is(':checked');
                }
            });
            
            this.saveSettings(settings, successMessage);
        },
        
        /**
         * Save Settings via AJAX
         */
        saveSettings: function(settings, successMessage) {
            EZMP_Admin.showLoading();
            
            $.post(ezmpAdmin.ajaxurl, {
                action: 'ezmp_save_settings',
                nonce: ezmpAdmin.nonce,
                settings: settings
            }, function(response) {
                EZMP_Admin.hideLoading();
                
                if (response.success) {
                    EZMP_Admin.showNotice(successMessage || 'Settings saved successfully!', 'success');
                } else {
                    EZMP_Admin.showNotice(response.data.message || 'Error saving settings', 'error');
                }
            }).fail(function() {
                EZMP_Admin.hideLoading();
                EZMP_Admin.showNotice('Connection error. Please try again.', 'error');
            });
        },
        
        /**
         * Show Admin Notice
         */
        showNotice: function(message, type) {
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.ezmp-wrap').prepend($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        /**
         * Generate Random API Key
         */
        generateApiKey: function() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let key = '';
            for (let i = 0; i < 32; i++) {
                key += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return key;
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        EZMP_Admin.init();
    });
    
})(jQuery);
