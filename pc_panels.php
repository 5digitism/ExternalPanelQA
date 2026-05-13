<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: loginpage.html");
    exit();
}

$allowed_roles = ['admin', 'PC', 'Head QA'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: loginpage.html");
    exit();
}

$username     = $_SESSION['username'];
$pc_programme = null;
$pc_name      = $_SESSION['name'] ?? 'Programme Coordinator';

$stmt = $conn->prepare("SELECT programme, name FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$pc_row = $stmt->get_result()->fetch_assoc();
if ($pc_row) {
    $pc_programme = $pc_row['programme'];
    $pc_name      = $pc_row['name'];
}

if ($pc_programme) {
    $stmt2 = $conn->prepare("
        SELECT *,
            (SELECT COUNT(*) FROM evaluations WHERE panel_id = panel_members.id) AS eval_count,
            (SELECT MAX(evaluation_date) FROM evaluations WHERE panel_id = panel_members.id) AS last_eval
        FROM panel_members
        WHERE programme = ? AND TRIM(LOWER(status)) = 'approved'
        ORDER BY panel_name ASC
    ");
    $stmt2->bind_param("s", $pc_programme);
    $stmt2->execute();
    $panels = $stmt2->get_result();
} else {
    $panels = $conn->query("
        SELECT *,
            (SELECT COUNT(*) FROM evaluations WHERE panel_id = panel_members.id) AS eval_count,
            (SELECT MAX(evaluation_date) FROM evaluations WHERE panel_id = panel_members.id) AS last_eval
        FROM panel_members
        WHERE TRIM(LOWER(status)) = 'approved'
        ORDER BY panel_name ASC
    ");
}

$panel_list = [];
if ($panels) {
    while ($row = $panels->fetch_assoc()) {
        $panel_list[] = $row;
    }
}
$panel_count = count($panel_list);

// Pre-fetch visit histories for all panels
$all_ids = array_column($panel_list, 'id');
$visit_history_map = [];
if (!empty($all_ids)) {
    $ids_sql = implode(',', array_map('intval', $all_ids));
    $vh = $conn->query("
        SELECT id, panel_id, visit_date, note, recorded_by, created_at
        FROM panel_visit_history
        WHERE panel_id IN ($ids_sql)
        ORDER BY visit_date DESC
    ");
    if ($vh) {
        while ($vrow = $vh->fetch_assoc()) {
            $visit_history_map[$vrow['panel_id']][] = $vrow;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Panels | EAP System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css?v=1.1">
<style>
body { background-color: #f0f2f5; font-family: 'Poppins', sans-serif; }
.page-banner {
    background: linear-gradient(135deg, #1a6fc4 0%, #0b3a6e 100%);
    color: white; padding: 36px 40px; border-radius: 20px;
    margin-bottom: 28px; position: relative; overflow: hidden;
    box-shadow: 0 10px 30px rgba(11,58,110,0.2);
}
.page-banner::after {
    content: "\f0c0"; font-family: "Font Awesome 6 Free"; font-weight: 900;
    position: absolute; right: 30px; bottom: -15px; font-size: 130px; opacity: 0.07;
}
.programme-pill {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3);
    color: white; border-radius: 30px; padding: 6px 18px;
    font-size: 0.82rem; font-weight: 500; margin-top: 10px;
}
.filter-bar {
    background: white; border-radius: 14px; padding: 14px 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 22px;
    display: flex; gap: 12px; align-items: center; flex-wrap: wrap;
}
.filter-bar input {
    border-radius: 10px; border: 1px solid #dee2e6; padding: 8px 14px;
    font-size: 0.85rem; font-family: 'Poppins', sans-serif; flex: 1; min-width: 200px;
}
.filter-bar input:focus { outline: none; border-color: #1a6fc4; box-shadow: 0 0 0 3px rgba(26,111,196,0.12); }
.panel-card {
    background: white; border-radius: 16px; padding: 22px 26px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.05); transition: transform 0.25s, box-shadow 0.25s;
    height: 100%; border-top: 4px solid #1a6fc4; display: flex; flex-direction: column;
}
.panel-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(0,0,0,0.1); }
.panel-avatar {
    width: 52px; height: 52px;
    background: linear-gradient(135deg, #1a6fc4, #0b3a6e);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    color: white; font-size: 1.2rem; font-weight: 700; flex-shrink: 0;
}
.panel-name  { font-weight: 700; font-size: 1rem; color: #0b3a6e; }
.panel-meta  { font-size: 0.78rem; color: #6c757d; }
.panel-meta i { color: #1a6fc4; width: 14px; }
.eval-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: #e8f4f8; color: #1a6fc4;
    border-radius: 20px; padding: 4px 12px; font-size: 0.72rem; font-weight: 600;
}
.overdue-badge  { background: #ffe4e4; color: #c0392b; }
.active-badge   { background: #d1fae5; color: #065f46; }
.visit-badge    { background: #fef9e7; color: #b7770d; border: 1px solid #f9e08e; }
.no-visit-badge { background: #f3f4f6; color: #9ca3af; }
.profile-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 16px; background: #1a6fc4; color: white;
    border: none; border-radius: 12px; font-size: 0.84rem; font-weight: 600;
    font-family: 'Poppins', sans-serif; margin-top: auto; cursor: pointer;
    transition: background 0.2s, transform 0.15s; width: 100%;
}
.profile-btn:hover { background: #0b3a6e; color: white; transform: translateY(-1px); }
.modal-content { border: none; border-radius: 20px; overflow: hidden; font-family: 'Poppins', sans-serif; }
.modal-profile-header {
    background: linear-gradient(135deg, #1a6fc4 0%, #0b3a6e 100%);
    padding: 30px 30px 70px; position: relative; color: white;
}
.modal-avatar-large {
    width: 88px; height: 88px; background: rgba(255,255,255,0.2);
    border: 3px solid rgba(255,255,255,0.5); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2.2rem; font-weight: 700; color: white; margin-bottom: 12px;
}
.modal-panel-name { font-size: 1.3rem; font-weight: 700; margin-bottom: 2px; }
.modal-panel-level { font-size: 0.82rem; opacity: 0.8; }
.header-status-pill {
    position: absolute; bottom: 16px; left: 30px;
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.35);
    border-radius: 30px; padding: 5px 14px; font-size: 0.76rem; font-weight: 600; color: white;
}
.modal-body { padding: 28px 30px 10px; }
.eval-strip {
    display: flex; gap: 12px; background: #f8f9ff;
    border-radius: 14px; padding: 16px 20px; margin-bottom: 24px;
}
.eval-strip-item { flex: 1; text-align: center; }
.eval-strip-item .es-value { font-size: 1.5rem; font-weight: 700; color: #0b3a6e; line-height: 1; }
.eval-strip-item .es-label {
    font-size: 0.7rem; color: #8a95a3; font-weight: 600;
    text-transform: uppercase; letter-spacing: .6px; margin-top: 4px;
}
.eval-strip-divider { width: 1px; background: #dee2e6; align-self: stretch; }
.info-section-title {
    font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1px; color: #1a6fc4; margin-bottom: 14px;
    padding-bottom: 6px; border-bottom: 1px solid #e9ecef;
}
.info-row { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
.info-row .info-icon {
    width: 32px; height: 32px; background: #f0f7ff; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: #1a6fc4; font-size: 0.85rem; flex-shrink: 0;
}
.info-row .info-label {
    font-size: 0.7rem; color: #adb5bd; font-weight: 600;
    text-transform: uppercase; letter-spacing: .6px; margin-bottom: 2px;
}
.info-row .info-value { font-size: 0.88rem; color: #212529; font-weight: 500; line-height: 1.4; }
.eval-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 24px; background: linear-gradient(135deg, #1a6fc4, #0b3a6e);
    color: white; border-radius: 12px; font-size: 0.88rem; font-weight: 600;
    text-decoration: none; transition: opacity 0.2s, transform 0.15s; width: 100%;
}
.eval-btn:hover { opacity: 0.92; color: white; transform: translateY(-1px); }
.no-eval-notice {
    background: #fff8e1; border: 1px solid #ffe082; border-radius: 10px;
    padding: 12px 16px; font-size: 0.82rem; color: #7c5a00;
    display: flex; align-items: center; gap: 8px; margin-bottom: 16px;
}
.empty-state { text-align: center; padding: 70px 20px; color: #adb5bd; }
.empty-state i { font-size: 3.5rem; margin-bottom: 16px; display: block; }

/* Visit Date Section */
.visit-section {
    background: #fefce8; border: 1px solid #fde68a; border-radius: 14px;
    padding: 16px 18px; margin-bottom: 18px;
}
.visit-section-title {
    font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1px; color: #b45309; margin-bottom: 12px;
    display: flex; align-items: center; justify-content: space-between;
}
.visit-date-big { font-size: 1.4rem; font-weight: 700; color: #0b3a6e; margin-bottom: 2px; }
.visit-date-sub { font-size: 0.75rem; color: #78716c; }
.add-visit-btn {
    display: inline-flex; align-items: center; gap: 6px;
    background: #b45309; color: white; border: none; border-radius: 8px;
    padding: 6px 14px; font-size: 0.78rem; font-weight: 600;
    font-family: 'Poppins', sans-serif; cursor: pointer; transition: background 0.2s;
}
.add-visit-btn:hover { background: #92400e; }
.visit-history-table { font-size: 0.8rem; }
.visit-history-table th { color: #78716c; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; }
.visit-history-table td { vertical-align: middle; }
.visit-edit-btn, .visit-del-btn {
    border: none; background: none; padding: 3px 7px; border-radius: 6px;
    cursor: pointer; font-size: 0.75rem; font-weight: 600;
}
.visit-edit-btn { color: #1a6fc4; background: #e8f4f8; }
.visit-del-btn  { color: #c0392b; background: #ffe4e4; margin-left: 4px; }
.visit-edit-btn:hover { background: #d0eaf7; }
.visit-del-btn:hover  { background: #ffc9c9; }
.no-visit-yet { font-size: 0.82rem; color: #9ca3af; font-style: italic; }
.visit-form-inline {
    background: white; border-radius: 10px; padding: 14px 16px;
    margin-top: 10px; border: 1px solid #e5e7eb;
}
.visit-form-inline label {
    font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 4px; display: block;
}
.visit-form-inline input, .visit-form-inline textarea {
    width: 100%; border: 1px solid #d1d5db; border-radius: 8px;
    padding: 7px 11px; font-size: 0.83rem; font-family: 'Poppins', sans-serif;
    outline: none; transition: border-color 0.2s;
}
.visit-form-inline input:focus, .visit-form-inline textarea:focus {
    border-color: #b45309; box-shadow: 0 0 0 3px rgba(180,83,9,0.12);
}
.visit-form-inline textarea { resize: vertical; min-height: 60px; }
.save-visit-btn {
    background: #b45309; color: white; border: none; border-radius: 8px;
    padding: 8px 20px; font-size: 0.82rem; font-weight: 600;
    font-family: 'Poppins', sans-serif; cursor: pointer; transition: background 0.2s;
}
.save-visit-btn:hover { background: #92400e; }
.cancel-visit-btn {
    background: #f3f4f6; color: #374151; border: none; border-radius: 8px;
    padding: 8px 14px; font-size: 0.82rem; font-weight: 600;
    font-family: 'Poppins', sans-serif; cursor: pointer; margin-left: 6px;
}
.toast-container { position: fixed; bottom: 24px; right: 24px; z-index: 9999; }

/* ── QA Announcement Banner ── */
.ann-banner {
    border-radius: 14px; overflow: hidden;
    box-shadow: 0 4px 14px rgba(13,110,253,0.08);
    margin-bottom: 20px;
}
.ann-banner-header {
    background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
    color: white; padding: 12px 20px;
    display: flex; align-items: center; justify-content: space-between; cursor: pointer;
    user-select: none;
}
.ann-banner-header h6 { margin: 0; font-weight: 700; font-size: 0.88rem; }
.ann-banner-body { background: #fff; padding: 14px 18px; border: 1.5px solid #e0e7ef; border-top: none; border-radius: 0 0 14px 14px; max-height: 280px; overflow-y: auto; }
.ann-item {
    background: #f8faff; border-left: 4px solid #0d6efd;
    border-radius: 0 10px 10px 0; padding: 10px 13px; margin-bottom: 8px;
}
.ann-item:last-child { margin-bottom: 0; }
.ann-item.priority-important { border-left-color: #fd7e14; background: #fff8f0; }
.ann-item.priority-urgent    { border-left-color: #dc3545; background: #fff5f5; }
.ann-item-title { font-weight: 700; font-size: 0.83rem; color: #0b3a6e; margin-bottom: 2px; }
.ann-item-body  { font-size: 0.78rem; color: #444; line-height: 1.5; white-space: pre-wrap; }
.ann-item-meta  { font-size: 0.69rem; color: #9ca3af; margin-top: 4px; }
.ann-priority-badge {
    display: inline-block; font-size: 0.6rem; font-weight: 700;
    padding: 1px 7px; border-radius: 20px; margin-left: 5px; text-transform: uppercase;
}
.badge-normal    { background: #e8f4f8; color: #0d6efd; }
.badge-important { background: #fff3cd; color: #856404; }
.badge-urgent    { background: #f8d7da; color: #842029; }
.ann-empty { text-align: center; color: #adb5bd; font-size: 0.79rem; padding: 14px 0; }
.ann-count-badge {
    background: rgba(255,255,255,0.25); color: white;
    border-radius: 20px; padding: 2px 9px; font-size: 0.72rem; font-weight: 700;
}
.ann-urgent-dot {
    width: 8px; height: 8px; background: #ff4444; border-radius: 50%;
    display: inline-block; margin-left: 5px; animation: blink 1.4s infinite;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.25} }
</style>
</head>
<body>
<div class="wrapper collapsed" id="wrapper">
  <div class="sidebar">
    <div class="sidebar-header">
      <h3>EAP System</h3>
      <button class="collapse-btn" onclick="toggleSidebar()"><i class="fas fa-chevron-left"></i></button>
    </div>
    <a href="pc_evaluation.php"><i class="fas fa-list-check me-2"></i> <span>My Programme</span></a>
    <a href="pc_panels.php" class="active"><i class="fas fa-users me-2"></i> <span>My Panels</span></a>
    <a href="newpanel.php"><i class="fas fa-globe me-2"></i> <span>Register New Panel</span></a>
    <hr>
    <a href="logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a>
  </div>

  <div class="content p-4">
    <div class="page-banner">
      <p class="mb-1 opacity-75 small text-uppercase fw-semibold" style="letter-spacing:1px;">Programme Coordinator</p>
      <h2 class="fw-bold mb-1">My Assigned Panels</h2>
      <p class="mb-0 opacity-75"><?php echo $panel_count; ?> approved panel<?php echo $panel_count !== 1 ? 's' : ''; ?> in your programme</p>
      <?php if ($pc_programme): ?>
      <div class="programme-pill"><i class="fas fa-graduation-cap"></i><?php echo htmlspecialchars($pc_programme); ?></div>
      <?php else: ?>
      <div class="programme-pill"><i class="fas fa-globe"></i> All Programmes (Admin View)</div>
      <?php endif; ?>
    </div>

    <!-- ── QA Announcements Banner ── -->
    <div class="ann-banner" id="annBanner">
        <div class="ann-banner-header" onclick="toggleAnnBanner()">
            <h6><i class="fas fa-bullhorn me-2"></i>Announcements from QA
                <span class="ann-count-badge ms-2" id="annCount">…</span>
                <span class="ann-urgent-dot" id="annUrgentDot" style="display:none;"></span>
            </h6>
            <i class="fas fa-chevron-down" id="annChevron" style="transition:transform 0.25s;"></i>
        </div>
        <div class="ann-banner-body" id="annBannerBody">
            <div class="ann-empty"><i class="fas fa-spinner fa-spin me-1"></i> Loading…</div>
        </div>
    </div>

    <div class="filter-bar">
      <i class="fas fa-search text-muted"></i>
      <input type="text" id="searchInput" placeholder="Search panel by name or qualification...">
      <span class="text-muted small ms-auto"><?php echo $panel_count; ?> panel<?php echo $panel_count !== 1 ? 's' : ''; ?></span>
    </div>

    <?php if ($panel_count > 0): ?>
    <div class="row g-4" id="panelGrid">
      <?php foreach ($panel_list as $p):
        $initials     = strtoupper(substr($p['panel_name'], 0, 1));
        $days_since   = $p['last_eval'] ? (int)((time() - strtotime($p['last_eval'])) / 86400) : null;
        $overdue      = ($days_since === null || $days_since > 30);
        $modal_id     = 'profileModal_' . $p['id'];
        $last_visit   = $p['last_visit_date'] ?? null;
        $visit_days   = $last_visit ? (int)((time() - strtotime($last_visit)) / 86400) : null;
        $panel_visits = $visit_history_map[$p['id']] ?? [];
      ?>
      <div class="col-md-4 panel-item"
           data-name="<?php echo strtolower(htmlspecialchars($p['panel_name'])); ?>"
           data-qual="<?php echo strtolower(htmlspecialchars($p['qualification'])); ?>">
        <div class="panel-card">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="panel-avatar"><?php echo $initials; ?></div>
            <div>
              <div class="panel-name"><?php echo htmlspecialchars($p['panel_name']); ?></div>
              <div class="panel-meta"><?php echo htmlspecialchars($p['level']); ?></div>
            </div>
          </div>
          <div class="panel-meta mb-2">
            <i class="fas fa-certificate me-1"></i><?php echo htmlspecialchars($p['qualification']); ?>
          </div>
          <div class="panel-meta mb-2">
            <i class="fas fa-calendar-check me-1"></i>Started: <?php echo date('d M Y', strtotime($p['start_date'])); ?>
          </div>
          <div class="panel-meta mb-3">
            <i class="fas fa-map-marker-alt me-1"></i>
            <?php if ($last_visit): ?>
              Last visit: <strong><?php echo date('d M Y', strtotime($last_visit)); ?></strong>
              <span class="text-muted">(<?php echo $visit_days; ?>d ago)</span>
            <?php else: ?>
              <span class="text-muted fst-italic">No visit recorded</span>
            <?php endif; ?>
          </div>
          <div class="d-flex gap-2 mb-4 flex-wrap">
            <span class="eval-badge">
              <i class="fas fa-file-alt"></i>
              <?php echo $p['eval_count']; ?> Evaluation<?php echo $p['eval_count'] != 1 ? 's' : ''; ?>
            </span>
            <?php if ($overdue): ?>
            <span class="eval-badge overdue-badge">
              <i class="fas fa-exclamation-circle"></i>
              <?php echo $days_since === null ? 'No submissions' : $days_since . ' days ago'; ?>
            </span>
            <?php else: ?>
            <span class="eval-badge active-badge">
              <i class="fas fa-check-circle"></i>Active
            </span>
            <?php endif; ?>
            <?php if ($last_visit): ?>
            <span class="eval-badge visit-badge">
              <i class="fas fa-shoe-prints"></i><?php echo count($panel_visits); ?> Visit<?php echo count($panel_visits) != 1 ? 's' : ''; ?>
            </span>
            <?php else: ?>
            <span class="eval-badge no-visit-badge">
              <i class="fas fa-map-marker-alt"></i>No Visits
            </span>
            <?php endif; ?>
          </div>
          <button class="profile-btn" data-bs-toggle="modal" data-bs-target="#<?php echo $modal_id; ?>">
            <i class="fas fa-id-card"></i> View Profile
          </button>
        </div>
      </div>

      <!-- PROFILE MODAL -->
      <div class="modal fade" id="<?php echo $modal_id; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:540px;">
          <div class="modal-content shadow-lg">
            <div class="modal-profile-header">
              <button type="button" class="btn-close btn-close-white position-absolute" style="top:16px;right:16px;" data-bs-dismiss="modal"></button>
              <div class="d-flex flex-column align-items-center text-center">
                <div class="modal-avatar-large"><?php echo $initials; ?></div>
                <div class="modal-panel-name"><?php echo htmlspecialchars($p['panel_name']); ?></div>
                <div class="modal-panel-level"><?php echo htmlspecialchars($p['level']); ?></div>
              </div>
              <div class="header-status-pill">
                <i class="fas fa-circle" style="font-size:0.45rem;color:#4ade80;"></i>
                Approved &bull; Panel ID: EAP-P-<?php echo $p['id']; ?>
              </div>
            </div>

            <div class="modal-body">
              <!-- Stats strip -->
              <div class="eval-strip">
                <div class="eval-strip-item">
                  <div class="es-value"><?php echo $p['eval_count']; ?></div>
                  <div class="es-label">Evaluations</div>
                </div>
                <div class="eval-strip-divider"></div>
                <div class="eval-strip-item">
                  <div class="es-value" style="font-size:0.9rem;padding-top:6px;">
                    <?php echo $last_visit ? date('d M Y', strtotime($last_visit)) : '—'; ?>
                  </div>
                  <div class="es-label">Last Visit</div>
                </div>
                <div class="eval-strip-divider"></div>
                <div class="eval-strip-item">
                  <div class="es-value" style="color:<?php echo $overdue ? '#c0392b' : '#065f46'; ?>;">
                    <?php echo $days_since === null ? '&infin;' : $days_since; ?>
                  </div>
                  <div class="es-label">Days (Eval)</div>
                </div>
              </div>

              <!-- ══ VISIT DATE SECTION ══ -->
              <div class="visit-section">
                <div class="visit-section-title">
                  <span><i class="fas fa-shoe-prints me-1"></i>Visit History</span>
                  <button class="add-visit-btn" onclick="showAddVisitForm(<?php echo $p['id']; ?>)">
                    <i class="fas fa-plus"></i> Record Visit
                  </button>
                </div>

                <?php if ($last_visit): ?>
                <div class="mb-2">
                  <div class="visit-date-big"><?php echo date('d M Y', strtotime($last_visit)); ?></div>
                  <div class="visit-date-sub">
                    Most recent visit &nbsp;&middot;&nbsp;
                    <?php echo $visit_days; ?> day<?php echo $visit_days != 1 ? 's' : ''; ?> ago &nbsp;&middot;&nbsp;
                    <?php echo count($panel_visits); ?> total record<?php echo count($panel_visits) != 1 ? 's' : ''; ?>
                  </div>
                </div>
                <?php else: ?>
                <p class="no-visit-yet mb-2">No visits have been recorded for this panel yet.</p>
                <?php endif; ?>

                <!-- Add form -->
                <div id="addVisitForm_<?php echo $p['id']; ?>" class="visit-form-inline" style="display:none;">
                  <div class="row g-2 mb-2">
                    <div class="col-sm-6">
                      <label>Visit Date <span class="text-danger">*</span></label>
                      <input type="date" id="visitDate_<?php echo $p['id']; ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-12">
                      <label>Note (optional)</label>
                      <textarea id="visitNote_<?php echo $p['id']; ?>" placeholder="e.g. Discussed progress, reviewed evaluation..."></textarea>
                    </div>
                  </div>
                  <button class="save-visit-btn" onclick="saveVisit(<?php echo $p['id']; ?>, 'add')">
                    <i class="fas fa-save me-1"></i>Save Visit
                  </button>
                  <button class="cancel-visit-btn" onclick="hideVisitForm(<?php echo $p['id']; ?>, 'add')">Cancel</button>
                </div>

                <!-- Edit form -->
                <div id="editVisitForm_<?php echo $p['id']; ?>" class="visit-form-inline" style="display:none;">
                  <input type="hidden" id="editHistoryId_<?php echo $p['id']; ?>">
                  <div class="row g-2 mb-2">
                    <div class="col-sm-6">
                      <label>Visit Date <span class="text-danger">*</span></label>
                      <input type="date" id="editVisitDate_<?php echo $p['id']; ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-12">
                      <label>Note (optional)</label>
                      <textarea id="editVisitNote_<?php echo $p['id']; ?>"></textarea>
                    </div>
                  </div>
                  <button class="save-visit-btn" onclick="saveVisit(<?php echo $p['id']; ?>, 'edit')">
                    <i class="fas fa-save me-1"></i>Update Visit
                  </button>
                  <button class="cancel-visit-btn" onclick="hideVisitForm(<?php echo $p['id']; ?>, 'edit')">Cancel</button>
                </div>

                <!-- History table -->
                <?php if (!empty($panel_visits)): ?>
                <div class="mt-3" style="max-height:200px;overflow-y:auto;">
                  <table class="table table-sm visit-history-table mb-0">
                    <thead>
                      <tr>
                        <th>#</th><th>Date</th><th>Note</th><th>By</th><th></th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($panel_visits as $idx => $vh): ?>
                      <tr>
                        <td><?php echo $idx + 1; ?></td>
                        <td><strong><?php echo date('d M Y', strtotime($vh['visit_date'])); ?></strong></td>
                        <td><?php echo $vh['note'] ? htmlspecialchars($vh['note']) : '<span class="text-muted">—</span>'; ?></td>
                        <td class="text-muted"><?php echo htmlspecialchars($vh['recorded_by']); ?></td>
                        <td>
                          <button class="visit-edit-btn"
                            onclick="openEditVisitForm(<?php echo $p['id']; ?>, <?php echo $vh['id']; ?>, '<?php echo $vh['visit_date']; ?>', <?php echo json_encode($vh['note'] ?? ''); ?>)">
                            <i class="fas fa-pencil-alt"></i>
                          </button>
                          <button class="visit-del-btn"
                            onclick="deleteVisit(<?php echo $p['id']; ?>, <?php echo $vh['id']; ?>)">
                            <i class="fas fa-trash"></i>
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <?php endif; ?>
              </div>
              <!-- END VISIT DATE SECTION -->

              <div class="info-section-title"><i class="fas fa-user me-1"></i>Personal Details</div>

              <div class="info-row">
                <div class="info-icon"><i class="fas fa-envelope"></i></div>
                <div>
                  <div class="info-label">Email Address</div>
                  <div class="info-value">
                    <?php echo !empty($p['email'])
                        ? '<a href="mailto:'.htmlspecialchars($p['email']).'" style="color:#1a6fc4;">'.htmlspecialchars($p['email']).'</a>'
                        : '<span class="text-muted">Not provided</span>'; ?>
                  </div>
                </div>
              </div>
              <div class="info-row">
                <div class="info-icon"><i class="fas fa-phone"></i></div>
                <div>
                  <div class="info-label">Phone Number</div>
                  <div class="info-value">
                    <?php echo !empty($p['phone']) ? htmlspecialchars($p['phone']) : '<span class="text-muted">Not provided</span>'; ?>
                  </div>
                </div>
              </div>
              <div class="info-row">
                <div class="info-icon"><i class="fas fa-graduation-cap"></i></div>
                <div>
                  <div class="info-label">Highest Qualification</div>
                  <div class="info-value"><?php echo htmlspecialchars($p['qualification']); ?></div>
                </div>
              </div>
              <div class="info-row">
                <div class="info-icon"><i class="fas fa-book-open"></i></div>
                <div>
                  <div class="info-label">Assigned Programme</div>
                  <div class="info-value"><?php echo htmlspecialchars($p['programme']); ?></div>
                </div>
              </div>
              <div class="info-row">
                <div class="info-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                  <div class="info-label">Academic Level</div>
                  <div class="info-value"><?php echo htmlspecialchars($p['level']); ?></div>
                </div>
              </div>
              <div class="info-row">
                <div class="info-icon"><i class="fas fa-calendar-alt"></i></div>
                <div>
                  <div class="info-label">Appointment Start Date</div>
                  <div class="info-value"><?php echo date('d F Y', strtotime($p['start_date'])); ?></div>
                </div>
              </div>
              <?php if (!empty($p['resume_path'])): ?>
              <div class="info-row">
                <div class="info-icon"><i class="fas fa-file-pdf"></i></div>
                <div>
                  <div class="info-label">Resume / CV</div>
                  <div class="info-value">
                    <a href="<?php echo htmlspecialchars($p['resume_path']); ?>" target="_blank" style="color:#1a6fc4;font-weight:600;">
                      <i class="fas fa-external-link-alt me-1" style="font-size:0.75rem;"></i>View Resume
                    </a>
                  </div>
                </div>
              </div>
              <?php endif; ?>

              <hr class="my-3 opacity-25">

              <?php if ($p['eval_count'] == 0): ?>
              <div class="no-eval-notice">
                <i class="fas fa-info-circle flex-shrink-0"></i>
                This panel has not submitted any evaluations yet.
              </div>
              <?php endif; ?>

              <a href="pc_evaluation.php?panel=<?php echo $p['id']; ?>" class="eval-btn mb-3">
                <i class="fas fa-clipboard-list"></i>
                View Evaluations
                <?php if ($p['eval_count'] > 0): ?>
                <span style="background:rgba(255,255,255,0.2);border-radius:20px;padding:1px 8px;font-size:0.75rem;margin-left:4px;">
                  <?php echo $p['eval_count']; ?>
                </span>
                <?php endif; ?>
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <i class="fas fa-users-slash"></i>
      <h5 class="fw-semibold text-muted">No Panels Assigned</h5>
      <p class="text-muted small">
        <?php echo $pc_programme
            ? 'No approved panels found for <strong>' . htmlspecialchars($pc_programme) . '</strong>.'
            : 'No approved panels in the system.'; ?>
      </p>
    </div>
    <?php endif; ?>

    <div class="text-center mt-5 mb-4 text-muted small">
      &copy; <?php echo date("Y"); ?> External Academic Panel System &mdash; Programme Coordinator Portal
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast-container">
  <div id="visitToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="visitToastMsg">Done.</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    document.getElementById("wrapper").classList.toggle("collapsed");
}
document.getElementById('searchInput').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.panel-item').forEach(item => {
        const match = item.dataset.name.includes(q) || item.dataset.qual.includes(q);
        item.style.display = match ? '' : 'none';
    });
});

function showToast(msg, type) {
    const el  = document.getElementById('visitToast');
    const msg_el = document.getElementById('visitToastMsg');
    el.className = 'toast align-items-center text-white border-0 bg-' + (type === 'success' ? 'success' : 'danger');
    msg_el.textContent = msg;
    bootstrap.Toast.getOrCreateInstance(el, { delay: 3000 }).show();
}

function showAddVisitForm(panelId) {
    hideVisitForm(panelId, 'edit');
    const form = document.getElementById('addVisitForm_' + panelId);
    const isOpen = form.style.display !== 'none';
    form.style.display = isOpen ? 'none' : 'block';
    if (!isOpen) {
        document.getElementById('visitDate_' + panelId).value = new Date().toISOString().split('T')[0];
    }
}

function hideVisitForm(panelId, which) {
    const id = (which === 'add' ? 'addVisitForm_' : 'editVisitForm_') + panelId;
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

function openEditVisitForm(panelId, historyId, visitDate, note) {
    hideVisitForm(panelId, 'add');
    document.getElementById('editHistoryId_'  + panelId).value = historyId;
    document.getElementById('editVisitDate_'  + panelId).value = visitDate;
    document.getElementById('editVisitNote_'  + panelId).value = note || '';
    document.getElementById('editVisitForm_'  + panelId).style.display = 'block';
}

function saveVisit(panelId, action) {
    let visitDate, note, historyId = 0;
    if (action === 'add') {
        visitDate = document.getElementById('visitDate_' + panelId).value;
        note      = document.getElementById('visitNote_' + panelId).value;
    } else {
        visitDate = document.getElementById('editVisitDate_'  + panelId).value;
        note      = document.getElementById('editVisitNote_'  + panelId).value;
        historyId = document.getElementById('editHistoryId_'  + panelId).value;
    }
    if (!visitDate) { showToast('Please select a visit date.', 'error'); return; }

    const fd = new FormData();
    fd.append('panel_id',   panelId);
    fd.append('visit_date', visitDate);
    fd.append('note',       note);
    fd.append('action',     action);
    if (action === 'edit') fd.append('history_id', historyId);

    fetch('save_visit_date.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 900);
            } else {
                showToast(data.message || 'Error saving.', 'error');
            }
        })
        .catch(() => showToast('Network error. Please try again.', 'error'));
}

function deleteVisit(panelId, historyId) {
    if (!confirm('Delete this visit record? This cannot be undone.')) return;
    const fd = new FormData();
    fd.append('panel_id',   panelId);
    fd.append('history_id', historyId);
    fd.append('action',     'delete');
    fetch('save_visit_date.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) setTimeout(() => location.reload(), 900);
        })
        .catch(() => showToast('Network error.', 'error'));
}

// ── QA Announcements ─────────────────────────────────────────────────────────
function escHtml(s) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(s));
    return d.innerHTML;
}
function timeAgoJs(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)    return 'Just now';
    if (diff < 3600)  return Math.floor(diff/60)   + ' min ago';
    if (diff < 86400) return Math.floor(diff/3600) + ' hr ago';
    return Math.floor(diff/86400) + 'd ago';
}
function priorityBadge(p) {
    const map = { normal:['badge-normal','Normal'], important:['badge-important','Important'], urgent:['badge-urgent','Urgent'] };
    const [cls, label] = map[p] || map.normal;
    return `<span class="ann-priority-badge ${cls}">${label}</span>`;
}
function toggleAnnBanner() {
    const body    = document.getElementById('annBannerBody');
    const chevron = document.getElementById('annChevron');
    const hidden  = body.style.display === 'none';
    body.style.display    = hidden ? '' : 'none';
    chevron.style.transform = hidden ? 'rotate(0deg)' : 'rotate(-90deg)';
}
function loadAnnouncements() {
    fetch('announcements.php?action=fetch&limit=15')
        .then(r => r.json())
        .then(d => {
            const body     = document.getElementById('annBannerBody');
            const countEl  = document.getElementById('annCount');
            const urgentDot = document.getElementById('annUrgentDot');

            if (!d.success || !d.announcements.length) {
                countEl.textContent = '0';
                body.innerHTML = '<div class="ann-empty"><i class="fas fa-check-circle me-1 text-success"></i>No announcements right now.</div>';
                return;
            }
            countEl.textContent = d.total;
            const hasUrgent = d.announcements.some(a => a.priority !== 'normal');
            urgentDot.style.display = hasUrgent ? 'inline-block' : 'none';

            body.innerHTML = d.announcements.map(a => `
                <div class="ann-item priority-${escHtml(a.priority)}">
                    <div class="ann-item-title">${escHtml(a.title)} ${priorityBadge(a.priority)}</div>
                    <div class="ann-item-body">${escHtml(a.body)}</div>
                    <div class="ann-item-meta">
                        <i class="fas fa-user-shield me-1"></i>${escHtml(a.poster_name)}
                        &nbsp;·&nbsp;
                        <i class="fas fa-clock me-1"></i>${timeAgoJs(a.created_at)}
                    </div>
                </div>`).join('');
        })
        .catch(() => {
            document.getElementById('annBannerBody').innerHTML =
                '<div class="ann-empty text-danger">Could not load announcements.</div>';
        });
}
loadAnnouncements();
</script>
</body>
</html>