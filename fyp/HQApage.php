<?php
session_start();
require_once 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

/** * Fetch counts for the statistics cards */
$approved_count = 0;
$count_query = "SELECT COUNT(*) as total FROM panel_members WHERE TRIM(LOWER(status)) = 'approved'";
$count_result = $conn->query($count_query);
if ($count_result) {
    $row = $count_result->fetch_assoc();
    $approved_count = $row['total'];
}

$pending_count = 0;
$pending_query = "SELECT COUNT(*) as total FROM panel_members WHERE status = 'Pending'";
$pending_result = $conn->query($pending_query);
if ($pending_result) {
    $row = $pending_result->fetch_assoc();
    $pending_count = $row['total'];
}

// Fetch recent activities
$activities = $conn->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 5");

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
    <title>HQA Dashboard | EAP System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=1.1">
    
    <style>
        body { 
            background-color: #f0f2f5; 
            font-family: 'Poppins', sans-serif; 
        }

        /* Gradient Header matching panel_dashboard */
        .dashboard-banner {
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.15);
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .activity-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .activity-item:last-child { border-bottom: none; }

        .btn-action-card {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 15px;
            display: block;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }
        .btn-action-card:hover {
            border-color: #0d6efd;
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .pulse-badge {
            animation: pulse-red 2s infinite;
        }
        @keyframes pulse-red {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
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
        <a href="HQApage.php" class="active"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a>
        <a href="HQApanel.php"><i class="fas fa-users me-2"></i> <span>Manage Panels</span></a>
        <hr>
        <a href="logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a>
    </div>

    <div class="content p-4">
        <div class="dashboard-banner d-flex justify-content-between align-items-center">
            <div>
                <h5 class="opacity-75 mb-1 text-uppercase small" style="letter-spacing: 1px;">QA Portal</h5>
                <h2 class="fw-bold mb-0">Welcome Back, <?= htmlspecialchars($_SESSION['name']); ?>!</h2>
            </div>
            <i class="fas fa-user-shield fa-3x opacity-25"></i>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card p-4 h-100">
                            <div class="d-flex align-items-center mb-3">
                                <div class="stat-icon me-3"><i class="fas fa-check-double"></i></div>
                                <h6 class="mb-0 text-muted">Approved Members</h6>
                            </div>
                            <h2 class="fw-bold"><?= $approved_count; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card p-4 h-100 border-start border-warning border-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="stat-icon bg-warning-subtle text-warning me-3"><i class="fas fa-hourglass-half"></i></div>
                                <h6 class="mb-0 text-muted">Pending Review</h6>
                            </div>
                            <h2 class="fw-bold"><?= $pending_count; ?></h2>
                        </div>
                    </div>
                </div>

                <h5 class="fw-bold mb-3">Quick Management</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="approvalpage.php" class="btn-action-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold mb-1">Approval Queue</h6>
                                    <p class="small text-muted mb-0">Review and verify panel applications.</p>
                                </div>
                                <?php if($pending_count > 0): ?>
                                    <span class="badge bg-danger rounded-pill pulse-badge"><?= $pending_count ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="HQApanel.php" class="btn-action-card">
                            <h6 class="fw-bold mb-1">Panel Directory</h6>
                            <p class="small text-muted mb-0">Search and manage existing members.</p>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-history me-2 text-primary"></i>Recent Activity</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($activities && $activities->num_rows > 0): ?>
                            <?php while($act = $activities->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div style="max-width: 70%;">
                                        <div class="small text-dark"><?= htmlspecialchars($act['description']) ?></div>
                                        <?php if($act['badge_text']): ?>
                                            <span class="badge rounded-pill <?= htmlspecialchars($act['badge_class']) ?> mt-1" style="font-size: 0.65rem;">
                                                <?= htmlspecialchars($act['badge_text']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.7rem;"><?= timeAgo($act['created_at']) ?></small>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted small">No recent logs found.</div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-light border-0 text-center py-3">
                        <a href="#" class="small text-decoration-none">View All Logs</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5 mb-4 text-muted small">
            &copy; <?php echo date("Y"); ?> External Academic Panel System. Quality Assurance Portal.
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