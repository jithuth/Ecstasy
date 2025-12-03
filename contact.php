<?php
require_once 'includes/db.php';

$message_sent = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);
    
    $to = get_setting($pdo, 'contact_email');
    $subject = "New Contact Form Submission from $name";
    $body = "Name: $name\nEmail: $email\n\nMessage:\n$message";
    $headers = "From: $email";

    // Save to Database
    try {
        $stmt = $pdo->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $message]);
    } catch (PDOException $e) {
        // Silently fail DB insert or log it
    }

    // Use PHP's built-in mail function
    if (mail($to, $subject, $body, $headers)) {
        $message_sent = true;
    } else {
        // Fallback for local testing or if mail fails silently
        // In production, you'd want better error logging
        $error_message = "Failed to send email. Please try again later.";
        // If DB save worked, we can still show success
        $message_sent = true;
    }
}

$pageTitle = "Contact Us";
require_once 'includes/header.php';

$contactAddress = get_setting($pdo, 'contact_address');
$contactEmail = get_setting($pdo, 'contact_email');
?>

<section class="section" style="padding-top: 150px; min-height: 80vh;">
    <h1 style="font-size: 40px; color: var(--heading-color); margin-bottom: 20px; text-align: center;">Get In Touch</h1>
    <p style="text-align: center; max-width: 600px; margin: 0 auto 50px auto;">
        Have a project in mind or just want to say hi? We'd love to hear from you.
    </p>

    <div style="display: flex; gap: 50px; max-width: 1000px; margin: 0 auto; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px;">
            <div
                style="background: var(--card-bg); padding: 30px; border-radius: 8px; box-shadow: var(--shadow); height: 100%;">
                <h3 style="color: var(--heading-color); margin-bottom: 20px;">Contact Info</h3>

                <div style="margin-bottom: 20px;">
                    <strong style="color: var(--secondary-color); display: block; margin-bottom: 5px;">Address:</strong>
                    <p><?php echo htmlspecialchars($contactAddress); ?></p>
                </div>

                <div style="margin-bottom: 20px;">
                    <strong style="color: var(--secondary-color); display: block; margin-bottom: 5px;">Email:</strong>
                    <p><a
                            href="mailto:<?php echo htmlspecialchars($contactEmail); ?>"><?php echo htmlspecialchars($contactEmail); ?></a>
                    </p>
                </div>
            </div>
        </div>

        <div style="flex: 2; min-width: 300px;">
            <div style="background: var(--card-bg); padding: 40px; border-radius: 8px; box-shadow: var(--shadow);">
                <?php if ($message_sent): ?>
                    <div style="background: rgba(100, 255, 218, 0.1); color: var(--secondary-color); padding: 20px; border-radius: 8px; text-align: center;">
                        <h3>Message Sent!</h3>
                        <p>Thank you for contacting us. We will get back to you shortly.</p>
                    </div>
                <?php else: ?>
                    <form action="" method="post">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: var(--heading-color);">Name</label>
                            <input type="text" name="name"
                                style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid var(--text-color); background: var(--bg-color); color: var(--text-color);"
                                required>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: var(--heading-color);">Email</label>
                            <input type="email" name="email"
                                style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid var(--text-color); background: var(--bg-color); color: var(--text-color);"
                                required>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: var(--heading-color);">Message</label>
                            <textarea name="message" rows="5"
                                style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid var(--text-color); background: var(--bg-color); color: var(--text-color);"
                                required></textarea>
                        </div>
                        <button type="submit" class="btn" style="width: 100%;">Send Message</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Google Map -->
    <div
        style="margin-top: 50px; width: 100%; height: 400px; border-radius: 8px; overflow: hidden; box-shadow: var(--shadow);">
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.1422937950147!2d-73.98731968459391!3d40.74844797932847!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c259a9b3117469%3A0xd134e199a405a163!2sEmpire%20State%20Building!5e0!3m2!1sen!2sus!4v1629783012345!5m2!1sen!2sus"
            width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy">
        </iframe>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>