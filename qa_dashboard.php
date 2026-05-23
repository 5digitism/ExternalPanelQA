<?php
session_start();
require_once 'db.php';
require_once 'panel_title_badge.php';
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// Filter by everything
$filter_panel = trim($_GET['panel'] ?? '');

$where = "";

if (!empty($filter_panel)) {
    $search = $conn->real_escape_string($filter_panel);

    $where = "WHERE (
        p.panel_name LIKE '%$search%' OR
        p.programme LIKE '%$search%' OR
        e.title LIKE '%$search%' OR
        e.evaluation_date LIKE '%$search%' OR
        q.latest_visit_date LIKE '%$search%' OR
        q.iac_irpc LIKE '%$search%' OR
        q.uac_urpc LIKE '%$search%' OR
        q.senate LIKE '%$search%' OR
        q.iac_date LIKE '%$search%' OR
        q.uac_date LIKE '%$search%' OR
        q.senate_date LIKE '%$search%'
    )";
}
$prog_list = $conn->query("
    SELECT DISTINCT p.programme 
    FROM evaluations e 
    JOIN panel_members p ON e.panel_id = p.id 
    ORDER BY p.programme ASC
");
$filter_programme = trim($_GET['programme'] ?? '');
$where_parts = [];

if (!empty($filter_panel)) {
    $search = $conn->real_escape_string($filter_panel);
    $where_parts[] = "(p.panel_name LIKE '%$search%' OR p.programme LIKE '%$search%'
        OR e.title LIKE '%$search%' OR q.iac_irpc LIKE '%$search%'
        OR q.uac_urpc LIKE '%$search%' OR q.senate LIKE '%$search%')";
}

if (!empty($filter_programme)) {
    $prog = $conn->real_escape_string($filter_programme);
    $where_parts[] = "p.programme = '$prog'";
}

$where = $where_parts ? "WHERE " . implode(" AND ", $where_parts) : "";

$rows = $conn->query("
    SELECT
        e.id            AS evaluation_id,
        e.title,
        e.evaluation_date,
        e.created_at,
        p.id            AS panel_id,
        p.panel_name,
        p.programme,
        q.latest_visit_date,
        q.iac_irpc,
        q.uac_urpc,
        q.senate,
        q.iac_date,
q.uac_date,
q.senate_date
    FROM evaluations e
    JOIN panel_members p ON e.panel_id = p.id
    LEFT JOIN qa_submission_status q ON q.evaluation_id = e.id
    $where
    ORDER BY p.programme ASC, e.evaluation_date DESC
");

// Row number per programme
$programme_counters = [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Submission | EAP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=1.1">
    <style>
        :root { --primary-blue: #0d6efd; --dark-navy: #002b6b; }
        body { background: #f4f7f6; font-family: 'Poppins', sans-serif; font-size: 13px; }

        .page-banner {
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-navy));
            color: white; padding: 30px 40px; border-radius: 20px; margin-bottom: 24px;
        }

        .card-wrap {
            background: white; border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07); overflow: hidden;
        }

        /* Table */
        .qa-table { width: 100%; border-collapse: collapse; }
        .qa-table thead th {
            background: #f1f5ff; padding: 11px 12px;
            font-size: 11px; font-weight: 700; color: #4b5563;
            text-transform: uppercase; letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0; white-space: nowrap;
        }
        .qa-table tbody td {
            padding: 10px 12px; border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        .qa-table tbody tr:hover { background: #f8faff; }

        /* Merged programme cell */
        .prog-cell {
            font-weight: 700; font-size: 14px; color: var(--primary-blue);
            text-align: center; background: #f8faff;
            border-right: 2px solid #e2e8f0;
        }

        /* Inline editable fields */
        .qa-input {
            border: 1px solid transparent; border-radius: 6px;
            padding: 5px 8px; width: 100%; font-size: 12px;
            font-family: 'Poppins', sans-serif;
            background: transparent; transition: all 0.2s;
        }
        .qa-input:hover { border-color: #cbd5e1; background: #f8faff; }
        .qa-input:focus { border-color: var(--primary-blue); background: white; outline: none; box-shadow: 0 0 0 3px rgba(13,110,253,0.1); }
        .qa-input.saved  { border-color: #86efac !important; background: #f0fdf4 !important; }
        .qa-input.unsaved { border-color: #fbbf24 !important; background: #fffbeb !important; }

        /* Save row button */
        .save-row-btn {
            font-size: 11px; padding: 4px 12px; border-radius: 6px;
            border: 1px solid #cbd5e1; background: white;
            cursor: pointer; white-space: nowrap; transition: all 0.2s;
        }
        .save-row-btn:hover { background: #f1f5ff; border-color: var(--primary-blue); color: var(--primary-blue); }
        .save-row-btn.is-saved { border-color: #86efac; color: #16a34a; background: #f0fdf4; }

        /* Actions link */
        .actions-link {
            font-size: 11px; color: var(--primary-blue);
            text-decoration: none; display: block;
            padding: 3px 0; line-height: 1.4;
        }
        .actions-link:hover { text-decoration: underline; }

        /* Filter bar */
        .filter-bar { display: flex; gap: 10px; align-items: center; padding: 16px 20px; border-bottom: 1px solid #f0f0f0; }
        .filter-input {
            border: 1px solid #e2e8f0; border-radius: 8px;
            padding: 7px 12px; font-size: 13px; width: 260px;
            font-family: 'Poppins', sans-serif;
        }
        .filter-input:focus { outline: none; border-color: var(--primary-blue); }

        /* Modal */
        .modal-xl .modal-body { padding: 0; }
        .modal-body iframe { width: 100%; height: 78vh; border: none; }

        /* Badge */
        .type-badge {
            font-size: 10px; padding: 2px 8px; border-radius: 999px; font-weight: 600;
        }
        .type-ee  { background: #dbeafe; color: #1d4ed8; }
        .type-iap { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>

<div class="wrapper collapsed" id="wrapper">

    <div class="sidebar">
        <div class="sidebar-header">
            <h3>EAP System</h3>
            <button class="collapse-btn" onclick="toggleSidebar()"><i class="fas fa-chevron-left"></i></button>
        </div>
        <a href="HQApage.php" ><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a>
        <a href="qa_dashboard.php" class="active"><i class="fas fa-table me-2"></i> <span>Status Submission</span></a>
        <a href="approvalpage.php" ><i class="fas fa-user-check me-2"></i> <span>Pending Approvals</span></a>
        <a href="HQApanel.php"><i class="fas fa-users me-2"></i> <span>Manage Panels</span></a>
        <hr>
        <a href="logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a>
    </div>


    <!-- Main content -->
    <div class="content p-4">

        <div class="page-banner d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1">Status Submission</h2>
                <p class="mb-0 opacity-75">IAC · IRPC · UAC · URPC · Senate</p>
            </div>
        </div>

        <?php $isQA = true; require_once 'announcement_widget.php'; ?>

        <div class="card-wrap">

            <!-- Filter bar -->
            <form method="GET" class="filter-bar">
                <i class="fas fa-search text-muted"></i>
                <input
                    type="text"
                    name="panel"
                    class="filter-input"
                    placeholder="Search..."
                    value="<?= htmlspecialchars($filter_panel) ?>"
                >
                <button type="submit" class="btn btn-sm btn-primary px-3">Search</button>
                <?php if ($filter_panel): ?>
                    <a href="qa_dashboard.php" class="btn btn-sm btn-outline-secondary">Clear</a>
                <?php endif; ?>
                <span class="text-muted ms-auto" style="font-size:12px">
                    <?= $rows ? $rows->num_rows : 0 ?> record(s)
                </span>
            </form>

            <!-- Table -->
            <div class="table-responsive">
            <table class="qa-table">
                <thead>
                    <tr>
                        <th style="width:36px">No.</th>
                        <th>Programme</th>
                        <th>Panel</th>
                        <th>Title</th>
                        <th title="Set by Programme Coordinator (PC). Auto-populated from PC's visit records.">Latest Visit Date <i class="fas fa-info-circle" style="font-size:9px;opacity:0.6;"></i></th>
                        <th>Report Submission Date</th>
                        <th>IAC / IRPC</th>
                        <th>UAC / URPC</th>
                        <th>Senate</th>
                        <th style="width:80px"></th>
                        <th>Actions to be Updated</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($rows && $rows->num_rows > 0):
                    $no = 1;
                    while ($row = $rows->fetch_assoc()):
                        $eval_id   = $row['evaluation_id'];
                        $has_qa    = !empty($row['iac_irpc']) || !empty($row['uac_urpc']) || !empty($row['senate']);

                        // Actions label: "PanelName _ Senate No."
                        $actions_label = htmlspecialchars($row['panel_name'])
                            . ($row['senate'] ? ' _ ' . htmlspecialchars($row['senate']) : '');
                ?>
                <tr id="row-<?= $eval_id ?>">
                    <td class="text-center text-muted fw-semibold"><?= $no++ ?></td>

                    <td class="prog-cell fw-bold"><?= htmlspecialchars($row['programme']) ?></td>

                    <td>
                        <?= htmlspecialchars($row['panel_name']) ?>
                        <?= panelTitleBadge($row['panel_title'] ?? '') ?>
                     
                    </td>

                    <td><?= htmlspecialchars($row['title'] ?? '—') ?></td>

                    <!-- Latest Visit Date — set by PC, read-only for HQA -->
                    <td>
                        <?php if ($row['latest_visit_date']): ?>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <span style="font-size:12px;font-weight:600;color:#065f46;">
                                    <i class="fas fa-calendar-check" style="font-size:10px;margin-right:3px;"></i>
                                    <?= date('d-M-Y', strtotime($row['latest_visit_date'])) ?>
                                </span>
                            </div>
                            <div style="font-size:10px;color:#6c757d;margin-top:2px;">
                                <i class="fas fa-user-tie" style="font-size:9px;"></i> Set by PC
                            </div>
                            <!-- Hidden input so saveRow() can still send the value -->
                            <input type="hidden" id="visit-<?= $eval_id ?>" value="<?= htmlspecialchars($row['latest_visit_date']) ?>">
                        <?php else: ?>
                            <span style="font-size:11px;color:#adb5bd;font-style:italic;">
                                <i class="fas fa-clock" style="font-size:10px;"></i> Awaiting PC
                            </span>
                            <input type="hidden" id="visit-<?= $eval_id ?>" value="">
                        <?php endif; ?>
                    </td>

                    <!-- Report Submission Date — from system (evaluation_date) -->
                    <td><?= $row['evaluation_date'] ? date('d-M-Y', strtotime($row['evaluation_date'])) : '<span class="text-muted">—</span>' ?></td>

                   <!-- IAC / IRPC -->
<td>
    <input type="text"
        class="qa-input <?= $row['iac_irpc'] ? 'saved' : '' ?>"
        id="iac-<?= $eval_id ?>"
        value="<?= htmlspecialchars($row['iac_irpc'] ?? '') ?>"
        placeholder="e.g. IAC No. 17(1/2024)"
        oninput="markUnsaved(<?= $eval_id ?>)">
    <input type="date"
        class="qa-input mt-1 <?= !empty($row['iac_date']) ? 'saved' : '' ?>"
        id="iac-date-<?= $eval_id ?>"
        value="<?= htmlspecialchars($row['iac_date'] ?? '') ?>"
        onchange="markUnsaved(<?= $eval_id ?>)">
    <button onclick="openUploadModal(<?= $eval_id ?>, 'iac', 'IAC/IRPC')"
        style="margin-top:4px;font-size:10px;padding:3px 8px;border-radius:6px;
               border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;cursor:pointer;width:100%">
        <i class="fas fa-paperclip me-1"></i>Proposal
    </button>
</td>

<!-- UAC / URPC -->
<td>
    <input type="text"
        class="qa-input <?= $row['uac_urpc'] ? 'saved' : '' ?>"
        id="uac-<?= $eval_id ?>"
        value="<?= htmlspecialchars($row['uac_urpc'] ?? '') ?>"
        placeholder="e.g. UAC Meeting No. 211(2/2024)"
        oninput="markUnsaved(<?= $eval_id ?>)">
    <input type="date"
        class="qa-input mt-1 <?= !empty($row['uac_date']) ? 'saved' : '' ?>"
        id="uac-date-<?= $eval_id ?>"
        value="<?= htmlspecialchars($row['uac_date'] ?? '') ?>"
        onchange="markUnsaved(<?= $eval_id ?>)">
    <button onclick="openUploadModal(<?= $eval_id ?>, 'uac', 'UAC/URPC')"
        style="margin-top:4px;font-size:10px;padding:3px 8px;border-radius:6px;
               border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;cursor:pointer;width:100%">
        <i class="fas fa-paperclip me-1"></i>Proposal
    </button>
</td>

<!-- Senate -->
<td>
    <input type="text"
        class="qa-input <?= $row['senate'] ? 'saved' : '' ?>"
        id="senate-<?= $eval_id ?>"
        value="<?= htmlspecialchars($row['senate'] ?? '') ?>"
        placeholder="e.g. Senate No. 132(2/2024)"
        oninput="markUnsaved(<?= $eval_id ?>)">
    <input type="date"
        class="qa-input mt-1 <?= !empty($row['senate_date']) ? 'saved' : '' ?>"
        id="senate-date-<?= $eval_id ?>"
        value="<?= htmlspecialchars($row['senate_date'] ?? '') ?>"
        onchange="markUnsaved(<?= $eval_id ?>)">
    <button onclick="openUploadModal(<?= $eval_id ?>, 'senate', 'Senate')"
        style="margin-top:4px;font-size:10px;padding:3px 8px;border-radius:6px;
               border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;cursor:pointer;width:100%">
        <i class="fas fa-paperclip me-1"></i>Proposal
    </button>
</td>
                    <!-- Save button -->
                    <td class="text-center">
                        <button
                            class="save-row-btn <?= $has_qa ? 'is-saved' : '' ?>"
                            id="save-btn-<?= $eval_id ?>"
                            onclick="saveRow(<?= $eval_id ?>)">
                            <?= $has_qa ? '✓ Saved' : 'Save' ?>
                        </button>
                        <div id="save-msg-<?= $eval_id ?>" style="font-size:10px;color:#16a34a;margin-top:3px"></div>
                    </td>

                    <!-- Actions to be Updated -->
                  <td>
    <a href="#" class="actions-link"
        onclick="openModal(<?= $eval_id ?>, '<?= addslashes(htmlspecialchars($row['panel_name'])) ?>'); return false;">
        <i class="fas fa-external-link-alt me-1" style="font-size:10px"></i>
        <?= htmlspecialchars($row['panel_name']) ?>
    </a>
    <a href="export_evaluation_pdf.php?id=<?= $eval_id ?>" target="_blank"
       style="display:inline-flex;align-items:center;gap:4px;margin-top:4px;
              font-size:10px;color:#dc2626;text-decoration:none;
              padding:3px 8px;border:1px solid #fecaca;border-radius:6px;
              background:#fef2f2">
        <i class="fas fa-file-pdf"></i> Export PDF
    </a>
</td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="11" class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                        No evaluations found.
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: PC Evaluation view (read-only for QA) -->
<div class="modal fade modal-xl" id="evalModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px; overflow:hidden">
            <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd, #002b6b); color:white; border:none">
                <h5 class="modal-title" id="modalTitle">Actions to be Updated</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <iframe id="evalFrame" src=""></iframe>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    document.getElementById("wrapper").classList.toggle("collapsed");
}

function markUnsaved(evalId) {
    const btn = document.getElementById('save-btn-' + evalId);
    btn.classList.remove('is-saved');
    btn.textContent = 'Save';

    // Highlight changed inputs yellow (visit is PC-owned, skip it)
    ['iac', 'uac', 'senate'].forEach(prefix => {
        const el = document.getElementById(prefix + '-' + evalId);
        if (el) { el.classList.remove('saved'); el.classList.add('unsaved'); }
    });
}

function saveRow(evalId) {
    const btn   = document.getElementById('save-btn-' + evalId);
    const msg   = document.getElementById('save-msg-' + evalId);

    const visit  = document.getElementById('visit-'  + evalId)?.value ?? '';
    const iac    = document.getElementById('iac-'    + evalId)?.value ?? '';
    const uac    = document.getElementById('uac-'    + evalId)?.value ?? '';
    const senate = document.getElementById('senate-' + evalId)?.value ?? '';
    const iacDate    = document.getElementById('iac-date-' + evalId)?.value ?? '';
const uacDate    = document.getElementById('uac-date-' + evalId)?.value ?? '';
const senateDate = document.getElementById('senate-date-' + evalId)?.value ?? '';

    btn.textContent = 'Saving…';
    btn.disabled = true;

    fetch('save_QAstatus.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'evaluation_id='     + evalId
            + '&latest_visit_date=' + encodeURIComponent(visit)
            + '&iac_irpc='          + encodeURIComponent(iac)
            + '&uac_urpc='          + encodeURIComponent(uac)
            + '&senate='            + encodeURIComponent(senate)
            + '&iac_date='     + encodeURIComponent(iacDate)
+ '&uac_date='     + encodeURIComponent(uacDate)
+ '&senate_date='  + encodeURIComponent(senateDate)
    })
    .then(r => r.text())
    .then(res => {
        btn.disabled = false;
        if (res === 'ok') {
            btn.classList.add('is-saved');
            btn.textContent = '✓ Saved';

            ['iac', 'uac', 'senate'].forEach(prefix => {
                const el = document.getElementById(prefix + '-' + evalId);
                if (el) { el.classList.remove('unsaved'); el.classList.add('saved'); }
            });

            msg.textContent = 'Saved!';
            setTimeout(() => msg.textContent = '', 2000);
        } else {
            btn.textContent = 'Error';
            btn.classList.add('btn-danger');
        }
    });
}

function openModal(evalId, label) {
    document.getElementById('modalTitle').textContent = label;
    document.getElementById('evalFrame').src = 'view_evaluation.php?id=' + evalId + '&qa=1';
    new bootstrap.Modal(document.getElementById('evalModal')).show();
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