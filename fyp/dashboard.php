<?php
session_start();
require_once 'db.php'; 

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// Fetch approved count for the statistic card
$approved_count = 0;
$count_query = "SELECT COUNT(*) as total FROM panel_members WHERE TRIM(LOWER(status)) = 'approved'";
$count_result = $conn->query($count_query);
if ($count_result) {
    $row = $count_result->fetch_assoc();
    $approved_count = $row['total'];
}

// Fetch the 5 most recent activities
$activities = $conn->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 5");

// Helper function for relative time
function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return round($diff / 60) . ' mins ago';
    if ($diff < 86400) return round($diff / 3600) . ' hours ago';
    return date('d M', $time);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | EAP System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=1.1">
    
    <style>
        body { 
            background-color: #f0f2f5; 
            font-family: 'Poppins', sans-serif; 
        }

        /* Gradient Banner Header */
        .dashboard-banner {
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.15);
            position: relative;
            overflow: hidden;
        }

        .dashboard-banner::after {
            content: "\f201";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            right: 30px;
            bottom: -20px;
            font-size: 150px;
            opacity: 0.1;
        }

        /* Stats Card Styling */
        .stat-card {
            border: none;
            border-radius: 15px;
            background: #fff;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            height: 100%;
        }

        .stat-card:hover { transform: translateY(-5px); }

        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        /* Activity Feed Styling */
        .activity-card {
            border: none;
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .activity-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f1f1;
            transition: background 0.2s;
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-item:hover { background-color: #fcfdfe; }

        .activity-icon {
            width: 35px;
            height: 35px;
            background: #eef2ff;
            color: #4338ca;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
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
    <a href="dashboard.php" class="active"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a>
    <a href="PCEvaluation.php"><i class="fas fa-list-check"></i> <span>Evaluation</span></a>
    <a href="panels.php"><i class="fas fa-users me-2"></i> <span>Manage Panels</span></a>
    <a href="newpanel.php"><i class="fas fa-user-plus me-2"></i> <span>Register New</span></a>

    <hr>
    <a href="logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a>
  </div>

  <div class="content p-4">
    
    <div class="dashboard-banner">
        <h5 class="opacity-75 mb-1 text-uppercase small" style="letter-spacing: 1px;">Admin Overview</h5>
        <h2 class="fw-bold mb-0">Hello, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Administrator'); ?>!</h2>
        <p class="mb-0 mt-2 opacity-75">Welcome to the External Academic Panel Management System.</p>
    </div>

    <div class="row g-4 mb-5">
      <div class="col-md-4">
        <div class="stat-card">
            <div class="icon-box bg-primary-subtle text-primary"><i class="fas fa-user-check"></i></div>
            <h6 class="text-muted mb-1 small text-uppercase fw-bold">Approved Panels</h6>
            <h2 class="fw-bold mb-0"><?= $approved_count; ?></h2>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
            <div class="icon-box bg-success-subtle text-success"><i class="fas fa-graduation-cap"></i></div>
            <h6 class="text-muted mb-1 small text-uppercase fw-bold">Programmes</h6>
            <h2 class="fw-bold mb-0">12</h2>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
            <div class="icon-box bg-danger-subtle text-danger"><i class="fas fa-file-alt"></i></div>
            <h6 class="text-muted mb-1 small text-uppercase fw-bold">Pending Reports</h6>
            <h2 class="fw-bold mb-0">3</h2>
        </div>
      </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="activity-card">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-stream text-primary me-2"></i>Recent Activity</h5>
                    <a href="#" class="btn btn-sm btn-light rounded-pill px-3">View All</a>
                </div>
                <div class="activity-list">
                    <?php if ($activities && $activities->num_rows > 0): ?>
                        <?php while($act = $activities->fetch_assoc()): ?>
                            <div class="activity-item d-flex align-items-center">
                                <div class="activity-icon me-3"><i class="fas fa-bell"></i></div>
                                <div class="flex-grow-1">
                                    <div class="small text-dark fw-medium"><?= htmlspecialchars($act['description']) ?></div>
                                    <?php if($act['badge_text']): ?>
                                        <span class="badge rounded-pill <?= htmlspecialchars($act['badge_class']) ?> mt-1" style="font-size: 0.65rem;">
                                            <?= htmlspecialchars($act['badge_text']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small"><?= timeAgo($act['created_at']) ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                            <p>No recent activities found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 rounded-4 shadow-sm p-4 h-100 bg-white">
                <h6 class="fw-bold mb-3">Quick Navigation</h6>
                <div class="d-grid gap-2">
                    <a href="panels.php" class="btn btn-outline-primary text-start rounded-3 p-3">
                        <i class="fas fa-users me-2"></i> Manage All Panels
                    </a>
                    <a href="newpanel.php" class="btn btn-outline-dark text-start rounded-3 p-3">
                        <i class="fas fa-user-plus me-2"></i> Register New Panel
                    </a>

                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-5 mb-4 text-muted small">
        &copy; <?php echo date("Y"); ?> External Academic Panel System. Admin Portal.
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebar() { 
        document.getElementById("wrapper").classList.toggle("collapsed"); 
    }
</script>
</body>
</html>