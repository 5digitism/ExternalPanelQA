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
 
$pc_name      = $_SESSION['name'] ?? 'Programme Coordinator';
$pc_programme = null;
 
$stmt = $conn->prepare("SELECT programme, name FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$pc_row = $stmt->get_result()->fetch_assoc();
if ($pc_row) {
    $pc_programme = $pc_row['programme'] ?? null;
    $pc_name      = $pc_row['name'];
}
 
$status_msg = "";
$alert_type = "info";
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $status_msg = "Panel successfully submitted for approval!";
        $alert_type = "success";
    } elseif ($_GET['status'] == 'error') {
        $status_msg = "Error: " . htmlspecialchars($_GET['msg']);
        $alert_type = "danger";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register New Panel | EAP System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css?v=1.1">
 
<style>
body { background-color: #f0f2f5; font-family: 'Poppins', sans-serif; }
 
/* ── Banner ─────────────────────────────────────────── */
.page-banner {
    background: linear-gradient(135deg, #1a6fc4 0%, #0b3a6e 100%);
    color: white; padding: 30px 40px; border-radius: 20px;
    margin-bottom: 28px; position: relative; overflow: hidden;
    box-shadow: 0 10px 30px rgba(11,58,110,0.2);
}
.page-banner::after {
    content: "\f234"; font-family: "Font Awesome 6 Free"; font-weight: 900;
    position: absolute; right: 30px; bottom: -18px;
    font-size: 130px; opacity: 0.07; pointer-events: none;
}
.programme-pill {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3);
    color: white; border-radius: 30px; padding: 5px 16px;
    font-size: 0.8rem; font-weight: 500; margin-top: 8px;
    backdrop-filter: blur(4px);
}
.btn-back {
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.35);
    color: white; border-radius: 50px; padding: 9px 22px;
    font-size: 0.82rem; font-weight: 500; transition: all 0.25s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
    white-space: nowrap; flex-shrink: 0;
}
.btn-back:hover { background: white; color: #1a6fc4; transform: translateX(-3px); }
 
/* ── Section cards ──────────────────────────────────── */
.form-section {
    background: white; border-radius: 16px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.05);
    margin-bottom: 20px; overflow: hidden;
}
.form-section-head {
    padding: 18px 28px; border-bottom: 1px solid #f1f3f5;
    display: flex; align-items: center; gap: 14px;
}
.section-icon {
    width: 40px; height: 40px; border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem; flex-shrink: 0;
}
.form-section-head h6 { font-weight: 700; font-size: 0.92rem; color: #0b3a6e; margin: 0; }
.form-section-head p  { font-size: 0.74rem; color: #8a95a3; margin: 0; }
.form-section-body { padding: 24px 28px; }
 
/* ── Inputs ─────────────────────────────────────────── */
.form-label {
    font-size: 0.75rem; font-weight: 600; color: #6c757d;
    text-transform: uppercase; letter-spacing: .6px; margin-bottom: 6px;
}
.form-control, .form-select {
    border-radius: 11px; padding: 11px 15px;
    border: 1.5px solid #e9ecef; background: #f8f9fc;
    font-size: 0.87rem; font-family: 'Poppins', sans-serif;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    color: #212529;
}
.form-control:focus, .form-select:focus {
    border-color: #1a6fc4; background: #fff;
    box-shadow: 0 0 0 3px rgba(26,111,196,0.1); outline: none;
}
.form-control::placeholder { color: #c0c8d5; }
.form-control:disabled, .form-select:disabled {
    background: #f1f3f5; color: #6c757d; cursor: not-allowed;
}
.input-group-text {
    border-radius: 11px 0 0 11px !important;
    border: 1.5px solid #e9ecef; border-right: none !important;
    background: #f8f9fc; color: #93c5fd;
}
.input-group .form-control {
    border-radius: 0 11px 11px 0 !important;
    border-left: none !important;
}
 
/* ── Locked programme display ───────────────────────── */
.locked-prog-display {
    border-radius: 11px; padding: 11px 15px;
    border: 1.5px solid #dbeafe; background: #eff6ff;
    font-size: 0.87rem; color: #1a6fc4; font-weight: 600;
    display: flex; align-items: center; gap: 10px;
}
.locked-prog-display i { color: #93c5fd; }
 
/* ── File upload ────────────────────────────────────── */
.file-drop {
    border: 2px dashed #d1d9e6; border-radius: 12px;
    padding: 30px 20px; text-align: center;
    background: #f8f9fc; cursor: pointer;
    transition: border-color 0.25s, background 0.25s;
    position: relative;
}
.file-drop:hover, .file-drop.dragover {
    border-color: #1a6fc4; background: #eff6ff;
}
.file-drop input[type="file"] {
    position: absolute; inset: 0; opacity: 0;
    cursor: pointer; width: 100%; height: 100%;
}
.file-drop .upload-icon { font-size: 2rem; color: #93c5fd; margin-bottom: 8px; }
.file-drop .upload-label { font-size: 0.85rem; font-weight: 600; color: #374151; }
.file-drop .upload-sub   { font-size: 0.74rem; color: #9ca3af; margin-top: 4px; }
.file-name-tag {
    display: inline-flex; align-items: center; gap: 8px;
    background: #dbeafe; color: #1e40af;
    border-radius: 8px; padding: 5px 12px;
    font-size: 0.78rem; font-weight: 600; margin-top: 10px;
}
 
/* ── Submit footer ──────────────────────────────────── */
.submit-footer {
    background: white; border-radius: 16px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.05);
    padding: 20px 28px; margin-bottom: 30px;
    display: flex; align-items: center;
    justify-content: space-between; flex-wrap: wrap; gap: 12px;
}
.submit-footer .note {
    font-size: 0.78rem; color: #8a95a3;
    display: flex; align-items: center; gap: 8px;
}
.btn-review {
    background: linear-gradient(135deg, #1a6fc4, #0b3a6e);
    color: white; border: none; border-radius: 50px;
    padding: 12px 36px; font-weight: 600; font-size: 0.88rem;
    font-family: 'Poppins', sans-serif; cursor: pointer;
    transition: all 0.25s; box-shadow: 0 6px 18px rgba(26,111,196,0.3);
    display: inline-flex; align-items: center; gap: 9px;
}
.btn-review:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(26,111,196,0.4); }
 
/* ── Preview Modal ──────────────────────────────────── */
.modal-content { border: none; border-radius: 20px; overflow: hidden; }
.modal-preview-header {
    background: linear-gradient(135deg, #1a6fc4, #0b3a6e);
    color: white; padding: 22px 28px;
    display: flex; align-items: center; justify-content: space-between;
}
.modal-preview-header h5 { font-weight: 700; font-size: 0.95rem; margin: 0; }
.preview-block-title {
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .8px; color: #1a6fc4; margin: 14px 0 6px;
    display: flex; align-items: center; gap: 6px;
}
.preview-row {
    display: flex; gap: 10px; padding: 9px 0;
    border-bottom: 1px solid #f1f3f5; align-items: flex-start;
}
.preview-row:last-child { border-bottom: none; }
.preview-row .p-key {
    font-size: 0.72rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: .5px; color: #9ca3af; min-width: 120px; padding-top: 2px;
}
.preview-row .p-val { font-size: 0.87rem; font-weight: 600; color: #212529; }
.btn-confirm {
    background: linear-gradient(135deg, #1a6fc4, #0b3a6e);
    color: white; border: none; border-radius: 50px;
    padding: 11px 30px; font-weight: 600; font-size: 0.87rem;
    font-family: 'Poppins', sans-serif; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    transition: opacity 0.2s;
}
.btn-confirm:hover { opacity: .9; }
.btn-edit-modal {
    background: #f1f3f5; color: #374151; border: none; border-radius: 50px;
    padding: 11px 22px; font-weight: 600; font-size: 0.87rem;
    font-family: 'Poppins', sans-serif; cursor: pointer;
    transition: background 0.2s;
}
.btn-edit-modal:hover { background: #e2e8f0; }
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
    <a href="pc_evaluation.php"><i class="fas fa-list-check me-2"></i><span>My Programme</span></a>
    <a href="pc_panels.php"><i class="fas fa-users me-2"></i><span>My Panels</span></a>
    <a href="newpanel.php" class="active"><i class="fas fa-user-plus me-2"></i><span>Register New Panel</span></a>
    <hr>
    <a href="logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-2"></i><span>Logout</span></a>
  </div>
 
  <!-- ── Main Content ── -->
  <div class="content p-4">
 
    <?php if ($status_msg): ?>
    <div class="alert alert-<?= $alert_type ?> alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
      <?= $alert_type === 'success'
          ? '<i class="fas fa-check-circle me-2"></i>'
          : '<i class="fas fa-exclamation-triangle me-2"></i>' ?>
      <?= $status_msg ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
 
    <!-- ── Banner ── -->
    <div class="page-banner d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <p class="mb-1 opacity-75 small text-uppercase fw-semibold" style="letter-spacing:1px;">Programme Coordinator</p>
        <h2 class="fw-bold mb-1">Register New Panel</h2>
        <p class="mb-0 opacity-75" style="font-size:.88rem;">Submit a new External Academic Panel member for approval.</p>
        <?php if ($pc_programme): ?>
          <div class="programme-pill mt-2">
            <i class="fas fa-lock" style="font-size:.7rem;"></i>
            Registering for: <strong><?= htmlspecialchars($pc_programme) ?></strong>
          </div>
        <?php else: ?>
          <div class="programme-pill mt-2">
            <i class="fas fa-globe" style="font-size:.7rem;"></i>
            All Programmes (Admin View)
          </div>
        <?php endif; ?>
      </div>
      <a href="pc_panels.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Back to My Panels
      </a>
    </div>
 
    <!-- ── Form ── -->
    <form id="addPanelForm" action="panelbackend.php" method="POST" enctype="multipart/form-data">
 
      <!-- Section 1: Personal & Contact -->
      <div class="form-section">
        <div class="form-section-head">
          <div class="section-icon bg-primary-subtle text-primary">
            <i class="fas fa-id-card"></i>
          </div>
          <div>
            <h6>Personal &amp; Contact Information</h6>
            <p>Identity and contact details of the panel member</p>
          </div>
        </div>
        <div class="form-section-body">
 
          <div class="mb-4">
            <label class="form-label">Full Name (with Title)</label>
            <input type="text" name="panel_name" id="panelName" class="form-control"
                   placeholder="e.g. Prof. Dr. Ahmad Zakaria" required>
          </div>
 
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Email Address</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" name="email" id="panelEmail" class="form-control"
                       placeholder="name@example.com" required>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone Number</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                <input type="tel" name="phone" id="panelPhone" class="form-control"
                       placeholder="e.g. +60123456789" required>
              </div>
            </div>
          </div>
 
        </div>
      </div>
 
      <!-- Section 2: Academic Assignment -->
      <div class="form-section">
        <div class="form-section-head">
          <div class="section-icon bg-success-subtle text-success">
            <i class="fas fa-graduation-cap"></i>
          </div>
          <div>
            <h6>Academic Assignment</h6>
            <p>Level, programme, and qualification details</p>
          </div>
        </div>
        <div class="form-section-body">
 
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Academic Level</label>
              <select name="level" id="level" class="form-select"
                      onchange="updateProgrammes()" required>
                <option value="" disabled selected>Select Level</option>
                <option value="Foundation">Foundation</option>
                <option value="Diploma">Diploma</option>
                <option value="Bachelor Degree">Bachelor Degree</option>
                <option value="Master's Degree">Master's Degree</option>
                <option value="Doctorate">Doctorate</option>
              </select>
            </div>
 
            <div class="col-md-6">
              <label class="form-label">Specific Programme</label>
              <?php if ($pc_programme): ?>
                <!-- PC account: locked to their own programme -->
                <div class="locked-prog-display">
                  <i class="fas fa-lock"></i>
                  <span><?= htmlspecialchars($pc_programme) ?></span>
                </div>
                <input type="hidden" name="programme" value="<?= htmlspecialchars($pc_programme) ?>">
              <?php else: ?>
                <!-- Admin: free to choose after selecting level -->
                <select name="programme" id="programme" class="form-select" disabled required>
                  <option value="" disabled selected>Select Level First</option>
                </select>
              <?php endif; ?>
            </div>
          </div>
 
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Highest Qualification</label>
              <input type="text" name="qualification" id="qualification" class="form-control"
                     placeholder="e.g. PhD in Computer Science" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Appointment Start Date</label>
              <input type="date" name="start_date" id="startDate" class="form-control" required>
            </div>
          </div>
 
        </div>
      </div>
 
      <!-- Section 3: Documents & Remarks -->
      <div class="form-section">
        <div class="form-section-head">
          <div class="section-icon bg-warning-subtle text-warning">
            <i class="fas fa-file-alt"></i>
          </div>
          <div>
            <h6>Documents &amp; Remarks</h6>
            <p>Upload CV and add any additional notes</p>
          </div>
        </div>
        <div class="form-section-body">
 
          <div class="mb-4">
            <label class="form-label">Resume / CV (PDF Format)</label>
            <div class="file-drop" id="fileDrop">
              <input type="file" name="resume" id="resumeInput"
                     accept="application/pdf" required onchange="showFileName(this)">
              <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
              <div class="upload-label">Click or drag &amp; drop your PDF here</div>
              <div class="upload-sub">PDF files only &middot; Max 10 MB</div>
              <div id="fileNameDisplay"></div>
            </div>
          </div>
 
          <div>
            <label class="form-label">Additional Remarks</label>
            <textarea name="remarks" id="remarks" class="form-control" rows="3"
              placeholder="Any additional notes about this panel member…"></textarea>
          </div>
 
        </div>
      </div>
 
      <!-- Submit footer -->
      <div class="submit-footer">
        <div class="note">
          <i class="fas fa-info-circle text-primary"></i>
          Submission will be sent for admin approval before the panel is activated.
        </div>
        <button type="button" class="btn-review" onclick="showPreview()">
          <i class="fas fa-eye"></i> Review &amp; Submit
        </button>
      </div>
 
    </form>
  </div><!-- /content -->
</div><!-- /wrapper -->
 
<!-- ── Preview / Confirm Modal ── -->
<div class="modal fade" id="previewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
 
      <div class="modal-preview-header">
        <h5><i class="fas fa-clipboard-check me-2 opacity-75"></i> Verify Before Submitting</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
 
      <div class="modal-body px-4 pt-3 pb-2">
        <p class="text-muted mb-3" style="font-size:.78rem;">
          Please confirm the details below are correct before proceeding.
        </p>
 
        <div class="preview-block-title"><i class="fas fa-user"></i> Personal Details</div>
        <div class="preview-row">
          <div class="p-key">Full Name</div>
          <div class="p-val" id="pName"></div>
        </div>
        <div class="preview-row">
          <div class="p-key">Email</div>
          <div class="p-val" id="pEmail" style="color:#1a6fc4;"></div>
        </div>
        <div class="preview-row">
          <div class="p-key">Phone</div>
          <div class="p-val" id="pPhone"></div>
        </div>
 
        <div class="preview-block-title"><i class="fas fa-graduation-cap"></i> Academic Assignment</div>
        <div class="preview-row">
          <div class="p-key">Level</div>
          <div class="p-val" id="pLevel"></div>
        </div>
        <div class="preview-row">
          <div class="p-key">Programme</div>
          <div class="p-val" id="pProg"></div>
        </div>
        <div class="preview-row">
          <div class="p-key">Qualification</div>
          <div class="p-val" id="pQual"></div>
        </div>
        <div class="preview-row">
          <div class="p-key">Start Date</div>
          <div class="p-val" id="pDate"></div>
        </div>
 
        <div class="preview-block-title"><i class="fas fa-file-pdf"></i> Document</div>
        <div class="preview-row">
          <div class="p-key">Resume / CV</div>
          <div class="p-val" id="pFile"></div>
        </div>
      </div>
 
      <div class="modal-footer border-0 px-4 pb-4 pt-2 gap-2">
        <button type="button" class="btn-edit-modal" data-bs-dismiss="modal">
          <i class="fas fa-pen me-1"></i> Edit
        </button>
        <button type="button" id="confirmSubmit" class="btn-confirm">
          <i class="fas fa-paper-plane"></i> Confirm &amp; Submit
        </button>
      </div>
 
    </div>
  </div>
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    document.getElementById("wrapper").classList.toggle("collapsed");
}
 
// ── Programme data (identical to original newpanel.php) ───────────────────
const programmeData = {
    "Foundation": [
        "Foundation in Computer Technology",
        "Foundation in Science and Technology (special track for Korean Universities)"
    ],
    "Diploma": [
        "Diploma in Information Technology",
        "Diploma in Networking Technology",
        "Diploma in Animation",
        "Diploma in Multimedia"
    ],
    "Bachelor Degree": [
        "Bachelor of Information Technology (Hons) in Software Engineering",
        "Bachelor of Information Technology (Hons) in Computer System Security",
        "Bachelor of Information Technology (Hons) in Internet of Things (IoT)",
        "Bachelor of Computer Engineering Technology (Hons) in Networking Systems",
        "Bachelor of Computer Engineering Technology (Hons) in Computer Systems",
        "Bachelor in Computing and Business Management (Honours)",
        "Bachelor of Multimedia Technology (Hons) in Interactive Multimedia Design",
        "Bachelor of Multimedia Technology (Hons) in Computer Animation",
        "Bachelor of Game Development Technology with Honours"
    ],
    "Master's Degree": [
        "Master of Information Technology",
        "Master in Computer Science",
        "Master in Creative Digital Media"
    ],
    "Doctorate": [
        "Doctor of Philosophy (Information Technology)"
    ]
};
 
// PHP-injected: the PC's locked programme (null for admin)
const lockedProgramme = <?= $pc_programme ? json_encode($pc_programme) : 'null' ?>;
 
// Only needed for admin (where the <select id="programme"> exists)
function updateProgrammes() {
    const progSelect = document.getElementById('programme');
    if (!progSelect) return;
    const selectedLevel = document.getElementById('level').value;
    progSelect.innerHTML = '<option value="" disabled selected>Select Programme</option>';
    progSelect.disabled = false;
    if (programmeData[selectedLevel]) {
        programmeData[selectedLevel].forEach(prog => {
            const opt = document.createElement('option');
            opt.value = prog; opt.textContent = prog;
            progSelect.appendChild(opt);
        });
    }
}
 
// ── File upload UI ────────────────────────────────────────────────────────
function showFileName(input) {
    const display = document.getElementById('fileNameDisplay');
    if (input.files && input.files[0]) {
        display.innerHTML = `<div class="file-name-tag">
            <i class="fas fa-file-pdf"></i> ${input.files[0].name}
        </div>`;
    }
}
const dropZone = document.getElementById('fileDrop');
['dragover','dragenter'].forEach(ev => dropZone.addEventListener(ev, () => dropZone.classList.add('dragover')));
['dragleave','drop'].forEach(ev => dropZone.addEventListener(ev, () => dropZone.classList.remove('dragover')));
 
// ── Preview modal ─────────────────────────────────────────────────────────
const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
 
function showPreview() {
    const form = document.getElementById('addPanelForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }
 
    // Programme: use locked value for PC, or dropdown for admin
    const progValue = lockedProgramme
        ? lockedProgramme
        : (document.getElementById('programme')?.value ?? '—');
 
    document.getElementById('pName').textContent  = document.getElementById('panelName').value;
    document.getElementById('pEmail').textContent = document.getElementById('panelEmail').value;
    document.getElementById('pPhone').textContent = document.getElementById('panelPhone').value;
    document.getElementById('pLevel').textContent = document.getElementById('level').value;
    document.getElementById('pProg').textContent  = progValue;
    document.getElementById('pQual').textContent  = document.getElementById('qualification').value;
 
    // Format date nicely
    const raw = document.getElementById('startDate').value;
    document.getElementById('pDate').textContent = raw
        ? new Date(raw + 'T00:00:00').toLocaleDateString('en-MY', { day:'2-digit', month:'long', year:'numeric' })
        : '—';
 
    // File name
    const fi = document.getElementById('resumeInput');
    document.getElementById('pFile').textContent = fi.files[0]?.name ?? '—';
 
    previewModal.show();
}
 
document.getElementById('confirmSubmit').addEventListener('click', function () {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Submitting…';
    document.getElementById('addPanelForm').submit();
});
</script>
</body>
</html>