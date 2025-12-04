<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Adjust for production security

require_once '../includes/db.php';

// Helper to get visitor IP
function get_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
        return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $_SERVER['REMOTE_ADDR'];
}

// Helper to detect device (Basic)
function get_device_type($ua)
{
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobi))/i', $ua)) {
        return 'Tablet';
    }
    if (preg_match('/(mobile|iphone|ipod|samsung|htc|blackberry|windows phone)/i', $ua)) {
        return 'Mobile';
    }
    return 'Desktop';
}

// Helper to detect OS
function get_os($ua)
{
    $os_array = [
        '/windows nt 10/i' => 'Windows 10',
        '/windows nt 6.3/i' => 'Windows 8.1',
        '/windows nt 6.2/i' => 'Windows 8',
        '/windows nt 6.1/i' => 'Windows 7',
        '/macintosh|mac os x/i' => 'Mac OS X',
        '/linux/i' => 'Linux',
        '/android/i' => 'Android',
        '/iphone/i' => 'iOS',
        '/ipad/i' => 'iOS'
    ];
    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $ua))
            return $value;
    }
    return 'Unknown';
}

// Helper to detect Browser
function get_browser_name($ua)
{
    if (strpos($ua, 'Opera') || strpos($ua, 'OPR/'))
        return 'Opera';
    if (strpos($ua, 'Edge'))
        return 'Edge';
    if (strpos($ua, 'Chrome'))
        return 'Chrome';
    if (strpos($ua, 'Safari'))
        return 'Safari';
    if (strpos($ua, 'Firefox'))
        return 'Firefox';
    if (strpos($ua, 'MSIE') || strpos($ua, 'Trident/7'))
        return 'Internet Explorer';
    return 'Other';
}

// Helper to get Country from IP (Robust)
function get_country($ip)
{
    // Skip for localhost
    if ($ip == '127.0.0.1' || $ip == '::1') {
        return 'Localhost';
    }

    $url = "http://ip-api.com/json/{$ip}";
    $country = 'Unknown';

    // Try cURL first (more robust)
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $json = curl_exec($ch);
        curl_close($ch);

        if ($json) {
            $data = json_decode($json, true);
            if ($data && isset($data['status']) && $data['status'] == 'success') {
                return $data['country'];
            }
        }
    }

    // Fallback to file_get_contents
    if ($country === 'Unknown') {
        try {
            $context = stream_context_create(['http' => ['timeout' => 3]]);
            $json = @file_get_contents($url, false, $context);
            if ($json) {
                $data = json_decode($json, true);
                if ($data && isset($data['status']) && $data['status'] == 'success') {
                    return $data['country'];
                }
            }
        } catch (Exception $e) {
            // Fail silently
        }
    }

    return 'Unknown';
}

// 1. Get Input Data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
    exit;
}

try {
    // 2. Identify Visitor
    $ip = get_ip();
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    // GDPR: Mask IP (last octet)
    $masked_ip = preg_replace('/(\d+)\.(\d+)\.(\d+)\.(\d+)/', '$1.$2.$3.0', $ip);

    // Generate a daily hash for the visitor to track unique visits per day/session without storing PII permanently if needed
    // For this simple system, we'll use a persistent hash based on IP+UA to track returning users loosely
    $visitor_hash = hash('sha256', $ip . $ua);

    // Check if visitor exists
    $stmt = $pdo->prepare("SELECT id, country FROM analytics_visitors WHERE visitor_hash = ?");
    $stmt->execute([$visitor_hash]);
    $visitor = $stmt->fetch();

    if ($visitor) {
        $visitor_id = $visitor['id'];

        // Update country if it's Unknown or Localhost (and we have a valid IP now)
        if (($visitor['country'] === 'Unknown' || $visitor['country'] === 'Localhost') && $ip !== '127.0.0.1' && $ip !== '::1') {
            $new_country = get_country($ip);
            if ($new_country !== 'Unknown' && $new_country !== 'Localhost') {
                $updateStmt = $pdo->prepare("UPDATE analytics_visitors SET country = ? WHERE id = ?");
                $updateStmt->execute([$new_country, $visitor_id]);
            }
        }
    } else {
        // Create new visitor
        $device = get_device_type($ua);
        $os = get_os($ua);
        $browser = get_browser_name($ua);
        $country = get_country($ip);

        $stmt = $pdo->prepare("INSERT INTO analytics_visitors (visitor_hash, ip_address, country, device_type, os, browser) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$visitor_hash, $masked_ip, $country, $device, $os, $browser]);
        $visitor_id = $pdo->lastInsertId();
    }

    // 3. Handle Request Type
    $type = $input['type'] ?? 'pageview';

    if ($type === 'pageview') {
        $url = $input['url'] ?? '';
        $title = $input['title'] ?? '';
        $referrer = $input['referrer'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO analytics_pageviews (visitor_id, page_url, page_title, referrer) VALUES (?, ?, ?, ?)");
        $stmt->execute([$visitor_id, $url, $title, $referrer]);

        echo json_encode(['status' => 'success', 'type' => 'pageview']);

    } elseif ($type === 'event') {
        $category = $input['category'] ?? 'general';
        $action = $input['action'] ?? 'click';
        $label = $input['label'] ?? '';
        $value = $input['value'] ?? 0.00;

        $stmt = $pdo->prepare("INSERT INTO analytics_events (visitor_id, event_category, event_action, event_label, event_value) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$visitor_id, $category, $action, $label, $value]);

        echo json_encode(['status' => 'success', 'type' => 'event']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid type']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>