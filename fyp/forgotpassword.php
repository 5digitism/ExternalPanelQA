<?php
date_default_timezone_set('Asia/Kuala_Lumpur');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'db.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $stmt = $conn->prepare("SELECT id FROM panel_members WHERE email = ? AND status = 'Approved'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
       // Sets expiry to 30 minutes from the current time
        $expiry = date("Y-m-d H:i:s", strtotime('+30 minutes'));

        $update = $conn->prepare("UPDATE panel_members SET reset_token = ?, reset_expiry = ? WHERE email = ?");
        $update->bind_param("sss", $token, $expiry, $email);
        $update->execute();

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'dopigangi@gmail.com'; 
            $mail->Password = 'uhle ptsx nbdg wfvi'; // The Google App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('admin@eap-system.com', 'EAP System Admin');
            $mail->addAddress($email);

            $resetLink = "http://localhost/fyp/resetpassword.php?token=$token";
            
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset - EAP System';
            $mail->Body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px; border-radius: 10px;'>
        <div style='text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 20px;'>
            <h2 style='color: #007bff; margin: 0;'>EAP System</h2>
        </div>
        <p>Hello,</p>
        <p>We received a request to reset the password for your account. You can reset your password by clicking the button below:</p>
        <div style='text-align: center; margin: 30px 0;'>
            <a href='$resetLink' style='background-color: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Reset Password</a>
        </div>
        <p><strong>Note:</strong> This link is valid for <b>30 minutes</b> only. If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
        <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
        <p style='font-size: 12px; color: #777;'>
            If you're having trouble clicking the button, copy and paste the link below into your web browser:<br>
            <a href='$resetLink' style='color: #007bff;'>$resetLink</a>
        </p>
        <p style='font-size: 12px; color: #777; text-align: center; margin-top: 30px;'>
            &copy; " . date('Y') . " External Advisory Panels Management System. All rights reserved.
        </p>
    </div>";

    // Plain text version for non-HTML email clients
    $mail->AltBody = "Hello, click the link below to reset your password: $resetLink. This link is valid for 30 minutes.";
            $mail->send();
            $message = "<div class='alert alert-success'>Link sent! Check your inbox.</div>";
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>Email failed. Error: {$mail->ErrorInfo}</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Email not found.</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center vh-100">
    <div class="container card p-4 shadow-sm" style="max-width: 400px;">
        <h4 class="text-center">Forgot Password</h4>
        <?php echo $message; ?>
        <form method="POST">
            <input type="email" name="email" class="form-control mb-3" placeholder="Enter Registered Email" required>
            <button type="submit" class="btn btn-primary w-100">Verify Identity</button>
            <a href="loginpage.html" class="d-block text-center mt-3 text-decoration-none">Back to Login</a>
        </form>
    </div>
</body>
</html>