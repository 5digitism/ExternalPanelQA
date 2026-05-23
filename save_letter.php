<?php
session_start();
require_once 'db.php';

// Force clear JSON formatting output
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized user session. Please log in again.']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'save') {
    $panel_id    = isset($_POST['panel_id']) ? intval($_POST['panel_id']) : 0;
    $ref_no      = trim($_POST['ref_no'] ?? '');
    $letter_date = trim($_POST['letter_date'] ?? date('Y-m-d'));
    $visit_date  = trim($_POST['visit_date'] ?? '');
    $visit_time  = trim($_POST['visit_time'] ?? '');
    $venue       = trim($_POST['venue'] ?? '');
    $purpose     = trim($_POST['purpose'] ?? '');
    $html        = $_POST['html'] ?? '';   // ← actual letter HTML

    if ($panel_id === 0 || empty($ref_no) || empty($visit_date)) {
        echo json_encode(['success' => false, 'message' => 'Mandatory fields missing.']);
        exit();
    }

    $check = $conn->prepare("SELECT id FROM invitation_letters WHERE panel_id = ?");
    $check->bind_param("i", $panel_id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $stmt = $conn->prepare("
            UPDATE invitation_letters 
            SET ref_no=?, letter_date=?, visit_date=?, visit_time=?, venue=?, purpose=?, generated_html=?
            WHERE panel_id=?
        ");
        $stmt->bind_param("sssssssi", $ref_no, $letter_date, $visit_date, $visit_time, $venue, $purpose, $html, $panel_id);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO invitation_letters 
            (panel_id, ref_no, letter_date, visit_date, visit_time, venue, purpose, generated_html)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssssss", $panel_id, $ref_no, $letter_date, $visit_date, $visit_time, $venue, $purpose, $html);
    }

    echo $stmt->execute()
        ? json_encode(['success' => true])
        : json_encode(['success' => false, 'message' => $stmt->error]);
    exit();
}

if ($action === 'get_saved') {
    $panel_id = isset($_GET['panel_id']) ? intval($_GET['panel_id']) : 0;

    $stmt = $conn->prepare("
        SELECT ref_no, letter_date, visit_date, visit_time, venue, purpose, generated_html 
        FROM invitation_letters 
        WHERE panel_id = ?
    ");
    $stmt->bind_param("i", $panel_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        echo json_encode(['success' => true, 'html' => $row['generated_html']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No saved letter found.']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid configuration action process context request.']);
exit();