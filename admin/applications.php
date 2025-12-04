<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

require_once '../includes/db.php';

$message = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Get resume path to delete file
    $stmt = $pdo->prepare("SELECT resume_path FROM career_applications WHERE id = ?");
    $stmt->execute([$id]);
    $app = $stmt->fetch();

    if ($app) {
        $filePath = '../assets/uploads/resumes/' . $app['resume_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $stmt = $pdo->prepare("DELETE FROM career_applications WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "Application deleted successfully!";
        }
    }
}

// Fetch Applications
$stmt = $pdo->query("
    SELECT a.*, o.title as job_title 
    FROM career_applications a 
    JOIN career_openings o ON a.opening_id = o.id 
    ORDER BY a.created_at DESC
");
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applications - Admin</title>
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

        .view-btn {
            background: rgba(100, 255, 218, 0.1);
            color: var(--neon-accent);
            border: 1px solid rgba(100, 255, 218, 0.2);
        }

        .view-btn:hover {
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
        <?php $currentPage = 'applications'; include 'header.php'; ?>

        <div class="admin-header">
            <h1>Received Applications</h1>
        </div>

        <?php if ($message): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><i class="fas fa-list" style="color: var(--neon-accent); margin-right: 10px;"></i> Applications List
            </h2>
            <?php if (count($applications) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Applicant</th>
                                <th>Job Title</th>
                                <th>Contact</th>
                                <th>Resume</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                                    <td>
                                        <div style="font-weight: 600; color: #ccd6f6;">
                                            <?php echo htmlspecialchars($app['name']); ?></div>
                                        <div style="font-size: 12px;"><?php echo htmlspecialchars($app['email']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                    <td>
                                        <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($app['phone']); ?></div>
                                        <?php if ($app['whatsapp']): ?>
                                            <div><i class="fab fa-whatsapp" style="color: #25D366;"></i>
                                                <?php echo htmlspecialchars($app['whatsapp']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="../assets/uploads/resumes/<?php echo htmlspecialchars($app['resume_path']); ?>"
                                            target="_blank" class="action-btn view-btn">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                    <td>
                                        <a href="?delete=<?php echo $app['id']; ?>" class="action-btn delete-btn"
                                            onclick="return confirm('Are you sure you want to delete this application?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; color: #8892b0;">No applications received yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>