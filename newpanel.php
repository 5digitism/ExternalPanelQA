<?php
require_once 'db.php'; 

$status_msg = "";
$alert_type = "info";

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $status_msg = "Panel successfully submitted for approval!";
        $alert_type = "success";
    } elseif ($_GET['status'] == 'error') {
        $status_msg = "Error: " . htmlspecialchars($_GET['msg']);
        $alert_type = "danger";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Panel - EAP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=1.1">
    <style>
        body { background-color: #f4f7f6; font-family: 'Poppins', sans-serif; }
        .form-card { background: #fff; border: none; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .page-banner { background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%); color: white; padding: 40px; border-radius: 20px; margin-bottom: 30px; }
        .form-label { font-weight: 600; color: #444; }
        .form-control, .form-select { border-radius: 10px; padding: 12px 15px; border: 1px solid #e0e0e0; background-color: #f8f9fa; }
        .btn-back-list { background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.4); color: white; transition: all 0.3s ease; }
        .btn-back-list:hover { background: rgba(255, 255, 255, 1); color: #0d6efd; transform: translateX(-5px); }
    </style>
</head>
<body>

<div class="wrapper collapsed" id="wrapper">
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>EAP System</h3>
            <button class="collapse-btn" onclick="toggleSidebar()"><i class="fas fa-chevron-left"></i></button>
        </div>
        <a href="dashboard.php"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a>
        <a href="PCEvaluation.php"><i class="fas fa-list-check"></i> <span>Evaluation</span></a>
        <a href="panels.php"><i class="fas fa-users me-2"></i> <span>Panels Directory</span></a>
        <a href="newpanel.php" class="active"><i class="fas fa-user-plus me-2"></i> <span>Add New Panel</span></a>
        <hr>
        <a href="logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a>
    </div>

    <div class="content p-4">
        <?php if($status_msg): ?>
            <div class="alert alert-<?= $alert_type ?> alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
                <?= $status_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="page-banner shadow-sm d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1">Registration Portal</h2>
                <p class="mb-0 opacity-75">Register a new External Academic Panel member.</p>
            </div>
            <a href="panels.php" class="btn btn-back-list rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i> Back to Panel List
            </a>
        </div>

        <div class="container-fluid">
            <div class="form-card">
                <form id="addPanelForm" action="panelbackend.php" method="POST" enctype="multipart/form-data">
                    
                    <h5 class="fw-bold text-primary mb-4"><i class="fas fa-id-card me-2"></i> Personal & Contact Information</h5>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Full Name (with Title)</label>
                        <input type="text" name="panel_name" id="panelName" class="form-control" placeholder="e.g. Prof. Dr. Ahmad Zakaria" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Email Address</label>
                            <input type="email" name="email" id="panelEmail" class="form-control" placeholder="name@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Phone Number</label>
                            <input type="tel" name="phone" id="panelPhone" class="form-control" placeholder="e.g. +60123456789" required>
                        </div>
                    </div>

                    <hr class="my-4 opacity-25">
                    <h5 class="fw-bold text-primary mb-4"><i class="fas fa-graduation-cap me-2"></i> Academic Assignment</h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Academic Level</label>
                            <select name="level" id="level" class="form-select" onchange="updateProgrammes()" required>
                                <option value="" disabled selected>Select Level</option>
                                <option value="Foundation">Foundation</option>
                                <option value="Diploma">Diploma</option>
                                <option value="Bachelor Degree">Bachelor Degree</option>
                                <option value="Master's Degree">Master's Degree</option>
                                <option value="Doctorate">Doctorate</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Specific Programme</label>
                            <select name="programme" id="programme" class="form-select" disabled required>
                                <option value="" disabled selected>Select Level First</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Highest Qualification</label>
                            <input type="text" name="qualification" id="qualification" class="form-control" placeholder="e.g. PhD in Computer Science" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Appointment Start Date</label>
                            <input type="date" name="start_date" id="startDate" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Resume / CV (PDF Format)</label>
                        <input type="file" name="resume" class="form-control" accept="application/pdf" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Additional Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="mt-5 border-top pt-4 text-end">
                        <button type="button" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm" onclick="showPreview()">
                            <i class="fas fa-eye me-2"></i> Review & Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-primary text-white p-4 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-clipboard-check me-2"></i> Verify Information</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-3">
                    <div class="col-5 text-muted small fw-bold text-uppercase">Full Name:</div>
                    <div id="pName" class="col-7 fw-bold"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-5 text-muted small fw-bold text-uppercase">Email:</div>
                    <div id="pEmail" class="col-7 text-primary fw-bold"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-5 text-muted small fw-bold text-uppercase">Phone:</div>
                    <div id="pPhone" class="col-7 fw-bold"></div>
                </div>
                <hr>
                <div class="row mb-3">
                    <div class="col-5 text-muted small fw-bold text-uppercase">Level:</div>
                    <div id="pLevel" class="col-7"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-5 text-muted small fw-bold text-uppercase">Programme:</div>
                    <div id="pProg" class="col-7"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-5 text-muted small fw-bold text-uppercase">Qualification:</div>
                    <div id="pQual" class="col-7"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-5 text-muted small fw-bold text-uppercase">Start Date:</div>
                    <div id="pDate" class="col-7"></div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Edit</button>
                <button type="button" id="confirmSubmit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Confirm & Submit</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebar() { document.getElementById("wrapper").classList.toggle("collapsed"); }

    const programmeData = {
        "Foundation": [
            "Foundation in Computer Technology",
            "Foundation in Science and Technology (special track for Korean Universities)"
        ],
        "Diploma": [
            "Diploma in Information Technology",
            "Diploma in Networking Technology",
            "Diploma in Animation",
            "Diploma in Multimedia"
        ],
        "Bachelor Degree": [
            "Bachelor of Information Technology (Hons) in Software Engineering",
            "Bachelor of Information Technology (Hons) in Computer System Security",
            "Bachelor of Information Technology (Hons) in Internet of Things (IoT)",
            "Bachelor of Computer Engineering Technology (Hons) in Networking Systems",
            "Bachelor of Computer Engineering Technology (Hons) in Computer Systems",
            "Bachelor in Computing and Business Management (Honours)",
            "Bachelor of Multimedia Technology (Hons) in Interactive Multimedia Design",
            "Bachelor of Multimedia Technology (Hons) in Computer Animation",
            "Bachelor of Game Development Technology with Honours"
        ],
        "Master's Degree": [
            "Master of Information Technology",
            "Master in Computer Science",
            "Master in Creative Digital Media"
        ],
        "Doctorate": [
            "Doctor of Philosophy (Information Technology)"
        ]
    };

    function updateProgrammes() {
        const levelSelect = document.getElementById('level');
        const progSelect = document.getElementById('programme');
        const selectedLevel = levelSelect.value;

        progSelect.innerHTML = '<option value="" disabled selected>Select Programme</option>';
        progSelect.disabled = false;

        if (programmeData[selectedLevel]) {
            programmeData[selectedLevel].forEach(prog => {
                const option = document.createElement('option');
                option.value = prog;
                option.textContent = prog;
                progSelect.appendChild(option);
            });
        }
    }

    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    
    function showPreview() {
        const form = document.getElementById('addPanelForm');
        if(!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Map values to Modal
        document.getElementById('pName').innerText = document.getElementById('panelName').value;
        document.getElementById('pEmail').innerText = document.getElementById('panelEmail').value;
        document.getElementById('pPhone').innerText = document.getElementById('panelPhone').value;
        document.getElementById('pLevel').innerText = document.getElementById('level').value;
        document.getElementById('pProg').innerText = document.getElementById('programme').value;
        document.getElementById('pQual').innerText = document.getElementById('qualification').value;
        document.getElementById('pDate').innerText = document.getElementById('startDate').value;
        
        previewModal.show();
    }

    document.getElementById('confirmSubmit').addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...';
        document.getElementById('addPanelForm').submit();
    });
</script>
</body>
</html>