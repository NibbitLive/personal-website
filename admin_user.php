<?php
session_start();
include('db_connection.php');

// Only allow the admin to view this page
if ($_SESSION['username'] != 'Dihein') {
    echo "You do not have permission to view this page.";
    exit;
}

// Get the list of users
$query = "SELECT * FROM users";
$result = $conn->query($query);

echo "<h2>Users</h2>";
echo "<table>";
echo "<tr><th>Username</th><th>Status</th><th>Action</th></tr>";

while ($row = $result->fetch_assoc()) {
    $banned_status = $row['banned'] == 1 ? "Banned" : "Not Banned";
    echo "<tr>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td>" . $banned_status . "</td>";
    echo "<td><a href='ban_user.php?username=" . $row['username'] . "'>Toggle Ban</a></td>";
    echo "</tr>";
}

echo "</table>";
?>
