<?php
header("Content-Type: application/json");

// Check if search query exists (support both 'q' and 'term')
$query = '';
if (isset($_GET['q']) && trim($_GET['q']) !== '') {
    $query = trim($_GET['q']);
} elseif (isset($_GET['term']) && trim($_GET['term']) !== '') {
    $query = trim($_GET['term']);
}

if ($query === '') {
    echo json_encode([
        "resultCount" => 0,
        "results" => []
    ]);
    exit;
}
$limit = isset($_GET['limit']) ? min(50, max(5, (int)$_GET['limit'])) : 30;

$url = "https://itunes.apple.com/search?term=" . urlencode($query) . "&entity=song&limit=" . $limit;

$response = false;

// 1. Try cURL
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'VARP Music/2.0 (compatible)');
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($status !== 200) {
        $response = false;
    }
}

// 2. Fallback to file_get_contents if cURL was unavailable or failed
if ($response === false && ini_get('allow_url_fopen')) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "User-Agent: VARP Music/2.0\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    $response = @file_get_contents($url, false, $ctx);
}

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        "error" => "Could not reach remote music catalog. Please check your internet connection.",
        "resultCount" => 0,
        "results" => []
    ]);
    exit;
}

$data = json_decode($response, true);
if (!is_array($data)) {
    http_response_code(502);
    echo json_encode([
        "error" => "Remote music provider returned invalid data.",
        "resultCount" => 0,
        "results" => []
    ]);
    exit;
}

echo json_encode($data);
?>
