<?php
require_once 'includes/db.php';

// Fetch Products from DB
$stmt = $pdo->query("SELECT * FROM flagship_products ORDER BY created_at ASC");
$products = $stmt->fetchAll();

$pageTitle = "Flagship Products";
require_once 'includes/header.php';
?>

<style>
    .section-header {
        margin-bottom: 60px;
        text-align: center;
    }

    .section-header h1 {
        font-size: 48px;
        color: var(--heading-color);
        margin-bottom: 20px;
        background: linear-gradient(90deg, #64ffda, #0070f3);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: inline-block;
    }

    .section-header p {
        color: var(--text-color);
        font-size: 18px;
        max-width: 700px;
        margin: 0 auto;
        line-height: 1.6;
    }

    .products-grid {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 30px;
        margin-bottom: 50px;
    }

    .product-card {
        flex: 1 1 300px;
        /* Grow, Shrink, Basis */
        max-width: 350px;
        /* Prevent them from getting too wide */
        /* ... existing styles ... */

        background: var(--light-bg);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        padding: 40px 30px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        border-color: rgba(100, 255, 218, 0.3);
    }

    .product-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #64ffda, #0070f3);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.4s ease;
    }

    .product-card:hover::before {
        transform: scaleX(1);
    }

    .product-icon {
        width: 80px;
        height: 80px;
        background: rgba(100, 255, 218, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 25px;
        color: var(--secondary-color);
        font-size: 30px;
        transition: all 0.3s ease;
    }

    .product-card:hover .product-icon {
        background: var(--secondary-color);
        color: var(--bg-color);
        transform: scale(1.1) rotate(5deg);
    }

    .product-img-thumb {
        width: 100px;
        height: 100px;
        object-fit: contain;
        margin-bottom: 20px;
        filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.2));
        border-radius: 12px;
        /* Smooth corners for icons */
    }

    .product-title {
        font-size: 24px;
        font-weight: 700;
        color: var(--heading-color);
        margin-bottom: 15px;
    }

    .product-desc {
        color: var(--text-color);
        line-height: 1.6;
        font-size: 16px;
        flex-grow: 1;
    }

    .cta-container {
        text-align: center;
        margin-top: 60px;
        padding: 40px;
        background: linear-gradient(135deg, rgba(100, 255, 218, 0.05), rgba(0, 112, 243, 0.05));
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .cta-btn {
        display: inline-block;
        background: var(--secondary-color);
        color: var(--bg-color);
        padding: 15px 40px;
        font-weight: 700;
        border-radius: 50px;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-top: 20px;
        font-size: 18px;
    }

    .cta-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(100, 255, 218, 0.3);
    }
</style>

<section class="section" style="padding-top: 150px; min-height: 80vh;">
    <div class="container">

        <div class="section-header">
            <h1>Our Flagship Products</h1>
            <p>Pioneering the future with our suite of advanced AI and automation solutions designed for the next
                generation of industries.</p>
        </div>

        <div class="products-grid">
            <?php foreach ($products as $prod): ?>
                <div class="product-card">
                    <?php if ($prod['image_path']): ?>
                        <!-- If you have images, display them -->
                        <img src="assets/images/products/<?php echo htmlspecialchars($prod['image_path']); ?>"
                            alt="<?php echo htmlspecialchars($prod['title']); ?>" class="product-img-thumb">
                    <?php else: ?>
                        <!-- Fallback Icons based on Title Keywords (Optional smart icons) -->
                        <div class="product-icon">
                            <?php
                            $icon = 'fas fa-cube'; // Default
                            if (stripos($prod['title'], 'AI') !== false)
                                $icon = 'fas fa-brain';
                            elseif (stripos($prod['title'], 'Drone') !== false || stripos($prod['description'], 'Drone') !== false)
                                $icon = 'fas fa-fighter-jet';
                            elseif (stripos($prod['title'], 'Game') !== false || stripos($prod['title'], 'Play') !== false)
                                $icon = 'fas fa-gamepad';
                            elseif (stripos($prod['title'], 'Fin') !== false || stripos($prod['title'], 'Compliance') !== false)
                                $icon = 'fas fa-chart-line';
                            elseif (stripos($prod['title'], 'Smart') !== false || stripos($prod['title'], 'IoT') !== false)
                                $icon = 'fas fa-wifi';
                            elseif (stripos($prod['title'], 'Labs') !== false)
                                $icon = 'fas fa-flask';
                            ?>
                            <i class="<?php echo $icon; ?>"></i>
                        </div>
                    <?php endif; ?>

                    <h3 class="product-title"><?php echo htmlspecialchars($prod['title']); ?></h3>
                    <p class="product-desc"><?php echo htmlspecialchars($prod['description']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cta-container">
            <h2 style="color: var(--heading-color); margin-bottom: 15px;">Ready to Transform Your Business?</h2>
            <p style="color: var(--text-color); margin-bottom: 0;">Integrate our cutting-edge solutions today.</p>
            <a href="contact.php" class="cta-btn">Contact Us Now</a>
        </div>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>