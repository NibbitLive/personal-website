<?php
session_start();
require 'db_connection.php'; // your DB connection file

if (!isset($_SESSION['username'])) {
	http_response_code(403);
	exit("Not logged in");
}

$username = $_SESSION['username'];
$id = $_POST['id'];
$new_message = $_POST['message'];

$stmt = $conn->prepare("UPDATE messages SET message = ? WHERE id = ? AND username = ?");
$stmt->bind_param("sis", $new_message, $id, $username);
$stmt->execute();
?>
