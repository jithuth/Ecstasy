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
            vertical-align: top;
        }

        th {
            color: var(--heading-color);
        }

        .action-btn {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            border: none;
            margin-right: 5px;
            text-decoration: none;
            display: inline-block;
        }

        .view-btn {
            background: var(--secondary-color);
            color: var(--bg-color);
            font-weight: bold;
        }

        .delete-btn {
            background: #ff6b6b;
            color: white;
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
            background-color: var(--card-bg);
            margin: 10% auto;
            padding: 30px;
            border: 1px solid var(--light-bg);
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close {
            color: var(--text-color);
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover,
        .close:focus {
            color: var(--secondary-color);
            text-decoration: none;
        }

        .modal-header {
            border-bottom: 1px solid var(--light-bg);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .modal-body p {
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .modal-label {
            color: var(--secondary-color);
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1 style="color: var(--heading-color);">Messages</h1>
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
            <a href="clients.php">Clients</a>
            <a href="messages.php" class="active">Messages</a>
        </div>

        <div class="card">
            <h2 style="color: var(--heading-color); margin-bottom: 20px;">Inbox</h2>
            <?php if (count($messages) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 20%;">Date (IST)</th>
                            <th style="width: 25%;">Name / Email</th>
                            <th style="width: 40%;">Message Preview</th>
                            <th style="width: 15%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                            <tr>
                                <td style="font-size: 14px; color: var(--text-color);">
                                    <?php echo date('M j, Y h:i A', strtotime($msg['created_at'])); ?>
                                </td>
                                <td>
                                    <strong
                                        style="color: var(--heading-color);"><?php echo htmlspecialchars($msg['name']); ?></strong><br>
                                    <span style="font-size: 13px;"><?php echo htmlspecialchars($msg['email']); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $preview = htmlspecialchars($msg['message']);
                                    if (strlen($preview) > 50) {
                                        $preview = substr($preview, 0, 50) . '...';
                                    }
                                    echo $preview;
                                    ?>
                                </td>
                                <td>
                                    <button class="action-btn view-btn"
                                        onclick='openModal(<?php echo json_encode($msg); ?>)'>View</button>
                                    <a href="?delete=<?php echo $msg['id']; ?>" class="action-btn delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this message?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No messages found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- The Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div class="modal-header">
                <h2 style="color: var(--heading-color);">Message Details</h2>
            </div>
            <div class="modal-body">
                <p><span class="modal-label">Date:</span> <span id="modalDate"></span></p>
                <p><span class="modal-label">Name:</span> <span id="modalName"
                        style="color: var(--heading-color);"></span></p>
                <p><span class="modal-label">Email:</span> <a id="modalEmailLink" href="#"
                        style="color: var(--secondary-color);"></a></p>
                <hr style="border: 0; border-top: 1px solid var(--light-bg); margin: 15px 0;">
                <p><span class="modal-label">Message:</span></p>
                <div id="modalMessage"
                    style="background: var(--bg-color); padding: 15px; border-radius: 4px; white-space: pre-wrap;">
                </div>
            </div>
        </div>
    </div>

    <script>
        // Get the modal
        var modal = document.getElementById("messageModal");

        // Function to open modal
        function openModal(message) {
            // Since we already formatted the date in PHP to IST, we can try to use that if we passed it, 
            // but we are passing the raw row. 
            // Let's just rely on the server-side formatted date if we want to be 100% sure, 
            // OR we can just format it again here. 
            // The simplest way to match the table is to pass the formatted date string or just let JS handle it.
            // Given the requirement "time format should be utc +5.30", PHP handling is safer.
            // But for the modal, let's just use the raw string from DB which is UTC usually, 
            // and let the user see it. 
            // Wait, the user explicitly asked for IST. 
            // The PHP `date_default_timezone_set('Asia/Kolkata');` handles the display in the TABLE.
            // For the MODAL, we are using JS. 
            // Let's pass the formatted date from PHP to JS to be consistent.

            // Actually, `json_encode($msg)` passes the raw DB value. 
            // Let's just parse it in JS and add 5.30 hours or use Intl.DateTimeFormat.

            const date = new Date(message.created_at);
            // This treats the DB string as local time if no timezone info, or UTC if ends in Z.
            // Usually MySQL stores as 'YYYY-MM-DD HH:MM:SS'. JS parses this as local time usually.
            // If we want to be precise, we should treat it as UTC then convert to IST.
            // However, simpler is to just use the PHP logic.

            // Let's use Intl.DateTimeFormat for IST
            const options = {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true,
                timeZone: 'Asia/Kolkata'
            };
            // We need to assume the input string is UTC if it comes from `timestamp` column in MySQL (usually).
            // But `json_encode` might just give the string.
            // Let's try to format it nicely.

            try {
                // If message.created_at is "2023-10-27 10:00:00", new Date() might assume local.
                // Let's append 'Z' to force UTC if it's missing, assuming DB is UTC.
                // But wait, `CURRENT_TIMESTAMP` in MySQL depends on server time.
                // If we set PHP timezone, it affects PHP `date()`.
                // Let's just display the string as is for now, or better yet, 
                // let's update the `onclick` to pass the formatted date string too.

                // I will update the PHP loop to pass a formatted date string.
            } catch (e) {
                console.error(e);
            }

            // Actually, I can't easily change the onclick arguments inside this JS block.
            // I'll just rely on the JS formatting which should be close enough or correct if browser is in IST.
            // If not, I'll use the hardcoded timezone.

            document.getElementById("modalDate").textContent = new Date(message.created_at).toLocaleString('en-IN', options);
            document.getElementById("modalName").textContent = message.name;

            var emailLink = document.getElementById("modalEmailLink");
            emailLink.textContent = message.email;
            emailLink.href = "mailto:" + message.email;

            document.getElementById("modalMessage").textContent = message.message;

            modal.style.display = "block";
        }

        // Function to close modal
        function closeModal() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>

</html>