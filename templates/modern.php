<?php
/**
 * Modern Template
 * 
 * Clean and modern design with gradient backgrounds
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: <?php echo esc_attr($bg_color); ?>;
            color: <?php echo esc_attr($text_color); ?>;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, <?php echo esc_attr($accent_color); ?>15 0%, transparent 50%, <?php echo esc_attr($accent_color); ?>10 100%);
            pointer-events: none;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
            text-align: center;
            position: relative;
            z-index: 1;
            animation: fadeIn 0.8s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo {
            margin-bottom: 40px;
            animation: fadeIn 0.8s ease-out 0.2s both;
        }
        
        .logo img {
            max-width: 200px;
            height: auto;
        }
        
        .icon {
            font-size: 80px;
            margin-bottom: 30px;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: <?php echo esc_attr($accent_color); ?>;
            animation: fadeIn 0.8s ease-out 0.4s both;
        }
        
        .message {
            font-size: 1.2rem;
            line-height: 1.8;
            opacity: 0.9;
            margin-bottom: 40px;
            animation: fadeIn 0.8s ease-out 0.6s both;
        }
        
        .countdown {
            display: inline-block;
            padding: 15px 30px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            margin-bottom: 30px;
            animation: fadeIn 0.8s ease-out 0.8s both;
        }
        
        .contact {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeIn 0.8s ease-out 1s both;
        }
        
        .contact a {
            color: <?php echo esc_attr($accent_color); ?>;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        
        .contact a:hover {
            opacity: 0.8;
        }
        
        .social-links {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: <?php echo esc_attr($text_color); ?>;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: <?php echo esc_attr($accent_color); ?>;
            color: <?php echo esc_attr($bg_color); ?>;
            transform: translateY(-3px);
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
        
        <div class="icon">
            <?php
            switch ($mode) {
                case 'construction':
                    echo '🚧';
                    break;
                case 'payment_overdue':
                    echo '💳';
                    break;
                default:
                    echo '🔧';
            }
            ?>
        </div>
        
        <h1><?php echo esc_html($title); ?></h1>
        
        <div class="message">
            <?php echo wp_kses_post(wpautop($message)); ?>
        </div>
        
        <?php if ($countdown_enabled && $countdown_date): ?>
            <div class="countdown">
                <strong>Estimated Return:</strong> <?php echo esc_html(date('F j, Y', strtotime($countdown_date))); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($show_contact): ?>
            <div class="contact">
                <p><strong>Need Help?</strong></p>
                <?php if ($contact_email): ?>
                    <p>Email: <a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></p>
                <?php endif; ?>
                <?php if ($contact_phone): ?>
                    <p>Phone: <a href="tel:<?php echo esc_attr($contact_phone); ?>"><?php echo esc_html($contact_phone); ?></a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($show_social && ($social_facebook || $social_twitter || $social_instagram)): ?>
            <div class="social-links">
                <?php if ($social_facebook): ?>
                    <a href="<?php echo esc_url($social_facebook); ?>" target="_blank" rel="noopener">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                <?php endif; ?>
                <?php if ($social_twitter): ?>
                    <a href="<?php echo esc_url($social_twitter); ?>" target="_blank" rel="noopener">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                    </a>
                <?php endif; ?>
                <?php if ($social_instagram): ?>
                    <a href="<?php echo esc_url($social_instagram); ?>" target="_blank" rel="noopener">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
