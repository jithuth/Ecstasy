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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Settings - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --glass-bg: rgba(17, 34, 64, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.1);
            --neon-accent: #64ffda;
        }

        body {
            background-color: #020c1b;
            background-image: radial-gradient(circle at 10% 20%, rgba(100, 255, 218, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(0, 112, 243, 0.05) 0%, transparent 20%);
            min-height: 100vh;
            color: #8892b0;
            font-family: 'Inter', sans-serif;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Navigation */
        .admin-nav {
            background: #0a192f;
            /* Darker background */
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 15px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }

        .admin-nav h3 {
            color: var(--neon-accent);
            font-size: 18px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }

        .admin-nav ul {
            display: flex;
            gap: 10px;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .admin-nav a {
            color: #8892b0;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 14px;
            text-decoration: none;
        }

        .admin-nav a:hover {
            color: var(--neon-accent);
            background: rgba(100, 255, 218, 0.05);
        }

        .admin-nav a.active {
            background: rgba(100, 255, 218, 0.1);
            color: var(--neon-accent);
            border: 1px solid rgba(100, 255, 218, 0.2);
        }

        .logout-btn {
            color: #ff6b6b !important;
            padding: 8px 12px !important;
        }

        .logout-btn:hover {
            background: rgba(255, 107, 107, 0.1) !important;
        }

        /* Header */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .admin-header h1 {
            font-size: 28px;
            color: #ccd6f6;
            margin: 0;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: 1px solid var(--neon-accent);
            color: var(--neon-accent);
            background: transparent;
        }

        .btn:hover {
            background: rgba(100, 255, 218, 0.1);
        }

        /* Forms */
        .card {
            background: var(--glass-bg);
            border: var(--glass-border);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .card h2 {
            color: #ccd6f6;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 15px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--neon-accent);
            font-size: 14px;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(2, 12, 27, 0.5);
            color: #ccd6f6;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--neon-accent);
            box-shadow: 0 0 10px rgba(100, 255, 218, 0.1);
        }

        .btn-submit {
            background: var(--neon-accent);
            color: #020c1b;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            display: block;
            width: 100%;
        }

        .btn-submit:hover {
            background: #4cdbb3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(100, 255, 218, 0.3);
        }

        .success-msg {
            background: rgba(100, 255, 218, 0.1);
            color: var(--neon-accent);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(100, 255, 218, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Responsive Nav */
        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .admin-nav ul {
                flex-wrap: wrap;
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Navigation -->
        <nav class="admin-nav">
            <h3><i class="fas fa-cogs"></i> Admin Panel</h3>
            <ul>
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="clients.php">Clients</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="carousel.php">Carousel</a></li>
                <li><a href="seo.php">SEO</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="analytics.php">Analytics</a></li>
                <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul>
        </nav>

        <div class="admin-header">
            <h1>General Settings</h1>
            <a href="../index.php" target="_blank" class="btn">
                <i class="fas fa-external-link-alt"></i> View Site
            </a>
        </div>

        <?php if ($message): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="card">
                <h2><i class="fas fa-home" style="color: var(--neon-accent); margin-right: 10px;"></i> Hero Section</h2>
                <p style="margin-bottom: 20px; font-size: 14px; color: #8892b0; opacity: 0.8;">
                    <i class="fas fa-info-circle"></i> This content is shown if no carousel slides are active.
                </p>
                <div class="form-group">
                    <label>Hero Title</label>
                    <input type="text" name="hero_title" value="<?php echo htmlspecialchars($heroTitle); ?>">
                </div>
                <div class="form-group">
                    <label>Hero Subtitle</label>
                    <input type="text" name="hero_subtitle" value="<?php echo htmlspecialchars($heroSubtitle); ?>">
                </div>
                <div class="form-group">
                    <label>Hero Description</label>
                    <textarea name="hero_description"
                        rows="4"><?php echo htmlspecialchars($heroDescription); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Hero Image</label>
                    <?php if ($heroImage): ?>
                        <div style="margin-bottom: 15px;">
                            <img src="../assets/images/hero/<?php echo htmlspecialchars($heroImage); ?>"
                                style="max-width: 200px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="hero_image" style="padding: 10px;">
                </div>
            </div>

            <div class="card">
                <h2><i class="fas fa-envelope" style="color: var(--neon-accent); margin-right: 10px;"></i> Contact Info
                </h2>
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

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
    </div>
</body>

</html>