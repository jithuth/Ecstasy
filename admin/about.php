<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update About Content
    $aboutContent = $_POST['about_content'];
    $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES ('about_content', ?) ON DUPLICATE KEY UPDATE value = ?");
    $stmt->execute([$aboutContent, $aboutContent]);

    // Handle Image Upload
    if (isset($_FILES['about_image']) && $_FILES['about_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['about_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array(strtolower($filetype), $allowed)) {
            $newFilename = 'about_' . time() . '.' . $filetype;
            $uploadPath = '../assets/images/about/' . $newFilename;

            if (move_uploaded_file($_FILES['about_image']['tmp_name'], $uploadPath)) {
                $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES ('about_image', ?) ON DUPLICATE KEY UPDATE value = ?");
                $stmt->execute([$newFilename, $newFilename]);
            }
        }
    }

    $message = "About Us settings saved successfully!";
}

$aboutContent = get_setting($pdo, 'about_content');
$aboutImage = get_setting($pdo, 'about_image');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>About Us Settings - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- TinyMCE CDN -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#about_content',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            skin: 'oxide-dark',
            content_css: 'dark'
        });
    </script>
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

        .form-group input {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid var(--text-color);
            background: var(--bg-color);
            color: var(--text-color);
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
            <h1 style="color: var(--heading-color);">About Us Settings</h1>
            <div>
                <a href="../index.php" target="_blank" class="btn" style="margin-right: 10px;">View Site</a>
                <a href="logout.php" class="btn" style="border-color: #ff6b6b; color: #ff6b6b;">Logout</a>
            </div>
        </div>

        <div class="admin-nav">
            <a href="index.php">General</a>
            <a href="services.php">Services</a>
            <a href="seo.php">SEO</a>
            <a href="about.php" class="active">About Us</a>
            <a href="carousel.php">Carousel</a>
            <a href="clients.php">Clients</a>
        </div>

        <?php if ($message)
            echo '<div class="success-msg">' . $message . '</div>'; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="card">
                <div class="form-group">
                    <label>About Content (Rich Text)</label>
                    <textarea name="about_content" id="about_content"
                        rows="10"><?php echo htmlspecialchars($aboutContent); ?></textarea>
                </div>
                <div class="form-group">
                    <label>About Image</label>
                    <?php if ($aboutImage): ?>
                        <img src="../assets/images/about/<?php echo htmlspecialchars($aboutImage); ?>"
                            style="max-width: 200px; display: block; margin-bottom: 10px; border-radius: 4px;">
                    <?php endif; ?>
                    <input type="file" name="about_image">
                </div>
            </div>
            <button type="submit" class="btn"
                style="background: var(--secondary-color); color: var(--bg-color); font-weight: bold;">Save About
                Us</button>
        </form>
    </div>
</body>

</html>