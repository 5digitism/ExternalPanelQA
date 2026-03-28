<?php
session_start();
require_once 'db.php';

// Security Check
if (!isset($_SESSION['panel_id'])) {
    header("Location: loginpage.html");
    exit();
}

$panel_id = $_SESSION['panel_id'];

// Fetch Panel Details
$query = "SELECT * FROM panel_members WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $panel_id);
$stmt->execute();
$result = $stmt->get_result();
$panel = $result->fetch_assoc();

if (!$panel) {
    session_destroy();
    header("Location: loginpage.html");
    exit();
}

// Handle Form Submission
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_evaluation'])) {

    $evaluation_title = $_POST['evaluation_title'];
    $evaluation_date = $_POST['evaluation_date'];
    $overall_comments = $_POST['overall_comments'];

    $sql = "INSERT INTO evaluations (panel_id, title, evaluation_date, overall_comments, status) 
            VALUES (?, ?, ?, ?, 'submitted')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $panel_id, $evaluation_title, $evaluation_date, $overall_comments);

    if ($stmt->execute()) {
        $evaluation_id = $conn->insert_id;

        $criteria_names = $_POST['criteria_name'] ?? [];
        $criteria_comments = $_POST['criteria_comment'] ?? [];

        if (count($criteria_names) > 0) {

            $sql_criteria = "INSERT INTO evaluation_criteria (evaluation_id, criteria_name, comments) VALUES (?, ?, ?)";
            $stmt_criteria = $conn->prepare($sql_criteria);

            for ($i = 0; $i < count($criteria_names); $i++) {

                $name = $criteria_names[$i];
                $comment = $criteria_comments[$i];

                if (empty($name) || empty($comment)) continue;

                $stmt_criteria->bind_param("iss", $evaluation_id, $name, $comment);
                $stmt_criteria->execute();
            }

            $success_msg = "Evaluation submitted successfully!";
            logActivity($conn, "Evaluation submitted by " . $panel['panel_name'], "Evaluation", "bg-info");

        } else {
            $error_msg = "Please add at least one criterion.";
        }

    } else {
        $error_msg = "Error submitting evaluation: " . $stmt->error;
    }
}

// Fetch previous evaluations
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
            color: white;
            padding: 60px 0;
            border-radius: 0 0 50px 50px;
            margin-bottom: -50px;
        }
        
      .info-card {
    background: white;
    border: none;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    transition: transform 0.3s ease; 
}

      .info-card:hover {
    transform: translateY(-5px);
    
}

        .section-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--primary-blue);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 20px;
        }
        .data-label { font-size: 0.75rem; color: #adb5bd; font-weight: 600; text-transform: uppercase; margin-bottom: 5px; }
        .data-value { font-size: 1.1rem; color: #212529; font-weight: 600; margin-bottom: 20px; }
        .sidebar-link {
            padding: 12px 20px;
            border-radius: 12px;
            color: #495057;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            margin-bottom: 10px;
        }

        .sidebar-link:hover, .sidebar-link.active {
            background-color: var(--primary-blue);
            color: white;
        }

        .sidebar-link i { width: 30px; }

 .criteria-row {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            position: relative;
        }

        .remove-criteria {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            color: #dc3545;
            font-size: 1.2rem;
        }
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
                    <a href="panel_dashboard.php" class="sidebar-link">
                        <i class="fas fa-th-large"></i> Overview
                    </a>
                    <a href="panel_evaluation.php" class="sidebar-link active">
                        <i class="fas fa-clipboard-check"></i> Evaluation
                    </a>
                    <a href="panelchangepassword.php" class="sidebar-link">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                    <hr class="my-4 opacity-25">
                    <a href="logout.php" class="sidebar-link text-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
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
                <h4 class="fw-bold mb-1"></i>Submit Evaluation</h4>
                <p class="text-muted small mb-4">Evaluate the programme based on various criteria</p>

<form method="POST">

<div class="mb-3">
<label>Title</label>
<input type="text" name="evaluation_title" class="form-control" required>
</div>

<div class="mb-3">
<label>Date</label>
<input type="date" name="evaluation_date" class="form-control" required>
</div>

<h5>Criteria</h5>

<div id="criteriaContainer">
<div class="criteria-row">
    <div class="mb-3">
        <label>Criteria Name</label>
        <input type="text" name="criteria_name[]" class="form-control" required>
    </div>
    <div>
        <label>Comments</label>
        <textarea name="criteria_comment[]" class="form-control" required></textarea>
    </div>
</div>
</div>

<button type="button" class="btn btn-primary my-3" onclick="addCriteria()">+ Add Criterion</button>

<div class="mb-4">
                        <label class="form-label fw-semibold">Overall Comments & Recommendations</label>
                        <textarea name="overall_comments" class="form-control" rows="4" 
                                  placeholder="Provide your overall assessment and recommendations for improvement..." required></textarea>
                    </div>

<button type="submit" name="submit_evaluation" class="btn btn-success">Submit</button>

</form>
</div>

<div class="info-card">
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
                                    <span class="badge bg-success rounded-pill">
                                        <?= ucfirst($eval['status']) ?>
                                    </span>
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
function addCriteria() {
    const html = `
    <div class="criteria-row">
        <span class="remove-criteria" onclick="removeCriteria(this)">✖</span>
        <div class="mb-3">
            <label>Criteria Name</label>
            <input type="text" name="criteria_name[]" class="form-control" required>
        </div>
        <div>
            <label>Comments</label>
            <textarea name="criteria_comment[]" class="form-control" required></textarea>
        </div>
    </div>`;
    document.getElementById('criteriaContainer').insertAdjacentHTML('beforeend', html);
}

function removeCriteria(el) {
    el.closest('.criteria-row').remove();
}
</script>

</body>
</html>