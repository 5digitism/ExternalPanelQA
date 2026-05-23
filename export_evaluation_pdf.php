<?php
session_start();
require_once 'db.php';
require_once 'panel_title_badge.php';
if (!isset($_SESSION['username'])) {
    header("Location: loginpage.html");
    exit();
}

$eval_id = intval($_GET['id'] ?? 0);
if ($eval_id <= 0) exit('Invalid ID');

// Fetch evaluation
$stmt = $conn->prepare("
    SELECT e.*, p.panel_name, p.programme, p.level, p.panel_title
    FROM evaluations e
    JOIN panel_members p ON e.panel_id = p.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $eval_id);
$stmt->execute();
$eval = $stmt->get_result()->fetch_assoc();
if (!$eval) exit('Not found');

// Fetch criteria
$criteria_list = $conn->query("
    SELECT * FROM evaluation_criteria 
    WHERE evaluation_id = $eval_id ORDER BY id ASC
");
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
function criteriaTypeBadge($type) {
    $map = [
        'concern'        => ['#fee2e2', '#991b1b', 'Concern'],
        'opportunity'    => ['#fef3c7', '#92400e', 'Opportunity for Improvement'],
        'recommendation' => ['#d1fae5', '#065f46', 'Recommendation'],
    ];
    if (!$type || !isset($map[$type])) return '';
    [$bg, $color, $label] = $map[$type];
    return "<span style='background:{$bg};color:{$color};font-size:10px;
        padding:2px 8px;border-radius:999px;font-weight:600'>{$label}</span>";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Evaluation Export — <?= htmlspecialchars($eval['title']) ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: Arial, sans-serif; font-size: 12px;
        color: #000; padding: 40px 50px; line-height: 1.5;
    }

    /* Header */
    .doc-header { border-bottom: 2px solid #002b6b; padding-bottom: 14px; margin-bottom: 20px; }
    .doc-title { font-size: 18px; font-weight: 700; color: #002b6b; margin-bottom: 4px; }
    .doc-meta { font-size: 11px; color: #6b7280; }

    /* Meta pills row */
    .meta-row { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
    .meta-item { font-size: 11px; }
    .meta-item strong { color: #002b6b; }

    /* Criteria block */
    .criteria-block { margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
    .criteria-header {
        background: #f1f5ff; padding: 8px 12px;
        display: flex; align-items: center; gap: 10px;
        border-bottom: 1px solid #e2e8f0;
    }
    .criteria-num {
        background: #dbeafe; color: #1d4ed8;
        font-size: 10px; padding: 2px 8px; border-radius: 999px; font-weight: 700;
    }
    .criteria-name { font-weight: 700; font-size: 12px; color: #1e3a5f; }

    /* Status */
    .status-closed { background: #d1fae5; color: #065f46; font-size: 10px;
        padding: 2px 8px; border-radius: 999px; font-weight: 600; }
    .status-open   { background: #fef3c7; color: #92400e; font-size: 10px;
        padding: 2px 8px; border-radius: 999px; font-weight: 600; }

    /* Issues table */
    table { width: 100%; border-collapse: collapse; font-size: 11px; }
    thead th {
        background: #f8f9fa; padding: 7px 10px; text-align: left;
        font-weight: 700; color: #6b7280; text-transform: uppercase;
        font-size: 9px; letter-spacing: 0.4px;
        border-bottom: 1px solid #e2e8f0;
    }
    tbody td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
    tbody tr:last-child td { border-bottom: none; }
    .cell-num { text-align: center; color: #9ca3af; font-weight: 700; width: 30px; }
    .cell-response { background: #f8faff; }
    .cell-empty { color: #d1d5db; font-style: italic; }

    /* Overall comments */
    .overall-box {
        background: #fffbeb; border: 1px solid #fde68a;
        border-left: 4px solid #f59e0b;
        border-radius: 8px; padding: 12px 14px; margin-top: 20px;
        font-size: 12px;
    }
    .overall-label {
        font-size: 10px; font-weight: 700; color: #92400e;
        text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;
    }

    /* Footer */
    .doc-footer {
        margin-top: 30px; padding-top: 12px; border-top: 1px solid #e2e8f0;
        font-size: 10px; color: #9ca3af; text-align: center;
    }

    /* Print button — hidden on print */
    .print-bar {
        position: fixed; top: 0; left: 0; right: 0;
        background: #002b6b; padding: 10px 20px;
        display: flex; align-items: center; gap: 12px;
        z-index: 999;
    }
    .print-bar button {
        padding: 6px 16px; border-radius: 8px; border: none;
        font-size: 12px; font-weight: 600; cursor: pointer;
        font-family: Arial, sans-serif;
    }
    .btn-print { background: white; color: #002b6b; }
    .btn-close-bar { background: rgba(255,255,255,0.2); color: white; }
    .print-bar span { color: white; font-size: 13px; font-weight: 600; }

    @media print {
        .print-bar { display: none !important; }
        body { padding: 20px 30px; }
    }
</style>
</head>
<body>

<!-- Print bar -->
<div class="print-bar">
    <span><i class="fas fa-file-pdf" style="margin-right:8px"></i>
        Evaluation Export — <?= htmlspecialchars($eval['title']) ?>
    </span>
    <button class="btn-print" onclick="window.print()">
        🖨 Print / Save as PDF
    </button>
    <button class="btn-close-bar" onclick="window.close()">✕ Close</button>
</div>

<div style="margin-top: 50px">

    <!-- Header -->
    <div class="doc-header">
        <div class="doc-title"><?= htmlspecialchars($eval['title']) ?></div>
        <div class="doc-meta">
            EAP System — Evaluation Export &nbsp;·&nbsp;
            Generated <?= date('d M Y, g:ia') ?>
        </div>
    </div>

    <!-- Meta -->
    <div class="meta-row">
        <div class="meta-item">
    <strong>Panel:</strong> 
    <?= htmlspecialchars($eval['panel_name']) ?>
    <?= panelTitleBadge($eval['panel_title'] ?? '') ?>
</div>
        <div class="meta-item"><strong>Programme:</strong> <?= htmlspecialchars($eval['programme']) ?></div>
        <div class="meta-item"><strong>Level:</strong> <?= htmlspecialchars($eval['level']) ?></div>
        <div class="meta-item"><strong>Evaluation Date:</strong> <?= date('d M Y', strtotime($eval['evaluation_date'])) ?></div>
        <div class="meta-item"><strong>Submitted:</strong> <?= date('d M Y', strtotime($eval['created_at'])) ?></div>
        <div class="meta-item"><strong>Status:</strong> <?= ucfirst($eval['status']) ?></div>
    </div>

    <!-- Criteria -->
    <?php
    if ($criteria_list && $criteria_list->num_rows > 0):
        $crit_num = 1;
        while ($c = $criteria_list->fetch_assoc()):
            $issues    = $conn->query("SELECT * FROM criteria_issues WHERE criteria_id = " . $c['id'] . " ORDER BY id ASC");
            $is_closed = ($c['status'] === 'closed');
    ?>
    <div class="criteria-block">
        <div class="criteria-header">
            <span class="criteria-num">Criteria <?= $crit_num ?></span>
            <span class="criteria-name"><?= htmlspecialchars($c['criteria_name']) ?></span>
            <?= criteriaTypeBadge($c['criteria_type']) ?>
            <span style="margin-left:auto">
                <span class="<?= $is_closed ? 'status-closed' : 'status-open' ?>">
                    <?= $is_closed ? 'Closed' : 'Open' ?>
                </span>
                <?php if ($is_closed && !empty($c['closed_at'])): ?>
                    <span style="font-size:10px;color:#6b7280;margin-left:6px">
                        <?= date('d M Y', strtotime($c['closed_at'])) ?>
                    </span>
                <?php endif; ?>
            </span>
        </div>

        <?php if ($issues && $issues->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                      <th style="width:100px">Type</th> 
                    <th style="width:33%">Issue raised by panel</th>
                    <th style="width:33%">Proposed action by campus</th>
                    <th style="width:33%">Remark</th>
                </tr>
            </thead>
            <tbody>

            <?php
            $inum = 1;
            while ($issue = $issues->fetch_assoc()):
                $has_comment = !empty(trim($issue['pc_comment'] ?? ''));
                $has_remark  = !empty(trim($issue['remark'] ?? ''));
            ?>
            <tr>
                
                <tr>
    <td class="cell-num"><?= $inum ?></td>
    <td><?= issueTypeBadge($issue['issue_type'] ?? null) ?></td>
    <td><?= nl2br(htmlspecialchars($issue['issue_text'])) ?></td>

                <td class="cell-response">
                    <?= $has_comment
                        ? nl2br(htmlspecialchars($issue['pc_comment']))
                        : '<span class="cell-empty">No response yet</span>' ?>
                </td>
                <td class="cell-response">
                    <?= $has_remark
                        ? nl2br(htmlspecialchars($issue['remark']))
                        : '<span class="cell-empty">No remark yet</span>' ?>
                </td>
            </tr>
            <?php $inum++; endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
        $crit_num++;
        endwhile;
    endif;
    ?>

    <!-- Overall comments -->
    <?php if (!empty($eval['overall_comments'])): ?>
    <div class="overall-box">
        <div class="overall-label">Panel overall comments &amp; recommendations</div>
        <?= nl2br(htmlspecialchars($eval['overall_comments'])) ?>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="doc-footer">
        External Academic Panel System &nbsp;·&nbsp;
        <?= htmlspecialchars($eval['panel_name']) ?> &nbsp;·&nbsp;
        <?= htmlspecialchars($eval['programme']) ?> &nbsp;·&nbsp;
        Printed <?= date('d M Y') ?>
    </div>

</div>
</body>
</html>