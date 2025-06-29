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
	// Handle sending message + file upload
	if (isset($_POST['username']) && isset($_POST['message'])) {
		$message = trim($_POST['message']);
		$filePath = null;

		// Handle file upload if exists
		if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = 'uploads/';
			if (!is_dir($uploadDir)) {
				mkdir($uploadDir, 0777, true);
			}

			// File size check server side (50MB for non-Dihein)
			$fileSize = $_FILES['file']['size'];
			if ($username !== 'Dihein' && $fileSize > 52428800) {
				echo json_encode(["status" => "error", "message" => "File size exceeds the 50 MB limit."]);
				exit;
			}

			$originalName = basename($_FILES['file']['name']);
			$safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $originalName);
			$targetPath = $uploadDir . uniqid() . '_' . $safeName;

			if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
				echo json_encode(["status" => "error", "message" => "Failed to save uploaded file."]);
				exit;
			}

			$filePath = $targetPath;
		}

		if ($message === '' && $filePath === null) {
			echo json_encode(["status" => "error", "message" => "Message and file both empty."]);
			exit;
		}

		$stmt = $conn->prepare("INSERT INTO messages (username, message, file_path) VALUES (?, ?, ?)");
		$stmt->bind_param("sss", $username, $message, $filePath);
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

		$stmt = $conn->prepare("UPDATE messages SET message = ?, edited_at = NOW() WHERE id = ? AND username = ?");
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
