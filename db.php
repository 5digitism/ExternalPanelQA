<?php
session_start(); // Ensure this is at the very top

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "eap_system";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

/**
 * Helper function to log system activities
 */
function logActivity($conn, $desc, $badge = null, $class = 'bg-secondary') {
    $query = "INSERT INTO activity_log (description, badge_text, badge_class) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sss", $desc, $badge, $class);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>