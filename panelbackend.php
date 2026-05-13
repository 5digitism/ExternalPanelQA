<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Detect which page submitted the form so we redirect back correctly
    $referer      = $_SERVER['HTTP_REFERER'] ?? '';
    $redirect_page = str_contains($referer, 'newpanel') ? 'newpanel.php' : 'newpanel.php';

    // Helper: redirect with error
    function fail($page, $msg) {
        header("Location: {$page}?status=error&msg=" . urlencode($msg));
        exit();
    }

    // 1. Collect and sanitize input
    $panel_name    = mysqli_real_escape_string($conn, $_POST['panel_name']   ?? '');
    $email         = mysqli_real_escape_string($conn, $_POST['email']        ?? '');
    $phone         = mysqli_real_escape_string($conn, $_POST['phone']        ?? '');
    $level         = mysqli_real_escape_string($conn, $_POST['level']        ?? '');
    $programme     = mysqli_real_escape_string($conn, $_POST['programme']    ?? '');
    $qualification = mysqli_real_escape_string($conn, $_POST['qualification'] ?? '');
    $start_date    = mysqli_real_escape_string($conn, $_POST['start_date']   ?? '');
    $remarks       = mysqli_real_escape_string($conn, $_POST['remarks']      ?? '');
    $default_pass  = "123456";

    // 2. Build upload directory using absolute path (fixes permission issues)
    $target_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'resumes' . DIRECTORY_SEPARATOR;

    // Create directory recursively if it doesn't exist
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0775, true)) {
            fail($redirect_page, "System failed to create upload directory. Check server folder permissions.");
        }
    }

    // Make sure the directory is actually writable
    if (!is_writable($target_dir)) {
        fail($redirect_page, "Upload folder exists but is not writable. Please contact your system administrator.");
    }

    // 3. Check for PHP upload errors
    if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            1 => "File exceeds upload_max_filesize in php.ini",
            2 => "File exceeds MAX_FILE_SIZE in HTML form",
            3 => "File was only partially uploaded",
            4 => "No file was uploaded",
            6 => "Missing a temporary folder",
            7 => "Failed to write file to disk — check server disk space",
            8 => "A PHP extension stopped the file upload",
        ];
        $code    = $_FILES['resume']['error'] ?? 4;
        $err_msg = $upload_errors[$code] ?? "Unknown upload error (code {$code})";
        fail($redirect_page, $err_msg);
    }

    // 4. Validate file type (by extension AND MIME type)
    $file_ext  = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
    $file_mime = mime_content_type($_FILES['resume']['tmp_name']);

    if ($file_ext !== 'pdf' || $file_mime !== 'application/pdf') {
        fail($redirect_page, "Only PDF files are allowed. Detected type: {$file_mime}");
    }

    // 5. Build safe filename and move file
    $safe_name    = preg_replace('/[^a-zA-Z0-9]/', '_', $panel_name);
    $new_filename = 'Resume_' . time() . '_' . $safe_name . '.pdf';
    $target_file  = $target_dir . $new_filename;
    // Store a relative path in the DB (portable across environments)
    $db_path      = 'uploads/resumes/' . $new_filename;

    if (!move_uploaded_file($_FILES['resume']['tmp_name'], $target_file)) {
        // Give a more detailed diagnosis
        $tmp = $_FILES['resume']['tmp_name'];
        $detail = "tmp={$tmp} | target={$target_file} | dir_writable=" . (is_writable($target_dir) ? 'yes' : 'no');
        fail($redirect_page, "Could not move uploaded file. Details: {$detail}");
    }

    // 6. Insert into database
    $sql  = "INSERT INTO panel_members 
                 (panel_name, email, phone, level, programme, qualification, start_date, resume_path, remarks, status, password)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        fail($redirect_page, "DB prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "ssssssssss",
        $panel_name, $email, $phone, $level, $programme,
        $qualification, $start_date, $db_path, $remarks, $default_pass
    );

    if (mysqli_stmt_execute($stmt)) {
        if (function_exists('logActivity')) {
            logActivity($conn, "New panel registered: " . $panel_name, "New", "bg-primary");
        }
        header("Location: {$redirect_page}?status=success");
        exit();
    } else {
        // Clean up the uploaded file if DB insert failed
        @unlink($target_file);
        fail($redirect_page, "Database error: " . mysqli_stmt_error($stmt));
    }
}
?>