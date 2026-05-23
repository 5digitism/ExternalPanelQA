<?php
session_start();
require_once 'db.php';
require_once 'panel_title_badge.php';
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
        SELECT panel_members.*,
            (SELECT COUNT(*) FROM evaluations WHERE panel_id = panel_members.id) AS eval_count,
            (SELECT MAX(evaluation_date) FROM evaluations WHERE panel_id = panel_members.id) AS last_eval,
            (SELECT MAX(q.latest_visit_date)
             FROM evaluations ev
             LEFT JOIN qa_submission_status q ON q.evaluation_id = ev.id
             WHERE ev.panel_id = panel_members.id
               AND q.latest_visit_date IS NOT NULL
            ) AS last_visit_date,
            (SELECT COUNT(*) FROM invitation_letters WHERE panel_id = panel_members.id) AS has_letter
        FROM panel_members
        WHERE programme = ? AND TRIM(LOWER(status)) = 'approved'
        ORDER BY panel_name ASC
    ");
    $stmt2->bind_param("s", $pc_programme);
    $stmt2->execute();
    $panels = $stmt2->get_result();
} else {
    $panels = $conn->query("
        SELECT panel_members.*,
            (SELECT COUNT(*) FROM evaluations WHERE panel_id = panel_members.id) AS eval_count,
            (SELECT MAX(evaluation_date) FROM evaluations WHERE panel_id = panel_members.id) AS last_eval,
            (SELECT MAX(q.latest_visit_date)
             FROM evaluations ev
             LEFT JOIN qa_submission_status q ON q.evaluation_id = ev.id
             WHERE ev.panel_id = panel_members.id
               AND q.latest_visit_date IS NOT NULL
            ) AS last_visit_date,
            (SELECT COUNT(*) FROM invitation_letters WHERE panel_id = panel_members.id) AS has_letter
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
.profile-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 16px; background: #1a6fc4; color: white;
    border: none; border-radius: 12px; font-size: 0.84rem; font-weight: 600;
    font-family: 'Poppins', sans-serif; margin-top: auto; cursor: pointer;
    transition: background 0.2s, transform 0.15s; width: 100%;
}
.profile-btn:hover { background: #0b3a6e; color: white; transform: translateY(-1px); }

/* Modal styles configuration mechanics */
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

/* Visit tracking modules */
.visit-edit-box {
    background: #f0f7ff; border: 1.5px solid #bee3f8; border-radius: 12px;
    padding: 14px 16px; margin-bottom: 16px;
}
.visit-edit-box .ve-label {
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .8px; color: #1a6fc4; margin-bottom: 8px;
    display: flex; align-items: center; gap: 6px;
}
.visit-edit-box .ve-row { display: flex; gap: 8px; align-items: center; }
.visit-edit-box input[type="date"] {
    flex: 1; border: 1px solid #b6d4fe; border-radius: 8px;
    padding: 7px 12px; font-size: 0.85rem; font-family: 'Poppins', sans-serif;
    color: #212529; background: white;
}
.visit-edit-box input[type="date"]:focus { outline: none; border-color: #1a6fc4; box-shadow: 0 0 0 3px rgba(26,111,196,0.15); }
.ve-save-btn {
    background: #1a6fc4; color: white; border: none; border-radius: 8px;
    padding: 7px 16px; font-size: 0.82rem; font-weight: 600;
    font-family: 'Poppins', sans-serif; cursor: pointer; white-space: nowrap;
    transition: background 0.2s;
}
.ve-save-btn:hover { background: #0b3a6e; }
.ve-save-btn.saved { background: #065f46; }
.ve-msg { font-size: 0.75rem; margin-top: 6px; color: #065f46; display: none; }

.visit-history-wrap { margin-bottom: 20px; }
.visit-history-title {
    font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1px; color: #1a6fc4; margin-bottom: 10px;
    padding-bottom: 6px; border-bottom: 1px solid #e9ecef;
    display: flex; align-items: center; justify-content: space-between;
}
.visit-timeline { list-style: none; padding: 0; margin: 0; position: relative; }
.visit-timeline::before {
    content: ''; position: absolute; left: 9px; top: 6px; bottom: 6px;
    width: 2px; background: #dee2e6; border-radius: 2px;
}
.visit-timeline li { display: flex; align-items: flex-start; gap: 14px; padding: 6px 0 6px 28px; position: relative; font-size: 0.82rem; }
.visit-timeline li::before {
    content: ''; position: absolute; left: 5px; top: 12px;
    width: 10px; height: 10px; border-radius: 50%;
    background: #1a6fc4; border: 2px solid white; box-shadow: 0 0 0 2px #1a6fc4;
}
.visit-timeline li.latest::before { background: #065f46; box-shadow: 0 0 0 2px #065f46; }
.vt-date { font-weight: 600; color: #0b3a6e; min-width: 90px; }
.vt-eval { color: #6c757d; font-size: 0.78rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; }
.visit-history-empty { font-size: 0.82rem; color: #adb5bd; font-style: italic; padding: 8px 0; }
.history-loading { font-size: 0.8rem; color: #adb5bd; padding: 8px 0; }

/* Invitation System Configuration Styling rules */
.invite-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 16px; background: linear-gradient(135deg, #16a34a, #14532d);
    color: white; border: none; border-radius: 12px; font-size: 0.84rem; font-weight: 600;
    font-family: 'Poppins', sans-serif; cursor: pointer;
    transition: opacity 0.2s, transform 0.15s; width: 100%; margin-bottom: 10px;
}
.invite-btn:hover { opacity: 0.88; transform: translateY(-1px); }

.view-saved-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 16px; background: linear-gradient(135deg, #4b5563, #1f2937);
    color: white; border: none; border-radius: 12px; font-size: 0.84rem; font-weight: 600;
    font-family: 'Poppins', sans-serif; cursor: pointer;
    transition: opacity 0.2s, transform 0.15s; width: 100%; margin-bottom: 10px;
}
.view-saved-btn:hover { opacity: 0.88; transform: translateY(-1px); }

#invitationFormModal .modal-content { border-radius: 18px; font-family: 'Poppins', sans-serif; }
#invitationFormModal .modal-header { background: linear-gradient(135deg, #16a34a, #14532d); color: white; border-radius: 18px 18px 0 0; padding: 20px 24px; border: none; }
#invitationFormModal .modal-header .btn-close { filter: invert(1); }
#invitationFormModal .form-label { font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px; color: #1a6fc4; margin-bottom: 5px; }
#invitationFormModal .form-control, #invitationFormModal .form-select { border-radius: 10px; border: 1px solid #dee2e6; font-family: 'Poppins', sans-serif; font-size: 0.88rem; padding: 9px 14px; }
#invitationFormModal .form-control:focus, #invitationFormModal .form-select:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,0.15); }
.generate-letter-btn { background: linear-gradient(135deg, #16a34a, #14532d); color: white; border: none; border-radius: 10px; padding: 11px 28px; font-size: 0.88rem; font-weight: 600; font-family: 'Poppins', sans-serif; cursor: pointer; transition: opacity 0.2s; }
.generate-letter-btn:hover { opacity: 0.88; }

#letterPreviewModal .modal-content { border-radius: 18px; font-family: 'Poppins', sans-serif; }
#letterPreviewModal .modal-header { background: linear-gradient(135deg, #0b3a6e, #1a6fc4); color: white; border-radius: 18px 18px 0 0; padding: 18px 24px; border: none; }
#letterPreviewModal .modal-header .btn-close { filter: invert(1); }
.letter-action-bar { display: flex; gap: 10px; padding: 14px 24px; background: #f8f9fa; border-bottom: 1px solid #e9ecef; }
.letter-action-bar button { display: flex; align-items: center; gap: 7px; padding: 8px 18px; border-radius: 9px; font-size: 0.82rem; font-weight: 600; font-family: 'Poppins', sans-serif; cursor: pointer; border: none; transition: opacity 0.2s, transform 0.15s; }
.letter-action-bar button:hover { opacity: 0.88; transform: translateY(-1px); }
.btn-print-letter { background: #1a6fc4; color: white; }
.btn-save-db-letter { background: #16a34a; color: white; }
.btn-back-letter  { background: #6c757d; color: white; }

/* Document Layout Structure Variables */
#letterPaperWrapper { padding: 28px 32px; background: #e9ecef; max-height: 72vh; overflow-y: auto; }
#letterPaper {
    background: white; width: 100%; max-width: 680px; margin: 0 auto;
    /* Large 140px padding gaps down the page layout structure underneath physical letterheads */
    padding: 140px 60px 60px 60px; box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    font-family: 'Arial', sans-serif; font-size: 13px;
    line-height: 1.6; color: #000; min-height: 880px; position: relative;
}
.letter-ref-date { display: flex; flex-direction: column; align-items: flex-start; margin-bottom: 22px; font-size: 13px; }
.letter-recipient { margin-bottom: 20px; line-height: 1.5; }
.letter-subject { font-weight: bold; margin-bottom: 18px; font-size: 13px; text-align: left; text-transform: uppercase; color: #000; }
.letter-body p { margin-bottom: 14px; text-align: justify; }
.letter-details-table { width: 100%; border-collapse: collapse; margin: 18px 0; font-size: 13px; }
.letter-details-table td { padding: 6px 10px; border: none; vertical-align: top; }
.letter-details-table td:first-child { font-weight: bold; width: 15%; }
.letter-details-table td:nth-child(2) { width: 3%; }
.letter-signature { margin-top: 45px; position: relative; }
.computer-generated-notice { font-style: italic; color: #444; font-size: 11px; margin-top: 15px; border-top: 1px dashed #ccc; padding-top: 8px; display: inline-block; }

/* Native Print Layout Overrides */
@media print {
    /* Hide everything except the letter */
    body > * { display: none !important; }
    
    /* Show only the letter wrapper */
    #letterPaperWrapper,
    #letterPaperWrapper * { 
        display: block !important; 
        visibility: visible !important;
    }
    
    #letterPaperWrapper {
        position: fixed !important;
        top: 0 !important; left: 0 !important;
        width: 100% !important;
        padding: 0 !important;
        background: white !important;
        overflow: visible !important;
        max-height: none !important;
    }
    
    #letterPaper {
        width: 100% !important;
        max-width: 100% !important;
        padding: 140px 50px 50px 50px !important;
        box-shadow: none !important;
        font-size: 11pt !important;
        min-height: auto !important;
    }
    
    /* Hide the action bar when printing */
    .letter-action-bar { display: none !important; }
    .modal-header { display: none !important; }
}
</style>
</head>
<body>
<div class="wrapper collapsed" id="wrapper">
  <div class="sidebar">
    <div class="sidebar-header">
      <h3>EAP System</h3>
      <button class="collapse-btn" onclick="toggleSidebar()"><i class="fas fa-chevron-left"></i></button>
    </div>
  <a href="pc_panels.php" class="active"><i class="fas fa-users me-2"></i> <span>My Panels</span></a>
    <a href="pc_evaluation.php"><i class="fas fa-list-check me-2"></i> <span>My Programme</span></a>
    
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
    <?php require_once 'announcement_widget.php'; ?>

    <div class="filter-bar">
      <i class="fas fa-search text-muted"></i>
      <input type="text" id="searchInput" placeholder="Search panel by name or qualification...">
      <span class="text-muted small ms-auto"><?php echo $panel_count; ?> panel<?php echo $panel_count !== 1 ? 's' : ''; ?></span>
    </div>
    <?php if ($panel_count > 0): ?>
    <div class="row g-4" id="panelGrid">
      <?php foreach ($panel_list as $p):
        $initials   = strtoupper(substr($p['panel_name'], 0, 1));
        $days_since = $p['last_eval'] ? (int)((time() - strtotime($p['last_eval'])) / 86400) : null;
        $overdue    = ($days_since === null || $days_since > 30);
        $modal_id   = 'profileModal_' . $p['id'];

        $last_visit      = $p['last_visit_date'] ?? null;
        $visit_days_ago  = $last_visit ? (int)((time() - strtotime($last_visit)) / 86400) : null;
      ?>
      <div class="col-md-4 panel-item"
           data-name="<?php echo strtolower(htmlspecialchars($p['panel_name'])); ?>"
           data-qual="<?php echo strtolower(htmlspecialchars($p['qualification'])); ?>">
        <div class="panel-card">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="panel-avatar"><?php echo $initials; ?></div>
            <div>
            <div class="panel-name">
    <?= htmlspecialchars($p['panel_name']) ?>
    <?= panelTitleBadge($p['panel_title'] ?? '') ?>
</div>
              <div class="panel-meta"><?php echo htmlspecialchars($p['level']); ?></div>
            </div>
          </div>
          <div class="panel-meta mb-2">
            <i class="fas fa-certificate me-1"></i><?php echo htmlspecialchars($p['qualification']); ?>
          </div>
          <div class="panel-meta mb-3">
            <i class="fas fa-calendar-check me-1"></i>Started: <?php echo date('d M Y', strtotime($p['start_date'])); ?>
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
            <span class="eval-badge" style="background:#f0f7ff;color:#0b3a6e;" title="Last PC Visit">
              <i class="fas fa-map-marker-alt"></i>
              Visit: <?php echo date('d M Y', strtotime($last_visit)); ?>
            </span>
            <?php else: ?>
            <span class="eval-badge" style="background:#f8f9fa;color:#adb5bd;" title="No visit recorded">
              <i class="fas fa-map-marker-alt"></i>No Visit
            </span>
            <?php endif; ?>
          </div>
          <button class="profile-btn" data-bs-toggle="modal" data-bs-target="#<?php echo $modal_id; ?>">
            <i class="fas fa-id-card"></i> View Profile
          </button>
        </div>
      </div>

      <div class="modal fade" id="<?php echo $modal_id; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:520px;">
          <div class="modal-content shadow-lg">
            <div class="modal-profile-header">
              <button type="button" class="btn-close btn-close-white position-absolute" style="top:16px;right:16px;" data-bs-dismiss="modal"></button>
              <div class="d-flex flex-column align-items-center text-center">
                <div class="modal-avatar-large"><?php echo $initials; ?></div>
                <div class="modal-panel-name"><?php echo htmlspecialchars($p['panel_name']); ?></div>
               <div class="modal-panel-level">
    <?= htmlspecialchars($p['level']) ?>
    <?= panelTitleBadge($p['panel_title'] ?? '') ?>
</div>
              </div>
              <div class="header-status-pill">
                <i class="fas fa-circle" style="font-size:0.45rem;color:#4ade80;"></i>
                Approved &bull; Panel ID: EAP-P-<?php echo $p['id']; ?>
              </div>
            </div>
            <div class="modal-body">
              <div class="eval-strip">
                <div class="eval-strip-item">
                  <div class="es-value"><?php echo $p['eval_count']; ?></div>
                  <div class="es-label">Evaluations</div>
                </div>
                <div class="eval-strip-divider"></div>
                <div class="eval-strip-item">
                  <div class="es-value" style="font-size:0.9rem;padding-top:6px;">
                    <?php echo $p['last_eval'] ? date('d M Y', strtotime($p['last_eval'])) : '—'; ?>
                  </div>
                  <div class="es-label">Last Submission</div>
                </div>
                <div class="eval-strip-divider"></div>
                <div class="eval-strip-item">
                  <div class="es-value" style="font-size:0.9rem;padding-top:6px;color:<?php echo $last_visit ? '#0b3a6e' : '#adb5bd'; ?>;">
                    <?php echo $last_visit ? date('d M Y', strtotime($last_visit)) : '—'; ?>
                  </div>
                  <div class="es-label">Last Visit</div>
                </div>
              </div>

        <div class="info-section-title">
    <span><i class="fas fa-map-marker-alt me-1"></i>Visit Date</span>
</div>

<div class="visit-edit-box">
    <div class="ve-label"><i class="fas fa-pencil-alt"></i> Record / Update Visit Date</div>

    <!-- Evaluation selector -->
    <div class="mb-2">
        <label style="font-size:11px;color:#6b7280;font-weight:600;
                      text-transform:uppercase;letter-spacing:.5px">
            Select Evaluation
        </label>
        <select id="evalSelect_<?= $p['id'] ?>"
                class="form-select form-select-sm"
                onchange="onEvalSelectChange(<?= $p['id'] ?>)">
            <option value="">— Choose evaluation —</option>
            <?php
            $evals_for_panel = $conn->prepare("
                SELECT e.id, e.title, e.evaluation_date,
                       q.latest_visit_date
                FROM evaluations e
                LEFT JOIN qa_submission_status q ON q.evaluation_id = e.id
                WHERE e.panel_id = ?
                ORDER BY e.evaluation_date DESC
            ");
            $evals_for_panel->bind_param("i", $p['id']);
            $evals_for_panel->execute();
            $evals_result = $evals_for_panel->get_result();
            while ($ev = $evals_result->fetch_assoc()):
            ?>
            <option value="<?= $ev['id'] ?>"
                    data-visit="<?= htmlspecialchars($ev['latest_visit_date'] ?? '') ?>">
                <?= htmlspecialchars($ev['title']) ?>
                (<?= date('d M Y', strtotime($ev['evaluation_date'])) ?>)
                <?= $ev['latest_visit_date'] ? '✓ Visit: ' . date('d M Y', strtotime($ev['latest_visit_date'])) : '' ?>
            </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="ve-row">
        <input type="date"
               id="visitDateInput_<?= $p['id'] ?>"
               max="<?= date('Y-m-d') ?>"
               placeholder="Select evaluation first">
        <button class="ve-save-btn"
                id="visitSaveBtn_<?= $p['id'] ?>"
                onclick="saveVisitDate(<?= $p['id'] ?>)">
            <i class="fas fa-save me-1"></i>Save
        </button>
    </div>
    <div class="ve-msg" id="visitMsg_<?= $p['id'] ?>"></div>
</div>

              <div class="visit-history-wrap">
                <div class="visit-history-title">
                  <span><i class="fas fa-history me-1"></i>Visit History</span>
                  <span class="text-muted" style="font-size:0.7rem;font-weight:400;">All recorded visits</span>
                </div>
                <div id="visitHistory_<?php echo $p['id']; ?>" class="history-loading"><i class="fas fa-spinner fa-spin me-1"></i> Loading...</div>
              </div>

              <div class="info-section-title"><i class="fas fa-user me-1"></i>Personal Details</div>

              <div class="info-row">
                <div class="info-icon"><i class="fas fa-envelope"></i></div>
                <div>
                  <div class="info-label">Email Address</div>
                  <div class="info-value">
                    <?php echo !empty($p['email']) ? '<a href="mailto:'.htmlspecialchars($p['email']).'" style="color:#1a6fc4;">'.htmlspecialchars($p['email']).'</a>' : '<span class="text-muted">Not provided</span>'; ?>
                  </div>
                </div>
              </div>
              <div class="info-row">
                <div class="info-icon"><i class="fas fa-phone"></i></div>
                <div>
                  <div class="info-label">Phone Number</div>
                  <div class="info-value"><?php echo !empty($p['phone']) ? htmlspecialchars($p['phone']) : '<span class="text-muted">Not provided</span>'; ?></div>
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
              <div class="no-eval-notice"><i class="fas fa-info-circle flex-shrink-0"></i>This panel has not submitted any evaluations yet.</div>
              <?php endif; ?>

              <?php
                $invite_data = json_encode([
                  'id'        => $p['id'],
                  'name'      => $p['panel_name'],
                  'address'   => $p['panel_address'] ?? '',
                  'programme' => $p['programme'],
                  'pcName'    => $pc_name,
                ]);
              ?>
              
              <?php if ($p['has_letter'] > 0): ?>
                <button class="view-saved-btn" onclick="loadSavedLetter(<?php echo $p['id']; ?>)">
                  <i class="fas fa-folder-open"></i> View / Print Confirmed Letter
                </button>
              <?php endif; ?>

              <button class="invite-btn" onclick="openInviteForm(<?php echo htmlspecialchars($invite_data, ENT_QUOTES); ?>)">
                <i class="fas fa-envelope-open-text"></i> <?php echo ($p['has_letter'] > 0) ? 'Generate New / Update Letter' : 'Generate Invitation Letter'; ?>
              </button>
              <hr class="my-3 opacity-25">
<div class="info-section-title">
    <i class="fas fa-file-upload me-1"></i>IAC Proposal Upload
</div>
<?php
// Get latest evaluation id for this panel
$latest_eval = $conn->prepare("
    SELECT id FROM evaluations 
    WHERE panel_id = ? 
    ORDER BY evaluation_date DESC LIMIT 1
");
$latest_eval->bind_param("i", $p['id']);
$latest_eval->execute();
$latest_eval_row = $latest_eval->get_result()->fetch_assoc();
$latest_eval_id  = $latest_eval_row['id'] ?? 0;
?>
<?php if ($latest_eval_id): ?>
    <button class="btn btn-sm w-100 mb-2"
            style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;
                   border-radius:10px;padding:9px;font-size:0.84rem;font-weight:600"
            onclick="openUploadModal(<?= $latest_eval_id ?>, 'iac', 'IAC/IRPC')">
        <i class="fas fa-upload me-1"></i> Upload / View IAC Proposal
    </button>
<?php else: ?>
    <p class="text-muted small fst-italic">
        No evaluation submitted yet — upload available after first submission.
    </p>
<?php endif; ?>

              <a href="pc_evaluation.php?panel=<?php echo $p['id']; ?>" class="eval-btn mb-3">
                <i class="fas fa-clipboard-list"></i> View Evaluations
                <?php if ($p['eval_count'] > 0): ?>
                <span style="background:rgba(255,255,255,0.2);border-radius:20px;padding:1px 8px;font-size:0.75rem;margin-left:4px;"><?php echo $p['eval_count']; ?></span>
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
        <?php echo $pc_programme ? 'No approved panels found for <strong>' . htmlspecialchars($pc_programme) . '</strong>.' : 'No approved panels in the system.'; ?>
      </p>
    </div>
    <?php endif; ?>
    <div class="text-center mt-5 mb-4 text-muted small">&copy; <?php echo date("Y"); ?> External Academic Panel System &mdash; Portal</div>
  </div>
</div>

<div class="modal fade" id="invitationFormModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
    <div class="modal-content shadow-lg">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-envelope-open-text me-2"></i>Generate Invitation Letter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted small mb-3">Fill in the fields below. The text output automatically fits spacing constraints below your physical paper letterhead.</p>
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-sticky-note me-1"></i>Reference Number</label>
          <input type="text" class="form-control" id="inviteRefNo" placeholder="e.g. UniKLMIIT/QA/PAV/MCSODL/2026/009">
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-calendar-alt me-1"></i>Visitation Date</label>
          <input type="date" class="form-control" id="inviteDate">
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-clock me-1"></i>Time Range</label>
          <input type="text" class="form-control" id="inviteTime" placeholder="e.g. 9.00 am - 5.00 pm">
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-map-marker-alt me-1"></i>Meeting Venue</label>
          <textarea class="form-control" id="inviteVenue" rows="2" placeholder="Meeting Room 2210, Level 22, UniKL Malaysian Institute of Information Technology"></textarea>
        </div><div class="mb-3">
  <label class="form-label"><i class="fas fa-building me-1"></i>Panel's Work Address</label>
  <textarea class="form-control" id="inviteWorkAddress" rows="3"
    placeholder="e.g. Universiti Teknologi Malaysia&#10;81310 Skudai, Johor"></textarea>
</div>
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-user-tie me-1"></i>Signatory Head Name</label>
          <input type="text" class="form-control" id="inviteSignatory" placeholder="Prof. Ts. Dr. Mohd Nizam Husen">
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-briefcase me-1"></i>Signatory Title Designation</label>
          <input type="text" class="form-control" id="inviteSignatoryTitle" placeholder="Dean / Head of Campus">
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="generate-letter-btn" onclick="generateLetter()"><i class="fas fa-file-alt me-2"></i>Compile & Preview</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="letterPreviewModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content shadow-lg">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-file-contract me-2"></i>Invitation Letter Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="letter-action-bar">
        <button class="btn-print-letter" onclick="printLetter()"><i class="fas fa-print"></i> Print Letter Document</button>
        <button class="btn-save-db-letter" id="dbSaveButton" onclick="commitLetterToDatabase()"><i class="fas fa-save"></i> Save & Confirm Letter</button>
        <button class="btn-back-letter" id="backEditButton" onclick="backToForm()"><i class="fas fa-arrow-left"></i> Adjust Variables</button>
      </div>
      <div id="letterPaperWrapper">
        <div id="letterPaper">
          </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() { document.getElementById("wrapper").classList.toggle("collapsed"); }
document.getElementById('searchInput').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.panel-item').forEach(item => {
        const match = item.dataset.name.includes(q) || item.dataset.qual.includes(q);
        item.style.display = match ? '' : 'none';
    });
});

function onEvalSelectChange(panelId) {
    const select   = document.getElementById('evalSelect_' + panelId);
    const input    = document.getElementById('visitDateInput_' + panelId);
    const selected = select.options[select.selectedIndex];
    // Pre-fill with existing visit date if any
    input.value = selected.dataset.visit || '';
}

function saveVisitDate(panelId) {
    const select    = document.getElementById('evalSelect_' + panelId);
    const input     = document.getElementById('visitDateInput_' + panelId);
    const btn       = document.getElementById('visitSaveBtn_' + panelId);
    const msg       = document.getElementById('visitMsg_' + panelId);
    const evalId    = select.value;
    const visitDate = input.value;

    if (!evalId) {
        msg.style.color = '#c0392b';
        msg.textContent = '⚠ Please select an evaluation first.';
        msg.style.display = 'block';
        return;
    }
    if (!visitDate) {
        input.style.borderColor = '#c0392b';
        setTimeout(() => input.style.borderColor = '', 1500);
        return;
    }

    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

    fetch('save_visit_file.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=save&evaluation_id=' + evalId
            + '&visit_date=' + encodeURIComponent(visitDate)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            btn.classList.add('saved');
            btn.innerHTML = '<i class="fas fa-check me-1"></i>Saved!';
            msg.style.color  = '#065f46';
            msg.textContent  = '✓ Visit date saved for selected evaluation.';
            msg.style.display = 'block';
            // Update the option text to reflect saved visit
            const opt = select.options[select.selectedIndex];
            opt.dataset.visit = visitDate;
            opt.textContent = opt.textContent.replace(/✓ Visit:.*$/, '')
                + ' ✓ Visit: ' + new Date(visitDate).toLocaleDateString('en-GB', {
                    day: '2-digit', month: 'short', year: 'numeric'
                });
            loadVisitHistory(panelId);
            setTimeout(() => {
                btn.classList.remove('saved');
                btn.innerHTML = '<i class="fas fa-save me-1"></i>Save';
            }, 3000);
        } else {
            msg.style.color  = '#c0392b';
            msg.textContent  = '✗ ' + (data.message || 'Failed to save.');
            msg.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-save me-1"></i>Save';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Save';
        msg.style.color  = '#c0392b';
        msg.textContent  = '✗ Network error.';
        msg.style.display = 'block';
    });
}

function loadVisitHistory(panelId) {
    const container = document.getElementById('visitHistory_' + panelId);
    if (!container) return;
    container.innerHTML = '<span class="history-loading"><i class="fas fa-spinner fa-spin me-1"></i> Loading...</span>';

    fetch('save_visit_file.php?action=get_history&panel_id=' + panelId)
    .then(r => r.json())
    .then(data => {
        if (!data.success || data.history.length === 0) {
            container.innerHTML = '<p class="visit-history-empty"><i class="fas fa-calendar-times me-1"></i>No visit dates recorded yet.</p>';
            return;
        }
        let html = '<ul class="visit-timeline">';
        data.history.forEach((h, i) => {
            const d = new Date(h.visit_date);
            const formatted = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            const evalLabel = h.eval_title || ('Evaluation #' + h.evaluation_id);
            html += `<li class="${i === 0 ? 'latest' : ''}">
                <span class="vt-date">${formatted}</span>
                <span class="vt-eval" title="${evalLabel}">${evalLabel}</span>
            </li>`;
        });
        html += '</ul>';
        container.innerHTML = html;
    })
    .catch(() => {
        container.innerHTML = '<p class="visit-history-empty text-danger">Failed to load history timeline trackers.</p>';
    });
}

document.addEventListener('shown.bs.modal', function (e) {
    const modal = e.target;
    const match = modal.id.match(/profileModal_(\d+)/);
    if (match) loadVisitHistory(parseInt(match[1]));
});

// ── Invitation Letter Form Submission Engines ──────────────────
let _invitePanel = {};
let currentSubmissionPayload = {};

function openInviteForm(data) {
    _invitePanel = data;
    document.getElementById('inviteRefNo').value = 'UniKLMIIT/QA/PAV/' + data.programme.replace(/\s+/g, '') + '/' + new Date().getFullYear() + '/';
    document.getElementById('inviteDate').value = '';
    document.getElementById('inviteTime').value = '9.00 am - 5.00 pm';
    document.getElementById('inviteVenue').value = 'Meeting Room, UniKL Malaysian Institute of Information Technology';
    document.getElementById('inviteWorkAddress').value = data.address || '';
    document.getElementById('inviteSignatory').value = '';
    document.getElementById('inviteSignatoryTitle').value = 'Dean / Head of Campus';
    
    document.getElementById('dbSaveButton').style.display = 'block';
    document.getElementById('backEditButton').style.display = 'block';

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('invitationFormModal'));
    modal.show();
}

function generateLetter() {
    const refNo    = document.getElementById('inviteRefNo').value.trim();
    const dateVal  = document.getElementById('inviteDate').value;
    const timeVal  = document.getElementById('inviteTime').value.trim();
    const venueVal = document.getElementById('inviteVenue').value.trim();
    const sigName  = document.getElementById('inviteSignatory').value.trim() || 'Prof. Ts. Dr. Mohd Nizam Husen';
    const sigTitle = document.getElementById('inviteSignatoryTitle').value.trim() || 'Dean / Head of Campus';

    if(!dateVal || !venueVal || !refNo) {
        alert('Please fill up Reference Number, Visitation Date and Venue parameters.');
        return;
    }

    const d = new Date(dateVal);
    const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const formattedVisitDate = d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear() + ' (' + days[d.getDay()] + ')';

    const today = new Date();
    const currentLetterDate = today.getDate() + ' ' + months[today.getMonth()] + ' ' + today.getFullYear();
    const workAddr = document.getElementById('inviteWorkAddress').value.trim();
const panelAddressClean = workAddr
    ? workAddr.replace(/\n/g, '<br>')
    : (_invitePanel.address ? _invitePanel.address.replace(/\n/g, '<br>') : 'Address Not Specified');

    currentSubmissionPayload = {
        panel_id: _invitePanel.id,
        ref_no: refNo,
        letter_date: today.toISOString().split('T')[0],
        visit_date: dateVal,
        visit_time: timeVal,
         work_address: workAddr, 
        venue: venueVal,
        purpose: 'INVITATION FOR PROGRAMME ADVISOR VISIT FOR ' + _invitePanel.programme
    };

    // Formatted Layout Output with Letterhead Excluded and Signatures Swapped with System String Notices
    const targetHTML = `
        <div class="letter-ref-date">
            <span><strong>Our Ref:</strong> ${refNo}</span>
            <span><strong>Date:</strong> ${currentLetterDate}</span>
        </div>

        <div class="letter-recipient">
            <strong>${_invitePanel.name}</strong><br>
            ${panelAddressClean}
        </div>

        <p>Dear ${_invitePanel.name.split(' ').slice(0, 3).join(' ')},</p>

        <div class="letter-subject">INVITATION FOR PROGRAMME ADVISOR VISIT (PROGRAMME ASSESSOR) FOR ${_invitePanel.programme.toUpperCase()}</div>

        <div class="letter-body">
            <p>The above matter is kindly referred.</p>
            <p>We are pleased to invite you for curriculum review and evaluation of the academic programme mentioned above. The details of the scheduling visit are:</p>

            <table class="letter-details-table">
                <tr><td><strong>Date</strong></td><td>:</td><td>${formattedVisitDate}</td></tr>
                <tr><td><strong>Time</strong></td><td>:</td><td>${timeVal}</td></tr>
                <tr><td><strong>Venue</strong></td><td>:</td><td>${venueVal}</td></tr>
            </table>

            <p>Your utmost cooperation and commitment to provide the comprehensive evaluation report framework on the same day during this visit window are highly appreciated. The report is important and become part of the programme input for curriculum improvement.</p>
            <p>All related expenses are claimable as per stated in the appointment letter. Any enquiries regarding the visit, please do not hesitate to contact your assigned Programme Coordinator.</p>
            
            <p>Thank you.</p>
            <p>Yours sincerely,</p>
            <p><strong>UNIVERSITI KUALA LUMPUR</strong></p>
        </div>

        <div class="letter-signature">
            <div class="letter-signature-title" style="font-weight: bold; text-transform: uppercase;">${sigName}</div>
            <div style="font-size:12px; color:#000;">${sigTitle}</div>
            <div style="font-size:12px; color:#000;">Malaysian Institute of Information Technology</div>
            <div>
               <span class="computer-generated-notice">This document is computer generated. No signature is required.</span>
            </div>
        </div>
    `;

    document.getElementById('letterPaper').innerHTML = targetHTML;
    currentSubmissionPayload.html = targetHTML;

    bootstrap.Modal.getInstance(document.getElementById('invitationFormModal')).hide();
    setTimeout(() => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('letterPreviewModal')).show();
    }, 350);
}

function commitLetterToDatabase() {
    const saveBtn = document.getElementById('dbSaveButton');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Synchronization running...';

    const formBody = new URLSearchParams();
    formBody.append('action', 'save');
    for (const key in currentSubmissionPayload) {
        formBody.append(key, currentSubmissionPayload[key]);
    }

    // ADVANCED ERROR DETECTION ENGINE IMPLEMENTATION
    fetch('save_letter.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formBody.toString()
    })
    .then(async response => {
        const text = await response.text();
        try {
            // Attempt parsed JSON conversion natively
            return JSON.parse(text);
        } catch (err) {
            // Server threw a raw string error message or crashed with a 500 status code template layout
            throw new Error("Raw response from server: " + text);
        }
    })
    .then(data => {
        if(data.success) {
            alert('Success! The generated letter configurations have been synchronized successfully.');
            location.reload();
        } else {
            alert('Server Operation Refused: ' + data.message);
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Save & Confirm Letter';
        }
    })
    .catch(error => {
        // Displays the raw PHP error logs directly to the user
        alert('Critical Error Occurred:\n' + error.message);
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Save & Confirm Letter';
    });
}

function loadSavedLetter(panelId) {
    fetch('save_letter.php?action=get_saved&panel_id=' + panelId)
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            document.getElementById('letterPaper').innerHTML = data.html;
            
            document.getElementById('dbSaveButton').style.display = 'none';
            document.getElementById('backEditButton').style.display = 'none';
            
            const openModal = bootstrap.Modal.getInstance(document.querySelector('.modal.show'));
            if(openModal) openModal.hide();

            setTimeout(() => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('letterPreviewModal')).show();
            }, 350);
        } else {
            alert('Could not sync content elements: ' + data.message);
        }
    });
}

function backToForm() {
    bootstrap.Modal.getInstance(document.getElementById('letterPreviewModal')).hide();
    setTimeout(() => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('invitationFormModal')).show(); 
    }, 350);
}

function printLetter() {
    const content = document.getElementById('letterPaper').innerHTML;
    const win = window.open('', '_blank', 'width=800,height=900');
    win.document.write(`
        <!DOCTYPE html><html><head><meta charset="utf-8"><title>Invitation Letter</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 13px; line-height: 1.6;
                   color: #000; margin: 0; padding: 140px 60px 60px 60px; }
            .letter-ref-date { margin-bottom: 22px; }
            .letter-recipient { margin-bottom: 20px; line-height: 1.5; }
            .letter-subject { font-weight: bold; margin-bottom: 18px; text-transform: uppercase; }
            .letter-body p { margin-bottom: 14px; text-align: justify; }
            .letter-details-table { width: 100%; border-collapse: collapse; margin: 18px 0; font-size: 13px; }
            .letter-details-table td { padding: 6px 10px; vertical-align: top; }
            .letter-details-table td:first-child { font-weight: bold; width: 15%; }
            .letter-details-table td:nth-child(2) { width: 3%; }
            .letter-signature { margin-top: 45px; }
            .computer-generated-notice { font-style: italic; color: #444; font-size: 11px;
                margin-top: 15px; border-top: 1px dashed #ccc; padding-top: 8px; display: inline-block; }
        </style></head>
        <body>${content}</body></html>
    `);
    win.document.close();
    win.focus();
    win.onload = function() { win.print(); win.close(); };
}
</script>
<!-- Upload Proposal Modal -->
<div class="modal fade" id="uploadProposalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;font-family:'Poppins',sans-serif">
            <div class="modal-header" style="background:linear-gradient(135deg,#0d6efd,#002b6b);color:white;border:none">
                <h5 class="modal-title" id="uploadModalTitle">
                    <i class="fas fa-upload me-2"></i>Upload Proposal
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3" id="uploadModalDesc">Upload the proposal PDF for this evaluation.</p>

                <!-- File list -->
                <div id="uploadFileList" class="mb-3"></div>

                <!-- Upload form -->
                <div id="uploadFormArea">
                    <label class="form-label fw-semibold" style="font-size:12px">
                        Select PDF file (max 10MB)
                    </label>
                    <input type="file" id="proposalFileInput" accept=".pdf"
                           class="form-control form-control-sm mb-3">
                    <button class="btn btn-primary btn-sm w-100" onclick="submitProposalUpload()">
                        <i class="fas fa-upload me-1"></i> Upload PDF
                    </button>
                    <div id="uploadStatusMsg" class="mt-2" style="font-size:12px"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let _uploadEvalId = 0;
let _uploadStage  = '';

function openUploadModal(evalId, stage, stageLabel) {
    _uploadEvalId = evalId;
    _uploadStage  = stage;
    document.getElementById('uploadModalTitle').innerHTML =
        '<i class="fas fa-upload me-2"></i>Upload ' + stageLabel + ' Proposal';
    document.getElementById('uploadModalDesc').textContent =
        'Upload or view proposal PDFs for ' + stageLabel + '.';
    document.getElementById('proposalFileInput').value = '';
    document.getElementById('uploadStatusMsg').textContent = '';
    loadUploadedFiles(evalId, stage);
    bootstrap.Modal.getOrCreateInstance(
        document.getElementById('uploadProposalModal')
    ).show();
}

function loadUploadedFiles(evalId, stage) {
    const list = document.getElementById('uploadFileList');
    list.innerHTML = '<p class="text-muted small"><i class="fas fa-spinner fa-spin me-1"></i>Loading...</p>';

    fetch('upload_proposal.php?action=get&evaluation_id=' + evalId + '&stage=' + stage)
    .then(r => r.json())
    .then(data => {
        if (!data.success || data.files.length === 0) {
            list.innerHTML = '<p class="text-muted small fst-italic">No files uploaded yet.</p>';
            return;
        }
        list.innerHTML = '<p style="font-size:11px;font-weight:700;text-transform:uppercase;'
            + 'letter-spacing:.6px;color:#6b7280;margin-bottom:8px">Uploaded Files</p>'
            + data.files.map(f => `
            <div style="display:flex;align-items:center;justify-content:space-between;
                        background:#f8faff;border:1px solid #e2e8f0;border-radius:8px;
                        padding:8px 12px;margin-bottom:6px">
                <div>
                    <div style="font-size:12px;font-weight:600;color:#0b3a6e">
                        <i class="fas fa-file-pdf me-1" style="color:#dc2626"></i>
                        ${f.original_name}
                    </div>
                    <div style="font-size:10px;color:#9ca3af">
                        ${f.uploader_username} · ${new Date(f.uploaded_at).toLocaleDateString('en-GB')}
                    </div>
                </div>
                <a href="${f.file_path}" target="_blank"
                   style="font-size:11px;color:#0d6efd;text-decoration:none;
                          padding:4px 10px;border:1px solid #bfdbfe;border-radius:6px">
                    <i class="fas fa-eye me-1"></i>View
                </a>
            </div>`).join('');
    });
}

function submitProposalUpload() {
    const fileInput = document.getElementById('proposalFileInput');
    const msg       = document.getElementById('uploadStatusMsg');

    if (!fileInput.files[0]) {
        msg.style.color = '#dc2626';
        msg.textContent = 'Please select a PDF file first.';
        return;
    }

    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('evaluation_id', _uploadEvalId);
    formData.append('stage', _uploadStage);
    formData.append('proposal', fileInput.files[0]);

    msg.style.color = '#6b7280';
    msg.textContent = 'Uploading...';

    fetch('upload_proposal.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            msg.style.color = '#065f46';
            msg.textContent = '✓ Uploaded successfully.';
            fileInput.value = '';
            loadUploadedFiles(_uploadEvalId, _uploadStage);
        } else {
            msg.style.color = '#dc2626';
            msg.textContent = '✗ ' + data.message;
        }
    })
    .catch(() => {
        msg.style.color = '#dc2626';
        msg.textContent = '✗ Upload failed. Check your connection.';
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