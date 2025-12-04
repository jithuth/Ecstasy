<nav class="admin-nav">
    <h3><i class="fas fa-cogs"></i> Admin Panel</h3>
    <ul>
        <li><a href="index.php" class="<?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
        <li><a href="services.php" class="<?php echo ($currentPage == 'services') ? 'active' : ''; ?>">Services</a></li>
        <li><a href="clients.php" class="<?php echo ($currentPage == 'clients') ? 'active' : ''; ?>">Clients</a></li>
        <li><a href="about.php" class="<?php echo ($currentPage == 'about') ? 'active' : ''; ?>">About</a></li>
        <li><a href="carousel.php" class="<?php echo ($currentPage == 'carousel') ? 'active' : ''; ?>">Carousel</a></li>
        <li><a href="seo.php" class="<?php echo ($currentPage == 'seo') ? 'active' : ''; ?>">SEO</a></li>
        <li><a href="messages.php" class="<?php echo ($currentPage == 'messages') ? 'active' : ''; ?>">Messages</a></li>
        <li><a href="careers.php" class="<?php echo ($currentPage == 'careers') ? 'active' : ''; ?>">Careers</a></li>
        <li><a href="applications.php"
                class="<?php echo ($currentPage == 'applications') ? 'active' : ''; ?>">Applications</a></li>
        <li><a href="analytics.php" class="<?php echo ($currentPage == 'analytics') ? 'active' : ''; ?>">Analytics</a>
        </li>
        <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></li>
    </ul>
</nav>