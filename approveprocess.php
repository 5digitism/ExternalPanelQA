<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once 'db.php'; //
require_once 'generateletter.php'; //

// Load PHPMailer files
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) { //
    $id = (int)$_POST['id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['action']); 
    $admin_note = mysqli_real_escape_string($conn, $_POST['admin_note']);
    
    $full_note = "\n\n[HQA DECISION - " . date('Y-m-d H:i') . "]: " . $admin_note;
    
    // Update database status
    $sql = "UPDATE panel_members SET status = ?, remarks = CONCAT(IFNULL(remarks,''), ?) WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssi", $new_status, $full_note, $id);

        if (mysqli_stmt_execute($stmt)) {
            // Log activity
            logActivity($conn, "Decision made for Panel EAP-P-$id", $new_status, ($new_status == 'Approved' ? 'bg-success' : 'bg-danger'));

            // If Approved, trigger the email
            if ($new_status === 'Approved') {
                $res = $conn->query("SELECT * FROM panel_members WHERE id = $id");
                $panelData = $res->fetch_assoc();

                if ($panelData && !empty($panelData['email'])) {
                    sendApprovalEmail($panelData);
                }
            }

            header("Location: approvalpage.php?status=success&msg=Decision+Recorded+and+Email+Sent"); //
            exit();
        }
    }
}

function sendApprovalEmail($data) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dopigangi@gmail.com'; // Your Gmail address
        $mail->Password   = 'liur ehhz bowt dwsl'; // The code from the yellow box
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('admin@eap-system.com', 'EAP System Admin');
        $mail->addAddress($data['email'], $data['panel_name']);

        // Generate PDF string from generateletter.php
        // We use 'S' mode to return PDF as a string for email attachment
        $pdf_content = generatePanelLetterPDF($data, 'S'); 
        $mail->addStringAttachment($pdf_content, 'Appointment_Letter.pdf');

        $mail->isHTML(true);
        $mail->Subject = 'Official Appointment Letter';
        $mail->Body    = "Dear <b>{$data['panel_name']}</b>,<br><br>Congratulations! Your application is approved. Please find your letter attached.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>