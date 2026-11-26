
<?php

include "../config/db.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["error" => "Use POST method"]);
    exit;
}

$song_id = $_POST["song_id"] ?? "";

if (empty($song_id) || !ctype_digit((string)$song_id)) {
    echo json_encode(["error" => "Valid song ID is required"]);
    exit;
}

$stmt = $conn->prepare(
    "DELETE FROM songs WHERE song_id = ?"
);

$stmt->bind_param("i", $song_id);

if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => "Song deleted successfully"]);
    } else {
        echo json_encode(["error" => "Song not found"]);
    }

} else {
    echo json_encode(["error" => "Could not delete song"]);
}

$stmt->close();

?>
