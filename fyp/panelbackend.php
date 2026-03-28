<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Collect and Sanitize Input
    $panel_name    = mysqli_real_escape_string($conn, $_POST['panel_name']);
    $email         = mysqli_real_escape_string($conn, $_POST['email']);
    $phone         = mysqli_real_escape_string($conn, $_POST['phone']);
    $level         = mysqli_real_escape_string($conn, $_POST['level']);
    $programme     = mysqli_real_escape_string($conn, $_POST['programme']);
    $qualification = mysqli_real_escape_string($conn, $_POST['qualification']);
    $start_date    = mysqli_real_escape_string($conn, $_POST['start_date']);
    $remarks       = mysqli_real_escape_string($conn, $_POST['remarks']);
    $default_pass  = "123456"; 

    // 2. FIXED File Upload Logic
    $target_dir = "uploads/resumes/";
    
    // Create directory if it doesn't exist with full permissions
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            header("Location: newpanel.php?status=error&msg=System+failed+to+create+upload+directory.");
            exit();
        }
    }

    // Check for PHP Upload Errors
    if ($_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            1 => "File exceeds upload_max_filesize in php.ini",
            2 => "File exceeds MAX_FILE_SIZE in HTML form",
            3 => "File was only partially uploaded",
            4 => "No file was uploaded",
            6 => "Missing a temporary folder",
            7 => "Failed to write file to disk",
            8 => "A PHP extension stopped the file upload"
        ];
        $err_msg = $error_messages[$_FILES['resume']['error']] ?? "Unknown upload error";
        header("Location: newpanel.php?status=error&msg=" . urlencode($err_msg));
        exit();
    }

    $file_extension = strtolower(pathinfo($_FILES["resume"]["name"], PATHINFO_EXTENSION));
    $new_filename   = "Resume_" . time() . "_" . preg_replace("/[^a-zA-Z0-9]/", "_", $panel_name) . ".pdf";
    $target_file    = $target_dir . $new_filename;

    if ($file_extension != "pdf") {
        header("Location: newpanel.php?status=error&msg=Only+PDF+files+are+allowed.");
        exit();
    }

    // 3. Move File and Insert into Database
    if (move_uploaded_file($_FILES["resume"]["tmp_name"], $target_file)) {
        
        $sql = "INSERT INTO panel_members (panel_name, email, phone, level, programme, qualification, start_date, resume_path, remarks, status, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssssssss", 
                $panel_name, $email, $phone, $level, $programme, 
                $qualification, $start_date, $target_file, $remarks, $default_pass
            );

            if (mysqli_stmt_execute($stmt)) {
                // Log activity
                if(function_exists('logActivity')) {
                    logActivity($conn, "New panel registered: " . $panel_name, "New", "bg-primary");
                }
                header("Location: newpanel.php?status=success");
                exit();
            } else {
                header("Location: newpanel.php?status=error&msg=Database+Error:+" . urlencode(mysqli_stmt_error($stmt)));
                exit();
            }
        }
    } else {
        header("Location: newpanel.php?status=error&msg=Server+permission+denied+to+move+file+to+uploads+folder.");
        exit();
    }
}
?>