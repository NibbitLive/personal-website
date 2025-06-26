<?php
session_start();
header('Content-Type: application/json');

include 'db_connection.php';

if (!isset($_SESSION['username'])) {
	echo json_encode(["status" => "error", "message" => "User not logged in"]);
	exit;
}

$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Handle sending message
	if (isset($_POST['username']) && isset($_POST['message'])) {
		$user = $_POST['username'];
		$message = $_POST['message'];

		$stmt = $conn->prepare("INSERT INTO messages (username, message) VALUES (?, ?)");
		$stmt->bind_param("ss", $user, $message);
		$stmt->execute();
		$stmt->close();

		echo json_encode(["status" => "success"]);
		exit;
	}

	// Handle delete
	if (isset($_POST['delete_id'])) {
		$id = intval($_POST['delete_id']);
		$stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND username = ?");
		$stmt->bind_param("is", $id, $username);
		$stmt->execute();
		$stmt->close();

		echo json_encode(["status" => "success"]);
		exit;
	}

	// Handle edit
	if (isset($_POST['edit_id']) && isset($_POST['new_message'])) {
		$id = intval($_POST['edit_id']);
		$new_message = $_POST['new_message'];

		$stmt = $conn->prepare("UPDATE messages SET message = ? WHERE id = ? AND username = ?");
		$stmt->bind_param("sis", $new_message, $id, $username);
		$stmt->execute();
		$stmt->close();

		echo json_encode(["status" => "success"]);
		exit;
	}
}

// Fetch all messages
$result = $conn->query("SELECT * FROM messages ORDER BY created_at DESC");
$messages = [];

while ($row = $result->fetch_assoc()) {
	$messages[] = $row;
}

echo json_encode($messages);
?>
