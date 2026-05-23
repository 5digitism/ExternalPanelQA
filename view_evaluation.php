<?php
session_start();
require_once 'db.php';
require_once 'panel_title_badge.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

// ── Auth: allow both panel and PC/QA sessions ──────────────────────────────
$is_panel = isset($_SESSION['panel_id']);
$is_staff = isset($_SESSION['username']);
$qa_mode  = isset($_GET['qa']) && $_GET['qa'] == '1';

if (!$is_panel && !$is_staff) {
    header("Location: login.html");
    exit();
}

// ── Fetch evaluation ────────────────────────────────────────────────────────
$eval_id = intval($_GET['id'] ?? 0);
if ($eval_id <= 0) {
    echo "<p class='text-danger p-4'>Invalid evaluation ID.</p>";
    exit();
}

$stmt = $conn->prepare("
    SELECT e.*, p.panel_name, p.programme, p.level, p.panel_title
    FROM evaluations e
    JOIN panel_members p ON e.panel_id = p.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $eval_id);
$stmt->execute();
$eval = $stmt->get_result()->fetch_assoc();

if (!$eval) {
    echo "<p class='text-danger p-4'>Evaluation not found.</p>";
    exit();
}
function criteriaTypeBadge($type) {
    $map = [
        'concern'        => ['#fee2e2', '#991b1b', 'Concern'],
        'opportunity'    => ['#fef3c7', '#92400e', 'Opportunity for Improvement'],
        'recommendation' => ['#d1fae5', '#065f46', 'Recommendation'],
    ];
    if (!$type || !isset($map[$type])) return '';
    [$bg, $color, $label] = $map[$type];
    return "<span style='background:{$bg};color:{$color};font-size:11px;
                padding:3px 10px;border-radius:999px;font-weight:600;'>
                {$label}</span>";
}
function issueTypeBadge($type) {
    $map = [
        'concern'        => ['#fee2e2', '#991b1b', 'Concern'],
        'opportunity'    => ['#fef3c7', '#92400e', 'Opportunity for Improvement'],
        'recommendation' => ['#d1fae5', '#065f46', 'Recommendation'],
    ];
    if (!$type || !isset($map[$type])) return '';
    [$bg, $color, $label] = $map[$type];
    return "<span style='background:{$bg};color:{$color};font-size:10px;
        padding:2px 8px;border-radius:999px;font-weight:600;white-space:nowrap'>
        {$label}</span>";
}
// Panel can only view their own evaluations
if ($is_panel && !$is_staff && $eval['panel_id'] != $_SESSION['panel_id']) {
    echo "<p class='text-danger p-4'>Access denied.</p>";
    exit();
}

// ── Fetch QA status row ─────────────────────────────────────────────────────
$qa_stmt = $conn->prepare("SELECT * FROM qa_submission_status WHERE evaluation_id = ?");
$qa_stmt->bind_param("i", $eval_id);
$qa_stmt->execute();
$qa_row = $qa_stmt->get_result()->fetch_assoc();

// ── Fetch criteria ──────────────────────────────────────────────────────────
$criteria_list = $conn->query("
    SELECT * FROM evaluation_criteria
    WHERE evaluation_id = $eval_id
    ORDER BY id ASC
");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Evaluation | EAP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary-blue: #0d6efd; --dark-navy: #002b6b; }

        body {
            background: <?= $qa_mode ? '#ffffff' : '#f4f7f6' ?>;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
        }

        /* ── Banner ── */
        .eval-banner {
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-navy));
            color: white;
            padding: <?= $qa_mode ? '20px 24px' : '30px 40px' ?>;
            border-radius: <?= $qa_mode ? '12px' : '20px' ?>;
            margin-bottom: 24px;
        }
        .eval-banner h4 { font-size: <?= $qa_mode ? '16px' : '20px' ?>; font-weight: 600; margin-bottom: 4px; }
        .eval-banner p  { font-size: 12px; opacity: 0.8; margin: 0; }

        /* ── Meta pills ── */
        .meta-row { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .meta-pill {
            display: flex; align-items: center; gap: 6px;
            background: white; border: 1px solid #e2e8f0;
            border-radius: 8px; padding: 6px 12px;
            font-size: 12px; color: #374151;
        }
        .meta-pill i { color: var(--primary-blue); font-size: 11px; }

        /* ── QA status strip ── */
        .qa-strip {
            background: #f0fdf4; border: 1px solid #86efac;
            border-radius: 10px; padding: 12px 16px;
            margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 16px;
        }
        .qa-strip-item { font-size: 12px; }
        .qa-strip-item .label { color: #6b7280; font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.4px; display: block; }
        .qa-strip-item .value { color: #111827; font-weight: 500; }

        /* ── Criteria block ── */
        .criteria-block {
            border: 1px solid #e2e8f0; border-radius: 12px;
            overflow: hidden; margin-bottom: 20px;
        }
        .criteria-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 16px; background: #f8faff;
            border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; gap: 8px;
        }
        .criteria-title { font-weight: 600; font-size: 13px; color: #1e3a5f; }
        .criteria-pill {
            font-size: 10px; padding: 2px 10px; border-radius: 999px;
            background: #dbeafe; color: #1d4ed8; font-weight: 600;
        }

        /* ── Status badge ── */
        .status-open   { background: #fef3c7; color: #92400e; }
        .status-closed { background: #d1fae5; color: #065f46; }
        .status-badge  {
            font-size: 10px; padding: 3px 10px; border-radius: 999px;
            font-weight: 600;
        }

        /* ── Issues table ── */
        .issues-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .issues-table thead th {
            background: #f8f9fa; padding: 9px 12px;
            font-size: 11px; font-weight: 700; color: #6b7280;
            text-transform: uppercase; letter-spacing: 0.4px;
            border-bottom: 1px solid #e2e8f0; text-align: left;
        }
        .issues-table thead th:first-child { width: 36px; }
        .issues-table tbody tr { border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .issues-table tbody tr:last-child { border-bottom: none; }
        .issues-table tbody td { padding: 12px; vertical-align: top; line-height: 1.6; }
        .issues-table tbody tr:hover { background: #fafbff; }

        /* ── Cell types ── */
        .cell-num   { text-align: center; color: #9ca3af; font-weight: 600; width: 36px; padding-top: 14px !important; }
        .cell-issue { color: #374151; }
        .cell-response {
            background: #f8faff;
            color: <?= $qa_mode ? '#111827' : '#374151' ?>;
            white-space: pre-wrap;
        }
        .cell-empty { color: #d1d5db; font-style: italic; font-size: 12px; }

        /* ── Overall comments ── */
        .overall-box {
            background: #fffbeb; border: 1px solid #fde68a;
            border-left: 4px solid #f59e0b;
            border-radius: 10px; padding: 14px 16px;
            font-size: 13px; line-height: 1.7; color: #374151;
        }
        .overall-box .label {
            font-size: 11px; font-weight: 700; color: #92400e;
            text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;
        }

        /* ── Section heading ── */
        .section-heading {
            font-size: 11px; font-weight: 700; color: var(--primary-blue);
            text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 12px;
        }

        /* ── QA mode: no sidebar, full width ── */
        <?php if ($qa_mode): ?>
        body { padding: 0 !important; }
        .qa-container { padding: 20px; max-width: 100%; }
        <?php else: ?>
        .view-container { max-width: 960px; margin: 0 auto; padding: 32px 16px; }
        <?php endif; ?>

        /* ── Back button ── */
        .back-btn {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 12px; color: #6b7280; text-decoration: none;
            padding: 6px 12px; border: 1px solid #e2e8f0;
            border-radius: 8px; margin-bottom: 16px; transition: all 0.2s;
        }
        .back-btn:hover { border-color: var(--primary-blue); color: var(--primary-blue); }

        /* ── No issues placeholder ── */
        .no-issues {
            text-align: center; padding: 24px; color: #9ca3af;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="<?= $qa_mode ? 'qa-container' : 'view-container' ?>">

    <?php if (!$qa_mode): ?>
        <!-- Back button -->
        <a href="<?= $is_panel ? 'panel_evaluation.php' : 'PCEvaluation.php' ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    <?php endif; ?>

    <!-- ── Banner ── -->
    <div class="eval-banner">
        <h4><?= htmlspecialchars($eval['title']) ?></h4>
        <p>
            <?= htmlspecialchars($eval['panel_name']) ?>
              <?= panelTitleBadge($eval['panel_title'] ?? '') ?>
            &nbsp;·&nbsp;
            <?= htmlspecialchars($eval['programme']) ?>
            &nbsp;·&nbsp;
            <span style="text-transform:uppercase; font-size:11px; background:rgba(255,255,255,0.2);
                padding:1px 8px; border-radius:999px">
                <?= htmlspecialchars($eval['level']) ?>
            </span>
        </p>
    </div>

    <!-- ── Meta pills ── -->
    <div class="meta-row">
        <div class="meta-pill">
            <i class="fas fa-calendar"></i>
            Evaluation date: <strong><?= date('d M Y', strtotime($eval['evaluation_date'])) ?></strong>
        </div>
        <div class="meta-pill">
            <i class="fas fa-clock"></i>
            Submitted: <strong><?= date('d M Y, g:ia', strtotime($eval['created_at'])) ?></strong>
        </div>
        <div class="meta-pill">
            <i class="fas fa-tag"></i>
            Status: <strong><?= ucfirst($eval['status']) ?></strong>
        </div>
    </div>

    <!-- ── QA submission status strip (shown to QA and staff, hidden from panel) ── -->
    <?php if ($is_staff && $qa_row): ?>
    <div class="qa-strip">
        <div class="qa-strip-item">
            <span class="label"><i class="fas fa-calendar-check me-1"></i>Latest Visit Date</span>
            <span class="value">
                <?= $qa_row['latest_visit_date'] ? date('d M Y', strtotime($qa_row['latest_visit_date'])) : '—' ?>
            </span>
        </div>
        <div class="qa-strip-item">
            <span class="label">IAC / IRPC</span>
            <span class="value"><?= htmlspecialchars($qa_row['iac_irpc'] ?? '—') ?></span>
        </div>
        <div class="qa-strip-item">
            <span class="label">UAC / URPC</span>
            <span class="value"><?= htmlspecialchars($qa_row['uac_urpc'] ?? '—') ?></span>
        </div>
        <div class="qa-strip-item">
            <span class="label">Senate</span>
            <span class="value"><?= htmlspecialchars($qa_row['senate'] ?? '—') ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Criteria + Issues ── -->
    <div class="section-heading">
        <i class="fas fa-clipboard-list me-1"></i> Criteria &amp; Issues
    </div>

    <?php
    if ($criteria_list && $criteria_list->num_rows > 0):
        $crit_num = 1;
        while ($c = $criteria_list->fetch_assoc()):
            $issues = $conn->query("
                SELECT * FROM criteria_issues
                WHERE criteria_id = " . $c['id'] . "
                ORDER BY id ASC
            ");
            $is_closed = ($c['status'] === 'closed');
    ?>

    <div class="criteria-block">
<div class="criteria-header">
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap">
        <span class="criteria-pill">Criteria <?= $crit_num ?></span>
        <span class="criteria-title"><?= htmlspecialchars($c['criteria_name']) ?></span>
        <?= criteriaTypeBadge($c['criteria_type']) ?>
    </div>
    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap">
        <span class="status-badge <?= $is_closed ? 'status-closed' : 'status-open' ?>">
            <i class="fas <?= $is_closed ? 'fa-lock' : 'fa-lock-open' ?> me-1" style="font-size:9px"></i>
            <?= $is_closed ? 'Closed' : 'Open' ?>
        </span>

        <?php if ($is_closed && !empty($c['closed_at'])): ?>
            <span style="font-size:11px; color:#6b7280;">
                <i class="fas fa-clock me-1"></i>
                <?= date('d M Y, g:ia', strtotime($c['closed_at'])) ?>
            </span>
        <?php endif; ?>
    </div>
</div>

        <?php if ($issues && $issues->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="issues-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th style="width:100px">Type</th>
                        <th style="width:30%">Issue raised by panel</th>
                        <th style="width:32%">Proposed action by campus</th>
                        <th style="width:32%">Remark</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $issue_num = 1;
                while ($issue = $issues->fetch_assoc()):
                    $has_comment = !empty(trim($issue['pc_comment'] ?? ''));
                    $has_remark  = !empty(trim($issue['remark'] ?? ''));
                ?>
                <tr>
                    <td class="cell-num"><?= $issue_num ?></td>
<td><?= issueTypeBadge($issue['issue_type'] ?? null) ?></td>
                    <td class="cell-issue">
                        <?= nl2br(htmlspecialchars($issue['issue_text'])) ?>
                    </td>

                    <td class="cell-response">
                        <?php if ($has_comment): ?>
                            <?= nl2br(htmlspecialchars($issue['pc_comment'])) ?>
                        <?php else: ?>
                            <span class="cell-empty">No response yet</span>
                        <?php endif; ?>
                    </td>

                    <td class="cell-response">
                        <?php if ($has_remark): ?>
                            <?= nl2br(htmlspecialchars($issue['remark'])) ?>
                        <?php else: ?>
                            <span class="cell-empty">No remark yet</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php $issue_num++; endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="no-issues">
                <i class="fas fa-inbox fa-lg mb-2 d-block"></i>
                No issues recorded for this criterion.
            </div>
        <?php endif; ?>
    </div>

    <?php
        $crit_num++;
        endwhile;
    else:
    ?>
    <div class="text-center text-muted py-4">
        <i class="fas fa-clipboard fa-2x mb-2 d-block opacity-25"></i>
        No criteria found for this evaluation.
    </div>
    <?php endif; ?>

    <!-- ── Overall Comments ── -->
    <?php if (!empty($eval['overall_comments'])): ?>
    <div class="section-heading mt-4">
        <i class="fas fa-comment-dots me-1"></i> Overall Comments
    </div>
    <div class="overall-box">
        <div class="label">Panel overall comments &amp; recommendations</div>
        <?= nl2br(htmlspecialchars($eval['overall_comments'])) ?>
    </div>
    <?php endif; ?>

    <?php if (!$qa_mode): ?>
    <div class="mt-4 mb-2">
        <a href="<?= $is_panel ? 'panel_evaluation.php' : 'PCEvaluation.php' ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to evaluations
        </a>
    </div>
    <?php endif; ?>

</div><!-- end container -->

</body>
</html>