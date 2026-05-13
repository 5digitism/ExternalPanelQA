<?php
// save_visit_date.php  — handles PC saving/editing a panel visit date
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

$recorded_by = $_SESSION['username'];

// ── Input ─────────────────────────────────────────────────────────────────────
$panel_id   = isset($_POST['panel_id'])   ? (int)$_POST['panel_id']            : 0;
$visit_date = isset($_POST['visit_date']) ? trim($_POST['visit_date'])          : '';
$note       = isset($_POST['note'])       ? trim($_POST['note'])                : '';
$action     = isset($_POST['action'])     ? trim($_POST['action'])              : 'add';  // 'add' | 'edit' | 'delete'
$history_id = isset($_POST['history_id']) ? (int)$_POST['history_id']          : 0;

// ── Validate ──────────────────────────────────────────────────────────────────
if ($panel_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid panel.']);
    exit();
}

if ($action !== 'delete') {
    if (empty($visit_date)) {
        echo json_encode(['success' => false, 'message' => 'Visit date is required.']);
        exit();
    }
    // Validate date format  YYYY-MM-DD
    $d = DateTime::createFromFormat('Y-m-d', $visit_date);
    if (!$d || $d->format('Y-m-d') !== $visit_date) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
        exit();
    }
}

// Ownership check — PC can only touch panels in their programme
if ($_SESSION['role'] === 'PC') {
    $ck = $conn->prepare("SELECT pm.id FROM panel_members pm
                          JOIN users u ON u.username = ?
                          WHERE pm.id = ? AND pm.programme = u.programme
                          AND TRIM(LOWER(pm.status)) = 'approved'");
    $ck->bind_param("si", $recorded_by, $panel_id);
    $ck->execute();
    if ($ck->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'You do not have access to this panel.']);
        exit();
    }
}

// ── Actions ───────────────────────────────────────────────────────────────────

if ($action === 'add') {
    // Insert into history
    $ins = $conn->prepare("INSERT INTO panel_visit_history (panel_id, visit_date, recorded_by, note) VALUES (?, ?, ?, ?)");
    $ins->bind_param("isss", $panel_id, $visit_date, $recorded_by, $note);
    if (!$ins->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to save visit: ' . $ins->error]);
        exit();
    }

    // Sync last_visit_date on panel_members (only if this date is newer)
    $conn->query("UPDATE panel_members
                  SET last_visit_date = '$visit_date'
                  WHERE id = $panel_id
                    AND (last_visit_date IS NULL OR last_visit_date < '$visit_date')");

    echo json_encode(['success' => true, 'message' => 'Visit date saved successfully.']);
    exit();
}

if ($action === 'edit') {
    if ($history_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid history record.']);
        exit();
    }
    $upd = $conn->prepare("UPDATE panel_visit_history
                            SET visit_date = ?, note = ?
                            WHERE id = ? AND panel_id = ?");
    $upd->bind_param("ssii", $visit_date, $note, $history_id, $panel_id);
    if (!$upd->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to update: ' . $upd->error]);
        exit();
    }

    // Re-sync last_visit_date to the MAX in history
    $conn->query("UPDATE panel_members pm
                  SET pm.last_visit_date = (
                      SELECT MAX(pvh.visit_date)
                      FROM panel_visit_history pvh
                      WHERE pvh.panel_id = $panel_id
                  )
                  WHERE pm.id = $panel_id");

    echo json_encode(['success' => true, 'message' => 'Visit date updated successfully.']);
    exit();
}

if ($action === 'delete') {
    if ($history_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid history record.']);
        exit();
    }
    $del = $conn->prepare("DELETE FROM panel_visit_history WHERE id = ? AND panel_id = ?");
    $del->bind_param("ii", $history_id, $panel_id);
    $del->execute();

    // Re-sync last_visit_date
    $conn->query("UPDATE panel_members pm
                  SET pm.last_visit_date = (
                      SELECT MAX(pvh.visit_date)
                      FROM panel_visit_history pvh
                      WHERE pvh.panel_id = $panel_id
                  )
                  WHERE pm.id = $panel_id");

    echo json_encode(['success' => true, 'message' => 'Visit record deleted.']);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);