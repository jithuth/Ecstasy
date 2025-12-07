<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

require_once '../includes/db.php';

$message = '';
$editMode = false;
$editImage = null;

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_path FROM why_work_images WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetch();

    if ($img) {
        $filePath = '../assets/images/why_work/' . $img['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $stmt = $pdo->prepare("DELETE FROM why_work_images WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Image deleted successfully!";
    }
}

// Handle Edit Fetch
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM why_work_images WHERE id = ?");
    $stmt->execute([$id]);
    $editImage = $stmt->fetch();
    if ($editImage) {
        $editMode = true;
    }
}

// Handle Add/Update Image
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['add_image'])) {
        // --- ADD LOGIC ---
        $title = $_POST['title'];
        $description = $_POST['description'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                $newFilename = 'why_work_' . time() . '_' . rand(1000, 9999) . '.' . $filetype;
                $uploadPath = '../assets/images/why_work/' . $newFilename;

                if (!file_exists('../assets/images/why_work/')) {
                    mkdir('../assets/images/why_work/', 0777, true);
                }

                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    $stmt = $pdo->prepare("INSERT INTO why_work_images (image_path, title, description) VALUES (?, ?, ?)");
                    if ($stmt->execute([$newFilename, $title, $description])) {
                        $message = "Image added successfully!";
                    } else {
                        $message = "Database error!";
                    }
                } else {
                    $message = "Failed to move uploaded file!";
                }
            } else {
                $message = "Invalid file type!";
            }
        } else {
            $message = "Please select an image!";
        }

    } elseif (isset($_POST['update_image'])) {
        // --- UPDATE LOGIC ---
        $id = $_POST['image_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $old_image = $_POST['old_image'];

        // Handle New Image if uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                $newFilename = 'why_work_' . time() . '_' . rand(1000, 9999) . '.' . $filetype;
                $uploadPath = '../assets/images/why_work/' . $newFilename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    // Delete old image
                    $oldPath = '../assets/images/why_work/' . $old_image;
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }

                    // Update with new image
                    $stmt = $pdo->prepare("UPDATE why_work_images SET image_path = ?, title = ?, description = ? WHERE id = ?");
                    if ($stmt->execute([$newFilename, $title, $description, $id])) {
                        $message = "Image updated successfully!";
                        header("Location: why_work.php"); // Clear get params
                        exit;
                    } else {
                        $message = "Database error!";
                    }
                } else {
                    $message = "Failed to upload new image!";
                }
            } else {
                $message = "Invalid file type!";
            }
        } else {
            // Update without changing image
            $stmt = $pdo->prepare("UPDATE why_work_images SET title = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$title, $description, $id])) {
                $message = "Details updated successfully!";
                header("Location: why_work.php"); // Clear get params
                exit;
            } else {
                $message = "Database error!";
            }
        }
    }
}

// Fetch Images
$stmt = $pdo->query("SELECT * FROM why_work_images ORDER BY created_at DESC");
$images = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Why Work With Us - Admin</title>
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Navigation */
        .admin-nav {
            background: #0a192f;
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
            display: inline-block;
            text-align: center;
        }

        .btn-submit:hover {
            background: #4cdbb3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(100, 255, 218, 0.3);
        }

        .btn-cancel {
            background: transparent;
            color: #8892b0;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 11px 25px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            text-decoration: none;
            margin-left: 10px;
            display: inline-block;
        }

        .btn-cancel:hover {
            color: #fff;
            border-color: #fff;
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

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .grid-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .grid-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        .grid-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .grid-content {
            padding: 15px;
        }

        .grid-title {
            color: #ccd6f6;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .grid-desc {
            color: #8892b0;
            font-size: 13px;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .edit-btn,
        .delete-btn {
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            text-align: center;
            font-size: 13px;
            transition: all 0.3s;
            flex: 1;
        }

        .edit-btn {
            background: rgba(100, 255, 218, 0.1);
            color: var(--neon-accent);
            border: 1px solid rgba(100, 255, 218, 0.2);
        }

        .edit-btn:hover {
            background: rgba(100, 255, 218, 0.2);
        }

        .delete-btn {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.2);
        }

        .delete-btn:hover {
            background: rgba(255, 107, 107, 0.2);
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
        <?php $currentPage = 'why_work';
        include 'header.php'; ?>

        <div class="admin-header">
            <h1>Why Work With Us</h1>
        </div>

        <?php if ($message): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>
                <?php if ($editMode): ?>
                    <i class="fas fa-edit" style="color: var(--neon-accent); margin-right: 10px;"></i> Edit Image
                <?php else: ?>
                    <i class="fas fa-plus-circle" style="color: var(--neon-accent); margin-right: 10px;"></i> Add New Image
                <?php endif; ?>
            </h2>

            <form action="" method="post" enctype="multipart/form-data">
                <?php if ($editMode): ?>
                    <input type="hidden" name="image_id" value="<?php echo $editImage['id']; ?>">
                    <input type="hidden" name="old_image" value="<?php echo $editImage['image_path']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Title (Optional)</label>
                    <input type="text" name="title" placeholder="e.g. Collaborative Environment"
                        value="<?php echo $editMode ? htmlspecialchars($editImage['title']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Description (Optional)</label>
                    <textarea name="description" rows="3"
                        placeholder="Brief description..."><?php echo $editMode ? htmlspecialchars($editImage['description']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $editMode ? 'Replace Image (Leave empty to keep existing)' : 'Image'; ?></label>

                    <?php if ($editMode && $editImage['image_path']): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="../assets/images/why_work/<?php echo $editImage['image_path']; ?>"
                                style="height: 100px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.1);">
                            <div style="font-size: 12px; color: #8892b0;">Current Image</div>
                        </div>
                    <?php endif; ?>

                    <input type="file" name="image" <?php echo $editMode ? '' : 'required'; ?> style="padding: 10px;">
                    <small style="color: #8892b0; display: block; margin-top: 5px;">Recommended: 800x600px or similar
                        ratio</small>
                </div>

                <?php if ($editMode): ?>
                    <button type="submit" name="update_image" class="btn-submit">
                        <i class="fas fa-save"></i> Update Image
                    </button>
                    <a href="why_work.php" class="btn-cancel">Cancel</a>
                <?php else: ?>
                    <button type="submit" name="add_image" class="btn-submit">
                        <i class="fas fa-upload"></i> Upload Image
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2><i class="fas fa-images" style="color: var(--neon-accent); margin-right: 10px;"></i> Gallery Images</h2>

            <?php if (count($images) > 0): ?>
                <div class="grid-container">
                    <?php foreach ($images as $img): ?>
                        <div class="grid-item">
                            <img src="../assets/images/why_work/<?php echo htmlspecialchars($img['image_path']); ?>" alt="Img"
                                class="grid-img">
                            <div class="grid-content">
                                <?php if ($img['title']): ?>
                                    <div class="grid-title"><?php echo htmlspecialchars($img['title']); ?></div>
                                <?php endif; ?>
                                <?php if ($img['description']): ?>
                                    <div class="grid-desc"><?php echo htmlspecialchars($img['description']); ?></div>
                                <?php endif; ?>

                                <div class="action-btns">
                                    <a href="?edit=<?php echo $img['id']; ?>" class="edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $img['id']; ?>" class="delete-btn"
                                        onclick="return confirm('Delete this image?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #8892b0;">No images uploaded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>