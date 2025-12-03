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
    <header>
        <div class="logo"><?php echo htmlspecialchars(get_setting($pdo, 'site_title')); ?></div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li>
                    <button id="theme-toggle" class="theme-toggle" title="Toggle Theme">☀️</button>
                </li>
            </ul>
        </nav>
    </header>