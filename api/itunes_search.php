
<?php

header("Content-Type: application/json");

// Check if search query exists
if (!isset($_GET['q']) || trim($_GET['q']) == '') {

    echo json_encode([
        "error" => "Please enter a song or artist name"
    ]);

    exit;
}

// Get search query
$query = trim($_GET['q']);

// Create iTunes API URL
$url = "https://itunes.apple.com/search?term="
       . urlencode($query)
       . "&entity=song&limit=25";

// Initialize cURL
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

// Execute API request
$response = curl_exec($ch);

// Check connection error
if ($response === false) {

    http_response_code(502);

    echo json_encode([
        "error" => "Could not connect to iTunes"
    ]);

    curl_close($ch);
    exit;
}

$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

// Convert response into PHP array
$data = json_decode($response, true);

// Check API response
if ($status != 200 || !is_array($data)) {

    http_response_code(502);

    echo json_encode([
        "error" => "iTunes API request failed"
    ]);

    exit;
}

// Return song results
echo json_encode($data);

?>
