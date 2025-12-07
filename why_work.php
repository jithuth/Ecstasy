<?php
require_once 'includes/db.php';

// Fetch Images
$stmt = $pdo->query("SELECT * FROM why_work_images ORDER BY created_at DESC");
$images = $stmt->fetchAll();

$pageTitle = "Why Work With Us";
require_once 'includes/header.php';
?>

<style>
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 40px;
        padding-bottom: 50px;
    }

    .gallery-item {
        position: relative;
        overflow: hidden;
        border-radius: 12px;
        height: 300px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        transition: transform 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .gallery-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }

    .gallery-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .gallery-item:hover .gallery-img {
        transform: scale(1.05);
    }

    .gallery-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
        padding: 20px;
        color: #fff;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s ease;
    }

    .gallery-item:hover .gallery-overlay {
        opacity: 1;
        transform: translateY(0);
    }

    .gallery-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .gallery-desc {
        font-size: 14px;
        opacity: 0.9;
        line-height: 1.4;
    }
</style>

<section class="section" style="padding-top: 150px; min-height: 80vh;">
    <div class="container">
        <h1 style="font-size: 40px; color: var(--heading-color); margin-bottom: 20px; text-align: center;">Why Work With
            Us</h1>
        <p
            style="text-align: center; max-width: 700px; margin: 0 auto 50px auto; color: var(--text-color); font-size: 18px; line-height: 1.6;">
            We're building a culture that empowers, inspires, and innovates. See what life at Ecstasy Solutions looks
            like.
        </p>

        <?php if (count($images) > 0): ?>
            <div class="gallery-grid">
                <?php foreach ($images as $img): ?>
                    <div class="gallery-item">
                        <img src="assets/images/why_work/<?php echo htmlspecialchars($img['image_path']); ?>"
                            alt="<?php echo htmlspecialchars($img['title']); ?>" class="gallery-img">
                        <?php if ($img['title'] || $img['description']): ?>
                            <div class="gallery-overlay">
                                <?php if ($img['title']): ?>
                                    <div class="gallery-title"><?php echo htmlspecialchars($img['title']); ?></div>
                                <?php endif; ?>
                                <?php if ($img['description']): ?>
                                    <div class="gallery-desc"><?php echo htmlspecialchars($img['description']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div
                style="text-align: center; padding: 50px; background: var(--light-bg); border-radius: 12px; margin-top: 30px;">
                <p style="font-size: 18px; color: var(--text-color); margin-bottom: 20px;">We are currently curating our
                    gallery. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>