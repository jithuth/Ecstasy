<?php
require_once 'includes/db.php';

// Fetch Carousel Slides
$stmt = $pdo->query("SELECT * FROM carousel ORDER BY sort_order ASC");
$slides = $stmt->fetchAll();

// Fetch About Content
$aboutContent = get_setting($pdo, 'about_content');
$aboutImage = get_setting($pdo, 'about_image');

// Fetch Clients
$stmt = $pdo->query("SELECT * FROM clients");
$clients = $stmt->fetchAll();

// Fetch Services
$stmt = $pdo->query("SELECT * FROM services ORDER BY created_at DESC LIMIT 3");
$services = $stmt->fetchAll();

$pageTitle = "Home";
require_once 'includes/header.php';
?>

<!-- Carousel Section -->
<?php if (count($slides) > 0): ?>
    <section id="home" class="carousel">
        <?php foreach ($slides as $index => $slide): ?>
            <div class="carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>"
                style="background-image: url('assets/images/carousel/<?php echo htmlspecialchars($slide['image']); ?>');">
                <div class="carousel-overlay"></div>
                <div class="carousel-content">
                    <h1><?php echo htmlspecialchars($slide['title']); ?></h1>
                    <h2><?php echo htmlspecialchars($slide['subtitle']); ?></h2>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="carousel-nav">
            <?php foreach ($slides as $index => $slide): ?>
                <div class="carousel-dot <?php echo $index === 0 ? 'active' : ''; ?>"
                    onclick="goToSlide(<?php echo $index; ?>)"></div>
            <?php endforeach; ?>
        </div>
    </section>
<?php else: ?>
    <!-- Fallback Hero if no slides -->
    <section id="home" class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1><?php echo htmlspecialchars(get_setting($pdo, 'hero_title')); ?></h1>
                <h2><?php echo htmlspecialchars(get_setting($pdo, 'hero_subtitle')); ?></h2>
                <p><?php echo htmlspecialchars(get_setting($pdo, 'hero_description')); ?></p>
            </div>
        </div>
    </section>
<?php endif; ?>

<section id="services" class="section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 50px;">
        <h2 class="section-title" style="margin-bottom: 0;">Latest Services</h2>
        <a href="services.php" style="color: var(--secondary-color);">View All -></a>
    </div>

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

<section id="about" class="section">
    <h2 class="section-title">About Us</h2>
    <div class="about-container">
        <div class="about-text">
            <?php echo $aboutContent; // Outputting HTML directly as it's from rich text editor ?>
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
    <section class="section" style="padding-top: 0;">
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

<section id="contact" class="section" style="text-align: center; padding-bottom: 150px;">
    <p style="color: var(--secondary-color); margin-bottom: 20px;">What's Next?</p>
    <h2 style="font-size: 50px; color: var(--heading-color); margin-bottom: 20px;">Get In Touch</h2>
    <p style="max-width: 600px; margin: 0 auto 50px auto;">
        Although we're currently looking for new opportunities, our inbox is always open. Whether you have a question or
        just want to say hi, we'll try our best to get back to you!
    </p>
    <a href="mailto:<?php echo htmlspecialchars(get_setting($pdo, 'contact_email')); ?>" class="btn">Say Hello</a>
</section>

<script>
    // Carousel Logic
    const slides = document.querySelectorAll('.carousel-slide');
    const dots = document.querySelectorAll('.carousel-dot');
    let currentSlide = 0;
    const slideInterval = 5000; // 5 seconds

    function goToSlide(n) {
        slides[currentSlide].classList.remove('active');
        dots[currentSlide].classList.remove('active');
        currentSlide = (n + slides.length) % slides.length;
        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
    }

    function nextSlide() {
        goToSlide(currentSlide + 1);
    }

    if (slides.length > 0) {
        setInterval(nextSlide, slideInterval);
    }
</script>

<?php require_once 'includes/footer.php'; ?>