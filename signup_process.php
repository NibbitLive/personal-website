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

	// Get all usernames
	$usernames = [];
	$query = "SELECT username FROM users";
	$result = $conn->query($query);

	if ($result) {
		while ($row = $result->fetch_assoc()) {
			$usernames[] = $row['username'];
		}

		// Sort the usernames using Quick Sort
		$sorted_usernames = quickSort($usernames);

		// Optional: Log or debug
		// file_put_contents('sorted_usernames.log', implode("\n", $sorted_usernames));
	}

	// Log the user in
	$_SESSION['username'] = $username;
	header("Location: index.php"); // Redirect to homepage or chat
	exit();
}

// Quick Sort function in PHP
function quickSort($array) {
	if (count($array) <= 1) {
		return $array;
	}

	$pivot = $array[count($array) - 1];
	$left = [];
	$right = [];

	for ($i = 0; $i < count($array) - 1; $i++) {
		if ($array[$i] <= $pivot) {
			$left[] = $array[$i];
		} else {
			$right[] = $array[$i];
		}
	}

	return array_merge(quickSort($left), [$pivot], quickSort($right));
}

// Redirect function if username exists
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
