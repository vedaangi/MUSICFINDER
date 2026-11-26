<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Please log in first."
    ]);
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/db_helper.php';

$user_id = (int)$_SESSION['user_id'];
$info = getUserTopGenreInfo($conn, $user_id);

echo json_encode([
    'success' => true,
    'status' => 'success',
    'has_history' => !empty($info['breakdown']),
    'top_genre' => $info['top_genre'],
    'play_count' => $info['top_count'],
    'breakdown' => $info['breakdown']
]);

$conn->close();
?>
