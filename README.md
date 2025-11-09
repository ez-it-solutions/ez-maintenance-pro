# Ez Maintenance Pro

Professional maintenance mode, under construction, and coming soon page plugin for WordPress with beautiful templates and advanced customization options.

**Version:** 1.0.0  
**Author:** Chris Hultberg | Ez IT Solutions  
**Website:** https://www.Ez-IT-Solutions.com

---

## Features

### 🎨 Beautiful Templates
- **Modern** - Clean design with gradient backgrounds
- **Minimal** - Simple and elegant minimalist style
- **Corporate** - Professional business template
- **Payment Required** - Special template for non-payment situations

### ⚙️ Advanced Customization
- Full color customization (background, text, accent)
- Custom logo upload
- Custom CSS injection
- Dark/Light mode admin interface
- Multiple display modes (Maintenance, Construction, Payment Overdue)

### 🔒 Access Control
- Role-based bypass (administrators, editors, etc.)
- IP whitelist for development access
- Automatic admin bypass

### 🌐 Integration Ready
- REST API for external control
- Perfect integration with Ez IT Client Manager
- Remote activation/deactivation
- Template switching via API

### 📱 Contact & Social
- Contact form display
- Email and phone support links
- Social media integration (Facebook, Twitter, Instagram)
- Countdown timer support

---

## Installation

### Manual Installation

1. Download the plugin files
2. Upload the `ez-maintenance-pro` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **Maintenance** in the WordPress admin menu

### Via WordPress Admin

1. Go to **Plugins > Add New**
2. Search for "Ez Maintenance Pro"
3. Click **Install Now** and then **Activate**

---

## Quick Start Guide

### 1. Activate Maintenance Mode

1. Go to **Maintenance > Dashboard**
2. Toggle the **Maintenance Mode** switch to ON
3. Your site is now in maintenance mode!

### 2. Choose a Template

1. Navigate to **Maintenance > Templates**
2. Browse available templates
3. Click **Select** on your preferred template
4. Click **Preview** to see how it looks

### 3. Customize Design

1. Go to **Maintenance > Design**
2. Customize colors (background, text, accent)
3. Upload your logo
4. Add custom CSS if needed
5. Click **Save Design Settings**

### 4. Edit Content

1. Navigate to **Maintenance > Content**
2. Edit the page title and message
3. Select display mode (Maintenance, Construction, Payment Overdue)
4. Click **Save Content Settings**

### 5. Configure Access

1. Go to **Maintenance > Access**
2. Select which user roles can bypass maintenance mode
3. Add IP addresses to whitelist (one per line)
4. Click **Save Access Settings**

---

## REST API Documentation

Ez Maintenance Pro provides a REST API for external control, perfect for integration with client management systems.

### Authentication

Include your API key in the request header:
```
X-EZMP-API-Key: your_api_key_here
```

Or authenticate as a WordPress administrator.

### Endpoints

#### Get Status
```
GET /wp-json/ezmp/v1/status
```

**Response:**
```json
{
  "enabled": true,
  "mode": "maintenance",
  "template": "modern",
  "title": "Under Maintenance",
  "message": "We'll be back shortly!"
}
```

#### Activate Maintenance Mode
```
POST /wp-json/ezmp/v1/activate
```

**Parameters:**
- `mode` (optional): `maintenance`, `construction`, or `payment_overdue`
- `template` (optional): Template ID
- `message` (optional): Custom message

**Response:**
```json
{
  "success": true,
  "message": "Maintenance mode activated",
  "status": { ... }
}
```

#### Deactivate Maintenance Mode
```
POST /wp-json/ezmp/v1/deactivate
```

**Response:**
```json
{
  "success": true,
  "message": "Maintenance mode deactivated"
}
```

#### Update Template
```
POST /wp-json/ezmp/v1/template
```

**Parameters:**
- `template` (required): Template ID

#### Update Settings
```
POST /wp-json/ezmp/v1/settings
```

**Parameters:**
- Any `ezmp_*` option key-value pairs

---

## Integration with Ez IT Client Manager

Ez Maintenance Pro is designed to work seamlessly with Ez IT Client Manager for remote site management.

### Automatic Activation on Non-Payment

When integrated with Ez IT Client Manager, maintenance mode can be automatically activated when:
- License payment is overdue
- Grace period has expired
- Manual activation via Laravel dashboard

### Remote Control

Control maintenance mode from your central management dashboard:
```php
// Activate maintenance mode
do_action('ezmp_activate_maintenance', $site_id, 'payment_overdue');

// Set custom template
do_action('ezmp_set_template', $site_id, 'payment-required');

// Set custom message
do_action('ezmp_set_message', $site_id, 'Payment is required to restore access.');
```

---

## Hooks & Filters

### Actions

```php
// Fired when maintenance mode is activated
do_action('ezmp_activated');

// Fired when maintenance mode is deactivated
do_action('ezmp_deactivated');

// Fired before displaying maintenance page
do_action('ezmp_before_display');

// Fired after displaying maintenance page
do_action('ezmp_after_display');

// Fired when action is logged
do_action('ezmp_action_logged', $action, $details);

// Fired when plugin is loaded
do_action('ezmp_loaded');
```

### Filters

```php
// Filter template file path
apply_filters('ezmp_template_file', $template_file, $template);

// Filter template data/variables
apply_filters('ezmp_template_data', $data);

// Filter bypass check
apply_filters('ezmp_should_bypass', false);

// Filter bypass roles
apply_filters('ezmp_bypass_roles', ['administrator']);

// Filter bypass IPs
apply_filters('ezmp_bypass_ips', []);
```

---

## Template Development

### Creating Custom Templates

1. Create a new PHP file in the `templates/` directory
2. Use the template variables provided by `EZMP_Templates::get_template_vars()`
3. Register your template using the `ezmp_register_templates` filter

**Example:**

```php
add_filter('ezmp_register_templates', function($templates) {
    $templates['my-custom'] = [
        'name' => 'My Custom Template',
        'description' => 'A custom template',
        'thumbnail' => 'path/to/thumbnail.png',
        'pro' => false
    ];
    return $templates;
});
```

### Available Template Variables

```php
$mode              // Display mode
$title             // Page title
$message           // Main message
$theme_mode        // dark or light
$bg_color          // Background color
$text_color        // Text color
$accent_color      // Accent color
$logo_url          // Logo URL
$show_logo         // Show logo boolean
$show_social       // Show social links boolean
$show_contact      // Show contact info boolean
$contact_email     // Contact email
$contact_phone     // Contact phone
$social_facebook   // Facebook URL
$social_twitter    // Twitter URL
$social_instagram  // Instagram URL
$countdown_enabled // Countdown enabled boolean
$countdown_date    // Countdown date
$custom_css        // Custom CSS
$site_name         // Site name
```

---

## Frequently Asked Questions

### How do I preview the maintenance page without activating it?

Click the **Preview Page** button on the Dashboard tab, or add `?ezmp_preview=1` to your site URL (admins only).

### Can I customize the templates?

Yes! You can add custom CSS in the Design tab, or create your own custom templates in the `templates/` directory.

### Will administrators see the maintenance page?

No, administrators automatically bypass maintenance mode. You can configure which roles bypass in the Access tab.

### Can I whitelist specific IP addresses?

Yes, go to **Maintenance > Access** and add IP addresses to the whitelist (one per line).

### How do I integrate with Ez IT Client Manager?

Ez IT Client Manager will automatically detect and control Ez Maintenance Pro if both plugins are installed. No additional configuration needed.

### Can I use this for "Coming Soon" pages?

Absolutely! Select the "Under Construction" mode and customize the message to create a coming soon page.

---

## Support

For support, feature requests, or bug reports:

- **Website:** https://www.Ez-IT-Solutions.com
- **Email:** chrishultberg@ez-it-solutions.com
- **Documentation:** https://www.Ez-IT-Solutions.com/docs/ez-maintenance-pro

---

## Changelog

### Version 1.0.0 (2025-01-09)
- Initial release
- 4 professional templates (Modern, Minimal, Corporate, Payment Required)
- Full color customization
- Dark/Light mode admin interface
- REST API for external control
- Role-based and IP-based access control
- Social media integration
- Countdown timer support
- Ez IT Client Manager integration

---

## License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2025 Ez IT Solutions | Chris Hultberg

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

---

**Built with ❤️ by Ez IT Solutions | Chris Hultberg**
