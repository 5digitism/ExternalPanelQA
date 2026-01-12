<?php
// Enable error reporting to debug blank screens
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Collect and Sanitize Input
    $panel_name    = mysqli_real_escape_string($conn, $_POST['panel_name']);
    $email         = mysqli_real_escape_string($conn, $_POST['email']);      // New Field
    $phone         = mysqli_real_escape_string($conn, $_POST['phone']);      // New Field
    $level         = mysqli_real_escape_string($conn, $_POST['level']);
    $programme     = mysqli_real_escape_string($conn, $_POST['programme']);
    $qualification = mysqli_real_escape_string($conn, $_POST['qualification']);
    $start_date    = mysqli_real_escape_string($conn, $_POST['start_date']);
    $remarks       = mysqli_real_escape_string($conn, $_POST['remarks']);
    $default_pass  = "123456"; // Default password for first login

    // 2. File Upload Logic
    $target_dir = "uploads/resumes/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($_FILES["resume"]["name"], PATHINFO_EXTENSION));
    $new_filename   = "Resume_" . time() . "_" . preg_replace("/[^a-zA-Z0-9]/", "_", $panel_name) . ".pdf";
    $target_file    = $target_dir . $new_filename;

    if ($file_extension != "pdf") {
        header("Location: newpanel.php?status=error&msg=Only+PDF+files+are+allowed.");
        exit();
    }

    if (move_uploaded_file($_FILES["resume"]["tmp_name"], $target_file)) {
        
        // 3. Database Insert (Including Email and Phone)
        // Ensure your database columns are named 'email' and 'phone'
        $sql = "INSERT INTO panel_members (panel_name, email, phone, level, programme, qualification, start_date, resume_path, remarks, status, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            // "ssssssssss" means 10 strings are being bound
            mysqli_stmt_bind_param($stmt, "ssssssssss", 
                $panel_name, 
                $email, 
                $phone, 
                $level, 
                $programme, 
                $qualification, 
                $start_date, 
                $target_file, 
                $remarks,
                $default_pass
            );

            if (mysqli_stmt_execute($stmt)) {
                // Log activity to the activity_log table
                $log_desc = "New panel registered: " . $panel_name;
                logActivity($conn, $log_desc, "New", "bg-primary");

                header("Location: newpanel.php?status=success");
                exit();
            } else {
                header("Location: newpanel.php?status=error&msg=" . urlencode(mysqli_stmt_error($stmt)));
                exit();
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        header("Location: newpanel.php?status=error&msg=Failed+to+upload+file.");
        exit();
    }
}
?>