<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

require_once '../includes/db.php';

// --- HELPERS ---
if (!function_exists('getTrend')) {
    function getTrend($current, $previous)
    {
        if ($previous == 0)
            return ($current > 0) ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 1);
    }
}

if (!function_exists('formatTrend')) {
    function formatTrend($percent)
    {
        if ($percent > 0)
            return '<span class="trend-badge trend-up"><i class="fas fa-arrow-up"></i> ' . $percent . '%</span>';
        if ($percent < 0)
            return '<span class="trend-badge trend-down"><i class="fas fa-arrow-down"></i> ' . abs($percent) . '%</span>';
        return '<span class="trend-badge trend-neutral"><i class="fas fa-minus"></i> 0%</span>';
    }
}

// Date Range
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;
$startDate = date('Y-m-d H:i:s', strtotime("-$days days"));
$prevDate = date('Y-m-d H:i:s', strtotime("-" . ($days * 2) . " days"));

// Initialize variables to default values to prevent undefined variable errors
$totalVisitors = 0;
$visitorTrend = 0;
$totalPageViews = 0;
$viewTrend = 0;
$bounceRate = 0;
$liveUsers = 0;

$dates = [];
$counts = [];
$dowLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$dowCounts = array_fill(0, 7, 0);
$hourlyLabels = [];
$hourlyCounts = array_fill(0, 24, 0);

$topPages = [];
$topCountries = [];
$trafficSources = [];
$browserLabels = [];
$browserCounts = [];
$osLabels = [];
$osCounts = [];

try {
    // --- 1. KEY METRICS WITH COMPARISON ---

    // Total Visitors (Current vs Previous)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM analytics_visitors WHERE created_at >= ?");
    $stmt->execute([$startDate]);
    $totalVisitors = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM analytics_visitors WHERE created_at >= ? AND created_at < ?");
    $stmt->execute([$prevDate, $startDate]);
    $prevVisitors = $stmt->fetchColumn();
    $visitorTrend = getTrend($totalVisitors, $prevVisitors);

    // Total Page Views
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM analytics_pageviews WHERE created_at >= ?");
    $stmt->execute([$startDate]);
    $totalPageViews = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM analytics_pageviews WHERE created_at >= ? AND created_at < ?");
    $stmt->execute([$prevDate, $startDate]);
    $prevPageViews = $stmt->fetchColumn();
    $viewTrend = getTrend($totalPageViews, $prevPageViews);

    // Bounce Rate
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM (SELECT visitor_id FROM analytics_pageviews WHERE created_at >= ? GROUP BY visitor_id HAVING COUNT(*) = 1) as t");
    $stmt->execute([$startDate]);
    $bounceCount = $stmt->fetchColumn();
    $bounceRate = ($totalVisitors > 0) ? round(($bounceCount / $totalVisitors) * 100, 1) : 0;

    // Live Users
    $stmt = $pdo->query("SELECT COUNT(DISTINCT visitor_id) FROM analytics_pageviews WHERE created_at >= NOW() - INTERVAL 5 MINUTE");
    $liveUsers = $stmt->fetchColumn();

    // --- 2. ADVANCED CHARTS DATA ---

    // Traffic Trend (Daily)
    $stmt = $pdo->prepare("SELECT DATE(created_at) as date, COUNT(*) as count FROM analytics_pageviews WHERE created_at >= ? GROUP BY DATE(created_at) ORDER BY date ASC");
    $stmt->execute([$startDate]);
    $chartData = $stmt->fetchAll();
    foreach ($chartData as $row) {
        $dates[] = date('M j', strtotime($row['date']));
        $counts[] = $row['count'];
    }

    // Day of Week Analysis
    $stmt = $pdo->prepare("SELECT DAYOFWEEK(created_at) as dw, COUNT(*) as count FROM analytics_pageviews WHERE created_at >= ? GROUP BY dw");
    $stmt->execute([$startDate]);
    $dowData = $stmt->fetchAll();
    foreach ($dowData as $row) {
        // Adjust index: MySQL 1(Sun) -> Array 6, MySQL 2(Mon) -> Array 0
        $idx = ($row['dw'] == 1) ? 6 : $row['dw'] - 2;
        if (isset($dowCounts[$idx]))
            $dowCounts[$idx] = $row['count'];
    }

    // Hourly Analysis
    for ($i = 0; $i < 24; $i++)
        $hourlyLabels[] = date("g A", strtotime("$i:00"));

    $stmt = $pdo->prepare("SELECT HOUR(created_at) as hr, COUNT(*) as count FROM analytics_pageviews WHERE created_at >= ? GROUP BY hr");
    $stmt->execute([$startDate]);
    $hData = $stmt->fetchAll();
    // Reset to keyed array to fill
    $hourlyCountsVal = array_fill(0, 24, 0);
    foreach ($hData as $row) {
        $hourlyCountsVal[$row['hr']] = $row['count'];
    }
    $hourlyCounts = $hourlyCountsVal;

    // --- 3. DETAILED LISTS ---

    // Top Pages
    $stmt = $pdo->prepare("SELECT page_title, page_url, COUNT(*) as views FROM analytics_pageviews WHERE created_at >= ? GROUP BY page_url ORDER BY views DESC LIMIT 15");
    $stmt->execute([$startDate]);
    $topPages = $stmt->fetchAll();

    // Top Countries
    $stmt = $pdo->prepare("SELECT country, COUNT(*) as count FROM analytics_visitors WHERE created_at >= ? GROUP BY country ORDER BY count DESC LIMIT 10");
    $stmt->execute([$startDate]);
    $topCountries = $stmt->fetchAll();

    // Referrers
    $stmt = $pdo->prepare("SELECT referrer, COUNT(*) as count FROM analytics_pageviews WHERE created_at >= ? GROUP BY referrer ORDER BY count DESC LIMIT 10");
    $stmt->execute([$startDate]);
    $referrersData = $stmt->fetchAll();
    foreach ($referrersData as $row) {
        $ref = $row['referrer'];
        $source = empty($ref) ? 'Direct' : (parse_url($ref, PHP_URL_HOST) ?? 'Unknown');
        $trafficSources[$source] = ($trafficSources[$source] ?? 0) + $row['count'];
    }
    arsort($trafficSources);
    $trafficSources = array_slice($trafficSources, 0, 8);

    // Browsers
    $stmt = $pdo->prepare("SELECT browser, COUNT(*) as count FROM analytics_visitors WHERE created_at >= ? GROUP BY browser ORDER BY count DESC");
    $stmt->execute([$startDate]);
    $browserStats = $stmt->fetchAll();
    $browserLabels = array_column($browserStats, 'browser');
    $browserCounts = array_column($browserStats, 'count');

    // OS
    $stmt = $pdo->prepare("SELECT os, COUNT(*) as count FROM analytics_visitors WHERE created_at >= ? GROUP BY os ORDER BY count DESC");
    $stmt->execute([$startDate]);
    $osStats = $stmt->fetchAll();
    $osLabels = array_column($osStats, 'os');
    $osCounts = array_column($osStats, 'count');

} catch (Throwable $e) {
    // In case of error (DB down, syntax, etc), variables are already initialized to defaults.
    // Ensure hourlyCounts is just values for chart js
    $hourlyCounts = array_values($hourlyCounts);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deep Analytics Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-dark: #020c1b;
            --card-bg: #0a192f;
            --text-main: #e6f1ff;
            --text-muted: #8892b0;
            --neon-green: #64ffda;
            --neon-blue: #57cbff;
            --neon-pink: #bd34fe;
            --border: 1px solid rgba(255, 255, 255, 0.05);
        }

        body {
            background-color: var(--bg-dark);
            font-family: 'Inter', sans-serif;
            color: var(--text-muted);
            margin: 0;
            padding-bottom: 50px;
        }

        .admin-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 30px;
        }

        /* HEADER */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: var(--card-bg);
            padding: 20px 30px;
            border-radius: 12px;
            border: var(--border);
            box-shadow: 0 10px 30px -10px rgba(2, 12, 27, 0.7);
        }

        .header-title h1 {
            color: var(--text-main);
            font-size: 22px;
            margin: 0;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .header-title p {
            margin: 5px 0 0;
            font-size: 13px;
            color: var(--text-muted);
        }

        .filter-group {
            display: flex;
            gap: 8px;
            background: rgba(255, 255, 255, 0.03);
            padding: 5px;
            border-radius: 8px;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 6px;
            color: var(--text-muted);
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s;
            font-weight: 500;
        }

        .filter-btn:hover {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.05);
        }

        .filter-btn.active {
            background: rgba(100, 255, 218, 0.1);
            color: var(--neon-green);
            font-weight: 600;
        }

        /* GRID SYSTEM */
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 24px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 24px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .grid-1-1 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        @media(max-width: 1200px) {

            .grid-4,
            .grid-3 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-2 {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width: 768px) {

            .grid-4,
            .grid-3,
            .grid-1-1 {
                grid-template-columns: 1fr;
            }

            .header-section {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }

        /* CARDS */
        .card {
            background: var(--card-bg);
            border: var(--border);
            border-radius: 12px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 10px 30px -15px rgba(2, 12, 27, 0.7);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(2, 12, 27, 0.8);
            border-color: rgba(100, 255, 218, 0.2);
        }

        .h-350 {
            height: 350px;
        }

        /* KPI STYLES */
        .kpi-icon {
            position: absolute;
            top: 24px;
            right: 24px;
            font-size: 20px;
            color: rgba(136, 146, 176, 0.3);
        }

        .kpi-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .kpi-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -1px;
            margin-bottom: 8px;
        }

        .trend-badge {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
        }

        .trend-up {
            background: rgba(100, 255, 218, 0.1);
            color: var(--neon-green);
        }

        .trend-down {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
        }

        .trend-neutral {
            background: rgba(136, 146, 176, 0.1);
            color: var(--text-muted);
        }

        /* CHARTS */
        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            flex: 1;
            overflow: hidden;
            /* display: flex; REMOVED to fix canvas resize */
            /* justify-content: center; */
        }

        canvas {
            width: 100% !important;
            height: 100% !important;
        }

        /* TABLES */
        .table-wrapper {
            overflow-x: auto;
            flex: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th {
            text-align: left;
            padding: 12px 15px;
            color: var(--text-muted);
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px 15px;
            color: var(--text-main);
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .progress-track {
            background: rgba(255, 255, 255, 0.08);
            height: 6px;
            border-radius: 3px;
            width: 80px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 3px;
        }

        .text-neon {
            color: var(--neon-green);
            font-weight: 600;
            font-family: monospace;
            font-size: 13px;
        }

        /* SCROLLBAR */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(136, 146, 176, 0.3);
            border-radius: 3px;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php $currentPage = 'analytics';
        include 'header.php'; ?>

        <!-- HEADER -->
        <div class="header-section">
            <div class="header-title">
                <h1>Analytics Dashboard</h1>
                <p>Real-time insights and performance metrics.</p>
            </div>
            <div class="filter-group">
                <a href="?days=7" class="filter-btn <?php echo $days == 7 ? 'active' : ''; ?>">7 Days</a>
                <a href="?days=30" class="filter-btn <?php echo $days == 30 ? 'active' : ''; ?>">30 Days</a>
                <a href="?days=90" class="filter-btn <?php echo $days == 90 ? 'active' : ''; ?>">90 Days</a>
            </div>
        </div>

        <!-- KPI ROW -->
        <div class="grid-4">
            <div class="card">
                <i class="fas fa-users kpi-icon"></i>
                <div class="kpi-label">Total Visitors</div>
                <div class="kpi-value"><?php echo number_format($totalVisitors); ?></div>
                <div><?php echo formatTrend($visitorTrend); ?></div>
            </div>
            <div class="card">
                <i class="fas fa-eye kpi-icon"></i>
                <div class="kpi-label">Page Views</div>
                <div class="kpi-value"><?php echo number_format($totalPageViews); ?></div>
                <div><?php echo formatTrend($viewTrend); ?></div>
            </div>
            <div class="card">
                <i class="fas fa-clock kpi-icon"></i>
                <div class="kpi-label">Bounce Rate</div>
                <div class="kpi-value"><?php echo $bounceRate; ?>%</div>
                <div class="trend-badge trend-neutral">Approximate</div>
            </div>
            <div class="card">
                <i class="fas fa-bolt kpi-icon"></i>
                <div class="kpi-label">Live Users</div>
                <div class="kpi-value"><?php echo number_format($liveUsers); ?></div>
                <div class="trend-badge trend-up" style="background: rgba(189, 52, 254, 0.1); color: #bd34fe;">Last 5
                </div>
            </div>
        </div>

        <!-- MAIN CHARTS -->
        <div class="grid-3">
            <div class="card h-350" style="grid-column: span 2;">
                <div class="chart-header">
                    <div class="chart-title"><i class="fas fa-chart-area" style="color:var(--neon-green)"></i> Traffic
                        Overview</div>
                </div>
                <div class="chart-wrapper"><canvas id="trafficChart"></canvas></div>
            </div>
            <div class="card h-350">
                <div class="chart-header">
                    <div class="chart-title"><i class="fas fa-calendar-alt" style="color:var(--neon-blue)"></i> Peak
                        Days</div>
                </div>
                <div class="chart-wrapper"><canvas id="dowChart"></canvas></div>
            </div>
        </div>

        <!-- SECOND ROW CHARTS -->
        <div class="grid-3">
            <div class="card h-350" style="grid-column: span 2;">
                <div class="chart-header">
                    <div class="chart-title"><i class="fas fa-clock" style="color:#ffbd2e"></i> Hourly Heatmap</div>
                </div>
                <div class="chart-wrapper"><canvas id="hourlyChart"></canvas></div>
            </div>
            <div class="card h-350" style="padding:0;">
                <div style="padding: 24px; padding-bottom:10px;" class="chart-title"><i class="fas fa-globe"
                        style="color:#ff6b6b"></i> Top Countries</div>
                <div class="table-wrapper" style="padding: 0 24px 24px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th>Visits</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topCountries as $c):
                                $pct = ($totalVisitors > 0) ? ($c['count'] / $totalVisitors) * 100 : 0; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($c['country']); ?></td>
                                    <td class="text-neon"><?php echo number_format($c['count']); ?></td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <div class="progress-track">
                                                <div class="progress-bar"
                                                    style="width:<?php echo $pct; ?>%; background: #ff6b6b;"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TECH SPECS ROW -->
        <div class="grid-3">
            <div class="card h-350">
                <div class="chart-title" style="margin-bottom:20px;">Browsers</div>
                <div class="chart-wrapper"><canvas id="browserChart"></canvas></div>
            </div>
            <div class="card h-350">
                <div class="chart-title" style="margin-bottom:20px;">OS</div>
                <div class="chart-wrapper"><canvas id="osChart"></canvas></div>
            </div>
            <div class="card h-350" style="padding:0;">
                <div style="padding:24px 24px 10px;" class="chart-title">Top Sources</div>
                <div class="table-wrapper" style="padding: 0 24px 24px;">
                    <table>
                        <tbody>
                            <?php foreach ($trafficSources as $src => $cnt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($src); ?></td>
                                    <td class="text-neon" style="text-align:right;"><?php echo $cnt; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CONTENT TABLE -->
        <div class="card" style="padding:0;">
            <div style="padding:24px;" class="chart-title"><i class="fas fa-file-alt" style="color:#bd34fe"></i> Most
                Viewed Content</div>
            <div class="table-wrapper" style="padding: 0 24px 24px;">
                <table>
                    <thead>
                        <tr>
                            <th>Page Title / URL</th>
                            <th>Views</th>
                            <th>Engagement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topPages as $p):
                            $share = ($totalPageViews > 0) ? ($p['views'] / $totalPageViews) * 100 : 0; ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600; color:#fff;">
                                        <?php echo htmlspecialchars($p['page_title']); ?>
                                    </div>
                                    <div
                                        style="font-size:12px; color:var(--text-muted); opacity:0.7; max-width:400px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?php echo htmlspecialchars($p['page_url']); ?>
                                    </div>
                                </td>
                                <td class="text-neon"><?php echo number_format($p['views']); ?></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div class="progress-track" style="width:100px;">
                                            <div class="progress-bar"
                                                style="width:<?php echo $share; ?>%; background: #bd34fe;"></div>
                                        </div>
                                        <span style="font-size:11px;"><?php echo round($share, 1); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        // GLOBAL CHART DEFAULTS
        Chart.defaults.color = '#8892b0';
        Chart.defaults.borderColor = 'rgba(255,255,255,0.05)';
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.size = 11;

        // 1. TRAFFIC LINE CHART
        const ctxTraffic = document.getElementById('trafficChart').getContext('2d');
        const gradTraffic = ctxTraffic.createLinearGradient(0, 0, 0, 300);
        gradTraffic.addColorStop(0, 'rgba(100, 255, 218, 0.2)');
        gradTraffic.addColorStop(1, 'rgba(100, 255, 218, 0)');

        new Chart(ctxTraffic, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Views',
                    data: <?php echo json_encode($counts); ?>,
                    borderColor: '#64ffda',
                    borderWidth: 2,
                    backgroundColor: gradTraffic,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false, backgroundColor: '#112240', titleColor: '#e6f1ff', bodyColor: '#8892b0', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1 } },
                scales: {
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 7 } },
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, border: { dash: [5, 5] } }
                }
            }
        });

        // 2. DAY OF WEEK BAR
        new Chart(document.getElementById('dowChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($dowLabels); ?>,
                datasets: [{ label: 'Visits', data: <?php echo json_encode($dowCounts); ?>, backgroundColor: '#57cbff', borderRadius: 4, barThickness: 20 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { grid: { display: false } }, y: { display: false } }
            }
        });

        // 3. HOURLY LABELS
        new Chart(document.getElementById('hourlyChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($hourlyLabels); ?>,
                datasets: [{ label: 'Activity', data: <?php echo json_encode(array_values($hourlyCounts)); ?>, borderColor: '#ffbd2e', borderWidth: 2, tension: 0.4, pointRadius: 0 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { ticks: { maxTicksLimit: 8 }, grid: { display: false } }, y: { display: false } }
            }
        });

        // 4. DONUT CONFIG
        const donutOptions = {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '85%', // NEW: THINNER RINGS
            plugins: { legend: { display: false } },
            layout: { padding: 10 }
        };

        const brLabels = <?php echo json_encode($browserLabels); ?>;
        const brCounts = <?php echo json_encode($browserCounts); ?>;
        console.log('Browser Data:', brLabels, brCounts);

        if (brCounts.length === 0) {
            // Handle empty data if necessary, maybe show a "No Data" message
            document.getElementById('browserChart').parentNode.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#8892b0;">No Data</div>';
        } else {
            new Chart(document.getElementById('browserChart'), {
                type: 'doughnut',
                data: {
                    labels: brLabels,
                    datasets: [{ data: brCounts, backgroundColor: ['#64ffda', '#57cbff', '#bd34fe', '#ffbd2e', '#ff6b6b'], borderWidth: 0 }]
                },
                options: donutOptions
            });
        }

        const osLabels = <?php echo json_encode($osLabels); ?>;
        const osCounts = <?php echo json_encode($osCounts); ?>;
        if (osCounts.length === 0) {
            document.getElementById('osChart').parentNode.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#8892b0;">No Data</div>';
        } else {
            new Chart(document.getElementById('osChart'), {
                type: 'doughnut',
                data: {
                    labels: osLabels,
                    datasets: [{ data: osCounts, backgroundColor: ['#ff6b6b', '#ffbd2e', '#57cbff', '#64ffda', '#bd34fe'], borderWidth: 0 }]
                },
                options: donutOptions
            });
        }

    </script>
</body>

</html>