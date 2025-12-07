<?php
require_once 'includes/db.php';

// Fetch SEO Settings
$seoTitle = get_setting($pdo, 'seo_title');
$seoDescription = get_setting($pdo, 'seo_description');
$seoKeywords = get_setting($pdo, 'seo_keywords');

$currentTitle = isset($pageTitle) ? $pageTitle . ' | ' . get_setting($pdo, 'site_title') : get_setting($pdo, 'site_title');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($currentTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seoDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seoKeywords); ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Preloader -->
    <div id="preloader">
        <div class="loader-content">
            <h2 class="loader-text">Ecstasy Solutions</h2>
            <div class="loader-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            const preloader = document.getElementById('preloader');
            // Add a small delay to ensure the animation is seen
            setTimeout(function () {
                preloader.style.opacity = '0';
                setTimeout(function () {
                    preloader.style.display = 'none';
                }, 500);
            }, 1500);
        });
    </script>

    <header>
        <div class="logo"><?php echo htmlspecialchars(get_setting($pdo, 'site_title')); ?></div>
        <button class="mobile-menu-toggle" aria-label="Toggle Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="careers.php">Careers</a></li>
                <li><a href="why_work.php">Why Work With Us</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li>
                    <button id="theme-toggle" class="theme-toggle" title="Toggle Theme">☀️</button>
                </li>
            </ul>
        </nav>
    </header>

    <script>
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const navLinks = document.querySelector('.nav-links');

        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
                mobileMenuToggle.classList.toggle('active');
            });
        }
    </script>