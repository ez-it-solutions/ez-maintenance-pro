# Plugin Actions Registry - Developer Guide

## Overview

The Plugin Actions Registry allows Ez IT Solutions plugins to dynamically register custom buttons and elements on the Company Info page with full customization options.

## Basic Usage

### 1. Register an Action

```php
add_action('ezit_register_plugin_actions', function() {
    EZIT_Plugin_Actions_Registry::register_action('my-plugin-slug', [
        'id' => 'my-custom-action',
        'label' => 'My Action',
        'type' => 'button',
        'ajax_action' => 'my_ajax_handler',
        'icon' => 'dashicons-star-filled',
        'color' => '#3b82f6',
    ]);
});
```

### 2. Handle the Action

```php
add_action('wp_ajax_my_ajax_handler', function() {
    check_ajax_referer('ezit_custom_action', 'nonce');
    
    // Your action logic here
    
    wp_send_json_success('Action completed!');
});
```

## Configuration Options

### Required Fields

- **`id`** (string): Unique identifier for the action
- **`label`** (string): Button text/label

### Action Types

- **`button`**: Standard button (default)
- **`link`**: Hyperlink
- **`separator`**: Visual separator line
- **`custom`**: Custom HTML via callback

### Styling Options

```php
[
    'color' => '#a3e635',           // Border/text color
    'bg_color' => 'transparent',    // Background color
    'hover_color' => '#ffffff',     // Hover text color
    'hover_bg' => '#a3e635',        // Hover background
    'font_weight' => '600',         // Font weight
    'font_size' => '13px',          // Font size
    'padding' => '6px 12px',        // Padding
    'border_width' => '1px',        // Border width
    'border_radius' => '4px',       // Border radius
    'custom_css' => 'my-class',     // Custom CSS classes
    'custom_style' => 'margin: 5px;', // Inline styles
]
```

### Behavior Options

```php
[
    'url' => '#',                   // URL for link type
    'onclick' => 'myFunction();',   // JavaScript onclick
    'callback' => 'my_function',    // PHP callback for custom type
    'position' => 10,               // Display order (lower = first)
    'show_if' => 'my_condition',    // Callback to determine visibility
]
```

### AJAX Options

```php
[
    'ajax_action' => 'my_action',   // AJAX action name
    'ajax_nonce' => 'my_nonce',     // Nonce action (default: ezit_custom_action)
    'confirm_message' => 'Are you sure?', // Confirmation dialog
    'success_message' => 'Done!',   // Success message
    'error_message' => 'Failed!',   // Error message
]
```

### Icons

Use any WordPress Dashicon:

```php
'icon' => 'dashicons-admin-generic'
'icon' => 'dashicons-database-export'
'icon' => 'dashicons-admin-network'
```

## Examples

### Example 1: Simple Link

```php
EZIT_Plugin_Actions_Registry::register_action('my-plugin', [
    'id' => 'view-logs',
    'label' => 'View Logs',
    'type' => 'link',
    'url' => admin_url('admin.php?page=my-plugin&tab=logs'),
    'icon' => 'dashicons-list-view',
    'color' => '#6366f1',
]);
```

### Example 2: AJAX Action with Confirmation

```php
EZIT_Plugin_Actions_Registry::register_action('my-plugin', [
    'id' => 'clear-cache',
    'label' => 'Clear Cache',
    'ajax_action' => 'my_plugin_clear_cache',
    'icon' => 'dashicons-trash',
    'color' => '#ef4444',
    'confirm_message' => 'Clear all cache?',
    'position' => 20,
]);

add_action('wp_ajax_my_plugin_clear_cache', function() {
    check_ajax_referer('ezit_custom_action', 'nonce');
    
    // Clear cache logic
    wp_cache_flush();
    
    wp_send_json_success('Cache cleared!');
});
```

### Example 3: Conditional Display

```php
EZIT_Plugin_Actions_Registry::register_action('my-plugin', [
    'id' => 'upgrade',
    'label' => 'Upgrade to Pro',
    'url' => 'https://example.com/upgrade',
    'color' => '#f59e0b',
    'show_if' => function() {
        return !get_option('my_plugin_pro_active');
    },
]);
```

### Example 4: Custom Styling

```php
EZIT_Plugin_Actions_Registry::register_action('my-plugin', [
    'id' => 'special-action',
    'label' => 'Special Action',
    'ajax_action' => 'my_special_action',
    'color' => '#8b5cf6',
    'bg_color' => 'rgba(139, 92, 246, 0.1)',
    'hover_bg' => '#8b5cf6',
    'hover_color' => '#ffffff',
    'font_weight' => '700',
    'font_size' => '14px',
    'border_width' => '2px',
    'border_radius' => '8px',
    'icon' => 'dashicons-awards',
]);
```

### Example 5: Separator

```php
EZIT_Plugin_Actions_Registry::register_action('my-plugin', [
    'id' => 'sep-1',
    'type' => 'separator',
    'position' => 15,
]);
```

### Example 6: Custom HTML

```php
EZIT_Plugin_Actions_Registry::register_action('my-plugin', [
    'id' => 'custom-html',
    'type' => 'custom',
    'callback' => function($plugin_slug) {
        echo '<div class="my-custom-element">Custom Content</div>';
    },
]);
```

## Color Palette

Recommended colors for consistency:

- **Primary (Lime Green)**: `#a3e635`
- **Blue**: `#3b82f6`
- **Orange**: `#f59e0b`
- **Red**: `#ef4444`
- **Green**: `#10b981`
- **Purple**: `#8b5cf6`
- **Indigo**: `#6366f1`

## Best Practices

1. **Use meaningful IDs**: Make action IDs descriptive and unique
2. **Position wisely**: Use position values to control display order
3. **Confirm destructive actions**: Always use `confirm_message` for destructive operations
4. **Handle errors**: Provide clear error messages in AJAX handlers
5. **Check permissions**: Verify user capabilities in AJAX handlers
6. **Use nonces**: Always verify nonces for security
7. **Conditional display**: Use `show_if` to show actions only when relevant

## API Reference

### Register Action

```php
EZIT_Plugin_Actions_Registry::register_action($plugin_slug, $action);
```

### Get Actions

```php
$actions = EZIT_Plugin_Actions_Registry::get_actions($plugin_slug);
```

### Remove Action

```php
EZIT_Plugin_Actions_Registry::remove_action($plugin_slug, $action_id);
```

### Clear All Actions

```php
EZIT_Plugin_Actions_Registry::clear_actions($plugin_slug);
```

## Hooks

### Registration Hook

```php
add_action('ezit_register_plugin_actions', function() {
    // Register your actions here
});
```

This hook fires during `admin_init` and is the proper place to register all plugin actions.
