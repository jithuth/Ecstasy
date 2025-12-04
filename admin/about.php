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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us Settings - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tiny.cloud/1/jwrqwamxwdi1or4o2jdmmdcejmy78k24rg4on5ufwv97p1fv/tinymce/6/tinymce.min.js"
        referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#about_content',
            skin: 'oxide-dark',
            content_css: 'dark',
            height: 400,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
            toolbar: 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            content_style: 'body { font-family:Inter,sans-serif; font-size:14px }'
        });
    </script>
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
        <?php $currentPage = 'about';
        include 'header.php'; ?>

        <div class="admin-header">
            <h1>About Us Settings</h1>
            <a href="../index.php#about" target="_blank" class="btn">
                <i class="fas fa-external-link-alt"></i> View Section
            </a>
        </div>

        <?php if ($message): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="card">
                <h2><i class="fas fa-info-circle" style="color: var(--neon-accent); margin-right: 10px;"></i> About
                    Content</h2>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="about_content" name="about_content"
                        rows="6"><?php echo htmlspecialchars($aboutContent); ?></textarea>
                </div>
                <div class="form-group">
                    <label>About Image</label>
                    <?php if ($aboutImage): ?>
                        <div style="margin-bottom: 15px;">
                            <img src="../assets/images/about/<?php echo htmlspecialchars($aboutImage); ?>"
                                style="max-width: 200px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="about_image" style="padding: 10px;">
                </div>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
    </div>
</body>

</html>