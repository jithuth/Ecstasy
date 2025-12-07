<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

require_once '../includes/db.php';

$message = '';
$editMode = false;
$editProduct = null;

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_path FROM flagship_products WHERE id = ?");
    $stmt->execute([$id]);
    $prod = $stmt->fetch();

    if ($prod && $prod['image_path']) {
        $filePath = '../assets/images/products/' . $prod['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM flagship_products WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Product deleted successfully!";
    }
}

// Handle Edit Fetch
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM flagship_products WHERE id = ?");
    $stmt->execute([$id]);
    $editProduct = $stmt->fetch();
    if ($editProduct) {
        $editMode = true;
    }
}

// Handle Add/Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Directory check
    if (!file_exists('../assets/images/products/')) {
        mkdir('../assets/images/products/', 0777, true);
    }

    if (isset($_POST['add_product'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $imagePath = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                $newFilename = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $filetype;
                if (move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/products/' . $newFilename)) {
                    $imagePath = $newFilename;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO flagship_products (title, description, image_path) VALUES (?, ?, ?)");
        if ($stmt->execute([$title, $description, $imagePath])) {
            $message = "Product added successfully!";
        } else {
            $message = "Database error!";
        }

    } elseif (isset($_POST['update_product'])) {
        $id = $_POST['product_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $old_image = $_POST['old_image'];
        $imagePath = $old_image;

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                $newFilename = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $filetype;
                if (move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/products/' . $newFilename)) {
                    $imagePath = $newFilename;
                    // Delete old image
                    if ($old_image && file_exists('../assets/images/products/' . $old_image)) {
                        unlink('../assets/images/products/' . $old_image);
                    }
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE flagship_products SET title = ?, description = ?, image_path = ? WHERE id = ?");
        if ($stmt->execute([$title, $description, $imagePath, $id])) {
            $message = "Product updated successfully!";
            header("Location: products.php");
            exit;
        } else {
            $message = "Database error!";
        }
    }
}

// Fetch Products
$stmt = $pdo->query("SELECT * FROM flagship_products ORDER BY created_at ASC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flagship Products - Admin</title>
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

        /* Rest of Styles matching why_work.php */
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
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--neon-accent);
        }

        .btn-submit {
            background: var(--neon-accent);
            color: #020c1b;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            display: inline-block;
        }

        .btn-submit:hover {
            background: #4cdbb3;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: transparent;
            color: #8892b0;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 11px 25px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
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
        }

        .prod-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .prod-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .prod-img {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
            background: #000;
        }

        .prod-content {
            flex: 1;
        }

        .prod-title {
            font-size: 18px;
            font-weight: 700;
            color: #ccd6f6;
            margin-bottom: 5px;
        }

        .prod-desc {
            color: #8892b0;
            font-size: 14px;
        }

        .prod-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            border: 1px solid transparent;
            transition: all 0.3s;
            color: #fff;
            text-decoration: none;
        }

        .edit-btn {
            background: rgba(100, 255, 218, 0.1);
            color: var(--neon-accent);
            border-color: rgba(100, 255, 218, 0.2);
        }

        .edit-btn:hover {
            background: rgba(100, 255, 218, 0.2);
        }

        .delete-btn {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
            border-color: rgba(255, 107, 107, 0.2);
        }

        .delete-btn:hover {
            background: rgba(255, 107, 107, 0.2);
        }

        @media (max-width: 768px) {
            .prod-item {
                flex-direction: column;
                text-align: center;
            }

            .prod-actions {
                width: 100%;
                justify-content: center;
                margin-top: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Navigation -->
        <?php $currentPage = 'products';
        include 'header.php'; ?>

        <div class="admin-header">
            <h1>Flagship Products</h1>
        </div>

        <?php if ($message): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>
                <?php if ($editMode): ?>
                    <i class="fas fa-edit" style="color: var(--neon-accent); margin-right: 10px;"></i> Edit Product
                <?php else: ?>
                    <i class="fas fa-plus-circle" style="color: var(--neon-accent); margin-right: 10px;"></i> Add Product
                <?php endif; ?>
            </h2>

            <form action="" method="post" enctype="multipart/form-data">
                <?php if ($editMode): ?>
                    <input type="hidden" name="product_id" value="<?php echo $editProduct['id']; ?>">
                    <input type="hidden" name="old_image" value="<?php echo $editProduct['image_path']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="title" required placeholder="e.g. Ecstasy AI Core"
                        value="<?php echo $editMode ? htmlspecialchars($editProduct['title']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" required
                        placeholder="e.g. Unified AI automation engine..."><?php echo $editMode ? htmlspecialchars($editProduct['description']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $editMode ? 'Replace Image' : 'Product Image (Optional)'; ?></label>

                    <?php if ($editMode && $editProduct['image_path']): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="../assets/images/products/<?php echo $editProduct['image_path']; ?>"
                                style="height: 60px; border-radius: 4px;">
                            <span style="font-size: 12px; color: #8892b0; margin-left: 10px;">Current</span>
                        </div>
                    <?php endif; ?>

                    <input type="file" name="image" style="padding: 10px;">
                </div>

                <?php if ($editMode): ?>
                    <button type="submit" name="update_product" class="btn-submit"><i class="fas fa-save"></i> Update
                        Product</button>
                    <a href="products.php" class="btn-cancel">Cancel</a>
                <?php else: ?>
                    <button type="submit" name="add_product" class="btn-submit"><i class="fas fa-plus"></i> Add
                        Product</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2><i class="fas fa-list" style="color: var(--neon-accent); margin-right: 10px;"></i> Product List</h2>

            <?php if (count($products) > 0): ?>
                <div class="prod-list">
                    <?php foreach ($products as $prod): ?>
                        <div class="prod-item">
                            <?php if ($prod['image_path']): ?>
                                <img src="../assets/images/products/<?php echo htmlspecialchars($prod['image_path']); ?>" alt="Img"
                                    class="prod-img">
                            <?php else: ?>
                                <div class="prod-img"
                                    style="display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05); color: #8892b0;">
                                    <i class="fas fa-cube fa-2x"></i>
                                </div>
                            <?php endif; ?>

                            <div class="prod-content">
                                <div class="prod-title"><?php echo htmlspecialchars($prod['title']); ?></div>
                                <div class="prod-desc"><?php echo htmlspecialchars($prod['description']); ?></div>
                            </div>

                            <div class="prod-actions">
                                <a href="?edit=<?php echo $prod['id']; ?>" class="action-btn edit-btn" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?php echo $prod['id']; ?>" class="action-btn delete-btn"
                                    onclick="return confirm('Delete this product?')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #8892b0;">No products added yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>