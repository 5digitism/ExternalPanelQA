<?php
session_start();
require_once 'db.php';

// Determine who is asking
if (isset($_SESSION['panel_id'])) {
    $type = 'panel';
    $id   = (string)$_SESSION['panel_id'];
} elseif (isset($_SESSION['username'])) {
    $type = 'qa';
    $id   = $_SESSION['username'];
} else {
    echo json_encode([]);
    exit();
}

$action = $_GET['action'] ?? 'fetch';

// ── Mark single as read ──────────────────────────────
if ($action === 'mark_read') {
    $notif_id = intval($_GET['id'] ?? 0);
    $now      = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1, seen_at = ?
        WHERE id = ? AND recipient_type = ? AND recipient_id = ?
    ");
    $stmt->bind_param("siss", $now, $notif_id, $type, $id);
    $stmt->execute();
    echo 'ok';
    exit();
}

// ── Mark all as read ─────────────────────────────────
if ($action === 'mark_all_read') {
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1, seen_at = ?
        WHERE recipient_type = ? AND recipient_id = ? AND is_read = 0
    ");
    $stmt->bind_param("sss", $now, $type, $id);
    $stmt->execute();
    echo 'ok';
    exit();
}

// ── Fetch notifications ──────────────────────────────
$stmt = $conn->prepare("
    SELECT * FROM notifications
    WHERE recipient_type = ? AND recipient_id = ?
    ORDER BY created_at DESC
    LIMIT 30
");
$stmt->bind_param("ss", $type, $id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
echo json_encode($rows);