<?php
session_start();
require_once 'db.php';

// Check login
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// Handle PC comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pc_comment'])) {
    
    $evaluation_id = $_POST['evaluation_id'];
    $pc_comment = $_POST['pc_comment'];

    $stmt = $conn->prepare("UPDATE evaluations SET pc_comment = ? WHERE id = ?");
    $stmt->bind_param("si", $pc_comment, $evaluation_id);
    $stmt->execute();
}

// Fetch all evaluations
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

/* Banner */
.page-banner {
    background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
    color: white;
    padding: 30px 40px;
    border-radius: 20px;
    margin-bottom: 30px;
}

/* Evaluation Card */
.card-eval {
    background: #fff;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
}

.card-eval:hover {
    transform: translateY(-5px);
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

    <a href="dashboard.php">
      <i class="fas fa-chart-line me-2"></i> <span>Dashboard</span>
    </a>

    <a href="PCEvaluation.php" class="active">
      <i class="fas fa-list-check me-2"></i> <span>Evaluation</span>
    </a>

    <a href="panels.php">
      <i class="fas fa-users me-2"></i> <span>Panels</span>
    </a>

    <a href="newpanel.php">
      <i class="fas fa-user-plus me-2"></i> <span>Register New</span>
    </a>

    <hr>

    <a href="logout.php" class="text-warning">
      <i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span>
    </a>
  </div>

  <!-- Content -->
  <div class="content p-4">

  

    <!-- Banner -->
    <div class="page-banner d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1">Programme Coordinator Evaluation</h2>
            <p class="mb-0 opacity-75">Review panel evaluations and add comments</p>
        </div>
    </div>

    <!-- Evaluations -->
    <?php if ($evaluations && $evaluations->num_rows > 0): ?>

        <?php while($eval = $evaluations->fetch_assoc()): ?>

        <div class="card-eval">

            <h5 class="fw-bold"><?= htmlspecialchars($eval['title']) ?></h5>

            <p class="text-muted mb-1">
                <i class="fas fa-user me-1"></i>
                <?= htmlspecialchars($eval['panel_name']) ?>
            </p>

            <p class="text-muted">
                <i class="fas fa-calendar me-1"></i>
                <?= $eval['evaluation_date'] ?>
            </p>

            <hr>
            <p><strong>Panel Overall Comments:</strong></p>
            <p><?= nl2br(htmlspecialchars($eval['overall_comments'])) ?></p>

            <p><strong>Panel Overall Comments:</strong></p>
            <p><?= nl2br(htmlspecialchars($eval['overall_comments'])) ?></p>


            <!-- Criteria -->
            <?php
            $criteria = $conn->query("SELECT * FROM evaluation_criteria WHERE evaluation_id = ".$eval['id']);
            if ($criteria && $criteria->num_rows > 0):
            ?>
                <p><strong>Criteria:</strong></p>
                <ul>
                    <?php while($c = $criteria->fetch_assoc()): ?>
                        <li>
                            <strong><?= htmlspecialchars($c['criteria_name']) ?>:</strong>
                            <?= htmlspecialchars($c['comments']) ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>

            <hr>

            <!-- PC Comment -->
            <form method="POST">
                <input type="hidden" name="evaluation_id" value="<?= $eval['id'] ?>">

                <div class="mb-2">
                    <label class="fw-semibold">Programme Coordinator Comment</label>
                    <textarea name="pc_comment" class="form-control" rows="3"
                        placeholder="Write your feedback..."><?= htmlspecialchars($eval['pc_comment']) ?></textarea>
                </div>

                <button type="submit" name="submit_pc_comment" class="btn btn-primary btn-sm">
                    Save Comment
                </button>
            </form>

        </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="text-center text-muted mt-5">
            <i class="fas fa-inbox fa-3x mb-3"></i>
            <p>No evaluations submitted yet.</p>
        </div>

    <?php endif; ?>

  </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById("wrapper").classList.toggle("collapsed");
}
</script>

</body>
</html>