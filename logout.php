<?php
session_start();
$conn = new mysqli("localhost", "root", "", "eap_system");

if (isset($_COOKIE['remember_me'])) {
    // Break down the cookie: [0]=ID/User, [1]=Token, [2]=Table
    $parts = explode(':', $_COOKIE['remember_me']);
    if (count($parts) === 3) {
        $id = $parts[0];
        $table = $parts[2];
        $col = ($table === 'users') ? 'username' : 'id';

        // Delete token from database
        $stmt = $conn->prepare("UPDATE $table SET remember_token = NULL WHERE $col = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
    }
    // Delete the browser cookie
    setcookie('remember_me', '', time() - 3600, "/");
}

session_unset();
session_destroy();
header("Location: loginpage.html");
exit();
?>