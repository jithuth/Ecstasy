<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

require_once '../includes/db.php';

$message = '';
$editMode = false;
$openingData = ['id' => '', 'title' => '', 'description' => '', 'status' => 'active'];

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM career_openings WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Opening deleted successfully!";
    }
}

// Handle Status Toggle
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE career_openings SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Status updated successfully!";
    }
}

// Handle Edit Fetch
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM career_openings WHERE id = ?");
    $stmt->execute([$id]);
    $fetchedOpening = $stmt->fetch();
    if ($fetchedOpening) {
        $editMode = true;
        $openingData = $fetchedOpening;
    }
}

// Handle Add/Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $id = $_POST['id'] ?? '';

    if (!empty($id)) {
        // Update
        $stmt = $pdo->prepare("UPDATE career_openings SET title = ?, description = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$title, $description, $status, $id])) {
            $message = "Opening updated successfully!";
            $editMode = false;
            $openingData = ['id' => '', 'title' => '', 'description' => '', 'status' => 'active'];
        }
    } else {
        // Add
        $stmt = $pdo->prepare("INSERT INTO career_openings (title, description, status) VALUES (?, ?, ?)");
        if ($stmt->execute([$title, $description, $status])) {
            $message = "Opening added successfully!";
        }
    }
}

// Fetch Openings
$stmt = $pdo->query("SELECT * FROM career_openings ORDER BY created_at DESC");
$openings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Careers - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: 'textarea[name="description"]',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            skin: 'oxide-dark',
            content_css: 'dark'
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
        .form-group select {
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
        .form-group select:focus {
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

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: rgba(100, 255, 218, 0.1);
            color: var(--neon-accent);
        }

        .status-inactive {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
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
        <?php $currentPage = 'careers';
        include 'header.php'; ?>

        <div class="admin-header">
            <h1>Manage Job Openings</h1>
            <a href="../careers.php" target="_blank" class="btn">
                <i class="fas fa-external-link-alt"></i> View Page
            </a>
        </div>

        <?php if ($message): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><i class="fas fa-<?php echo $editMode ? 'edit' : 'plus-circle'; ?>"
                    style="color: var(--neon-accent); margin-right: 10px;"></i>
                <?php echo $editMode ? 'Edit Opening' : 'Add New Opening'; ?>
            </h2>
            <form action="" method="post">
                <input type="hidden" name="id" value="<?php echo $openingData['id']; ?>">
                <div class="form-group">
                    <label>Job Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($openingData['title']); ?>"
                        required>
                </div>
                <div class="form-group">
                    <label>Job Description</label>
                    <textarea name="description"
                        rows="10"><?php echo htmlspecialchars($openingData['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active" <?php echo $openingData['status'] == 'active' ? 'selected' : ''; ?>>Active
                        </option>
                        <option value="inactive" <?php echo $openingData['status'] == 'inactive' ? 'selected' : ''; ?>>
                            Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> <?php echo $editMode ? 'Update Opening' : 'Post Opening'; ?>
                </button>
                <?php if ($editMode): ?>
                    <a href="careers.php" class="btn"
                        style="margin-top: 10px; width: 100%; text-align: center; border-color: #8892b0; color: #8892b0;">Cancel
                        Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2><i class="fas fa-list" style="color: var(--neon-accent); margin-right: 10px;"></i> Current Openings</h2>
            <?php if (count($openings) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Posted Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($openings as $opening): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($opening['title']); ?></td>
                                <td>
                                    <span
                                        class="status-badge <?php echo $opening['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo ucfirst($opening['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($opening['created_at'])); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $opening['id']; ?>" class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?toggle=<?php echo $opening['id']; ?>" class="action-btn edit-btn"
                                        style="border-color: #8892b0; color: #8892b0;">
                                        <i class="fas fa-power-off"></i>
                                        <?php echo $opening['status'] == 'active' ? 'Disable' : 'Enable'; ?>
                                    </a>
                                    <a href="?delete=<?php echo $opening['id']; ?>" class="action-btn delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this opening?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; color: #8892b0;">No job openings found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>