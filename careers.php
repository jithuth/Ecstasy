<?php
require_once 'includes/db.php';

// Fetch Active Openings
$stmt = $pdo->query("SELECT * FROM career_openings WHERE status = 'active' ORDER BY created_at DESC");
$openings = $stmt->fetchAll();

$pageTitle = "Careers";
require_once 'includes/header.php';
?>

<style>
    .careers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-top: 40px;
        justify-content: center;
    }

    .career-card {
        background: var(--light-bg);
        padding: 30px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
        text-align: center;
        align-items: center;
    }

    .career-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .career-title {
        color: var(--heading-color);
        font-size: 22px;
        margin-bottom: 15px;
        font-weight: 700;
    }

    .career-excerpt {
        color: var(--text-color);
        margin-bottom: 20px;
        line-height: 1.6;
        flex-grow: 1;
        font-size: 15px;
        /* Limit text to 3 lines */
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .apply-btn {
        background: var(--secondary-color);
        color: var(--bg-color);
        padding: 12px 25px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 600;
        display: block;
        text-align: center;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: auto;
    }

    .apply-btn:hover {
        background: #4cdbb3;
        /* Lighter shade/Hover state */
        transform: translateY(-2px);
        color: var(--bg-color);
    }
</style>

<section class="section" style="padding-top: 150px; min-height: 80vh;">
    <div class="container">
        <h1 style="font-size: 40px; color: var(--heading-color); margin-bottom: 20px; text-align: center;">Join Our Team
        </h1>
        <p style="text-align: center; max-width: 600px; margin: 0 auto 50px auto; color: var(--text-color);">
            We are always looking for talented individuals to join our growing team. Check out our current openings
            below.
        </p>

        <div class="careers-grid">
            <?php if (count($openings) > 0): ?>
                <?php foreach ($openings as $opening): ?>
                    <div class="career-card">
                        <h3 class="career-title"><?php echo htmlspecialchars($opening['title']); ?></h3>
                        <div class="career-excerpt">
                            <?php
                            // Strip tags to show plain text excerpt
                            echo strip_tags($opening['description']);
                            ?>
                        </div>
                        <a href="job_details.php?id=<?php echo $opening['id']; ?>" class="apply-btn">
                            View Details & Apply
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center;">
                    <p style="font-size: 18px; color: var(--text-color);">No current openings. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>