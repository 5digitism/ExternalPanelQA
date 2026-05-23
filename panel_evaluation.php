<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['panel_id'])) {
    header("Location: loginpage.html");
    exit();
}

$panel_id = $_SESSION['panel_id'];

$query = "SELECT * FROM panel_members WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $panel_id);
$stmt->execute();
$panel = $stmt->get_result()->fetch_assoc();

if (!$panel) {
    session_destroy();
    header("Location: loginpage.html");
    exit();
}

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_evaluation'])) {

    $evaluation_title   = trim($_POST['evaluation_title']);
    $evaluation_date    = $_POST['evaluation_date'];
    $overall_comments   = trim($_POST['overall_comments']);
    $criteria_names     = $_POST['criteria_name'] ?? [];
    // issues is a 2D array: issues[criteria_index][issue_index]
    $all_issues         = $_POST['issues'] ?? [];

    if (empty($criteria_names)) {
        $error_msg = "Please add at least one criterion.";
    } else {

        $sql = "INSERT INTO evaluations (panel_id, title, evaluation_date, overall_comments, status)
                VALUES (?, ?, ?, ?, 'submitted')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $panel_id, $evaluation_title, $evaluation_date, $overall_comments);

        if ($stmt->execute()) {
            $evaluation_id = $conn->insert_id;

            $sql_crit = "INSERT INTO evaluation_criteria (evaluation_id, criteria_name) VALUES (?, ?)";
            $stmt_crit = $conn->prepare($sql_crit);

            $sql_issue = "INSERT INTO criteria_issues (criteria_id, issue_text) VALUES (?, ?)";
            $stmt_issue = $conn->prepare($sql_issue);

            foreach ($criteria_names as $i => $criteria_name) {
                $criteria_name = trim($criteria_name);
                if (empty($criteria_name)) continue;

                $stmt_crit->bind_param("is", $evaluation_id, $criteria_name);
                $stmt_crit->execute();
                $criteria_id = $conn->insert_id;

                $issues = $all_issues[$i] ?? [];
                foreach ($issues as $issue_text) {
                    $issue_text = trim($issue_text);
                    if (empty($issue_text)) continue;
                    $stmt_issue->bind_param("is", $criteria_id, $issue_text);
                    $stmt_issue->execute();
                }
            }

            $success_msg = "Evaluation submitted successfully!";
            logActivity($conn, "Evaluation submitted by " . $panel['panel_name'], "Evaluation", "bg-info");

        } else {
            $error_msg = "Error submitting evaluation: " . $stmt->error;
        }
    }
}

$prev_evaluations = $conn->query("
    SELECT * FROM evaluations
    WHERE panel_id = $panel_id
    ORDER BY created_at DESC
");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programme Evaluation | EAP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary-blue: #0d6efd; --dark-navy: #002b6b; --bg-light: #f4f7f6; }
        body { background-color: var(--bg-light); font-family: 'Poppins', sans-serif; }
        .dashboard-banner {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-navy) 100%);
            color: white; padding: 60px 0; border-radius: 0 0 50px 50px; margin-bottom: -50px;
        }
        .info-card {
            background: white; border: none; border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: transform 0.3s ease;
        }
        .info-card:hover { transform: translateY(-5px); }
        .section-title {
            font-size: 0.85rem; font-weight: 700; color: var(--primary-blue);
            text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 20px;
        }
        .sidebar-link {
            padding: 12px 20px; border-radius: 12px; color: #495057;
            text-decoration: none; display: flex; align-items: center;
            transition: all 0.3s; margin-bottom: 10px;
        }
        .sidebar-link:hover, .sidebar-link.active { background-color: var(--primary-blue); color: white; }
        .sidebar-link i { width: 30px; }

        /* Criteria block */
        .criteria-block {
            border: 1px solid #e9ecef; border-radius: 12px;
            overflow: hidden; margin-bottom: 1rem;
        }
        .criteria-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 16px; background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .criteria-pill {
            font-size: 11px; padding: 2px 10px; border-radius: 999px;
            background: #cfe2ff; color: #084298; font-weight: 600;
        }
        .criteria-body { padding: 16px; }

        /* Issues table */
        .issues-table { width: 100%; border-collapse: collapse; font-size: 13px; table-layout: fixed; }
        .issues-table thead th {
            background: #f8f9fa; padding: 8px 10px; font-size: 11px;
            font-weight: 600; color: #6c757d; border-bottom: 1px solid #dee2e6; text-align: left;
        }
        .issues-table thead th:nth-child(1) { width: 36px; }
        .issues-table thead th:nth-child(3) { width: 60px; }
        .issues-table tbody tr { border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        .issues-table tbody tr:last-child { border-bottom: none; }
        .issues-table tbody td { padding: 8px 10px; vertical-align: top; }
        .row-num { font-size: 12px; color: #adb5bd; text-align: center; padding-top: 10px !important; }
        .table-wrapper { border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="dashboard-banner">
    <div class="container text-center">
        <h2 class="fw-bold mb-1">Programme Evaluation</h2>
        <p class="opacity-75"><?= htmlspecialchars($panel['panel_name']) ?> | <?= htmlspecialchars($panel['programme']) ?></p>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">

        <div class="col-lg-3">
            <div class="info-card p-4">
                <div class="section-title">Navigation</div>
                <nav>
                    <a href="panel_dashboard.php" class="sidebar-link"><i class="fas fa-th-large"></i> Overview</a>
                    <a href="panel_evaluation.php" class="sidebar-link active"><i class="fas fa-clipboard-check"></i> Evaluation</a>
                    <a href="panelchangepassword.php" class="sidebar-link"><i class="fas fa-key"></i> Change Password</a>
                    <hr class="my-4 opacity-25">
                    <a href="logout.php" class="sidebar-link text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </div>

        <div class="col-lg-9">

            <?php if($success_msg): ?>
                <div class="alert alert-success"><?= $success_msg ?></div>
            <?php endif; ?>
            <?php if($error_msg): ?>
                <div class="alert alert-danger"><?= $error_msg ?></div>
            <?php endif; ?>

            <div class="info-card p-5 mb-4">
                <h4 class="fw-bold mb-1">Submit Evaluation</h4>
                <p class="text-muted small mb-4">Add each issue as a separate row under its criterion</p>

                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="evaluation_title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="evaluation_date" class="form-control" required>
                    </div>

                    <h5 class="mb-3">Criteria &amp; Issues</h5>
                    <div id="criteriaContainer"></div>

                    <button type="button" class="btn btn-outline-primary mb-4" onclick="addCriteria()">
                        + Add Criterion
                    </button>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Overall Comments &amp; Recommendations</label>
                        <textarea name="overall_comments" class="form-control" rows="4"
                            placeholder="Provide your overall assessment..." required></textarea>
                    </div>

                    <button type="submit" name="submit_evaluation" class="btn btn-success">Submit Evaluation</button>
                </form>
            </div>

            <!-- Previous Evaluations -->
            <div class="info-card p-5">
                <h5 class="fw-bold mb-4"><i class="fas fa-history me-2 text-primary"></i>My Previous Evaluations</h5>
                <?php if($prev_evaluations && $prev_evaluations->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while($eval = $prev_evaluations->fetch_assoc()): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($eval['title']) ?></h6>
                                        <p class="mb-1 small text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('F d, Y', strtotime($eval['evaluation_date'])) ?>
                                        </p>
                                        <span class="badge bg-success rounded-pill"><?= ucfirst($eval['status']) ?></span>
                                    </div>
                                    <a href="view_evaluation.php?id=<?= $eval['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-clipboard-list fa-3x mb-3 opacity-25"></i>
                        <p>You haven't submitted any evaluations yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
let critCount = 0;
let issueCounts = {};

function addCriteria() {
    critCount++;
    issueCounts[critCount] = 0;
    const div = document.createElement('div');
    div.className = 'criteria-block';
    div.id = 'crit-' + critCount;
    div.innerHTML = buildCriteriaHTML(critCount);
    document.getElementById('criteriaContainer').appendChild(div);
    // Add first issue row automatically
    addIssue(critCount);
    renumberCriteria();
}

function buildCriteriaHTML(id) {
    return `
    <div class="criteria-header">
        <span class="criteria-pill" id="pill-${id}">Criteria ${id}</span>
        <input type="text" name="criteria_name[${id - 1}]"
            class="form-control form-control-sm w-50"
            placeholder="Criteria Name " required>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCriteria('crit-${id}')">Remove</button>
    </div>
    <div class="criteria-body">
        <div class="table-wrapper">
            <table class="issues-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Issue / finding</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tbody-${id}"></tbody>
            </table>
        </div>
        <button type="button" class="btn btn-sm btn-outline-info" onclick="addIssue(${id})">+ Add Issue</button>
    </div>`;
}

function addIssue(critId) {
    issueCounts[critId] = (issueCounts[critId] || 0) + 1;
    const num = issueCounts[critId];
    const critIndex = getCritIndex(critId);
    const row = document.createElement('tr');
    row.id = 'issue-' + critId + '-' + num;
    row.innerHTML = `
        <td class="row-num">${num}</td>
        <td>
            <textarea name="issues[${critIndex}][]"
                class="form-control form-control-sm" rows="2"
                placeholder="Describe the issue…" required></textarea>
        </td>
        <td style="padding-top:8px">
            <button type="button" class="btn btn-sm btn-outline-danger"
                onclick="removeIssue('issue-${critId}-${num}', ${critId})">✕</button>
        </td>`;
    document.getElementById('tbody-' + critId).appendChild(row);
    renumberIssues(critId);
}

function removeIssue(rowId, critId) {
    const el = document.getElementById(rowId);
    if (el) el.remove();
    renumberIssues(critId);
}

function removeCriteria(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
    renumberCriteria();
}

function renumberIssues(critId) {
    const tbody = document.getElementById('tbody-' + critId);
    if (!tbody) return;
    tbody.querySelectorAll('tr').forEach((r, i) => {
        const cell = r.querySelector('.row-num');
        if (cell) cell.textContent = i + 1;
    });
}

function renumberCriteria() {
    document.querySelectorAll('.criteria-block').forEach((b, i) => {
        const pill = b.querySelector('.criteria-pill');
        if (pill) pill.textContent = 'Criteria ' + (i + 1);
    });
}

function getCritIndex(critId) {
    const blocks = document.querySelectorAll('.criteria-block');
    for (let i = 0; i < blocks.length; i++) {
        if (blocks[i].id === 'crit-' + critId) return i;
    }
    return 0;
}

// Start with one criterion by default
addCriteria();
</script>

</body>
</html>