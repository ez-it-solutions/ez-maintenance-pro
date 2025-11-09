<?php
/**
 * Payment Required Template - Special template for non-payment situations
 */
if (!defined('ABSPATH')) exit;
$vars = EZMP_Templates::get_template_vars();
extract($vars);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html($title); ?> - <?php echo esc_html($site_name); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: <?php echo esc_attr($bg_color); ?>;
            color: <?php echo esc_attr($text_color); ?>;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            width: 100%;
            text-align: center;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 193, 7, 0.3);
            border-radius: 15px;
            padding: 50px 40px;
        }
        .icon { font-size: 80px; margin-bottom: 30px; }
        h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 20px; color: #ffc107; }
        .message { font-size: 1.1rem; line-height: 1.7; opacity: 0.9; margin: 30px 0; }
        .contact { margin-top: 40px; padding-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        .contact a { color: #ffc107; text-decoration: none; font-weight: 600; }
        <?php if ($custom_css): ?><?php echo wp_kses_post($custom_css); ?><?php endif; ?>
    </style>
</head>
<body>
    <div class="container">
        <?php if ($show_logo && $logo_url): ?>
            <div class="logo"><img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" style="max-width: 180px;"></div>
        <?php endif; ?>
        <div class="icon">💳</div>
        <h1><?php echo esc_html($title); ?></h1>
        <div class="message"><?php echo wp_kses_post(wpautop($message)); ?></div>
        <?php if ($show_contact): ?>
            <div class="contact">
                <p><strong>Need Assistance?</strong></p>
                <?php if ($contact_email): ?><p>Email: <a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></p><?php endif; ?>
                <?php if ($contact_phone): ?><p>Phone: <a href="tel:<?php echo esc_attr($contact_phone); ?>"><?php echo esc_html($contact_phone); ?></a></p><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
