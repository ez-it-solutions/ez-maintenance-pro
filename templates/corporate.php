<?php
/**
 * Corporate Template
 * 
 * Professional corporate style
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: <?php echo esc_attr($bg_color); ?>;
            color: <?php echo esc_attr($text_color); ?>;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            padding: 30px 40px;
            border-bottom: 3px solid <?php echo esc_attr($accent_color); ?>;
        }
        
        .logo img {
            max-width: 180px;
            height: auto;
        }
        
        .main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 700px;
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 60px 50px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            background: <?php echo esc_attr($accent_color); ?>;
            color: <?php echo esc_attr($bg_color); ?>;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 30px;
        }
        
        h1 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: <?php echo esc_attr($accent_color); ?>;
        }
        
        .message {
            font-size: 1.15rem;
            line-height: 1.7;
            opacity: 0.9;
            margin-bottom: 40px;
        }
        
        .info-box {
            background: rgba(255, 255, 255, 0.05);
            border-left: 4px solid <?php echo esc_attr($accent_color); ?>;
            padding: 20px 25px;
            margin: 30px 0;
        }
        
        .info-box strong {
            color: <?php echo esc_attr($accent_color); ?>;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .contact-item {
            text-align: center;
        }
        
        .contact-item strong {
            display: block;
            margin-bottom: 10px;
            color: <?php echo esc_attr($accent_color); ?>;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .contact-item a {
            color: <?php echo esc_attr($text_color); ?>;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .contact-item a:hover {
            color: <?php echo esc_attr($accent_color); ?>;
        }
        
        .footer {
            padding: 20px 40px;
            text-align: center;
            font-size: 0.9rem;
            opacity: 0.7;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 40px 30px;
            }
            
            h1 {
                font-size: 2rem;
            }
        }
        
        <?php if ($custom_css): ?>
        <?php echo wp_kses_post($custom_css); ?>
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="header">
        <?php if ($show_logo && $logo_url): ?>
            <div class="logo">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>">
            </div>
        <?php else: ?>
            <h2 style="color: <?php echo esc_attr($accent_color); ?>;"><?php echo esc_html($site_name); ?></h2>
        <?php endif; ?>
    </div>
    
    <div class="main">
        <div class="container">
            <div class="status-badge">
                <?php
                switch ($mode) {
                    case 'construction':
                        echo 'Under Construction';
                        break;
                    case 'payment_overdue':
                        echo 'Temporarily Offline';
                        break;
                    default:
                        echo 'Maintenance Mode';
                }
                ?>
            </div>
            
            <h1><?php echo esc_html($title); ?></h1>
            
            <div class="message">
                <?php echo wp_kses_post(wpautop($message)); ?>
            </div>
            
            <?php if ($countdown_enabled && $countdown_date): ?>
                <div class="info-box">
                    <strong>Estimated Completion:</strong> <?php echo esc_html(date('F j, Y \a\t g:i A', strtotime($countdown_date))); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($show_contact && ($contact_email || $contact_phone)): ?>
                <div class="contact-grid">
                    <?php if ($contact_email): ?>
                        <div class="contact-item">
                            <strong>Email Support</strong>
                            <a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a>
                        </div>
                    <?php endif; ?>
                    <?php if ($contact_phone): ?>
                        <div class="contact-item">
                            <strong>Phone Support</strong>
                            <a href="tel:<?php echo esc_attr($contact_phone); ?>"><?php echo esc_html($contact_phone); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. All rights reserved.</p>
    </div>
</body>
</html>
