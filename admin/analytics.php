<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

require_once '../includes/db.php';

// Helper for date range (Default: Last 30 days)
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;
$startDate = date('Y-m-d H:i:s', strtotime("-$days days"));

// 1. Fetch Overview Stats
// Total Visitors
$stmt = $pdo->prepare("SELECT COUNT(*) FROM analytics_visitors WHERE created_at >= ?");
$stmt->execute([$startDate]);
$totalVisitors = $stmt->fetchColumn();

// Total Page Views
$stmt = $pdo->prepare("SELECT COUNT(*) FROM analytics_pageviews WHERE created_at >= ?");
$stmt->execute([$startDate]);
$totalPageViews = $stmt->fetchColumn();

// Live Users (Active in last 5 minutes)
// We count unique visitor_ids from pageviews in the last 5 minutes
$stmt = $pdo->query("SELECT COUNT(DISTINCT visitor_id) FROM analytics_pageviews WHERE created_at >= NOW() - INTERVAL 5 MINUTE");
$liveUsers = $stmt->fetchColumn();

// Live Users Breakdown (Mobile vs Desktop)
$stmt = $pdo->query("
    SELECT v.device_type, COUNT(DISTINCT p.visitor_id) as count
    FROM analytics_pageviews p
    JOIN analytics_visitors v ON p.visitor_id = v.id
    WHERE p.created_at >= NOW() - INTERVAL 5 MINUTE
    GROUP BY v.device_type
");
$liveStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$liveDesktop = $liveStats['Desktop'] ?? 0;
$liveMobile = ($liveStats['Mobile'] ?? 0) + ($liveStats['Tablet'] ?? 0);

// 2. Fetch Chart Data (Visitors per Day)
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM analytics_pageviews 
    WHERE created_at >= ? 
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
");
$stmt->execute([$startDate]);
$chartData = $stmt->fetchAll();

$dates = [];
$counts = [];
foreach ($chartData as $row) {
    $dates[] = $row['date'];
    $counts[] = $row['count'];
}

// 3. Fetch Top Pages
$stmt = $pdo->prepare("
    SELECT page_title, page_url, COUNT(*) as views 
    FROM analytics_pageviews 
    WHERE created_at >= ? 
    GROUP BY page_url 
    ORDER BY views DESC 
    LIMIT 10
");
$stmt->execute([$startDate]);
$topPages = $stmt->fetchAll();

// 4. Fetch Top Countries
$stmt = $pdo->prepare("
    SELECT country, COUNT(*) as count 
    FROM analytics_visitors 
    WHERE created_at >= ? 
    GROUP BY country 
    ORDER BY count DESC 
    LIMIT 5
");
$stmt->execute([$startDate]);
$topCountries = $stmt->fetchAll();

// 5. Fetch Device Stats
$stmt = $pdo->prepare("
    SELECT device_type, COUNT(*) as count 
    FROM analytics_visitors 
    WHERE created_at >= ? 
    GROUP BY device_type 
    ORDER BY count DESC
");
$stmt->execute([$startDate]);
$deviceStats = $stmt->fetchAll();

$deviceLabels = [];
$deviceCounts = [];
foreach ($deviceStats as $row) {
    $deviceLabels[] = $row['device_type'];
    $deviceCounts[] = $row['count'];
}

// 6. Fetch OS Stats
$stmt = $pdo->prepare("
    SELECT os, COUNT(*) as count 
    FROM analytics_visitors 
    WHERE created_at >= ? 
    GROUP BY os 
    ORDER BY count DESC
");
$stmt->execute([$startDate]);
$osStats = $stmt->fetchAll();

$osLabels = [];
$osCounts = [];
foreach ($osStats as $row) {
    $osLabels[] = $row['os'];
    $osCounts[] = $row['count'];
}

// Export Logic
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="analytics_report.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Page Title', 'URL', 'Views']);
    foreach ($topPages as $page) {
        fputcsv($output, [$page['page_title'], $page['page_url'], $page['views']]);
    }
    fclose($output);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --glass-bg: rgba(17, 34, 64, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.1);
            --neon-accent: #64ffda;
        }

        body {
            background-color: #020c1b;
            background-image: radial-gradient(circle at 10% 20%, rgba(100, 255, 218, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(0, 112, 243, 0.05) 0%, transparent 20%);
            min-height: 100vh;
            color: #8892b0;
            font-family: 'Inter', sans-serif;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Navigation */
        .admin-nav {
            background: #0a192f; /* Darker background */
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 15px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }

        .admin-nav h3 {
            color: var(--neon-accent);
            font-size: 18px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }

        .admin-nav ul {
            display: flex;
            gap: 10px;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .admin-nav a {
            color: #8892b0;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 14px;
            text-decoration: none;
        }

        .admin-nav a:hover {
            color: var(--neon-accent);
            background: rgba(100, 255, 218, 0.05);
        }

        .admin-nav a.active {
            background: rgba(100, 255, 218, 0.1);
            color: var(--neon-accent);
            border: 1px solid rgba(100, 255, 218, 0.2);
        }
        
        .logout-btn {
             color: #ff6b6b !important;
             padding: 8px 12px !important;
        }
        
        .logout-btn:hover {
            background: rgba(255, 107, 107, 0.1) !important;
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-actions h1 {
            font-size: 28px;
            color: var(--heading-color);
            margin: 0;
        }

        .date-filters {
            background: var(--glass-bg);
            padding: 5px;
            border-radius: 8px;
            border: var(--glass-border);
            display: flex;
            gap: 5px;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 6px;
            color: var(--text-color);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .filter-btn.active,
        .filter-btn:hover {
            background: var(--neon-accent);
            color: #020c1b;
            font-weight: 600;
        }

        .export-btn {
            background: transparent;
            border: 1px solid var(--neon-accent);
            color: var(--neon-accent);
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .export-btn:hover {
            background: rgba(100, 255, 218, 0.1);
            transform: translateY(-2px);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--glass-bg);
            border: var(--glass-border);
            padding: 25px;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border-color: rgba(100, 255, 218, 0.3);
        }

        .stat-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            color: rgba(136, 146, 176, 0.2);
            transition: all 0.3s;
        }

        .stat-card:hover .stat-icon {
            color: var(--neon-accent);
            transform: scale(1.1);
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--heading-color);
            margin: 10px 0 5px;
        }

        .stat-label {
            color: var(--text-color);
            font-size: 14px;
            font-weight: 500;
        }

        .trend-indicator {
            font-size: 12px;
            color: var(--neon-accent);
            margin-top: 10px;
            display: inline-block;
            background: rgba(100, 255, 218, 0.1);
            padding: 2px 8px;
            border-radius: 10px;
        }
        
        /* Live Indicator */
        .live-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: #ff6b6b;
            border-radius: 50%;
            margin-right: 5px;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(255, 107, 107, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 107, 107, 0); }
        }

        /* Charts & Tables */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        @media (max-width: 1024px) {
            .dashboard-grid, .charts-row {
                grid-template-columns: 1fr;
            }
        }

        .chart-container {
            background: var(--glass-bg);
            border: var(--glass-border);
            padding: 25px;
            border-radius: 16px;
            height: 400px;
            position: relative;
            width: 100%;
        }
        
        .donut-container {
            background: var(--glass-bg);
            border: var(--glass-border);
            padding: 25px;
            border-radius: 16px;
            height: 350px;
            position: relative;
            width: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .donut-wrapper {
            flex: 1;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .table-container {
            background: var(--glass-bg);
            border: var(--glass-border);
            padding: 25px;
            border-radius: 16px;
            height: 100%;
            overflow: hidden;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h3 {
            color: var(--heading-color);
            font-size: 18px;
            margin: 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            text-align: left;
            padding: 12px;
            color: var(--text-color);
            font-size: 13px;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .data-table td {
            padding: 15px 12px;
            color: var(--heading-color);
            font-size: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .page-url {
            font-size: 12px;
            color: var(--text-color);
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Responsive Nav */
        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .admin-nav ul {
                flex-wrap: wrap;
                gap: 10px;
            }

            .header-actions {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Navigation -->
        <nav class="admin-nav">
            <h3><i class="fas fa-chart-line"></i> Analytics</h3>
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="clients.php">Clients</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="carousel.php">Carousel</a></li>
                <li><a href="seo.php">SEO</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="analytics.php" class="active">Analytics</a></li>
                <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul>
        </nav>

        <!-- Header -->
        <div class="header-actions">
            <div>
                <h1>Dashboard Overview</h1>
                <p style="color: var(--text-color); margin-top: 5px;">Track your website performance and growth.</p>
            </div>
            <div style="display: flex; gap: 15px; align-items: center;">
                <div class="date-filters">
                    <a href="?days=7" class="filter-btn <?php echo $days == 7 ? 'active' : ''; ?>">7 Days</a>
                    <a href="?days=30" class="filter-btn <?php echo $days == 30 ? 'active' : ''; ?>">30 Days</a>
                </div>
                <a href="?export=csv" class="export-btn">
                    <i class="fas fa-download"></i> Export
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users stat-icon"></i>
                <div class="stat-label">Total Visitors</div>
                <div class="stat-number"><?php echo number_format($totalVisitors); ?></div>
                <div class="trend-indicator"><i class="fas fa-arrow-up"></i> Unique Users</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-eye stat-icon"></i>
                <div class="stat-label">Page Views</div>
                <div class="stat-number"><?php echo number_format($totalPageViews); ?></div>
                <div class="trend-indicator"><i class="fas fa-chart-bar"></i> Total Impressions</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-bolt stat-icon" style="color: #ff6b6b;"></i>
                <div class="stat-label">Live Users</div>
                <div class="stat-number">
                    <span class="live-dot"></span> <?php echo number_format($liveUsers); ?>
                </div>
                <div class="trend-indicator" style="color: #ff6b6b; background: rgba(255, 107, 107, 0.1);">
                    <span style="margin-right: 10px;"><i class="fas fa-desktop"></i> Web: <?php echo $liveDesktop; ?></span>
                    <span><i class="fas fa-mobile-alt"></i> Mobile: <?php echo $liveMobile; ?></span>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="dashboard-grid">
            <!-- Main Chart -->
            <div class="chart-container">
                <div class="section-header">
                    <h3><i class="fas fa-chart-area" style="color: var(--neon-accent); margin-right: 10px;"></i> Traffic
                        Overview</h3>
                </div>
                <canvas id="trafficChart"></canvas>
            </div>

            <!-- Top Countries (Side Panel) -->
            <div class="table-container">
                <div class="section-header">
                    <h3><i class="fas fa-globe" style="color: #ffbd2e; margin-right: 10px;"></i> Top Countries</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th>Visitors</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($topCountries) > 0): ?>
                                <?php foreach ($topCountries as $country): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600; color: var(--heading-color);">
                                                <?php echo htmlspecialchars($country['country']); ?>
                                            </div>
                                        </td>
                                        <td style="color: var(--neon-accent); font-weight: bold;">
                                            <?php echo number_format($country['count']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" style="text-align: center; padding: 20px;">No data yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Device & OS Charts Row -->
        <div class="charts-row">
            <!-- Device Chart -->
            <div class="donut-container">
                <div class="section-header">
                    <h3><i class="fas fa-mobile-alt" style="color: #ff6b6b; margin-right: 10px;"></i> Device Breakdown</h3>
                </div>
                <div class="donut-wrapper">
                    <canvas id="deviceChart"></canvas>
                </div>
            </div>
            
            <!-- OS Chart -->
            <div class="donut-container">
                <div class="section-header">
                    <h3><i class="fas fa-desktop" style="color: #4cc9f0; margin-right: 10px;"></i> Operating Systems</h3>
                </div>
                <div class="donut-wrapper">
                    <canvas id="osChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Pages (Full Width) -->
        <div class="table-container" style="margin-bottom: 30px;">
            <div class="section-header">
                <h3><i class="fas fa-file-alt" style="color: #64ffda; margin-right: 10px;"></i> Most Visited Pages</h3>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 60%;">Page Title / URL</th>
                            <th style="width: 20%;">Views</th>
                            <th style="width: 20%;">Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($topPages) > 0): ?>
                            <?php
                            $maxViews = $topPages[0]['views']; // For progress bar
                            foreach ($topPages as $page):
                                $percent = ($page['views'] / $maxViews) * 100;
                                ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; font-size: 15px;">
                                            <?php echo htmlspecialchars($page['page_title']); ?>
                                        </div>
                                        <div class="page-url"><?php echo htmlspecialchars($page['page_url']); ?></div>
                                    </td>
                                    <td style="font-size: 16px; font-weight: bold;"><?php echo number_format($page['views']); ?>
                                    </td>
                                    <td>
                                        <div
                                            style="background: rgba(255,255,255,0.1); height: 6px; border-radius: 3px; width: 100%;">
                                            <div
                                                style="background: var(--neon-accent); height: 100%; border-radius: 3px; width: <?php echo $percent; ?>%;">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 20px;">No page views recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        // Common Chart Options
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#8892b0',
                        font: { family: "'Inter', sans-serif" },
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 34, 64, 0.9)',
                    titleColor: '#ccd6f6',
                    bodyColor: '#8892b0',
                    borderColor: 'rgba(100, 255, 218, 0.3)',
                    borderWidth: 1,
                    padding: 10
                }
            }
        };

        // Traffic Chart
        const ctx = document.getElementById('trafficChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(100, 255, 218, 0.4)');
        gradient.addColorStop(1, 'rgba(100, 255, 218, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Page Views',
                    data: <?php echo json_encode($counts); ?>,
                    borderColor: '#64ffda',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    pointBackgroundColor: '#0a192f',
                    pointBorderColor: '#64ffda',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    legend: { display: false },
                    tooltip: commonOptions.plugins.tooltip
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false },
                        ticks: { color: '#8892b0', font: { family: "'Inter', sans-serif" } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#8892b0', font: { family: "'Inter', sans-serif" } }
                    }
                }
            }
        });

        // Device Chart (Donut)
        new Chart(document.getElementById('deviceChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($deviceLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($deviceCounts); ?>,
                    backgroundColor: ['#64ffda', '#ff6b6b', '#4cc9f0'],
                    borderColor: '#020c1b',
                    borderWidth: 2
                }]
            },
            options: commonOptions
        });

        // OS Chart (Donut)
        new Chart(document.getElementById('osChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($osLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($osCounts); ?>,
                    backgroundColor: ['#4cc9f0', '#ffbd2e', '#ff6b6b', '#64ffda', '#a8a8a8'],
                    borderColor: '#020c1b',
                    borderWidth: 2
                }]
            },
            options: commonOptions
        });
    </script>
</body>
</html>