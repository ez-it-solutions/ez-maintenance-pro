<?php
/**
 * Minimal Template
 * 
 * Simple and elegant minimalist design
 */

if (!defined('ABSPATH')) {
    exit;
}

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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Georgia', serif;
            background: <?php echo esc_attr($bg_color); ?>;
            color: <?php echo esc_attr($text_color); ?>;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .logo {
            margin-bottom: 60px;
        }
        
        .logo img {
            max-width: 150px;
            height: auto;
            opacity: 0.9;
        }
        
        h1 {
            font-size: 2.5rem;
            font-weight: 400;
            margin-bottom: 30px;
            color: <?php echo esc_attr($accent_color); ?>;
            letter-spacing: -0.5px;
        }
        
        .divider {
            width: 60px;
            height: 2px;
            background: <?php echo esc_attr($accent_color); ?>;
            margin: 30px auto;
        }
        
        .message {
            font-size: 1.1rem;
            line-height: 1.8;
            opacity: 0.85;
            margin-bottom: 40px;
        }
        
        .contact {
            margin-top: 60px;
            font-size: 0.95rem;
        }
        
        .contact a {
            color: <?php echo esc_attr($accent_color); ?>;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: border-color 0.3s;
        }
        
        .contact a:hover {
            border-bottom-color: <?php echo esc_attr($accent_color); ?>;
        }
        
        <?php if ($custom_css): ?>
        <?php echo wp_kses_post($custom_css); ?>
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="container">
        <?php if ($show_logo && $logo_url): ?>
            <div class="logo">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>">
            </div>
        <?php endif; ?>
        
        <h1><?php echo esc_html($title); ?></h1>
        
        <div class="divider"></div>
        
        <div class="message">
            <?php echo wp_kses_post(wpautop($message)); ?>
        </div>
        
        <?php if ($countdown_enabled && $countdown_date): ?>
            <p style="margin: 30px 0;">
                <strong>Expected Return:</strong><br>
                <?php echo esc_html(date('F j, Y', strtotime($countdown_date))); ?>
            </p>
        <?php endif; ?>
        
        <?php if ($show_contact): ?>
            <div class="contact">
                <div class="divider"></div>
                <?php if ($contact_email): ?>
                    <p><a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></p>
                <?php endif; ?>
                <?php if ($contact_phone): ?>
                    <p><a href="tel:<?php echo esc_attr($contact_phone); ?>"><?php echo esc_html($contact_phone); ?></a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
