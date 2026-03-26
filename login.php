<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$db   = "eap_system";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // --- CASE 1: PANEL MEMBER LOGIN ---
    if (str_starts_with(strtoupper($username), 'PANEL')) {
        
        // Extract the ID number (e.g., "PANEL005" becomes 5)
        $panel_id = (int)filter_var($username, FILTER_SANITIZE_NUMBER_INT);
        
        $stmt = $conn->prepare("SELECT * FROM panel_members WHERE id = ? AND status = 'Approved'");
        $stmt->bind_param("i", $panel_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $panel_data = $result->fetch_assoc();

            // Verify Password
            if ($password === $panel_data['password']) {
                
                $_SESSION['panel_id']   = $panel_data['id'];
                $_SESSION['username']   = $username; // e.g. PANEL001
                $_SESSION['name']       = $panel_data['panel_name'];
                $_SESSION['role']       = 'Panel';

                // First Time Login Check (Redirect to change password if still default)
                if ($panel_data['password'] === '123456') {
                    header("Location: panelchangepassword.php");
                } else {
                    header("Location: panel_dashboard.php");
                }
                exit();
            } else {
                echo "<script>alert('Invalid password for Panel account');window.location='loginpage.html';</script>";
            }
        } else {
            echo "<script>alert('Panel account not found or not yet approved');window.location='loginpage.html';</script>";
        }

    // --- CASE 2: STAFF LOGIN (Admin / Head QA) ---
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user_data = $result->fetch_assoc();

            if ($password === $user_data['password'] || password_verify($password, $user_data['password'])) {
                
                $_SESSION['username'] = $user_data['username'];
                $_SESSION['name']     = $user_data['name'];
                $_SESSION['role']     = $user_data['role'];

                if ($_SESSION['role'] === 'Head QA') {
                    header("Location: HQApage.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
                
            } else {
                echo "<script>alert('Invalid staff password');window.location='loginpage.html';</script>";
            }
        } else {
            echo "<script>alert('User not found');window.location='loginpage.html';</script>";
        }
    }
}
?>