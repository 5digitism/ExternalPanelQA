<?php
require_once 'db.php'; 

// Access control: Only allow Head QA
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Head QA') {
    header("Location: loginpage.html");
    exit();
}

// Fetching Pending panels
$query = "SELECT * FROM panel_members WHERE status = 'Pending' ORDER BY id ASC";
$result = $conn->query($query);

if (!$result) {
    die("Query Error: " . $conn->error);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Management | EAP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>"> 
    <style>
        body { background-color: #f4f7f6; font-family: 'Poppins', sans-serif; }
        .approval-card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: transform 0.2s; background: #fff; }
        .approval-card:hover { transform: translateY(-5px); }
        
        .page-banner { 
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%); 
            color: white; padding: 40px; border-radius: 20px; margin-bottom: 30px; 
        }

        /* Adjusted Modal Layout to fit standard size */
        .review-container { display: flex; flex-direction: column; height: 75vh; background: white; }
        .review-details-top { padding: 20px; border-bottom: 1px solid #eee; background: #f8f9fa; }
        .review-split { display: flex; flex: 1; overflow: hidden; }
        .review-form-side { flex: 0 0 320px; padding: 20px; overflow-y: auto; border-right: 1px solid #eee; }
        .review-pdf-side { flex: 1; background: #525659; }
        #reviewPdfFrame { width: 100%; height: 100%; border: none; }
        
        .info-label { font-size: 0.65rem; font-weight: 700; color: #999; text-transform: uppercase; margin-bottom: 0px; }
        .info-value { font-size: 0.9rem; font-weight: 600; color: #333; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="wrapper collapsed" id="wrapper">
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>EAP System</h3>
            <button class="collapse-btn" onclick="toggleSidebar()"><i class="fas fa-chevron-left"></i></button>
        </div>
        <a href="HQApage.php"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a>
        <a href="approvalpage.php" class="active"><i class="fas fa-user-check me-2"></i> <span>Pending Approvals</span></a>
        <a href="HQApanel.php"><i class="fas fa-users me-2"></i> <span>Panels Directory</span></a>
        <hr>
        <a href="logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a>
    </div>

    <div class="content p-4">
        <div class="page-banner shadow-sm d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1">Approval Management</h2>
                <p class="mb-0 opacity-75">Review and verify applications for External Academic Panels.</p>
            </div>
            <div class="text-end">
                <span class="badge bg-white text-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="fas fa-clock me-2"></i> <?php echo $result->num_rows; ?> Pending
                </span>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row g-4">
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card approval-card p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                    <i class="fas fa-user-tie text-primary fa-lg"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($row['panel_name']); ?></h5>
                                    <small class="text-muted">Applied: <?php echo date('d M Y', strtotime($row['start_date'])); ?></small>
                                </div>
                            </div>
                            <button class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm" 
                                    onclick='openReview(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                <i class="fas fa-search me-2"></i> Review Application
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <h4 class="text-muted">No pending applications.</h4>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Reviewing: <span id="vHeaderName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="review-container">
                    <div class="review-details-top row g-0">
                        <div class="col-md-4">
                            <div class="info-label">Email</div>
                            <div id="vEmail" class="info-value text-primary"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-label">Programme</div>
                            <div id="vProg" class="info-value"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-label">Qualification</div>
                            <div id="vQual" class="info-value"></div>
                        </div>
                    </div>

                    <div class="review-split">
                        <div class="review-form-side">
                            <form action="approveprocess.php" method="POST">
                                <input type="hidden" name="id" id="panelIdInput">
                                
                                <div class="mb-3">
                                    <div class="info-label">Academic Level</div>
                                    <div id="vLevel" class="info-value"></div>
                                    <div class="info-label">Phone</div>
                                    <div id="vPhone" class="info-value"></div>
                                </div>

                                <div class="mb-4">
                                    <label class="info-label text-dark fw-bold mb-1">Decision Remarks</label>
                                    <textarea name="admin_note" class="form-control form-control-sm" rows="5" placeholder="Reason for decision..." required></textarea>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" name="action" value="Approved" class="btn btn-success fw-bold">Approve</button>
                                    <button type="submit" name="action" value="Rejected" class="btn btn-danger fw-bold">Reject</button>
                                </div>
                            </form>
                        </div>
                        <div class="review-pdf-side">
                            <iframe id="reviewPdfFrame" src=""></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebar() { 
        document.getElementById("wrapper").classList.toggle("collapsed"); 
    }

    const myModal = new bootstrap.Modal(document.getElementById('reviewModal'));
    
    function openReview(data) {
        document.getElementById('panelIdInput').value = data.id;
        document.getElementById('vHeaderName').innerText = data.panel_name;
        document.getElementById('vEmail').innerText = data.email || 'N/A';
        document.getElementById('vPhone').innerText = data.phone || 'N/A';
        document.getElementById('vLevel').innerText = data.level;
        document.getElementById('vProg').innerText = data.programme;
        document.getElementById('vQual').innerText = data.qualification;
        
        if (data.resume_path) {
            document.getElementById('reviewPdfFrame').src = data.resume_path + "#view=FitH";
        }
        myModal.show();
    }

    document.getElementById('reviewModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('reviewPdfFrame').src = "";
    });
</script>
</body>
</html>