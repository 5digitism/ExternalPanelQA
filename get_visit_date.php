<?php
// get_visit_date.php — returns the latest visit date + full visit history for a panel
// Used by HQA (HQApanel.php) to fetch live data recorded by PC
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// ── Auth ──────────────────────────────────────────────────────────────────────
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

$allowed_roles = ['admin', 'PC', 'Head QA'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit();
}

// ── Input ─────────────────────────────────────────────────────────────────────
$panel_id = isset($_GET['panel_id']) ? (int)$_GET['panel_id'] : 0;

if ($panel_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid panel ID.']);
    exit();
}

// ── Fetch latest visit date from panel_members (synced by save_visit_date.php) ─
$stmt = $conn->prepare("SELECT last_visit_date FROM panel_members WHERE id = ?");
$stmt->bind_param("i", $panel_id);
$stmt->execute();
$panel_row = $stmt->get_result()->fetch_assoc();

if (!$panel_row) {
    echo json_encode(['success' => false, 'message' => 'Panel not found.']);
    exit();
}

$last_visit_date = $panel_row['last_visit_date']; // may be null

// ── Fetch full visit history ordered newest first ─────────────────────────────
$hist_stmt = $conn->prepare("
    SELECT id, visit_date, note, recorded_by, created_at
    FROM panel_visit_history
    WHERE panel_id = ?
    ORDER BY visit_date DESC, created_at DESC
");
$hist_stmt->bind_param("i", $panel_id);
$hist_stmt->execute();
$hist_result = $hist_stmt->get_result();

$history = [];
while ($row = $hist_result->fetch_assoc()) {
    $history[] = [
        'id'          => $row['id'],
        'visit_date'  => $row['visit_date'],
        'note'        => $row['note'] ?? '',
        'recorded_by' => $row['recorded_by'],
        'created_at'  => $row['created_at'],
    ];
}

echo json_encode([
    'success'         => true,
    'panel_id'        => $panel_id,
    'last_visit_date' => $last_visit_date,   // null if never visited
    'history'         => $history,
    'total_visits'    => count($history),
]);