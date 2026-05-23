<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$evaluation_id      = intval($_POST['evaluation_id'] ?? 0);

$latest_visit_date  = !empty($_POST['latest_visit_date']) ? $_POST['latest_visit_date'] : null;

$iac_irpc           = trim($_POST['iac_irpc'] ?? '');
$uac_urpc           = trim($_POST['uac_urpc'] ?? '');
$senate             = trim($_POST['senate'] ?? '');

$iac_date           = !empty($_POST['iac_date']) ? $_POST['iac_date'] : null;
$uac_date           = !empty($_POST['uac_date']) ? $_POST['uac_date'] : null;
$senate_date        = !empty($_POST['senate_date']) ? $_POST['senate_date'] : null;

if ($evaluation_id <= 0) {
    http_response_code(400);
    exit('Invalid evaluation ID');
}

$stmt = $conn->prepare("
    INSERT INTO qa_submission_status (
        evaluation_id,
        latest_visit_date,
        iac_irpc,
        uac_urpc,
        senate,
        iac_date,
        uac_date,
        senate_date
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)

    ON DUPLICATE KEY UPDATE
        latest_visit_date = VALUES(latest_visit_date),
        iac_irpc          = VALUES(iac_irpc),
        uac_urpc          = VALUES(uac_urpc),
        senate            = VALUES(senate),
        iac_date          = VALUES(iac_date),
        uac_date          = VALUES(uac_date),
        senate_date       = VALUES(senate_date),
        updated_at        = CURRENT_TIMESTAMP
");

$stmt->bind_param(
    "isssssss",
    $evaluation_id,
    $latest_visit_date,
    $iac_irpc,
    $uac_urpc,
    $senate,
    $iac_date,
    $uac_date,
    $senate_date
);

echo $stmt->execute() ? 'ok' : 'error';