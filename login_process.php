<?php
session_start();
include('db_connection.php');

$username = $_POST['username'];
$password = $_POST['password'];

// Query to get the user by username
$query = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($query);

if ($stmt === false) {
	die("Prepare failed: " . $conn->error);
}

$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
	$user = $result->fetch_assoc();

	// Check if the account is banned
	if (isset($user['banned']) && $user['banned'] == 1) {
		echo "Your account is banned. Please contact support.";
		exit;
	}

	// Verify the password hash
	if (password_verify($password, $user['password'])) {
		// Password correct — log user in
		$_SESSION['username'] = $username;
		header("Location: index.php");
		exit;
	} else {
		// Wrong password
		loginRedirectWithError($username, $password);
	}
} else {
	// Username not found
	loginRedirectWithError($username, $password);
}

function loginRedirectWithError($username, $password) {
	echo '
		<form id="redirectForm" action="login.php?error=1" method="post">
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
