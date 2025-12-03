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
            // Reset form after update
            $editMode = false;
            $slideData = ['id' => '', 'title' => '', 'subtitle' => '', 'sort_order' => 0, 'image' => ''];
        }
    } else {
        // Insert
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
    <title>Manage Carousel - Admin</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-color);
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--light-bg);
        }

        th {
            color: var(--heading-color);
        }

        .action-btn {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .edit-btn {
            background: var(--secondary-color);
            color: var(--bg-color);
        }

        .delete-btn {
            background: #ff6b6b;
            color: white;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1 style="color: var(--heading-color);">Manage Carousel</h1>
            <div>
                <a href="../index.php" target="_blank" class="btn" style="margin-right: 10px;">View Site</a>
                <a href="logout.php" class="btn" style="border-color: #ff6b6b; color: #ff6b6b;">Logout</a>
            </div>
        </div>

        <div class="admin-nav">
            <a href="index.php">General</a>
            <a href="services.php">Services</a>
            <a href="seo.php">SEO</a>
            <a href="about.php">About Us</a>
            <a href="carousel.php" class="active">Carousel</a>
            <a href="clients.php">Clients</a>
        </div>

        <?php if ($message)
            echo '<div class="success-msg">' . $message . '</div>'; ?>

        <div class="card">
            <h2 style="color: var(--heading-color); margin-bottom: 20px;">
                <?php echo $editMode ? 'Edit Slide' : 'Add New Slide'; ?></h2>
            <form action="carousel.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($slideData['id']); ?>">
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
                    <input type="number" name="sort_order"
                        value="<?php echo htmlspecialchars($slideData['sort_order']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Image <?php echo $editMode ? '(Leave empty to keep current)' : ''; ?></label>
                    <?php if ($editMode && $slideData['image']): ?>
                        <img src="../assets/images/carousel/<?php echo htmlspecialchars($slideData['image']); ?>"
                            width="100" style="display: block; margin-bottom: 10px; border-radius: 4px;">
                    <?php endif; ?>
                    <input type="file" name="image" <?php echo $editMode ? '' : 'required'; ?>>
                </div>
                <button type="submit" class="btn"
                    style="background: var(--secondary-color); color: var(--bg-color); font-weight: bold;">
                    <?php echo $editMode ? 'Update Slide' : 'Add Slide'; ?>
                </button>
                <?php if ($editMode): ?>
                    <a href="carousel.php" class="btn"
                        style="background: transparent; border: 1px solid var(--text-color); color: var(--text-color); margin-left: 10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2 style="color: var(--heading-color); margin-bottom: 20px;">Current Slides</h2>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Order</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slides as $slide): ?>
                        <tr>
                            <td><img src="../assets/images/carousel/<?php echo htmlspecialchars($slide['image']); ?>"
                                    width="100" style="border-radius: 4px;"></td>
                            <td><?php echo htmlspecialchars($slide['title']); ?></td>
                            <td><?php echo htmlspecialchars($slide['sort_order']); ?></td>
                            <td>
                                <a href="?edit=<?php echo $slide['id']; ?>" class="action-btn edit-btn">Edit</a>
                                <a href="?delete=<?php echo $slide['id']; ?>" class="action-btn delete-btn"
                                    onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>