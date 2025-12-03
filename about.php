<?php
require_once 'includes/db.php';

// Fetch About Content
$aboutContent = get_setting($pdo, 'about_content');
$aboutImage = get_setting($pdo, 'about_image');

// Fetch Clients
$stmt = $pdo->query("SELECT * FROM clients");
$clients = $stmt->fetchAll();

$pageTitle = "About Us";
require_once 'includes/header.php';
?>

<section class="section" style="padding-top: 150px;">
    <h1 style="font-size: 40px; color: var(--heading-color); margin-bottom: 50px; text-align: center;">About Us</h1>

    <div class="about-container">
        <div class="about-text" style="font-size: 16px; line-height: 1.8;">
            <?php echo $aboutContent; ?>
        </div>
        <div class="about-image">
            <?php if ($aboutImage && file_exists("assets/images/about/" . $aboutImage)): ?>
                <img src="assets/images/about/<?php echo htmlspecialchars($aboutImage); ?>" alt="About Us">
            <?php else: ?>
                <div
                    style="width: 100%; height: 300px; background: var(--light-bg); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    No Image</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Trusted Clients Marquee -->
<?php if (count($clients) > 0): ?>
    <section class="section">
        <h2 class="section-title" style="justify-content: center; margin-bottom: 30px;">Trusted By</h2>
        <div class="marquee-container">
            <div class="marquee-content">
                <?php foreach ($clients as $client): ?>
                    <?php if (file_exists("assets/images/clients/" . $client['logo'])): ?>
                        <img src="assets/images/clients/<?php echo htmlspecialchars($client['logo']); ?>"
                            alt="<?php echo htmlspecialchars($client['name']); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <!-- Duplicate for seamless loop -->
                <?php foreach ($clients as $client): ?>
                    <?php if (file_exists("assets/images/clients/" . $client['logo'])): ?>
                        <img src="assets/images/clients/<?php echo htmlspecialchars($client['logo']); ?>"
                            alt="<?php echo htmlspecialchars($client['name']); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>