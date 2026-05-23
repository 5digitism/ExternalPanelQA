<?php
session_start();
require_once 'db.php';
 
// ── Security ──────────────────────────────────────────────────────────────────
if (!isset($_SESSION['username'])) {
    header("Location: loginpage.html");
    exit();
}
 
$allowed_roles = ['admin', 'PC', 'Head QA'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: loginpage.html");
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
// ── Fetch this PC's assigned programme ───────────────────────────────────────
$username     = $_SESSION['username'];
$pc_programme = null;
$pc_name      = $_SESSION['name'] ?? 'Programme Coordinator';
 
$stmt = $conn->prepare("SELECT programme, name FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$pc_row = $stmt->get_result()->fetch_assoc();
 
if ($pc_row) {
    $pc_programme = $pc_row['programme'];   // NULL = admin (see all)
    $pc_name      = $pc_row['name'];
}
 
// ── Fetch evaluations filtered by programme ───────────────────────────────────
if ($pc_programme) {
    $stmt = $conn->prepare("
        SELECT e.*, p.panel_name, p.programme
        FROM evaluations e
        JOIN panel_members p ON e.panel_id = p.id
        WHERE p.programme = ?
        ORDER BY e.evaluation_date DESC
    ");
    $stmt->bind_param("s", $pc_programme);
    $stmt->execute();
    $evaluations = $stmt->get_result();
} else {
    $evaluations = $conn->query("
        SELECT e.*, p.panel_name, p.programme
        FROM evaluations e
        JOIN panel_members p ON e.panel_id = p.id
        ORDER BY e.evaluation_date DESC
    ");
}
 
// ── Count panels in this programme ───────────────────────────────────────────
if ($pc_programme) {
    $cnt = $conn->prepare("SELECT COUNT(*) AS total FROM panel_members WHERE programme = ? AND TRIM(LOWER(status)) = 'approved'");
    $cnt->bind_param("s", $pc_programme);
    $cnt->execute();
    $panel_count = $cnt->get_result()->fetch_assoc()['total'];
} else {
    $panel_count = $conn->query("SELECT COUNT(*) AS total FROM panel_members WHERE TRIM(LOWER(status)) = 'approved'")->fetch_assoc()['total'];
}
 
$eval_count = $evaluations ? $evaluations->num_rows : 0;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PC Evaluation | EAP System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css?v=1.1">
 
<style>
body { background-color: #f0f2f5; font-family: 'Poppins', sans-serif; }
 
/* ── Banner ─────────────────────────────────────────── */
.page-banner {
    background: linear-gradient(135deg, #1a6fc4 0%, #0b3a6e 100%);
    color: white; padding: 36px 40px; border-radius: 20px;
    margin-bottom: 28px; position: relative; overflow: hidden;
    box-shadow: 0 10px 30px rgba(11,58,110,0.2);
}
.page-banner::after {
    content: "\f19d"; font-family: "Font Awesome 6 Free"; font-weight: 900;
    position: absolute; right: 30px; bottom: -15px;
    font-size: 130px; opacity: 0.07;
}
.programme-pill {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3);
    color: white; border-radius: 30px; padding: 6px 18px;
    font-size: 0.82rem; font-weight: 500; margin-top: 10px;
    backdrop-filter: blur(4px);
}
 
/* ── Stat mini-cards ────────────────────────────────── */
.mini-stat {
    background: white; border-radius: 14px; padding: 18px 22px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    display: flex; align-items: center; gap: 16px; margin-bottom: 24px;
}
.mini-stat .icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}
.mini-stat .label { font-size: 0.72rem; color: #8a95a3; font-weight: 600; text-transform: uppercase; letter-spacing: .8px; }
.mini-stat .value { font-size: 1.6rem; font-weight: 700; color: #212529; line-height: 1; }
 
/* ── Filter bar ─────────────────────────────────────── */
.filter-bar {
    background: white; border-radius: 14px; padding: 16px 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 22px;
    display: flex; gap: 12px; align-items: center; flex-wrap: wrap;
}
.filter-bar input, .filter-bar select {
    border-radius: 10px; border: 1px solid #dee2e6;
    padding: 8px 14px; font-size: 0.85rem; font-family: 'Poppins', sans-serif;
}
.filter-bar input:focus, .filter-bar select:focus {
    outline: none; border-color: #1a6fc4;
    box-shadow: 0 0 0 3px rgba(26,111,196,0.12);
}
 
/* ── Evaluation card ────────────────────────────────── */
.card-eval {
    background: #fff; border-radius: 16px; padding: 28px;
    margin-bottom: 20px; box-shadow: 0 4px 14px rgba(0,0,0,0.05);
    border-left: 5px solid #1a6fc4;
    transition: transform 0.25s, box-shadow 0.25s;
}
.card-eval:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(0,0,0,0.09); }
.card-eval .eval-title { font-size: 1.1rem; font-weight: 700; color: #0b3a6e; margin-bottom: 4px; }
.card-eval .meta-row { display: flex; flex-wrap: wrap; gap: 18px; margin-bottom: 16px; }
.card-eval .meta-item { display: flex; align-items: center; gap: 6px; font-size: 0.82rem; color: #6c757d; }
.card-eval .meta-item i { color: #1a6fc4; width: 14px; text-align: center; }
 
.section-label {
    font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .9px; color: #1a6fc4; margin-bottom: 8px;
}
 
/* Status badge */
.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    border-radius: 20px; padding: 4px 12px; font-size: 0.72rem;
    font-weight: 600; text-transform: uppercase; letter-spacing: .5px;
}
.badge-submitted { background: #d1fae5; color: #065f46; }
.badge-pending   { background: #fef9c3; color: #78350f; }
 
/* ── Criteria box ───────────────────────────────────── */
.criteria-box {
    background: #f8f9fa; border-radius: 12px; padding: 15px;
    border: 1px solid #e9ecef; transition: all 0.3s ease;
}
 
/* Empty state */
.empty-state { text-align: center; padding: 70px 20px; color: #adb5bd; }
.empty-state i { font-size: 3.5rem; margin-bottom: 16px; display: block; }
</style>
</head>
<body>
 
<div class="wrapper collapsed" id="wrapper">
 
  <!-- ── Sidebar ── -->
  <div class="sidebar">
    <div class="sidebar-header">
      <h3>EAP System</h3>
      <button class="collapse-btn" onclick="toggleSidebar()">
        <i class="fas fa-chevron-left"></i>
      </button>
    </div>
    <a href="pc_panels.php">
      <i class="fas fa-users me-2"></i><span>My Panels</span>
    </a>
    <a href="pc_evaluation.php" class="active">
      <i class="fas fa-list-check me-2"></i><span>My Programme</span>
    </a>
 
    <a href="newpanel.php">
      <i class="fas fa-globe me-2"></i><span>Register New Panels</span>
    </a>
    <hr>
    <a href="logout.php" class="text-warning">
      <i class="fas fa-sign-out-alt me-2"></i><span>Logout</span>
    </a>
  </div>
 
  <!-- ── Main Content ── -->
  <div class="content p-4">
 
    <!-- ── Banner ── -->
    <div class="page-banner">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <p class="mb-1 opacity-75 small text-uppercase fw-semibold" style="letter-spacing:1px;">Programme Coordinator</p>
          <h2 class="fw-bold mb-1"><?= htmlspecialchars($pc_name) ?></h2>
          <p class="mb-0 opacity-75">Review panel evaluations and add your comments below.</p>
          <?php if ($pc_programme): ?>
            <div class="programme-pill mt-2">
              <i class="fas fa-graduation-cap"></i>
              <?= htmlspecialchars($pc_programme) ?>
            </div>
          <?php else: ?>
            <div class="programme-pill mt-2">
              <i class="fas fa-globe"></i> All Programmes (Admin View)
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
 
    <!-- ── Stats Row ── -->
    <div class="row g-3 mb-2">
      <div class="col-md-4">
        <div class="mini-stat">
          <div class="icon bg-primary-subtle text-primary"><i class="fas fa-users"></i></div>
          <div>
            <div class="label">Panels in Programme</div>
            <div class="value"><?= $panel_count ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="mini-stat">
          <div class="icon bg-success-subtle text-success"><i class="fas fa-file-alt"></i></div>
          <div>
            <div class="label">Evaluations Received</div>
            <div class="value"><?= $eval_count ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="mini-stat">
          <div class="icon bg-warning-subtle text-warning"><i class="fas fa-comment-dots"></i></div>
          <div>
            <div class="label">Awaiting Your Comment</div>
            <?php
             
if ($pc_programme) {
    $aw = $conn->prepare("
        SELECT COUNT(DISTINCT e.id) AS c
        FROM evaluations e
        JOIN panel_members p ON e.panel_id = p.id
        JOIN evaluation_criteria ec ON ec.evaluation_id = e.id
        JOIN criteria_issues ci ON ci.criteria_id = ec.id
        WHERE p.programme = ?
        AND (ci.pc_comment IS NULL OR ci.pc_comment = '')
    ");
    $aw->bind_param("s", $pc_programme);
} else {
    $aw = $conn->prepare("
        SELECT COUNT(DISTINCT e.id) AS c
        FROM evaluations e
        JOIN evaluation_criteria ec ON ec.evaluation_id = e.id
        JOIN criteria_issues ci ON ci.criteria_id = ec.id
        WHERE ci.pc_comment IS NULL OR ci.pc_comment = ''
    ");
}
$aw->execute();
$await_count = $aw->get_result()->fetch_assoc()['c'];
            ?>
            <div class="value <?= $await_count > 0 ? 'text-warning' : '' ?>"><?= $await_count ?></div>
          </div>
        </div>
      </div>
    </div>
 
    <!-- ── Filter Bar ── -->
    <div class="filter-bar">
      <i class="fas fa-filter text-muted"></i>
      <input type="text" id="searchInput" placeholder="Search by panel name or meeting title…" style="flex:1; min-width:200px;">
      <select id="statusFilter">
        <option value="">All Status</option>
        <option value="open">Open</option>
        <option value="closed">Closed</option>
      </select>
    </div>
 
    <!-- ── Evaluation Cards ── -->
    <?php
    // Re-run query to iterate (num_rows was consumed above)
    if ($pc_programme) {
        $stmt2 = $conn->prepare("
            SELECT e.*, p.panel_name, p.programme
            FROM evaluations e
            JOIN panel_members p ON e.panel_id = p.id
            WHERE p.programme = ?
            ORDER BY e.evaluation_date DESC
        ");
        $stmt2->bind_param("s", $pc_programme);
        $stmt2->execute();
        $evaluations = $stmt2->get_result();
    } else {
        $evaluations = $conn->query("
            SELECT e.*, p.panel_name, p.programme
            FROM evaluations e
            JOIN panel_members p ON e.panel_id = p.id
            ORDER BY e.evaluation_date DESC
        ");
    }
    ?>
 
    <div id="evalList">
    <?php if ($evaluations && $evaluations->num_rows > 0): ?>
 
      <?php while ($eval = $evaluations->fetch_assoc()): ?>
 
        <div class="card-eval"
             data-title="<?= strtolower(htmlspecialchars($eval['title'])) ?>"
             data-panel="<?= strtolower(htmlspecialchars($eval['panel_name'])) ?>">
 
          <!-- Title + meta -->
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <div class="eval-title"><?= htmlspecialchars($eval['title']) ?></div>
          </div>
          <div class="meta-row">
            <div class="meta-item"><i class="fas fa-user-tie"></i><span><?= htmlspecialchars($eval['panel_name']) ?></span></div>
            <div class="meta-item"><i class="fas fa-calendar-alt"></i><span><?= date('d M Y', strtotime($eval['evaluation_date'])) ?></span></div>
            <div class="meta-item"><i class="fas fa-graduation-cap"></i><span><?= htmlspecialchars($eval['programme']) ?></span></div>
            <div class="meta-item"><i class="fas fa-clock"></i><span>Submitted <?= date('d M Y', strtotime($eval['created_at'])) ?></span></div>
          </div>
 
          <hr class="my-3 opacity-25">
 
          <!-- ── Criteria + Issues ── -->
          <?php
            $criteria_list = $conn->query("
                SELECT * FROM evaluation_criteria
                WHERE evaluation_id = " . (int)$eval['id']
            );
 
            if ($criteria_list && $criteria_list->num_rows > 0):
                $crit_num = 1;
                while ($c = $criteria_list->fetch_assoc()):
                    $issues   = $conn->query("SELECT * FROM criteria_issues WHERE criteria_id = " . (int)$c['id']);
                    $is_closed = ($c['status'] === 'closed');
          ?>
 
          <div class="criteria-box mb-4" id="criteria-block-<?= $c['id'] ?>">
 
            <!-- Criteria header: name + status badge + toggle -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
              <p class="fw-semibold mb-0 section-label" style="font-size:.82rem;">
                Criteria <?= $crit_num ?>: <?= htmlspecialchars($c['criteria_name']) ?>
                 <?= criteriaTypeBadge($c['criteria_type']) ?>
              </p>
              <div class="d-flex align-items-center gap-2">
                <span class="badge <?= $is_closed ? 'bg-success' : 'bg-warning text-dark' ?>"
                      id="status-badge-<?= $c['id'] ?>">
                  <?= $is_closed ? 'Closed' : 'Open' ?>
                </span>
                 <span class="text-muted" 
      style="font-size:11px;<?= ($is_closed && !empty($c['closed_at'])) ? '' : 'display:none' ?>" 
      id="closed-at-<?= $c['id'] ?>">
    <?php if ($is_closed && !empty($c['closed_at'])): ?>
        <i class="fas fa-clock me-1"></i>
        <?= date('d M Y, g:ia', strtotime($c['closed_at'])) ?>
    <?php endif; ?>
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
            <table class="table table-bordered align-middle mb-2" style="font-size:14px; min-width:900px">
              <thead class="table-light">
                <tr>
                  <th style="width:40px">#</th>
                  <th style="width:100px">Type</th> 
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
                  <td><?= issueTypeBadge($issue['issue_type'] ?? null) ?></td>
 
                  <td style="white-space: pre-wrap; line-height:1.6">
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
            <?php else: ?>
              <p class="text-muted small mb-0"><em>No issues recorded for this criteria.</em></p>
            <?php endif; ?>
 
          </div><!-- /criteria-box -->
 
          <?php
                $crit_num++;
                endwhile; // criteria
            endif;
          ?>
 
        </div><!-- /card-eval -->
 
      <?php endwhile; // evaluations ?>
 
    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-folder-open"></i>
        <h5 class="fw-semibold text-muted">No Evaluations Found</h5>
        <p class="text-muted small">
          <?php if ($pc_programme): ?>
            No panel evaluations have been submitted for <strong><?= htmlspecialchars($pc_programme) ?></strong> yet.
          <?php else: ?>
            No evaluations have been submitted yet.
          <?php endif; ?>
        </p>
      </div>
    <?php endif; ?>
    </div><!-- /evalList -->
 
    <div class="text-center mt-5 mb-4 text-muted small">
      &copy; <?= date("Y") ?> External Academic Panel System &mdash; Programme Coordinator Portal
    </div>
 
  </div><!-- /content -->
</div><!-- /wrapper -->
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    document.getElementById("wrapper").classList.toggle("collapsed");
}
 
// ── Live search/filter ───────────────────────────────────────────────────────
function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value.toLowerCase();
 
    document.querySelectorAll('#evalList .card-eval').forEach(card => {
        const matchSearch = !search || card.dataset.title.includes(search) || card.dataset.panel.includes(search);
 
        // For status filter: check if any criteria-box inside matches
        let matchStatus = true;
        if (status) {
            const boxes = card.querySelectorAll('.criteria-box');
            if (boxes.length > 0) {
                matchStatus = [...boxes].some(box => {
                    const badge = box.querySelector('[id^="status-badge-"]');
                    return badge && badge.textContent.trim().toLowerCase() === status;
                });
            }
        }
 
        card.style.display = (matchSearch && matchStatus) ? '' : 'none';
    });
 
    // Empty state for filter
    const visible = [...document.querySelectorAll('#evalList .card-eval')].filter(c => c.style.display !== 'none');
    let noResult = document.getElementById('filterEmpty');
    if (visible.length === 0) {
        if (!noResult) {
            noResult = document.createElement('div');
            noResult.id = 'filterEmpty';
            noResult.className = 'empty-state';
            noResult.innerHTML = '<i class="fas fa-search-minus"></i><h5 class="fw-semibold text-muted">No Results</h5><p class="text-muted small">Try adjusting your search or filters.</p>';
            document.getElementById('evalList').appendChild(noResult);
        }
    } else if (noResult) {
        noResult.remove();
    }
}
 
document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('statusFilter').addEventListener('change', applyFilters);
 
// ── Save proposed action + remark ────────────────────────────────────────────
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
 
    btn.disabled    = true;
    btn.textContent = 'Saving…';
 
    fetch("save_comment.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=save_comment"
            + "&issue_id="   + issueId
            + "&pc_comment=" + encodeURIComponent(comment)
            + "&remark="     + encodeURIComponent(remark)
    })
    .then(res => res.text())
    .then(res => {
        if (res === 'ok') {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');
            btn.textContent  = 'Saved';
            btn.disabled     = false;
            statusEl.textContent = '✓ Saved';
            setTimeout(() => statusEl.textContent = '', 2000);
        } else {
            btn.classList.add('btn-danger');
            btn.textContent = 'Error';
            btn.disabled    = false;
        }
    });
}
 
// ── Toggle criteria open/closed ──────────────────────────────────────────────
function toggleStatus(criteriaId, currentStatus) {
    const newStatus = currentStatus === 'open' ? 'closed' : 'open';
    const btn       = document.getElementById('status-btn-'   + criteriaId);
    const badge     = document.getElementById('status-badge-' + criteriaId);
    const block     = document.getElementById('criteria-block-' + criteriaId);
    const closedAt  = document.getElementById('closed-at-' + criteriaId);

    btn.disabled    = true;
    btn.textContent = 'Updating…';

    fetch("save_comment.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=update_status&criteria_id=" + criteriaId + "&status=" + newStatus
    })
    .then(res => res.text())
    .then(res => {
        if (res === 'closed' || res === 'open') {
            badge.textContent = res === 'closed' ? 'Closed' : 'Open';
            badge.className   = 'badge ' + (res === 'closed' ? 'bg-success' : 'bg-warning text-dark');

            btn.textContent = res === 'closed' ? 'Reopen' : 'Mark as Closed';
            btn.className   = 'btn btn-sm ' + (res === 'closed' ? 'btn-outline-warning' : 'btn-outline-success');
            btn.disabled    = false;
            btn.onclick     = () => toggleStatus(criteriaId, res);

            // Update closed_at display
            if (closedAt) {
                if (res === 'closed') {
                    const now = new Date();
                    const formatted = now.toLocaleDateString('en-GB', {
                        day: '2-digit', month: 'short', year: 'numeric'
                    }) + ', ' + now.toLocaleTimeString('en-GB', {
                        hour: 'numeric', minute: '2-digit', hour12: true
                    });
                    closedAt.innerHTML = '<i class="fas fa-clock me-1"></i>' + formatted;
                    closedAt.style.display = '';
                } else {
                    // Reopened — hide the date
                    closedAt.innerHTML = '';
                    closedAt.style.display = 'none';
                }
            }

            block.querySelectorAll('textarea').forEach(ta => ta.disabled = res === 'closed');
            block.querySelectorAll('button[id^="save-btn-"]').forEach(b => b.disabled = res === 'closed');
        } else {
            btn.textContent = 'Error';
            btn.disabled    = false;
        }
    });
}
</script>
 
</body>
<?php require_once 'check_overdue.php'; ?>
<?php require_once 'notifications_ui.php'; ?>
<div id="notifBannerStrip"
     style="position:fixed; bottom:90px; left:20px; right:100px; z-index:9997; pointer-events:none;">
</div>
</html>