<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['panel_id'])) {
    header("Location: login.html");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_pass = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    if (strlen($new_pass) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($new_pass !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $panel_id = $_SESSION['panel_id'];
        $sql = "UPDATE panel_members SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $new_pass, $panel_id);
        
        if (mysqli_stmt_execute($stmt)) {
            header("Location: panel_dashboard.php");
            exit();
        } else {
            $error = "Update failed. Please try again.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>First Login Security Update</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .card { width: 100%; max-width: 400px; padding: 30px; border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class="card">
    <h4 class="text-center fw-bold mb-3">Setup Your Account</h4>
    <p class="text-muted text-center small mb-4">You are using a temporary password. Please set a new one to continue.</p>
    <?php if($error): ?> <div class="alert alert-danger py-2 small"><?= $error ?></div> <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold">New Password</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 fw-bold">Update & Login</button>
    </form>
</div>
</body>
</html>