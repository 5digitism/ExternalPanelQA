<?php
// announcements.php — AJAX backend for QA announcements
// Actions: list | create | delete
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? 'list';

// ── LIST ────────────────────────────────────────────────────
if ($action === 'list') {
    // Show active (not yet expired) announcements, newest first
    $sql = "SELECT id, title, body, priority, created_by, created_at, expires_at
            FROM announcements
            WHERE expires_at IS NULL OR expires_at >= CURDATE()
            ORDER BY
                FIELD(priority,'urgent','important','normal'),
                created_at DESC";
    $res = $conn->query($sql);
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    echo json_encode(['ok' => true, 'data' => $rows]);
    exit;
}

// ── CREATE (QA only) ────────────────────────────────────────
if ($action === 'create') {
    if (!isset($_SESSION['username'])) {
        echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
        exit;
    }
    $title    = trim($_POST['title']    ?? '');
    $body     = trim($_POST['body']     ?? '');
    $priority = trim($_POST['priority'] ?? 'normal');
    $expires  = trim($_POST['expires']  ?? '');

    if ($title === '' || $body === '') {
        echo json_encode(['ok' => false, 'msg' => 'Title and body required']);
        exit;
    }
    $allowed = ['normal', 'important', 'urgent'];
    if (!in_array($priority, $allowed)) $priority = 'normal';

    $expires_val = ($expires !== '') ? $expires : null;
    $by = $conn->real_escape_string($_SESSION['username']);

    $stmt = $conn->prepare(
        "INSERT INTO announcements (title, body, priority, created_by, expires_at)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sssss', $title, $body, $priority, $by, $expires_val);
    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        echo json_encode(['ok' => true, 'id' => $newId]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'DB error']);
    }
    exit;
}

// ── DELETE (QA only) ────────────────────────────────────────
if ($action === 'delete') {
    if (!isset($_SESSION['username'])) {
        echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
        exit;
    }
    $id = intval($_POST['id'] ?? 0);
    if ($id < 1) { echo json_encode(['ok' => false, 'msg' => 'Invalid id']); exit; }

    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Unknown action']);