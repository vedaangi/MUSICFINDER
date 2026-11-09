<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$user_id = getCurrentUserId();
$playlist_id = isset($_POST['playlist_id']) ? (int)$_POST['playlist_id'] : 0;

if ($playlist_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Valid playlist ID is required']);
    exit;
}

$conn->begin_transaction();

try {
    // remove songs associated with this playlist
    $stmt1 = $conn->prepare("DELETE FROM playlist_songs WHERE playlist_id = ?");
    $stmt1->bind_param("i", $playlist_id);
    $stmt1->execute();
    $stmt1->close();

    // delete playlist ensuring it belongs to current user
    $stmt2 = $conn->prepare("DELETE FROM playlists WHERE id = ? AND user_id = ?");
    $stmt2->bind_param("ii", $playlist_id, $user_id);
    $stmt2->execute();

    if ($stmt2->affected_rows === 0) {
        throw new Exception("Playlist not found or you are not authorized to delete it.");
    }

    $stmt2->close();
    $conn->commit();

    echo json_encode(['status' => 'success', 'message' => 'Playlist deleted successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>
