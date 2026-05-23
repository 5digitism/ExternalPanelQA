<?php
require_once 'db.php';

function createNotification($conn, $evaluation_id, $type, $qa_message, $panel_message = null) {

    // Use qa_message for panel too if no separate panel message given
    if ($panel_message === null) $panel_message = $qa_message;

    // Get panel_id from evaluation
    $stmt = $conn->prepare("
        SELECT e.panel_id, p.panel_name
        FROM evaluations e
        JOIN panel_members p ON e.panel_id = p.id
        WHERE e.id = ?
    ");
    $stmt->bind_param("i", $evaluation_id);
    $stmt->execute();
    $eval = $stmt->get_result()->fetch_assoc();
    if (!$eval) return;

    // Notify all Head QA users — use qa_message
    $qa_users = $conn->query("SELECT username FROM users WHERE role = 'Head QA'");
    if ($qa_users) {
        while ($qa = $qa_users->fetch_assoc()) {
            $recip = $qa['username'];

            $check = $conn->prepare("
                SELECT id FROM notifications
                WHERE evaluation_id = ?
                AND recipient_type = 'qa'
                AND recipient_id = ?
                AND type = ?
                AND DATE(created_at) = CURDATE()
            ");
            $check->bind_param("iss", $evaluation_id, $recip, $type);
            $check->execute();
            if ($check->get_result()->num_rows > 0) continue;

            $ins = $conn->prepare("
                INSERT INTO notifications
                (type, evaluation_id, recipient_type, recipient_id, message)
                VALUES (?, ?, 'qa', ?, ?)
            ");
            $ins->bind_param("siss", $type, $evaluation_id, $recip, $qa_message);
            $ins->execute();
        }
    }

    // Notify the panel — use panel_message
    $panel_id = (string)$eval['panel_id'];

    $check2 = $conn->prepare("
        SELECT id FROM notifications
        WHERE evaluation_id = ?
        AND recipient_type = 'panel'
        AND recipient_id = ?
        AND type = ?
        AND DATE(created_at) = CURDATE()
    ");
    $check2->bind_param("iss", $evaluation_id, $panel_id, $type);
    $check2->execute();

    if ($check2->get_result()->num_rows === 0) {
        $ins2 = $conn->prepare("
            INSERT INTO notifications
            (type, evaluation_id, recipient_type, recipient_id, message)
            VALUES (?, ?, 'panel', ?, ?)
        ");
        $ins2->bind_param("siss", $type, $evaluation_id, $panel_id, $panel_message);
        $ins2->execute();
    }
}