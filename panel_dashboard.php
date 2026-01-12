<?php
session_start();
require_once 'db.php';

// 1. Session and Security Check
if (!isset($_SESSION['panel_id'])) {
    header("Location: login.html");
    exit();
}

$panel_id = $_SESSION['panel_id'];

// 2. Fetch Panel Details
$query = "SELECT * FROM panel_members WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $panel_id);
$stmt->execute();
$result = $stmt->get_result();
$panel = $result->fetch_assoc();

if (!$panel) {
    session_destroy();
    header("Location: login.html");
    exit();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Dashboard | EAP System</title>
    
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

        .info-card:hover { transform: translateY(-5px); }

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

        .contact-pill {
            background: #f0f7ff;
            border: 1px solid #cfe2ff;
            border-radius: 12px;
            padding: 15px;
            height: 100%;
        }
    </style>
</head>
<body>

<div class="dashboard-banner">
    <div class="container text-center">
        <h2 class="fw-bold mb-1">Welcome back, <?= htmlspecialchars($panel['panel_name']) ?>!</h2>
        <p class="opacity-75">Panel ID: EAP-P-<?= str_pad($panel['id'], 4, '0', STR_PAD_LEFT) ?> | Status: <span class="badge bg-success"><?= $panel['status'] ?></span></p>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        
        <div class="col-lg-3">
            <div class="info-card p-4">
                <div class="section-title">Navigation</div>
                <nav>
                    <a href="panel_dashboard.php" class="sidebar-link active">
                        <i class="fas fa-th-large"></i> Overview
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
            <div class="info-card p-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0">My Profile Information</h4>
                    <a href="<?= htmlspecialchars($panel['resume_path']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" target="_blank">
                        <i class="fas fa-file-pdf me-2"></i> View Uploaded CV
                    </a>
                </div>

                <div class="section-title">Contact & Personal Details</div>
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="contact-pill">
                            <p class="data-label"><i class="fas fa-envelope me-1"></i> Registered Email</p>
                            <p class="data-value mb-0 text-primary"><?= htmlspecialchars($panel['email']) ?></p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="contact-pill">
                            <p class="data-label"><i class="fas fa-phone me-1"></i> Registered Phone</p>
                            <p class="data-value mb-0"><?= htmlspecialchars($panel['phone']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="section-title mt-2">Academic Assignment</div>
                <div class="row">
                    <div class="col-md-6">
                        <p class="data-label">Academic Level</p>
                        <p class="data-value"><?= htmlspecialchars($panel['level']) ?></p>
                        
                        <p class="data-label">Highest Qualification</p>
                        <p class="data-value"><?= htmlspecialchars($panel['qualification']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="data-label">Specific Programme</p>
                        <p class="data-value text-wrap"><?= htmlspecialchars($panel['programme']) ?></p>
                        
                        <p class="data-label">Appointment Start Date</p>
                        <p class="data-value"><?= date('d F Y', strtotime($panel['start_date'])) ?></p>
                    </div>
                </div>

                <hr class="my-4 opacity-25">

                <div class="section-title">Administrative Notes</div>
                <div class="p-3 bg-light rounded-3 border-start border-primary border-4">
                    <p class="small text-muted mb-0">
                        <?= !empty($panel['remarks']) ? nl2br(htmlspecialchars($panel['remarks'])) : "No specific administrative remarks have been recorded for your profile." ?>
                    </p>
                </div>
                
                <div class="mt-5 text-center">
                    <p class="text-muted small">Need to update your details? Please contact the administrator at <a href="mailto:admin@eapsystem.edu.my">admin@eapsystem.edu.my</a></p>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>