<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

$message = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Service deleted successfully!";
    }
}

// Handle Add/Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $image = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array(strtolower($filetype), $allowed)) {
            $newFilename = 'service_' . time() . '.' . $filetype;
            $uploadPath = '../assets/images/services/' . $newFilename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $image = $newFilename;
            }
        }
    }

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $sql = "UPDATE services SET title = ?, description = ?";
        $params = [$title, $description];
        if ($image) {
            $sql .= ", image = ?";
            $params[] = $image;
        }
        $sql .= " WHERE id = ?";
        $params[] = $_POST['id'];

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $message = "Service updated successfully!";
        }
    } else {
        // Add
        $stmt = $pdo->prepare("INSERT INTO services (title, description, image) VALUES (?, ?, ?)");
        if ($stmt->execute([$title, $description, $image])) {
            $message = "Service added successfully!";
        }
    }
}

// Fetch all services
$stmt = $pdo->query("SELECT * FROM services ORDER BY created_at DESC");
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Services</title>
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
        }

        .edit-btn {
            background: var(--secondary-color);
            color: var(--bg-color);
            border: none;
        }

        .delete-btn {
            background: #ff6b6b;
            color: white;
            border: none;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1 style="color: var(--heading-color);">Manage Services</h1>
            <div>
                <a href="../index.php" target="_blank" class="btn" style="margin-right: 10px;">View Site</a>
                <a href="logout.php" class="btn" style="border-color: #ff6b6b; color: #ff6b6b;">Logout</a>
            </div>
        </div>

        <div class="admin-nav">
            <a href="index.php">General Settings</a>
            <a href="services.php" class="active">Manage Services</a>
        </div>

        <?php if ($message)
            echo '<div class="success-msg">' . $message . '</div>'; ?>

        <div class="card">
            <h2 style="color: var(--heading-color); margin-bottom: 20px;">Add New Service</h2>
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" id="service-id">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" id="service-title" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="service-description" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Image</label>
                    <input type="file" name="image">
                </div>
                <button type="submit" class="btn"
                    style="background: var(--secondary-color); color: var(--bg-color); font-weight: bold;">Save
                    Service</button>
                <button type="button" class="btn" onclick="resetForm()" style="margin-left: 10px;">Cancel</button>
            </form>
        </div>

        <div class="card">
            <h2 style="color: var(--heading-color); margin-bottom: 20px;">Existing Services</h2>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td>
                                <?php if ($service['image']): ?>
                                    <img src="../assets/images/services/<?php echo htmlspecialchars($service['image']); ?>"
                                        width="50" height="50" style="object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($service['title']); ?></td>
                            <td><?php echo htmlspecialchars(substr($service['description'], 0, 50)) . '...'; ?></td>
                            <td>
                                <button class="action-btn edit-btn"
                                    onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)">Edit</button>
                                <a href="?delete=<?php echo $service['id']; ?>" class="action-btn delete-btn"
                                    onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function editService(service) {
            document.getElementById('service-id').value = service.id;
            document.getElementById('service-title').value = service.title;
            document.getElementById('service-description').value = service.description;
            window.scrollTo(0, 0);
        }

        function resetForm() {
            document.getElementById('service-id').value = '';
            document.getElementById('service-title').value = '';
            document.getElementById('service-description').value = '';
        }
    </script>
</body>

</html>