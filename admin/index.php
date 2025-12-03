<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update Text Settings
    $settings = [
        'hero_title' => $_POST['hero_title'],
        'hero_subtitle' => $_POST['hero_subtitle'],
        'hero_description' => $_POST['hero_description'],
        'contact_email' => $_POST['contact_email'],
        'contact_address' => $_POST['contact_address']
    ];

    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
        $stmt->execute([$key, $value, $value]);
    }

    // Handle Hero Image Upload
    if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['hero_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array(strtolower($filetype), $allowed)) {
            $newFilename = 'hero_' . time() . '.' . $filetype;
            $uploadPath = '../assets/images/hero/' . $newFilename;

            if (move_uploaded_file($_FILES['hero_image']['tmp_name'], $uploadPath)) {
                // Update DB
                $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES ('hero_image', ?) ON DUPLICATE KEY UPDATE value = ?");
                $stmt->execute([$newFilename, $newFilename]);
            }
        }
    }

    $message = "Settings saved successfully!";
}

// Fetch current settings
$heroTitle = get_setting($pdo, 'hero_title');
$heroSubtitle = get_setting($pdo, 'hero_subtitle');
$heroDescription = get_setting($pdo, 'hero_description');
$heroImage = get_setting($pdo, 'hero_image');
$contactEmail = get_setting($pdo, 'contact_email');
$contactAddress = get_setting($pdo, 'contact_address');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
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
            <h1 style="color: var(--heading-color);">Dashboard</h1>
            <div>
                <a href="../index.php" target="_blank" class="btn" style="margin-right: 10px;">View Site</a>
                <a href="logout.php" class="btn" style="border-color: #ff6b6b; color: #ff6b6b;">Logout</a>
            </div>
        </div>

        <div class="admin-nav">
            <a href="index.php" class="active">General</a>
            <a href="services.php">Services</a>
            <a href="seo.php">SEO</a>
            <a href="about.php">About Us</a>
            <a href="carousel.php">Carousel</a>
            <a href="clients.php">Clients</a>
            <a href="messages.php">Messages</a>
        </div>

        <?php if ($message)
            echo '<div class="success-msg">' . $message . '</div>'; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="card">
                <h2 style="color: var(--heading-color); margin-bottom: 20px;">Hero Section (Fallback)</h2>
                <p style="margin-bottom: 20px; font-size: 14px; opacity: 0.7;">This content is shown if no carousel
                    slides are active.</p>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="hero_title" value="<?php echo htmlspecialchars($heroTitle); ?>">
                </div>
                <div class="form-group">
                    <label>Subtitle</label>
                    <input type="text" name="hero_subtitle" value="<?php echo htmlspecialchars($heroSubtitle); ?>">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="hero_description"
                        rows="4"><?php echo htmlspecialchars($heroDescription); ?></textarea>
                </div>
            </div>

            <div class="card">
                <h2 style="color: var(--heading-color); margin-bottom: 20px;">Contact Settings</h2>
                <div class="form-group">
                    <label>Contact Email</label>
                    <input type="email" name="contact_email" value="<?php echo htmlspecialchars($contactEmail); ?>">
                </div>
                <div class="form-group">
                    <label>Contact Address</label>
                    <textarea name="contact_address"
                        rows="3"><?php echo htmlspecialchars($contactAddress); ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn"
                style="background: var(--secondary-color); color: var(--bg-color); font-weight: bold;">Save
                Changes</button>
        </form>
    </div>
</body>

</html>