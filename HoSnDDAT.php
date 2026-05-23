<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: loginpage.html");
    exit();
}

$allowed_roles = ['HoS', 'DDAT', 'Head QA', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: loginpage.html");
    exit();
}

// Get this user's programme (null = see all)
$stmt = $conn->prepare("SELECT programme, name FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$user_row = $stmt->get_result()->fetch_assoc();

$user_programme = $user_row['programme'] ?? null;
$user_name      = $user_row['name'] ?? $_SESSION['name'];

// Filter by panel name search
$filter_panel = trim($_GET['panel'] ?? '');

// Build WHERE clause
$where_parts = [];
$params      = [];
$types       = '';

if ($user_programme) {
    $where_parts[] = "p.programme = ?";
    $params[]      = $user_programme;
    $types        .= 's';
}

if ($filter_panel) {
    $where_parts[] = "p.panel_name LIKE ?";
    $params[]      = '%' . $filter_panel . '%';
    $types        .= 's';
}

$where = $where_parts ? "WHERE " . implode(" AND ", $where_parts) : "";

$sql = "
    SELECT
        e.id            AS evaluation_id,
        e.title,
        e.evaluation_date,
        e.created_at,
        p.id            AS panel_id,
        p.panel_name,
        p.programme,
        p.level,
        q.latest_visit_date,
        q.iac_irpc,
        q.uac_urpc,
        q.senate
    FROM evaluations e
    JOIN panel_members p ON e.panel_id = p.id
    LEFT JOIN qa_submission_status q ON q.evaluation_id = e.id
    $where
    ORDER BY p.programme ASC, e.evaluation_date DESC
";

if ($params) {
    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param($types, ...$params);
    $stmt2->execute();
    $rows = $stmt2->get_result();
} else {
    $rows = $conn->query($sql);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Submission | EAP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        .prog-cell {
            font-weight: 700; font-size: 14px; color: var(--primary-blue);
            text-align: center; background: #f8faff;
            border-right: 2px solid #e2e8f0;
        }

        /* Read-only value display */
        .field-val {
            font-size: 12px; color: #374151;
            padding: 6px 8px; border-radius: 6px;
            background: #f9fafb; border: 1px solid #e5e7eb;
            min-height: 32px; display: flex; align-items: center;
        }
        .field-val.empty { color: #9ca3af; font-style: italic; }

        /* Status pills */
        .filled-pill {
            font-size: 11px; padding: 3px 10px; border-radius: 999px;
            background: #d1fae5; color: #065f46; font-weight: 600;
        }
        .empty-pill {
            font-size: 11px; padding: 3px 10px; border-radius: 999px;
            background: #fef3c7; color: #92400e; font-weight: 600;
        }

        .type-badge {
            font-size: 10px; padding: 2px 8px; border-radius: 999px; font-weight: 600;
        }
        .type-ee  { background: #dbeafe; color: #1d4ed8; }
        .type-iap { background: #dcfce7; color: #166534; }

        /* Filter bar */
        .filter-bar {
            display: flex; gap: 10px; align-items: center;
            padding: 16px 20px; border-bottom: 1px solid #f0f0f0;
        }
        .filter-input {
            border: 1px solid #e2e8f0; border-radius: 8px;
            padding: 7px 12px; font-size: 13px; width: 260px;
            font-family: 'Poppins', sans-serif;
        }
        .filter-input:focus { outline: none; border-color: var(--primary-blue); }

        /* View modal */
        .modal-xl .modal-body { padding: 0; }
        .modal-body iframe { width: 100%; height: 78vh; border: none; }

        /* Read-only banner */
        .readonly-banner {
            background: #eff6ff; border: 1px solid #bfdbfe;
            border-radius: 10px; padding: 10px 16px;
            font-size: 12px; color: #1d4ed8;
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 20px;
        }
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
        <a href="view_status.php" class="active">
            <i class="fas fa-table me-2"></i><span>Status Submission</span>
        </a>
        <hr>
        <a href="logout.php" class="text-warning">
            <i class="fas fa-sign-out-alt me-2"></i><span>Logout</span>
        </a>
    </div>

    <div class="content p-4">

        <!-- Banner -->
        <div class="page-banner d-flex justify-content-between align-items-center">
            <div>
                <h5 class="opacity-75 mb-1 text-uppercase small" style="letter-spacing:1px;">
                    <?= htmlspecialchars($_SESSION['role']) ?>
                </h5>
                <h2 class="fw-bold mb-1">Welcome, <?= htmlspecialchars($user_name) ?></h2>
                <p class="mb-0 opacity-75">
                    <?php if ($user_programme): ?>
                        Viewing status for: <strong><?= htmlspecialchars($user_programme) ?></strong>
                    <?php else: ?>
                        Viewing all programmes
                    <?php endif; ?>
                </p>
            </div>
            <i class="fas fa-table fa-3x opacity-10"></i>
        </div>

        <!-- Read-only notice -->
        <div class="readonly-banner">
            <i class="fas fa-eye"></i>
            You have <strong>view-only</strong> access to this page. 
            Contact QA to update submission status.
        </div>

        <div class="card-wrap">

            <!-- Filter bar -->
            <form method="GET" class="filter-bar">
                <i class="fas fa-search text-muted"></i>
                <input type="text" name="panel" class="filter-input"
                    placeholder="Filter by panel name…"
                    value="<?= htmlspecialchars($filter_panel) ?>">
                <button type="submit" class="btn btn-sm btn-primary px-3">Search</button>
                <?php if ($filter_panel): ?>
                    <a href="view_status.php" class="btn btn-sm btn-outline-secondary">Clear</a>
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
                        <th>Latest Visit Date</th>
                        <th>Report Submission Date</th>
                        <th>IAC / IRPC</th>
                        <th>UAC / URPC</th>
                        <th>Senate</th>
                        <th>Actions to be Updated</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($rows && $rows->num_rows > 0):
                    $no = 1;
                    while ($row = $rows->fetch_assoc()):
                        $eval_id = $row['evaluation_id'];
                        $actions_label = htmlspecialchars($row['panel_name'])
                            . ($row['senate'] ? ' _ ' . htmlspecialchars($row['senate']) : '');
                ?>
                <tr>
                    <td class="text-center text-muted fw-semibold"><?= $no++ ?></td>

                    <td class="prog-cell"><?= htmlspecialchars($row['programme']) ?></td>

                    <td>
                        <?= htmlspecialchars($row['panel_name']) ?>
                        <span class="type-badge <?= strtolower($row['level']) === 'ee' ? 'type-ee' : 'type-iap' ?> ms-1">
                            <?= htmlspecialchars($row['level']) ?>
                        </span>
                    </td>

                    <td><?= htmlspecialchars($row['title'] ?? '—') ?></td>

                    <!-- Latest Visit Date — read only -->
                    <td>
                        <?php if ($row['latest_visit_date']): ?>
                            <span class="filled-pill">
                                <?= date('d-M-Y', strtotime($row['latest_visit_date'])) ?>
                            </span>
                        <?php else: ?>
                            <span class="empty-pill">Pending</span>
                        <?php endif; ?>
                    </td>

                    <!-- Report Submission Date — from system -->
                    <td>
                        <?= $row['evaluation_date']
                            ? date('d-M-Y', strtotime($row['evaluation_date']))
                            : '<span class="text-muted">—</span>' ?>
                    </td>

                    <!-- IAC / IRPC — read only -->
                    <td>
                        <?php if (!empty($row['iac_irpc'])): ?>
                            <span class="filled-pill"><?= htmlspecialchars($row['iac_irpc']) ?></span>
                        <?php else: ?>
                            <span class="empty-pill">Pending</span>
                        <?php endif; ?>
                    </td>

                    <!-- UAC / URPC — read only -->
                    <td>
                        <?php if (!empty($row['uac_urpc'])): ?>
                            <span class="filled-pill"><?= htmlspecialchars($row['uac_urpc']) ?></span>
                        <?php else: ?>
                            <span class="empty-pill">Pending</span>
                        <?php endif; ?>
                    </td>

                    <!-- Senate — read only -->
                    <td>
                        <?php if (!empty($row['senate'])): ?>
                            <span class="filled-pill"><?= htmlspecialchars($row['senate']) ?></span>
                        <?php else: ?>
                            <span class="empty-pill">Pending</span>
                        <?php endif; ?>
                    </td>

                    <!-- Actions to be Updated -->
                    <td>
                        <?php if ($row['senate']): ?>
                            <a href="#" class="text-primary" style="font-size:11px"
                                onclick="openModal(<?= $eval_id ?>, '<?= addslashes($actions_label) ?>'); return false;">
                                <i class="fas fa-eye me-1" style="font-size:10px"></i>
                                <?= $actions_label ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted" style="font-size:11px">— pending Senate</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center text-muted py-5">
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

<!-- Modal -->
<div class="modal fade modal-xl" id="evalModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px; overflow:hidden">
            <div class="modal-header"
                style="background: linear-gradient(135deg, #0d6efd, #002b6b); color:white; border:none">
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

function openModal(evalId, label) {
    document.getElementById('modalTitle').textContent = label;
    document.getElementById('evalFrame').src = 'view_evaluation.php?id=' + evalId + '&qa=1';
    new bootstrap.Modal(document.getElementById('evalModal')).show();
}
</script>

</body>
</html>