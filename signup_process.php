<?php
session_start();
include('db_connection.php'); // Include your database connection code

// Get the form data
$username = $_POST['username'];
$password = $_POST['password'];

// Check if the username already exists
$query = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
	die("Prepare failed: " . $conn->error);
}

$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
	// Username already exists
	loginRedirectWithError($username, $password);
} else {
	// Optional: Hash the password for security
	$hashed_password = password_hash($password, PASSWORD_DEFAULT);

	// Insert new user into the database
	$query = "INSERT INTO users (username, password) VALUES (?, ?)";
	$stmt = $conn->prepare($query);

	if (!$stmt) {
		die("Prepare failed: " . $conn->error);
	}

	$stmt->bind_param('ss', $username, $hashed_password);
	$stmt->execute();

	// Log the user in
	$_SESSION['username'] = $username;
	header("Location: index.php"); // Redirect to homepage or chat
	exit();
}

function loginRedirectWithError($username, $password) {
	echo '
		<form id="redirectForm" action="signup.php?error=1" method="post">
			<input type="hidden" name="username" value="' . htmlspecialchars($username) . '">
			<input type="hidden" name="password" value="' . htmlspecialchars($password) . '">
		</form>
		<script>
			document.getElementById("redirectForm").submit();
		</script>
	';
	exit;
}
?>
