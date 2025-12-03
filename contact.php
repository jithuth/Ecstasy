<?php
require_once 'includes/db.php';

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
                <form action="" method="post">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: var(--heading-color);">Name</label>
                        <input type="text"
                            style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid var(--text-color); background: var(--bg-color); color: var(--text-color);"
                            required>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: var(--heading-color);">Email</label>
                        <input type="email"
                            style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid var(--text-color); background: var(--bg-color); color: var(--text-color);"
                            required>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: var(--heading-color);">Message</label>
                        <textarea rows="5"
                            style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid var(--text-color); background: var(--bg-color); color: var(--text-color);"
                            required></textarea>
                    </div>
                    <button type="submit" class="btn" style="width: 100%;">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>