<?php
require_once 'includes/db.php';

$message = '';
$error = '';
$opening = null;

// Get Job ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM career_openings WHERE id = ? AND status = 'active'");
    $stmt->execute([$id]);
    $opening = $stmt->fetch();
}

if (!$opening) {
    header("Location: careers.php");
    exit;
}

// Handle Application Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apply'])) {
    $opening_id = $_POST['opening_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $whatsapp = $_POST['whatsapp'];

    // Check if email already exists for this opening
    $stmt = $pdo->prepare("SELECT id FROM career_applications WHERE opening_id = ? AND email = ?");
    $stmt->execute([$opening_id, $email]);

    if ($stmt->rowCount() > 0) {
        $error = "You have already applied for this position with this email address.";
    } else {
        // Handle Resume Upload
        $resume = '';
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
            $allowed = ['pdf', 'doc', 'docx'];
            $filename = $_FILES['resume']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                $newFilename = 'resume_' . time() . '_' . rand(1000, 9999) . '.' . $filetype;
                $uploadDir = 'assets/uploads/resumes/';

                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                if (move_uploaded_file($_FILES['resume']['tmp_name'], $uploadDir . $newFilename)) {
                    $resume = $newFilename;
                } else {
                    $error = "Failed to upload resume.";
                }
            } else {
                $error = "Invalid file type. Only PDF, DOC, and DOCX allowed.";
            }
        } else {
            $error = "Resume is required.";
        }

        if (!$error && $resume) {
            $stmt = $pdo->prepare("INSERT INTO career_applications (opening_id, name, email, phone, whatsapp, resume_path) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$opening_id, $name, $email, $phone, $whatsapp, $resume])) {
                $message = "Application submitted successfully! We will contact you soon.";
            } else {
                $error = "Database error. Please try again.";
            }
        }
    }
}

$pageTitle = $opening['title'] . " - Careers";
require_once 'includes/header.php';
?>

<style>
    .job-details-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    .job-header {
        margin-bottom: 40px;
        border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
        padding-bottom: 20px;
    }

    .job-title {
        color: var(--heading-color);
        font-size: 36px;
        margin-bottom: 10px;
    }

    .job-meta {
        color: var(--text-color);
        font-size: 14px;
        margin-bottom: 20px;
    }

    .job-description {
        color: var(--text-color);
        line-height: 1.8;
        font-size: 16px;
        margin-bottom: 40px;
    }

    .job-description h2,
    .job-description h3 {
        color: var(--heading-color);
        margin-top: 30px;
        margin-bottom: 15px;
    }

    .job-description ul {
        margin-bottom: 20px;
        padding-left: 20px;
    }

    .job-description li {
        margin-bottom: 10px;
    }

    .application-form-card {
        background: var(--card-bg, #112240);
        padding: 40px;
        border-radius: 12px;
        border: 1px solid var(--border-color, rgba(255, 255, 255, 0.05));
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .form-title {
        color: var(--heading-color);
        font-size: 24px;
        margin-bottom: 25px;
        text-align: center;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #ffffff;
        font-weight: 500;
    }

    [data-theme="light"] .form-group label {
        color: var(--heading-color);
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        background: var(--input-bg, #0a192f);
        border: 1px solid var(--border-color, #233554);
        color: var(--text-color, #ccd6f6);
        border-radius: 4px;
        transition: border-color 0.3s;
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--secondary-color, #64ffda);
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 25px;
        color: var(--text-color);
    }

    .checkbox-group input {
        width: auto;
    }

    .submit-btn {
        background: var(--secondary-color, #64ffda);
        color: var(--bg-color, #0a192f);
        padding: 15px 30px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 700;
        display: block;
        width: 100%;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 16px;
        text-align: center;
    }

    .submit-btn:hover {
        background: var(--primary-hover, #4cdbb3);
        transform: translateY(-2px);
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 30px;
        text-align: center;
    }

    .alert-success {
        background: rgba(100, 255, 218, 0.1);
        color: var(--secondary-color, #64ffda);
        border: 1px solid rgba(100, 255, 218, 0.2);
    }

    .alert-error {
        background: rgba(255, 107, 107, 0.1);
        color: #ff6b6b;
        border: 1px solid rgba(255, 107, 107, 0.2);
    }
</style>

<section class="section" style="padding-top: 150px; min-height: 100vh;">
    <div class="job-details-container">

        <div class="job-header">
            <h1 class="job-title"><?php echo htmlspecialchars($opening['title']); ?></h1>
            <div class="job-meta">
                <i class="far fa-clock"></i> Posted on <?php echo date('F d, Y', strtotime($opening['created_at'])); ?>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="job-description">
            <?php echo $opening['description']; ?>
        </div>

        <div class="application-form-card" id="apply-form">
            <h2 class="form-title">Apply for this Position</h2>

            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="opening_id" value="<?php echo $opening['id']; ?>">
                <input type="hidden" name="apply" value="1">

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="John Doe">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="john@example.com">
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" required placeholder="+1 234 567 8900">
                </div>

                <div class="form-group">
                    <label>WhatsApp Number (Optional)</label>
                    <input type="tel" name="whatsapp" placeholder="+1 234 567 8900">
                </div>

                <div class="form-group">
                    <label>Resume / CV (PDF, DOC, DOCX)</label>
                    <input type="file" name="resume" accept=".pdf,.doc,.docx" required>
                    <small style="color: var(--text-color); opacity: 0.7; display: block; margin-top: 5px;">Max file
                        size: 5MB</small>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="terms" required id="terms">
                    <label for="terms" style="margin: 0; cursor: pointer;">I agree to the Terms and Conditions and
                        Privacy Policy.</label>
                </div>

                <button type="submit" class="submit-btn">Submit Application</button>
            </form>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <a href="careers.php" style="color: var(--secondary-color); text-decoration: none; font-weight: 600;">
                <i class="fas fa-arrow-left"></i> Back to Careers
            </a>
        </div>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>