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
$limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 8;

$rec_data = getRecommendedSongsList($conn, $user_id, $limit);

echo json_encode([
    'success' => true,
    'status' => 'success',
    'type' => $rec_data['is_personalized'] ? 'personalized' : 'fallback',
    'top_genre' => $rec_data['top_genre'],
    'top_count' => $rec_data['top_count'],
    'recommendations' => $rec_data['recommendations']
]);

$conn->close();
?>
