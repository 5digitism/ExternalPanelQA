<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$allowed_roles = ['admin', 'PC', 'Head QA'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$action = $_POST['action'] ?? '';

// ── ACTION: Save / update a visit date for a specific evaluation ──────────────
if ($action === 'save') {
    $evaluation_id = intval($_POST['evaluation_id'] ?? 0);
    $visit_date    = !empty($_POST['visit_date']) ? $_POST['visit_date'] : null;

    if ($evaluation_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid evaluation ID']);
        exit();
    }

    // Validate date format
    if ($visit_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $visit_date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit();
    }

    // Upsert into qa_submission_status
    $stmt = $conn->prepare("
        INSERT INTO qa_submission_status (evaluation_id, latest_visit_date)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE
            latest_visit_date = VALUES(latest_visit_date),
            updated_at        = CURRENT_TIMESTAMP
    ");
    $stmt->bind_param("is", $evaluation_id, $visit_date);

    if ($stmt->execute()) {
        // Log the activity
        $username = $_SESSION['username'];
        $label    = $visit_date ?? 'cleared';
        logActivity($conn,
            "PC ($username) updated visit date for evaluation #$evaluation_id to $label",
            'Visit Updated',
            'bg-info'
        );
        echo json_encode(['success' => true, 'message' => 'Visit date saved']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit();
}

// ── ACTION: Get full visit history for a panel ────────────────────────────────
if ($action === 'get_history') {
    $panel_id = intval($_GET['panel_id'] ?? $_POST['panel_id'] ?? 0);

    if ($panel_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid panel ID']);
        exit();
    }

    $stmt = $conn->prepare("
        SELECT
            e.id            AS evaluation_id,
            e.title         AS eval_title,
            e.evaluation_date,
            q.latest_visit_date,
            q.updated_at    AS visit_updated_at
        FROM evaluations e
        LEFT JOIN qa_submission_status q ON q.evaluation_id = e.id
        WHERE e.panel_id = ?
          AND q.latest_visit_date IS NOT NULL
        ORDER BY q.latest_visit_date DESC
    ");
    $stmt->bind_param("i", $panel_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = [
            'evaluation_id'   => $row['evaluation_id'],
            'eval_title'      => $row['eval_title'] ?? 'Evaluation #' . $row['evaluation_id'],
            'evaluation_date' => $row['evaluation_date'],
            'visit_date'      => $row['latest_visit_date'],
            'visit_updated'   => $row['visit_updated_at'],
        ];
    }

    echo json_encode(['success' => true, 'history' => $history]);
    exit();
}

// ── ACTION: Get the latest evaluation ID for a panel (for saving visit date) ──
if ($action === 'get_latest_eval') {
    $panel_id = intval($_POST['panel_id'] ?? 0);

    if ($panel_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid panel ID']);
        exit();
    }

    $stmt = $conn->prepare("
        SELECT id FROM evaluations
        WHERE panel_id = ?
        ORDER BY evaluation_date DESC, id DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $panel_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        echo json_encode(['success' => true, 'evaluation_id' => $row['id']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No evaluations found for this panel']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);