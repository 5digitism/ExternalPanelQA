<?php
// announcements.php — CRUD API for QA Announcements
// POST  action=post   → create announcement (QA/admin only)
// GET   action=fetch  → return announcements (all roles)
// POST  action=delete → soft-delete announcement (QA/admin only)

session_start();
require_once 'db.php';

header('Content-Type: application/json');

// ── Auth ──────────────────────────────────────────────────────────────────────
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

$role     = $_SESSION['role']     ?? '';
$username = $_SESSION['username'] ?? '';
$name     = $_SESSION['name']     ?? $username;

$can_post = in_array($role, ['admin', 'Head QA']);

// ── Ensure table exists (auto-create if missing) ──────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS qa_announcements (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        title       VARCHAR(255)  NOT NULL,
        body        TEXT          NOT NULL,
        priority    ENUM('normal','important','urgent') DEFAULT 'normal',
        posted_by   VARCHAR(100)  NOT NULL,
        poster_name VARCHAR(150)  NOT NULL,
        is_deleted  TINYINT(1)    DEFAULT 0,
        created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP
    )
");

// ── Route ─────────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? 'fetch';

// ── FETCH ─────────────────────────────────────────────────────────────────────
if ($action === 'fetch') {
    $limit  = isset($_GET['limit'])  ? (int)$_GET['limit']  : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    $stmt = $conn->prepare("
        SELECT id, title, body, priority, poster_name, created_at
        FROM   qa_announcements
        WHERE  is_deleted = 0
        ORDER  BY created_at DESC
        LIMIT  ? OFFSET ?
    ");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Count for unread badge
    $total_res = $conn->query("SELECT COUNT(*) as cnt FROM qa_announcements WHERE is_deleted = 0");
    $total = $total_res->fetch_assoc()['cnt'];

    echo json_encode(['success' => true, 'announcements' => $rows, 'total' => (int)$total]);
    exit();
}

// ── POST ──────────────────────────────────────────────────────────────────────
if ($action === 'post') {
    if (!$can_post) {
        echo json_encode(['success' => false, 'message' => 'Only Head QA / Admin can post announcements.']);
        exit();
    }

    $title    = trim($_POST['title']    ?? '');
    $body     = trim($_POST['body']     ?? '');
    $priority = trim($_POST['priority'] ?? 'normal');

    if (empty($title) || empty($body)) {
        echo json_encode(['success' => false, 'message' => 'Title and body are required.']);
        exit();
    }

    if (!in_array($priority, ['normal', 'important', 'urgent'])) {
        $priority = 'normal';
    }

    $stmt = $conn->prepare("
        INSERT INTO qa_announcements (title, body, priority, posted_by, poster_name)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssss", $title, $body, $priority, $username, $name);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        // Log activity
        if (function_exists('logActivity')) {
            logActivity($conn, "QA Announcement posted: \"$title\"", "Announcement", "bg-info");
        }
        echo json_encode(['success' => true, 'message' => 'Announcement posted.', 'id' => $new_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to post: ' . $stmt->error]);
    }
    exit();
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    if (!$can_post) {
        echo json_encode(['success' => false, 'message' => 'Permission denied.']);
        exit();
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE qa_announcements SET is_deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Announcement removed.']);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);