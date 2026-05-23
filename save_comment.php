<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$action = $_POST['action'] ?? 'save_comment';

if ($action === 'save_comment') {

    $issue_id   = intval($_POST['issue_id'] ?? 0);
    $pc_comment = trim($_POST['pc_comment'] ?? '');
    $remark     = trim($_POST['remark'] ?? '');

    if ($issue_id <= 0) { http_response_code(400); exit('Invalid issue ID'); }

    $stmt = $conn->prepare("UPDATE criteria_issues SET pc_comment = ?, remark = ? WHERE id = ?");
    $stmt->bind_param("ssi", $pc_comment, $remark, $issue_id);
    echo $stmt->execute() ? 'ok' : 'error';

} elseif ($action === 'update_status') {

    $criteria_id = intval($_POST['criteria_id'] ?? 0);
    $status      = $_POST['status'] === 'closed' ? 'closed' : 'open';

    if ($criteria_id <= 0) { http_response_code(400); exit('Invalid criteria ID'); }

    if ($status === 'closed') {
        $closed_at = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE evaluation_criteria SET status = ?, closed_at = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $closed_at, $criteria_id);
    } else {
        $stmt = $conn->prepare("UPDATE evaluation_criteria SET status = ?, closed_at = NULL WHERE id = ?");
        $stmt->bind_param("si", $status, $criteria_id);
    }

    echo $stmt->execute() ? $status : 'error';
}