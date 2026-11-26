<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Please sign in to like songs."
    ]);
    exit();
}

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$song_id = isset($_POST['song_id']) ? (int)$_POST['song_id'] : 0;

if ($song_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid song ID is required']);
    exit;
}

// Ensure favorites table exists
$conn->query("CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    song_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_fav (user_id, song_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
)");

// Check if already favorited
$check = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND song_id = ?");
$check->bind_param("ii", $user_id, $song_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    // Remove from favorites
    $del = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND song_id = ?");
    $del->bind_param("ii", $user_id, $song_id);
    $del->execute();
    $del->close();
    echo json_encode([
        'success' => true,
        'is_favorite' => false,
        'message' => 'Removed from Liked Songs'
    ]);
} else {
    $check->close();
    // Add to favorites
    $ins = $conn->prepare("INSERT INTO favorites (user_id, song_id) VALUES (?, ?)");
    $ins->bind_param("ii", $user_id, $song_id);
    if ($ins->execute()) {
        echo json_encode([
            'success' => true,
            'is_favorite' => true,
            'message' => 'Added to Liked Songs!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to favorite track: ' . $conn->error
        ]);
    }
    $ins->close();
}

$conn->close();
?>
