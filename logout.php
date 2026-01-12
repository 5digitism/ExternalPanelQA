<?php
session_start();

// Destroy ALL session data
session_unset();
session_destroy();

// Redirect user to login page
header("Location: loginpage.html");
exit();
?>
