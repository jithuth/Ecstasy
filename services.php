<?php
require_once 'includes/db.php';

// Fetch all services
$stmt = $pdo->query("SELECT * FROM services ORDER BY created_at DESC");
$services = $stmt->fetchAll();

$pageTitle = "Services";
require_once 'includes/header.php';
?>

<section class="section" style="padding-top: 150px;">
    <h1 style="font-size: 40px; color: var(--heading-color); margin-bottom: 20px; text-align: center;">Our Services</h1>
    <p style="text-align: center; max-width: 600px; margin: 0 auto 50px auto;">
        We offer a wide range of software development services to help you achieve your business goals.
    </p>

    <div class="services-grid">
        <?php foreach ($services as $service): ?>
            <div class="service-card">
                <?php if ($service['image'] && file_exists("assets/images/services/" . $service['image'])): ?>
                    <img src="assets/images/services/<?php echo htmlspecialchars($service['image']); ?>"
                        alt="<?php echo htmlspecialchars($service['title']); ?>">
                <?php else: ?>
                    <div
                        style="width: 100%; height: 200px; background: var(--bg-color); margin-bottom: 20px; display: flex; align-items: center; justify-content: center;">
                        No Image</div>
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                <p><?php echo htmlspecialchars($service['description']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>