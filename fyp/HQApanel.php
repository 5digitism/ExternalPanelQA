<?php
require_once 'db.php';

// Fetch all panels
$query = "SELECT * FROM panel_members ORDER BY id DESC";
$result = $conn->query($query);

// Fetch filter options for the sorting section
$level_options = $conn->query("SELECT DISTINCT level FROM panel_members WHERE level != '' ORDER BY level ASC");
$prog_options = $conn->query("SELECT DISTINCT programme FROM panel_members WHERE programme != '' ORDER BY programme ASC");

if (!$result) {
    die("Error fetching panels: " . $conn->error);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Panels | HQA Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=1.1">
    
    <style>
        body { background-color: #f0f2f5; font-family: 'Poppins', sans-serif; }
        
        /* Modal Split View Styles (Adopted from approvalpage.php) */
        .review-container { display: flex; height: 80vh; overflow: hidden; background: white; }
        .review-sidebar { flex: 0 0 380px; padding: 25px; overflow-y: auto; border-right: 1px solid #eee; background: #fff; }
        .review-pdf { flex: 1; background: #525659; position: relative; }
        #detailsPdfFrame { width: 100%; height: 100%; border: none; }

        .info-group { margin-bottom: 20px; }
        .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #999; font-weight: 700; margin-bottom: 3px; }
        .info-value { font-size: 1rem; color: #333; font-weight: 500; }

        /* Original HQA UI Styles */
        .page-banner {
            background: linear-gradient(135deg, #212529 0%, #484e53 100%);
            color: white; padding: 30px 40px; border-radius: 20px; margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .filter-card { background: #fff; border: none; border-radius: 15px; padding: 20px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .panel-card { border: none; border-radius: 15px; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="wrapper collapsed" id="wrapper">
  <div class="sidebar">
    <div class="sidebar-header">
      <h3>HQA Portal</h3>
      <button class="collapse-btn" onclick="toggleSidebar()"><i class="fas fa-chevron-left"></i></button>
    </div>
    <a href="HQApage.php"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a>
    <a href="HQApanel.php" class="active"><i class="fas fa-users-cog me-2"></i> <span>Manage Panels</span></a>
    <hr>
    <a href="logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a>
  </div>

  <div class="content p-4">
    <div class="page-banner d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1">Manage Panels</h2>
            <p class="mb-0 opacity-75">HQA Review and Monitoring System</p>
        </div>
        <button onclick="window.location.reload();" class="btn btn-light rounded-pill px-4 shadow-sm">
            <i class="fas fa-sync-alt me-1"></i> Refresh List
        </button>
    </div>

    <div class="filter-card">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="input-group rounded-pill overflow-hidden border bg-light">
                    <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="searchInput" class="form-control border-0 bg-transparent" placeholder="Search panel name..." onkeyup="applyAllFilters()">
                </div>
            </div>
            <div class="col-md-3">
                <select id="filterLevel" class="form-select border rounded-pill" onchange="applyAllFilters()">
                    <option value="all">All Levels</option>
                    <?php while($l = $level_options->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($l['level']) ?>"><?= htmlspecialchars($l['level']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filterProg" class="form-select border rounded-pill" onchange="applyAllFilters()">
                    <option value="all">All Programmes</option>
                    <?php while($p = $prog_options->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($p['programme']) ?>"><?= htmlspecialchars($p['programme']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100 rounded-pill" onclick="resetFilters()">Reset</button>
            </div>
        </div>
    </div>

    <div class="panel-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Panel Name</th>
                        <th class="text-center">Level</th>
                        <th>Programme</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="panel-row" data-level="<?= htmlspecialchars($row['level']) ?>" data-prog="<?= htmlspecialchars($row['programme']) ?>">
                            <td class="ps-4">
                                <div class="fw-bold text-dark panel-search-name"><?= htmlspecialchars($row['panel_name']) ?></div>
                                <small class="text-muted">EAP-P-<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></small>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-light text-dark border px-3"><?= htmlspecialchars($row['level']) ?></span>
                            </td>
                            <td class="small"><?= htmlspecialchars($row['programme']) ?></td>
                            <td class="text-center">
                                <?php 
                                    $s = $row['status'];
                                    $badge = ($s == 'Approved') ? 'bg-success' : (($s == 'Pending') ? 'bg-warning text-dark' : 'bg-danger');
                                    echo "<span class='badge $badge rounded-pill px-3'>$s</span>";
                                ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-4" 
                                        onclick='showFullDetails(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    Review
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
  </div>
</div>

<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-clipboard-check me-2"></i>HQA Panel Detailed Review</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="review-container">
                    <div class="review-sidebar">
                        <div class="info-group">
                            <div class="info-label">Verification Status</div>
                            <div id="mStatus"></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Full Name</div>
                            <div id="mName" class="info-value text-primary fs-5 fw-bold"></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Qualification</div>
                            <div id="mQual" class="info-value"></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Academic Level</div>
                            <div id="mLevel" class="info-value"></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Specific Programme</div>
                            <div id="mProg" class="info-value"></div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="alert alert-info border-0 small">
                            <i class="fas fa-info-circle me-1"></i> You are viewing this profile as a Head of Quality Assurance.
                        </div>
                    </div>

                    <div class="review-pdf">
                        <iframe id="detailsPdfFrame" src=""></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebar() { document.getElementById("wrapper").classList.toggle("collapsed"); }

    // Search and Filter Logic
    function applyAllFilters() {
        const query = document.getElementById('searchInput').value.toLowerCase();
        const selLevel = document.getElementById('filterLevel').value;
        const selProg = document.getElementById('filterProg').value;

        document.querySelectorAll('.panel-row').forEach(row => {
            const name = row.querySelector('.panel-search-name').innerText.toLowerCase();
            const level = row.dataset.level;
            const prog = row.dataset.prog;

            const matchesSearch = name.includes(query);
            const matchesLevel = (selLevel === 'all' || level === selLevel);
            const matchesProg = (selProg === 'all' || prog === selProg);

            row.style.display = (matchesSearch && matchesLevel && matchesProg) ? '' : 'none';
        });
    }

    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterLevel').value = 'all';
        document.getElementById('filterProg').value = 'all';
        applyAllFilters();
    }

    // Modal Control
    const bModal = new bootstrap.Modal(document.getElementById('detailsModal'));

    function showFullDetails(data) {
        document.getElementById('mName').innerText = data.panel_name;
        document.getElementById('mQual').innerText = data.qualification || 'N/A';
        document.getElementById('mLevel').innerText = data.level;
        document.getElementById('mProg').innerText = data.programme;
        
        let s = data.status;
        let bClass = (s === 'Approved') ? 'bg-success' : (s === 'Pending' ? 'bg-warning text-dark' : 'bg-danger');
        document.getElementById('mStatus').innerHTML = `<span class="badge ${bClass} rounded-pill px-3">${s}</span>`;

        const iframe = document.getElementById('detailsPdfFrame');
        if (data.resume_path) { 
            iframe.src = data.resume_path + "#view=FitH"; 
        }
        bModal.show();
    }

    // Clear PDF on close
    document.getElementById('detailsModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('detailsPdfFrame').src = "";
    });
</script>
</body>
</html>