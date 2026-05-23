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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']); // Checkbox from your HTML

    // --- CASE 1: PANEL MEMBER LOGIN ---
    if (str_starts_with(strtoupper($username), 'PANEL')) {
        $panel_id = (int)filter_var($username, FILTER_SANITIZE_NUMBER_INT);
        
        $stmt = $conn->prepare("SELECT * FROM panel_members WHERE id = ? AND status = 'Approved'");
        $stmt->bind_param("i", $panel_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user_data = $result->fetch_assoc();
            // Checking plain text '123456' or hashed password
            if ($password === $user_data['password'] || password_verify($password, $user_data['password'])) {
                $_SESSION['panel_id'] = $user_data['id'];
                $_SESSION['username'] = $username;
                $_SESSION['name']     = $user_data['panel_name'];
                $_SESSION['role']     = 'Panel';
                
                // TRIGGER REMEMBER ME
                handleRememberMe($conn, 'panel_members', $user_data['id'], $remember);

                if ($user_data['password'] === '123456') {
                    header("Location: panelchangepassword.php");
                } else {
                    header("Location: panel_dashboard.php");
                }
                exit();
            }
        }
        echo "<script>alert('Invalid Panel credentials or account not approved');window.location='loginpage.html';</script>";

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

                // TRIGGER REMEMBER ME
                handleRememberMe($conn, 'users', $user_data['username'], $remember, 'username');

                if ($_SESSION['role'] === 'Head QA') {
                    header("Location: HQApage.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            }
        }
        echo "<script>alert('Invalid Staff credentials');window.location='loginpage.html';</script>";
    }
}

/**
 * Saves token to DB and sets a 30-day cookie
 */
function handleRememberMe($conn, $table, $identifier, $remember, $id_column = 'id') {
    if ($remember) {
        $token = bin2hex(random_bytes(20));
        $stmt = $conn->prepare("UPDATE $table SET remember_token = ? WHERE $id_column = ?");
        $stmt->bind_param("ss", $token, $identifier);
        $stmt->execute();

        // Cookie format: identifier:token:table
        $cookie_value = $identifier . ':' . $token . ':' . $table;
        setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/"); 
    }
}
?>