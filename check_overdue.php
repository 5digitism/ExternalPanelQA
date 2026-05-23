<?php
require_once 'db.php';
require_once 'create_notification.php';

$result = $conn->query("
    SELECT
        e.id            AS evaluation_id,
        e.evaluation_date,
        e.title,
        p.panel_name,
        p.id            AS panel_id,
        p.programme,
        qs.iac_irpc
    FROM evaluations e
    JOIN panel_members p ON e.panel_id = p.id
    LEFT JOIN qa_submission_status qs ON qs.evaluation_id = e.id
    WHERE DATEDIFF(CURDATE(), e.evaluation_date) >= 14
    AND (qs.id IS NULL OR qs.iac_irpc IS NULL OR qs.iac_irpc = '')
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $days = (int)((time() - strtotime($row['evaluation_date'])) / 86400);

        $qa_message = "Follow-up needed: {$row['panel_name']} submitted \"{$row['title']}\" {$days} days ago but has not attended any meeting yet. Please arrange a meeting.";

        $panel_message = "Hi {$row['panel_name']}, your evaluation report \"{$row['title']}\" was submitted {$days} days ago. Please make sure to attend the upcoming IAC/IRPC meeting. Contact your Programme Coordinator if you need assistance.";

        // Notify QA + Panel (existing)
        createNotification($conn, $row['evaluation_id'], 'overdue_meeting', $qa_message, $panel_message);

        // Notify the PC assigned to this programme
        $pc_stmt = $conn->prepare("
            SELECT username FROM users 
            WHERE role = 'PC' AND programme = ?
        ");
        $pc_stmt->bind_param("s", $row['programme']);
        $pc_stmt->execute();
        $pc_users = $pc_stmt->get_result();

        while ($pc = $pc_users->fetch_assoc()) {
            $pc_username = $pc['username'];
            $pc_message  = "Reminder: Your panel {$row['panel_name']} submitted \"{$row['title']}\" {$days} days ago but no IAC meeting has been recorded. Please follow up.";

            // Check no duplicate today
            $chk = $conn->prepare("
                SELECT id FROM notifications
                WHERE evaluation_id = ? AND recipient_type = 'qa'
                AND recipient_id = ? AND type = ?
                AND DATE(created_at) = CURDATE()
            ");
            $type = 'overdue_meeting';
            $chk->bind_param("iss", $row['evaluation_id'], $pc_username, $type);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) continue;

            $ins = $conn->prepare("
                INSERT INTO notifications
                (type, evaluation_id, recipient_type, recipient_id, message)
                VALUES (?, ?, 'qa', ?, ?)
            ");
            $ins->bind_param("siss", $type, $row['evaluation_id'], $pc_username, $pc_message);
            $ins->execute();
        }
    }
}