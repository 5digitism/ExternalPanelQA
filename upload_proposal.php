<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$action        = $_POST['action'] ?? $_GET['action'] ?? '';
$username      = $_SESSION['username'];
$role          = $_SESSION['role'];

// ── Upload ────────────────────────────────────────────────────
if ($action === 'upload') {
    $evaluation_id = intval($_POST['evaluation_id'] ?? 0);
    $stage         = $_POST['stage'] ?? '';

    if (!in_array($stage, ['iac', 'uac', 'senate'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid stage.']);
        exit();
    }

    // PC can only upload IAC
    if ($role === 'PC' && $stage !== 'iac') {
        echo json_encode(['success' => false, 'message' => 'PC can only upload IAC proposals.']);
        exit();
    }

    if (!isset($_FILES['proposal']) || $_FILES['proposal']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
        exit();
    }

    $file         = $_FILES['proposal'];
    $original     = basename($file['name']);
    $ext          = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if ($ext !== 'pdf') {
        echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed.']);
        exit();
    }

    // Max 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large. Max 10MB.']);
        exit();
    }

    $upload_dir = __DIR__ . '/uploads/proposals/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $filename  = $stage . '_eval' . $evaluation_id . '_' . time() . '.pdf';
    $file_path = 'uploads/proposals/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
        exit();
    }

    $uploaded_by = ($role === 'PC') ? 'pc' : 'qa';

    $stmt = $conn->prepare("
        INSERT INTO proposal_uploads 
        (evaluation_id, stage, uploaded_by, uploader_username, file_path, original_name)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssss", $evaluation_id, $stage, $uploaded_by, $username, $file_path, $original);

    echo $stmt->execute()
        ? json_encode(['success' => true, 'file_path' => $file_path, 'original_name' => $original])
        : json_encode(['success' => false, 'message' => $stmt->error]);
    exit();
}

// ── Get uploads for an evaluation ────────────────────────────
if ($action === 'get') {
    $evaluation_id = intval($_GET['evaluation_id'] ?? 0);
    $stage         = $_GET['stage'] ?? '';

    if (!in_array($stage, ['iac', 'uac', 'senate'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid stage.']);
        exit();
    }

    $stmt = $conn->prepare("
        SELECT id, original_name, file_path, uploader_username, uploaded_by, uploaded_at
        FROM proposal_uploads
        WHERE evaluation_id = ? AND stage = ?
        ORDER BY uploaded_at DESC
    ");
    $stmt->bind_param("is", $evaluation_id, $stage);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'files' => $rows]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);