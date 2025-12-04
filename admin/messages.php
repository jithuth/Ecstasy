<?php
session_start();
require_once '../includes/db.php';

// Set Timezone to Indian Standard Time
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: messages.php");
    exit;
}

// Fetch Messages
$stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Messages - Admin</title>
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
            background: #0a192f; /* Darker background */
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

        /* Table */
        .card {
            background: var(--glass-bg);
            border: var(--glass-border);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: #8892b0;
        }

        th, td {
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #112240;
            margin: 10% auto;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            position: relative;
            animation: slideDown 0.3s ease-out;
            color: #8892b0;
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close {
            color: #8892b0;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover {
            color: var(--neon-accent);
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
            color: #ccd6f6;
            font-size: 22px;
        }

        .modal-body p {
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .modal-label {
            color: var(--neon-accent);
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .modal-value {
            color: #ccd6f6;
            font-size: 15px;
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
                <li><a href="carousel.php">Carousel</a></li>
                <li><a href="seo.php">SEO</a></li>
                <li><a href="messages.php" class="active">Messages</a></li>
                <li><a href="analytics.php">Analytics</a></li>
                <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul>
        </nav>

        <div class="admin-header">
            <h1>Messages</h1>
            <a href="../index.php" target="_blank" class="action-btn view-btn" style="padding: 8px 20px; font-size: 14px;">
                <i class="fas fa-external-link-alt"></i> View Site
            </a>
        </div>

        <div class="card">
            <?php if (count($messages) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                        <tr>
                            <td><?php echo date('d M Y, h:i A', strtotime($msg['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($msg['name']); ?></td>
                            <td><?php echo htmlspecialchars($msg['email']); ?></td>
                            <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                            <td>
                                <button class="action-btn view-btn" onclick='openModal(<?php echo json_encode($msg); ?>)'>
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <a href="?delete=<?php echo $msg['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this message?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; color: #8892b0;">No messages found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- The Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div class="modal-header">
                <h2>Message Details</h2>
            </div>
            <div class="modal-body">
                <p>
                    <span class="modal-label">Date</span>
                    <span class="modal-value" id="modalDate"></span>
                </p>
                <p>
                    <span class="modal-label">From</span>
                    <span class="modal-value" id="modalName"></span> 
                    (<a id="modalEmailLink" href="#" style="color: var(--neon-accent);"></a>)
                </p>
                <p>
                    <span class="modal-label">Subject</span>
                    <span class="modal-value" id="modalSubject"></span> <!-- Added Subject display -->
                </p>
                <p>
                    <span class="modal-label">Message</span>
                    <span class="modal-value" id="modalMessage" style="white-space: pre-wrap;"></span>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Get the modal
        var modal = document.getElementById("messageModal");

        // Function to open modal
        function openModal(message) {
            const options = {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true,
                timeZone: 'Asia/Kolkata'
            };

            document.getElementById("modalDate").textContent = new Date(message.created_at).toLocaleString('en-IN', options);
            document.getElementById("modalName").textContent = message.name;
            
            var emailLink = document.getElementById("modalEmailLink");
            emailLink.textContent = message.email;
            emailLink.href = "mailto:" + message.email;

            // Check if subject exists in message object (it should based on table structure)
            if(message.subject) {
                 document.getElementById("modalSubject").textContent = message.subject;
            } else {
                 document.getElementById("modalSubject").textContent = "No Subject";
            }

            document.getElementById("modalMessage").textContent = message.message;

            modal.style.display = "block";
        }

        // Function to close modal
        function closeModal() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>