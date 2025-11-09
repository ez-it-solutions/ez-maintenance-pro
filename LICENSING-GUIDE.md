# Ez IT Solutions - Licensing System Guide

## Overview

This licensing system is designed to be **reusable across all Ez IT Solutions WordPress plugins**. It provides:

- License activation/deactivation
- Remote validation ("phone home" functionality)
- Subscription-based feature protection
- Grace period for offline validation
- Plan-based feature gating (Free, Pro, Business)

---

## Architecture

### Core Components

1. **`class-license.php`** - Reusable licensing class
2. **License Server API** - Hosted at `https://licensing.ez-it-solutions.com/api/v1`
3. **WordPress Integration** - AJAX handlers and admin UI
4. **Feature Protection** - Plan-based access control

---

## License Server API Endpoints

### Required Endpoints

Your licensing server must implement these endpoints:

#### 1. Activate License
```
POST /api/v1/activate

Request Body:
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "email": "customer@example.com",
  "site_url": "https://example.com",
  "product_id": "ez-maintenance-pro",
  "wp_version": "6.4",
  "plugin_version": "1.0.0",
  "php_version": "8.1"
}

Success Response:
{
  "success": true,
  "status": "active",
  "plan": "pro",
  "expires_at": "2025-12-31 23:59:59",
  "message": "License activated successfully"
}

Error Response:
{
  "success": false,
  "message": "Invalid license key"
}
```

#### 2. Verify License
```
POST /api/v1/verify

Request Body:
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "site_url": "https://example.com",
  "product_id": "ez-maintenance-pro"
}

Success Response:
{
  "success": true,
  "status": "active",
  "plan": "pro",
  "expires_at": "2025-12-31 23:59:59"
}
```

#### 3. Deactivate License
```
POST /api/v1/deactivate

Request Body:
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "site_url": "https://example.com",
  "product_id": "ez-maintenance-pro"
}

Response:
{
  "success": true,
  "message": "License deactivated"
}
```

---

## Integration Guide

### Step 1: Copy the License Class

Copy `includes/class-license.php` to your plugin's `includes/` directory.

### Step 2: Update Product ID

In the license class constructor, change the product ID:

```php
private $product_id = 'your-plugin-slug';
```

### Step 3: Load the Class

In your main plugin file:

```php
require_once PLUGIN_DIR . 'includes/class-license.php';

// In your init function:
Your_Plugin_License::init();
```

### Step 4: Add Admin UI

Add the license activation UI to your settings page (see `admin/class-admin.php` lines 525-588 for reference).

### Step 5: Add Action Links

Add license status to the plugins page:

```php
add_filter('plugin_action_links_' . PLUGIN_BASENAME, [$this, 'add_action_links']);

public function add_action_links($links) {
    $license_key = get_option('plugin_license_key', '');
    if (empty($license_key)) {
        $links[] = '<a href="..." style="color: #a3e635; font-weight: 600;">Activate License</a>';
    } else {
        $links[] = '<span style="color: #a3e635;">✓ Licensed</span>';
    }
    return $links;
}
```

---

## Feature Protection

### Plan-Based Features

The system supports three plans:

**Free Plan:**
- Basic templates
- Color customization
- Basic access control

**Pro Plan:**
- All Free features
- Premium templates
- Countdown timer
- Social links
- Custom CSS
- API access

**Business Plan:**
- All Pro features
- White label
- Priority support
- Multisite support

### Protecting Features

```php
$license = EZMP_License::init();

// Check if license is active
if ($license->is_active()) {
    // Show premium feature
}

// Check specific feature
if ($license->has_feature('premium_templates')) {
    // Show premium templates
} else {
    // Show upgrade notice
    EZMP_License::upgrade_notice('Premium Templates');
}

// Get current plan
$plan = $license->get_plan(); // 'free', 'pro', or 'business'
```

### Upgrade Notice

Display a professional upgrade prompt:

```php
EZMP_License::upgrade_notice('Feature Name');
```

This displays a styled notice with:
- Feature name
- Plan requirements
- Link to pricing page
- Call-to-action button

---

## Validation & Grace Period

### Daily Checks

The system automatically verifies licenses daily via WordPress cron:

```php
wp_schedule_event(time(), 'daily', 'ezmp_daily_license_check');
```

### Grace Period

If the licensing server is unreachable, the system uses cached license status for **7 days**:

```php
$last_verified = get_option('ezmp_license_verified', 0);
if (time() - $last_verified < (7 * DAY_IN_SECONDS)) {
    return get_option('ezmp_license_status') === 'active';
}
```

This prevents legitimate users from losing access due to temporary server issues.

---

## Database Storage

License data is stored in WordPress options:

```php
ezmp_license_key       // License key
ezmp_license_email     // Customer email
ezmp_license_status    // active, expired, invalid
ezmp_license_plan      // free, pro, business
ezmp_license_expires   // Expiration date
ezmp_license_verified  // Last verification timestamp
```

---

## Security Considerations

1. **AJAX Nonces** - All AJAX requests use WordPress nonces
2. **Capability Checks** - Only admins can manage licenses
3. **Sanitization** - All inputs are sanitized
4. **HTTPS** - API calls use HTTPS
5. **Rate Limiting** - Daily checks prevent API abuse

---

## Customization

### Change API URL

```php
private $api_url = 'https://your-licensing-server.com/api/v1';
```

### Add Custom Features

Edit the `has_feature()` method to add your features:

```php
$features = [
    'free' => ['feature1', 'feature2'],
    'pro' => ['feature1', 'feature2', 'feature3'],
    'business' => ['feature1', 'feature2', 'feature3', 'feature4']
];
```

### Custom Validation Logic

Override the `verify_license()` method for custom validation.

---

## Testing

### Test License Activation

1. Enter a test license key in Settings > License Activation
2. Click "Activate License"
3. Check the response from your licensing server
4. Verify license data is stored in WordPress options

### Test Feature Protection

```php
// In your code
if (!$license->has_feature('premium_feature')) {
    EZMP_License::upgrade_notice('Premium Feature');
    return;
}
```

### Test Grace Period

1. Activate a license
2. Temporarily disable your licensing server
3. Verify the plugin continues working for 7 days
4. After 7 days, verify features are restricted

---

## Logging

All license actions are logged to the database:

```php
$wpdb->insert($wpdb->prefix . 'ezmp_logs', [
    'action' => 'license_activated',
    'details' => json_encode(['plan' => 'pro']),
    'user_id' => get_current_user_id(),
    'created_at' => current_time('mysql')
]);
```

View logs in the database or create an admin page to display them.

---

## Troubleshooting

### License Won't Activate

1. Check API URL is correct
2. Verify licensing server is online
3. Check WordPress error logs
4. Test API endpoint with Postman

### Features Not Unlocking

1. Verify license status: `get_option('ezmp_license_status')`
2. Check plan: `get_option('ezmp_license_plan')`
3. Ensure feature name matches in `has_feature()` array

### Validation Failing

1. Check last verification: `get_option('ezmp_license_verified')`
2. Manually trigger: `EZMP_License::init()->verify_license()`
3. Check server response in Network tab

---

## Reusability Checklist

When adding to a new plugin:

- [ ] Copy `class-license.php` to `includes/`
- [ ] Update `$product_id` in class
- [ ] Update option names (replace `ezmp_` prefix)
- [ ] Load class in main plugin file
- [ ] Add admin UI for license activation
- [ ] Add action links to plugins page
- [ ] Update feature list in `has_feature()`
- [ ] Test activation/deactivation
- [ ] Test feature protection
- [ ] Document plugin-specific features

---

## Support

For licensing system support:
- **Email:** chrishultberg@ez-it-solutions.com
- **Website:** https://www.Ez-IT-Solutions.com

---

**Built with ❤️ by Ez IT Solutions | Chris Hultberg**
