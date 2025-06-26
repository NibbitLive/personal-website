<?php
session_start();
require 'db.php';

if (!isset($_SESSION['username'])) {
	http_response_code(403);
	exit("Not logged in");
}

$username = $_SESSION['username'];
$id = $_POST['id'];

$stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND username = ?");
$stmt->bind_param("is", $id, $username);
$stmt->execute();
?>
