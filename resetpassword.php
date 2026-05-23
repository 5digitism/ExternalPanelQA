<?php
date_default_timezone_set('Asia/Kuala_Lumpur'); // Set to your specific timezone
require_once 'db.php';

$token = $_GET['token'] ?? '';
$message = "";
$valid_token = false;

if (!empty($token)) {
    // We check both tables since both Panels and Staff can reset passwords
    // First, check panel_members
    $stmt = $conn->prepare("SELECT id, 'panel_members' as source FROM panel_members WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        // If not found in panels, check users table
        $stmt = $conn->prepare("SELECT username as id, 'users' as source FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
    }

    if ($res->num_rows > 0) {
        $valid_token = true;
        $user_info = $res->fetch_assoc();
    } else {
        // DEBUG: Check if the token exists but is expired
        $check_expired = $conn->prepare("SELECT reset_expiry FROM panel_members WHERE reset_token = ?");
        $check_expired->bind_param("s", $token);
        $check_expired->execute();
        $exp_res = $check_expired->get_result();
        
        if ($exp_res->num_rows > 0) {
            $message = "<div class='alert alert-warning'>This link has expired. Please request a new one.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Invalid security token.</div>";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $new_pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass === $confirm_pass) {
        // Note: For Panel members, we check if they still use default '123456'
        // But here we apply the new hashed password regardless
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $table = $user_info['source'];
        $id_col = ($table === 'users') ? 'username' : 'id';

        $update = $conn->prepare("UPDATE $table SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE $id_col = ?");
        $update->bind_param("ss", $hashed_pass, $user_info['id']);
        
        if ($update->execute()) {
            echo "<script>alert('Password updated! Please login.'); window.location='loginpage.html';</script>";
            exit();
        }
    } else {
        $message = "<div class='alert alert-danger'>Passwords do not match!</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center vh-100">
    <div class="container card p-4 shadow-sm" style="max-width: 400px;">
        <h3 class="text-center">New Password</h3>
        <?php echo $message; ?>
        
        <?php if ($valid_token): ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
                <button type="submit" class="btn btn-success w-100">Update Password</button>
            </form>
        <?php else: ?>
            <a href="forgotpassword.php" class="btn btn-primary w-100 mt-3">Request New Link</a>
        <?php endif; ?>
    </div>
</body>
</html>