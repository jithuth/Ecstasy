<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update SEO Settings
    $settings = [
        'seo_title' => $_POST['seo_title'],
        'seo_description' => $_POST['seo_description'],
        'seo_keywords' => $_POST['seo_keywords']
    ];

    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
        $stmt->execute([$key, $value, $value]);
    }
    $message = "SEO settings saved successfully!";
}

$seoTitle = get_setting($pdo, 'seo_title');
$seoDescription = get_setting($pdo, 'seo_description');
$seoKeywords = get_setting($pdo, 'seo_keywords');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>SEO Settings - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container {
            padding: 50px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            border-bottom: 1px solid var(--text-color);
            padding-bottom: 20px;
        }

        .admin-nav {
            margin-bottom: 30px;
        }

        .admin-nav a {
            margin-right: 20px;
            font-weight: bold;
            color: var(--text-color);
        }

        .admin-nav a.active {
            color: var(--secondary-color);
        }

        .card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--heading-color);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid var(--text-color);
            background: var(--bg-color);
            color: var(--text-color);
            font-family: inherit;
        }

        .success-msg {
            background: rgba(100, 255, 218, 0.1);
            color: var(--secondary-color);
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1 style="color: var(--heading-color);">SEO Settings</h1>
            <div>
                <a href="../index.php" target="_blank" class="btn" style="margin-right: 10px;">View Site</a>
                <a href="logout.php" class="btn" style="border-color: #ff6b6b; color: #ff6b6b;">Logout</a>
            </div>
        </div>

        <div class="admin-nav">
            <a href="index.php">General</a>
            <a href="services.php">Services</a>
            <a href="seo.php" class="active">SEO</a>
            <a href="about.php">About Us</a>
            <a href="carousel.php">Carousel</a>
            <a href="clients.php">Clients</a>
            <a href="messages.php">Messages</a>
        </div>

        <?php if ($message)
            echo '<div class="success-msg">' . $message . '</div>'; ?>

        <form action="" method="post">
            <div class="card">
                <div class="form-group">
                    <label>Meta Title</label>
                    <input type="text" name="seo_title" value="<?php echo htmlspecialchars($seoTitle); ?>">
                </div>
                <div class="form-group">
                    <label>Meta Description</label>
                    <textarea name="seo_description"
                        rows="4"><?php echo htmlspecialchars($seoDescription); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Meta Keywords</label>
                    <textarea name="seo_keywords" rows="3"><?php echo htmlspecialchars($seoKeywords); ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn"
                style="background: var(--secondary-color); color: var(--bg-color); font-weight: bold;">Save SEO
                Settings</button>
        </form>
    </div>
</body>

</html>