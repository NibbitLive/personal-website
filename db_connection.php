<?php
$host = 'localhost'; // Database host (usually localhost for local servers)
$username = 'root';  // Database username (default is usually 'root' for XAMPP)
$password = '';      // Database password (default is empty for XAMPP)
$dbname = 'chatapp'; // Name of your database

// Create the connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
	$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	die("Database connection failed: " . $e->getMessage());
}

?>
