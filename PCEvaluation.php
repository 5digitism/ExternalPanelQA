<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$last_saved_id = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pc_comment'])) {
    $criteria_id = $_POST['criteria_id'];
    $pc_comment  = $_POST['pc_comment'];

    $stmt = $conn->prepare("UPDATE evaluation_criteria SET pc_comment = ? WHERE id = ?");
    $stmt->bind_param("si", $pc_comment, $criteria_id);
    if ($stmt->execute()) {
        $last_saved_id = $criteria_id;
    }
}

$evaluations = $conn->query("
    SELECT e.*, p.panel_name 
    FROM evaluations e
    JOIN panel_members p ON e.panel_id = p.id
    ORDER BY e.created_at DESC
");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Evaluation | EAP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .page-banner {
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
            color: white; padding: 30px 40px; border-radius: 20px; margin-bottom: 30px;
        }
        .card-eval {
            background: #fff; border-radius: 15px; padding: 25px;
            margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .card-eval:hover { transform: translateY(-5px); }
        .criteria-box {
            background: #f8f9fa; border-radius: 12px; padding: 15px;
            border: 1px solid #e9ecef; transition: all 0.3s ease;
        }
        .form-control[readonly] { background-color: #f1f3f5; }
    </style>
</head>
<body>

<div class="wrapper collapsed" id="wrapper">

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>EAP System</h3>
            <button class="collapse-btn" onclick="toggleSidebar()">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        <a href="dashboard.php"><i class="fas fa-chart-line me-2"></i><span>Dashboard</span></a>
        <a href="PCEvaluation.php" class="active"><i class="fas fa-list-check me-2"></i><span>Evaluation</span></a>
        <a href="panels.php"><i class="fas fa-users me-2"></i><span>Panels</span></a>
        <a href="newpanel.php"><i class="fas fa-user-plus me-2"></i><span>Register New</span></a>
        <hr>
        <a href="logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-2"></i><span>Logout</span></a>
    </div>

    <!-- Content -->
    <div class="content p-4">

        <div class="page-banner d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1">Programme Coordinator Evaluation</h2>
                <p class="mb-0 opacity-75">Review panel evaluations and add comments</p>
            </div>
        </div>

        <?php if ($evaluations && $evaluations->num_rows > 0): ?>

            <?php while ($eval = $evaluations->fetch_assoc()): ?>  <!--  LOOP OPEN -->

                <div class="card-eval">
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($eval['title']) ?></h5>
                    <p class="text-muted small mb-3">
                        Panel: <?= htmlspecialchars($eval['panel_name']) ?> &nbsp;|&nbsp;
                        Date: <?= date('F d, Y', strtotime($eval['evaluation_date'])) ?>
                    </p>


<?php
$criteria_list = $conn->query("
    SELECT * FROM evaluation_criteria
    WHERE evaluation_id = " . $eval['id']
);

if ($criteria_list && $criteria_list->num_rows > 0):
    $crit_num = 1;
    while ($c = $criteria_list->fetch_assoc()):
        $issues = $conn->query("
            SELECT * FROM criteria_issues
            WHERE criteria_id = " . $c['id']
        );
        $is_closed = ($c['status'] === 'closed');
?>

<div class="criteria-box mb-4" id="criteria-block-<?= $c['id'] ?>">

    <!-- Criteria header with status badge + toggle button -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <p class="fw-semibold mb-0">
            Criteria <?= $crit_num ?>: <?= htmlspecialchars($c['criteria_name']) ?>
        </p>
        <div class="d-flex align-items-center gap-2">
            <span class="badge <?= $is_closed ? 'bg-success' : 'bg-warning text-dark' ?>"
                  id="status-badge-<?= $c['id'] ?>">
                <?= $is_closed ? 'Closed' : 'Open' ?>
            </span>
            <button class="btn btn-sm <?= $is_closed ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                    id="status-btn-<?= $c['id'] ?>"
                    onclick="toggleStatus(<?= $c['id'] ?>, '<?= $is_closed ? 'closed' : 'open' ?>')">
                <?= $is_closed ? 'Reopen' : 'Mark as Closed' ?>
            </button>
        </div>
    </div>

    <?php if ($issues && $issues->num_rows > 0): ?>
    <div class="table-responsive">
    <table class="table table-bordered align-middle mb-2" style="font-size:14cpx; min-width:900px">
        <thead class="table-light">
            <tr>
                <th style="width:40px">#</th>
                <th style="width:26%">Issue raised by panel</th>
                <th style="width:30%">Proposed action by campus</th>
                <th style="width:30%">Remark</th>
                <th style="width:90px"></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $issue_num = 1;
            while ($issue = $issues->fetch_assoc()):
            ?>
            <tr id="issue-row-<?= $issue['id'] ?>">
                <td class="text-center text-muted fw-semibold"><?= $issue_num ?></td>

                <td style="white-space: wrap; line-height:1.6">
                    <?= nl2br(htmlspecialchars($issue['issue_text'])) ?>
                </td>

                <td>
                    <textarea
                        id="pc-comment-<?= $issue['id'] ?>"
                        class="form-control"
                        rows="6"
                        placeholder="Type proposed action…"
                        onkeyup="trackChange(<?= $issue['id'] ?>)"
                        <?= $is_closed ? 'disabled' : '' ?>
                    ><?= htmlspecialchars($issue['pc_comment'] ?? '') ?></textarea>
                </td>

                <td>
                    <textarea
                        id="remark-<?= $issue['id'] ?>"
                        class="form-control"
                        rows="6"
                        placeholder="Type remark…"
                        onkeyup="trackChange(<?= $issue['id'] ?>)"
                        <?= $is_closed ? 'disabled' : '' ?>
                    ><?= htmlspecialchars($issue['remark'] ?? '') ?></textarea>
                </td>

                <td class="text-center">
                    <button
                        id="save-btn-<?= $issue['id'] ?>"
                        class="btn btn-sm <?= (!empty($issue['pc_comment']) || !empty($issue['remark'])) ? 'btn-success' : 'btn-primary' ?>"
                        onclick="saveRow(<?= $issue['id'] ?>)"
                        <?= $is_closed ? 'disabled' : '' ?>
                    >
                        <?= (!empty($issue['pc_comment']) || !empty($issue['remark'])) ? 'Saved' : 'Save' ?>
                    </button>
                    <div id="save-status-<?= $issue['id'] ?>" style="font-size:11px; margin-top:4px; color:#198754"></div>
                </td>
            </tr>
            <?php $issue_num++; endwhile; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>

</div>

<?php
        $crit_num++;
    endwhile;
endif;
?>
                </div><!-- end card-eval -->

            <?php endwhile; // LOOP CLOSE?>

        <?php else: ?>
            <div class="text-center text-muted mt-5">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>No evaluations submitted yet.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
function trackChange(issueId) {
    const btn = document.getElementById('save-btn-' + issueId);
    if (!btn || btn.disabled) return;
    btn.classList.remove('btn-success');
    btn.classList.add('btn-primary');
    btn.textContent = 'Save';
}

function saveRow(issueId) {
    const btn      = document.getElementById('save-btn-' + issueId);
    const comment  = document.getElementById('pc-comment-' + issueId).value;
    const remark   = document.getElementById('remark-' + issueId).value;
    const statusEl = document.getElementById('save-status-' + issueId);

    btn.disabled = true;
    btn.textContent = 'Saving…';

    fetch("save_comment.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=save_comment"
            + "&issue_id=" + issueId
            + "&pc_comment=" + encodeURIComponent(comment)
            + "&remark=" + encodeURIComponent(remark)
    })
    .then(res => res.text())
    .then(res => {
        if (res === 'ok') {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');
            btn.textContent = 'Saved';
            btn.disabled = false;
            statusEl.textContent = '✓ Saved';
            setTimeout(() => statusEl.textContent = '', 2000);
        } else {
            btn.classList.add('btn-danger');
            btn.textContent = 'Error';
            btn.disabled = false;
        }
    });
}

function toggleStatus(criteriaId, currentStatus) {
    const newStatus = currentStatus === 'open' ? 'closed' : 'open';
    const btn       = document.getElementById('status-btn-' + criteriaId);
    const badge     = document.getElementById('status-badge-' + criteriaId);
    const block     = document.getElementById('criteria-block-' + criteriaId);

    btn.disabled = true;
    btn.textContent = 'Updating…';

    fetch("save_comment.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=update_status&criteria_id=" + criteriaId + "&status=" + newStatus
    })
    .then(res => res.text())
    .then(res => {
        if (res === 'closed' || res === 'open') {

            // Update badge
            badge.textContent = res === 'closed' ? 'Closed' : 'Open';
            badge.className   = 'badge ' + (res === 'closed' ? 'bg-success' : 'bg-warning text-dark');

            // Update button
            btn.textContent  = res === 'closed' ? 'Reopen' : 'Mark as Closed';
            btn.className    = 'btn btn-sm ' + (res === 'closed' ? 'btn-outline-warning' : 'btn-outline-success');
            btn.disabled     = false;
            btn.onclick      = () => toggleStatus(criteriaId, res);

            // Disable/enable all textareas and save buttons inside this block
            const textareas = block.querySelectorAll('textarea');
            const saveBtns  = block.querySelectorAll('button[id^="save-btn-"]');

            textareas.forEach(ta => ta.disabled = res === 'closed');
            saveBtns.forEach(b  => b.disabled  = res === 'closed');

        } else {
            btn.textContent = 'Error';
            btn.disabled = false;
        }
    });
}
</script>

</body>
</html>