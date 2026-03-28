<?php
session_start();
require_once 'db.php';

// 1. Session and Security Check
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// 2. Validate Panel ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h2>Error: Panel ID missing.</h2>
            <a href='panels.php'>Return to Directory</a>
         </div>");
}

$id = intval($_GET['id']);

// 3. Fetch Panel Details from database
$query = "SELECT * FROM panel_members WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$panel = $result->fetch_assoc();

// 4. Verification Logic
if (!$panel) {
    die("<script>alert('Panel member not found.'); window.location.href='panels.php';</script>");
}

// Block access if not approved
if (trim(strtolower($panel['status'])) !== 'approved') {
    die("<script>alert('Access Denied: This profile is only available for approved panel members.'); window.close();</script>");
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile View | <?= htmlspecialchars($panel['panel_name']) ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary-blue: #0d6efd; --bg-gray: #f4f7f6; }
        body { background-color: var(--bg-gray); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* Profile Header Design */
        .profile-header { 
            background: linear-gradient(135deg, #212529 0%, #343a40 100%); 
            color: white; 
            padding: 50px 0; 
            border-bottom: 5px solid var(--primary-blue);
        }
        
        .profile-card { 
            border: none; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            background: white; 
            margin-top: -40px; 
        }

        .section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--primary-blue);
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .data-label { font-size: 0.8rem; color: #adb5bd; font-weight: 600; margin-bottom: 0; }
        .data-value { font-size: 1.05rem; color: #212529; font-weight: 500; margin-bottom: 15px; }

        .contact-box {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-blue);
        }

        .pdf-frame {
            width: 100%;
            height: 700px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            background-color: #525659;
        }

        .status-pill {
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="profile-header text-center">
    <div class="container">
        <h1 class="fw-bold mb-1"><?= htmlspecialchars($panel['panel_name']) ?></h1>
        <p class="opacity-75 mb-3">Academic Panel Member | EAP-P-<?= str_pad($panel['id'], 4, '0', STR_PAD_LEFT) ?></p>
        <div class="status-pill bg-success text-white">
            <i class="fas fa-check-circle me-1"></i> <?= $panel['status'] ?>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card profile-card p-4">
                
                <div class="section-title">Contact Information</div>
                <div class="contact-box">
                    <p class="data-label"><i class="fas fa-envelope me-2"></i>Email Address</p>
                    <p class="data-value mb-0 text-primary fw-bold"><?= htmlspecialchars($panel['email'] ?? 'Not Provided') ?></p>
                </div>
                <div class="contact-box">
                    <p class="data-label"><i class="fas fa-phone me-2"></i>Phone Number</p>
                    <p class="data-value mb-0 fw-bold"><?= htmlspecialchars($panel['phone'] ?? 'Not Provided') ?></p>
                </div>

                <div class="section-title mt-4">Academic Background</div>
                
                <p class="data-label">Academic Level</p>
                <p class="data-value"><?= htmlspecialchars($panel['level']) ?></p>

                <p class="data-label">Programme Specialty</p>
                <p class="data-value"><?= htmlspecialchars($panel['programme']) ?></p>

                <p class="data-label">Highest Qualification</p>
                <p class="data-value"><?= htmlspecialchars($panel['qualification']) ?></p>

                <p class="data-label">Appointment Date</p>
                <p class="data-value"><?= date('d F Y', strtotime($panel['start_date'])) ?></p>

                <div class="section-title mt-4">Administrative Remarks</div>
                <p class="text-muted small"><?= nl2br(htmlspecialchars($panel['remarks'] ?? 'No specific remarks recorded.')) ?></p>

                <div class="mt-4 d-grid gap-2">
                    <a href="generateletter.php?id=<?= $panel['id'] ?>" class="btn btn-primary rounded-pill shadow-sm" target="_blank">
                        <i class="fas fa-file-pdf me-2"></i> View Appointment Letter
                    </a>
                    <button class="btn btn-outline-secondary rounded-pill" onclick="window.close()">
                        <i class="fas fa-arrow-left me-2"></i> Close Profile
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card profile-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0" style="border:none;">Resume & Supporting Documents</div>
                    <?php if(!empty($panel['resume_path'])): ?>
                        <a href="<?= htmlspecialchars($panel['resume_path']) ?>" download class="btn btn-sm btn-outline-primary px-3 rounded-pill">
                            <i class="fas fa-download me-1"></i> Download
                        </a>
                    <?php endif; ?>
                </div>

                <?php if(!empty($panel['resume_path'])): ?>
                    <iframe src="<?= htmlspecialchars($panel['resume_path']) ?>#view=FitH" class="pdf-frame"></iframe>
                <?php else: ?>
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                        <i class="fas fa-file-circle-exclamation fa-4x mb-3 opacity-25"></i>
                        <p>No document was uploaded for this profile.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>