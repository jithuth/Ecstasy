<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

$message = '';
$editMode = false;
$slideData = ['id' => '', 'title' => '', 'subtitle' => '', 'sort_order' => 0, 'image' => ''];

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM carousel WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Slide deleted successfully!";
    }
}

// Handle Edit Fetch
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM carousel WHERE id = ?");
    $stmt->execute([$id]);
    $fetchedSlide = $stmt->fetch();
    if ($fetchedSlide) {
        $editMode = true;
        $slideData = $fetchedSlide;
    }
}

// Handle Add/Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $sort_order = $_POST['sort_order'];
    $id = $_POST['id'];

    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array(strtolower($filetype), $allowed)) {
            $newFilename = 'slide_' . time() . '.' . $filetype;
            $uploadPath = '../assets/images/carousel/' . $newFilename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $image = $newFilename;
            }
        }
    }

    if (!empty($id)) {
        // Update
        $sql = "UPDATE carousel SET title = ?, subtitle = ?, sort_order = ?";
        $params = [$title, $subtitle, $sort_order];
        if ($image) {
            $sql .= ", image = ?";
            $params[] = $image;
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $message = "Slide updated successfully!";
            // Reset form
            $editMode = false;
            $slideData = ['id' => '', 'title' => '', 'subtitle' => '', 'sort_order' => 0, 'image' => ''];
        }
    } else {
        // Add
        if ($image) {
            $stmt = $pdo->prepare("INSERT INTO carousel (title, subtitle, image, sort_order) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$title, $subtitle, $image, $sort_order])) {
                $message = "Slide added successfully!";
            }
        } else {
            $message = "Please upload an image.";
        }
    }
}

// Fetch Slides
$stmt = $pdo->query("SELECT * FROM carousel ORDER BY sort_order ASC");
$slides = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Carousel - Admin</title>
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

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            color: #8892b0;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            vertical-align: top;
        }

        th {
            color: #ccd6f6;
            font-weight: 600;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            border: none;
            margin-right: 5px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
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
        <nav class="admin-nav">
            <h3><i class="fas fa-cogs"></i> Admin Panel</h3>
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="clients.php">Clients</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="carousel.php" class="active">Carousel</a></li>
                <li><a href="seo.php">SEO</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="analytics.php">Analytics</a></li>
                <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul>
        </nav>

        <div class="admin-header">
            <h1>Manage Carousel</h1>
            <a href="../index.php" target="_blank" class="btn">
                <i class="fas fa-external-link-alt"></i> View Site
            </a>
        </div>

        <?php if ($message): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><i class="fas fa-images" style="color: var(--neon-accent); margin-right: 10px;"></i>
                <?php echo $editMode ? 'Edit Slide' : 'Add New Slide'; ?></h2>
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $slideData['id']; ?>">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($slideData['title']); ?>"
                        required>
                </div>
                <div class="form-group">
                    <label>Subtitle</label>
                    <input type="text" name="subtitle" value="<?php echo htmlspecialchars($slideData['subtitle']); ?>"
                        required>
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" value="<?php echo $slideData['sort_order']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Image <?php echo $editMode ? '(Leave empty to keep current)' : ''; ?></label>
                    <?php if ($editMode && $slideData['image']): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="../assets/images/carousel/<?php echo htmlspecialchars($slideData['image']); ?>"
                                style="width: 100px; border-radius: 4px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" <?php echo $editMode ? '' : 'required'; ?>>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> <?php echo $editMode ? 'Update Slide' : 'Add Slide'; ?>
                </button>
                <?php if ($editMode): ?>
                    <a href="carousel.php" class="btn"
                        style="margin-top: 10px; width: 100%; text-align: center; border-color: #8892b0; color: #8892b0;">Cancel
                        Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2><i class="fas fa-list" style="color: var(--neon-accent); margin-right: 10px;"></i> Existing Slides</h2>
            <?php if (count($slides) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Subtitle</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slides as $slide): ?>
                            <tr>
                                <td><?php echo $slide['sort_order']; ?></td>
                                <td>
                                    <img src="../assets/images/carousel/<?php echo htmlspecialchars($slide['image']); ?>"
                                        style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px;">
                                </td>
                                <td><?php echo htmlspecialchars($slide['title']); ?></td>
                                <td><?php echo htmlspecialchars($slide['subtitle']); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $slide['id']; ?>" class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $slide['id']; ?>" class="action-btn delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this slide?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; color: #8892b0;">No slides found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>