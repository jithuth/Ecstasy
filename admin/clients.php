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
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Client deleted successfully!";
    }
}

// Handle Add
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $logo = '';

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
        $filename = $_FILES['logo']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array(strtolower($filetype), $allowed)) {
            $newFilename = 'client_' . time() . '.' . $filetype;
            $uploadPath = '../assets/images/clients/' . $newFilename;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                $logo = $newFilename;
            }
        }
    }

    if ($logo) {
        $stmt = $pdo->prepare("INSERT INTO clients (name, logo) VALUES (?, ?)");
        if ($stmt->execute([$name, $logo])) {
            $message = "Client added successfully!";
        }
    } else {
        $message = "Please upload a logo.";
    }
}

// Fetch Clients
$stmt = $pdo->query("SELECT * FROM clients");
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Clients - Admin</title>
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

        .delete-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1 style="color: var(--heading-color);">Manage Clients</h1>
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
            <a href="carousel.php">Carousel</a>
            <a href="clients.php" class="active">Clients</a>
        </div>

        <?php if ($message)
            echo '<div class="success-msg">' . $message . '</div>'; ?>

        <div class="card">
            <h2 style="color: var(--heading-color); margin-bottom: 20px;">Add Trusted Client</h2>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Client Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Logo</label>
                    <input type="file" name="logo" required>
                </div>
                <button type="submit" class="btn"
                    style="background: var(--secondary-color); color: var(--bg-color); font-weight: bold;">Add
                    Client</button>
            </form>
        </div>

        <div class="card">
            <h2 style="color: var(--heading-color); margin-bottom: 20px;">Current Clients</h2>
            <table>
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><img src="../assets/images/clients/<?php echo htmlspecialchars($client['logo']); ?>"
                                    height="50" style="background: #fff; padding: 5px; border-radius: 4px;"></td>
                            <td><?php echo htmlspecialchars($client['name']); ?></td>
                            <td><a href="?delete=<?php echo $client['id']; ?>" class="delete-btn"
                                    onclick="return confirm('Are you sure?')">Delete</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>