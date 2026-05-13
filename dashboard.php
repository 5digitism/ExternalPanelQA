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

        /* ── Announcement Widget ── */
        .ann-composer {
            background: #fff;
            border-radius: 16px;
            border: 1.5px solid #e0e7ef;
            box-shadow: 0 4px 16px rgba(13,110,253,0.07);
            overflow: hidden;
        }
        .ann-composer-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
            color: white;
            padding: 16px 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ann-composer-header h6 { margin: 0; font-weight: 700; font-size: 0.95rem; }
        .ann-composer-body { padding: 20px 22px; }
        .ann-composer-body input,
        .ann-composer-body textarea,
        .ann-composer-body select {
            border-radius: 10px;
            border: 1px solid #dee2e6;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            width: 100%;
            padding: 9px 13px;
            margin-bottom: 10px;
            transition: border-color 0.2s;
        }
        .ann-composer-body input:focus,
        .ann-composer-body textarea:focus,
        .ann-composer-body select:focus {
            outline: none; border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,0.1);
        }
        .ann-composer-body textarea { resize: vertical; min-height: 80px; }
        .ann-post-btn {
            background: #0d6efd; color: white; border: none;
            border-radius: 10px; padding: 9px 22px;
            font-family: 'Poppins', sans-serif; font-size: 0.85rem; font-weight: 600;
            cursor: pointer; transition: background 0.2s;
        }
        .ann-post-btn:hover { background: #0043a8; }
        .ann-post-btn:disabled { background: #adb5bd; cursor: not-allowed; }

        .ann-feed { margin-top: 18px; }
        .ann-item {
            background: #f8faff;
            border-left: 4px solid #0d6efd;
            border-radius: 0 12px 12px 0;
            padding: 13px 16px;
            margin-bottom: 10px;
            position: relative;
            transition: box-shadow 0.2s;
        }
        .ann-item.priority-important { border-left-color: #fd7e14; background: #fff8f0; }
        .ann-item.priority-urgent    { border-left-color: #dc3545; background: #fff5f5; }
        .ann-item-title  { font-weight: 700; font-size: 0.88rem; color: #0b3a6e; margin-bottom: 3px; }
        .ann-item-body   { font-size: 0.82rem; color: #444; line-height: 1.55; white-space: pre-wrap; }
        .ann-item-meta   { font-size: 0.72rem; color: #9ca3af; margin-top: 7px; }
        .ann-delete-btn  {
            position: absolute; top: 10px; right: 12px;
            background: none; border: none; color: #ccc; font-size: 0.8rem; cursor: pointer;
            padding: 2px 6px; border-radius: 6px; transition: color 0.2s, background 0.2s;
        }
        .ann-delete-btn:hover { color: #dc3545; background: #ffe4e4; }
        .ann-priority-badge {
            display: inline-block; font-size: 0.65rem; font-weight: 700;
            padding: 2px 8px; border-radius: 20px; margin-left: 6px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .badge-normal    { background: #e8f4f8; color: #0d6efd; }
        .badge-important { background: #fff3cd; color: #856404; }
        .badge-urgent    { background: #f8d7da; color: #842029; }
        .ann-empty { text-align: center; color: #adb5bd; font-size: 0.83rem; padding: 20px 0; }
        #annPostStatus { font-size: 0.8rem; margin-top: 6px; min-height: 18px; }
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
                <div class="row g-3 mb-4">
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

                <!-- ── Announcement Composer ── -->
                <div class="ann-composer">
                    <div class="ann-composer-header">
                        <i class="fas fa-bullhorn fa-lg"></i>
                        <h6>Post Announcement to All PCs</h6>
                    </div>
                    <div class="ann-composer-body">
                        <input type="text" id="annTitle" placeholder="Announcement title…" maxlength="200">
                        <div class="d-flex gap-2 mb-0">
                            <select id="annPriority" style="flex:0 0 160px;margin-bottom:10px;">
                                <option value="normal">🔵 Normal</option>
                                <option value="important">🟠 Important</option>
                                <option value="urgent">🔴 Urgent</option>
                            </select>
                        </div>
                        <textarea id="annBody" placeholder="Write your announcement here…"></textarea>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <button class="ann-post-btn" id="annPostBtn" onclick="postAnnouncement()">
                                <i class="fas fa-paper-plane me-1"></i> Post Announcement
                            </button>
                            <div id="annPostStatus"></div>
                        </div>

                        <!-- Live feed of posted announcements -->
                        <div class="ann-feed" id="annFeed">
                            <div class="ann-empty"><i class="fas fa-spinner fa-spin me-1"></i> Loading…</div>
                        </div>
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

    // ── Announcement helpers ──────────────────────────────────────────────────
    function escHtml(s) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(s));
        return d.innerHTML;
    }

    function timeAgoJs(dateStr) {
        const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
        if (diff < 60)   return 'Just now';
        if (diff < 3600) return Math.floor(diff/60)   + ' min ago';
        if (diff < 86400)return Math.floor(diff/3600) + ' hr ago';
        return Math.floor(diff/86400) + 'd ago';
    }

    function priorityBadge(p) {
        const map = { normal: ['badge-normal','Normal'], important: ['badge-important','Important'], urgent: ['badge-urgent','Urgent'] };
        const [cls, label] = map[p] || map.normal;
        return `<span class="ann-priority-badge ${cls}">${label}</span>`;
    }

    function renderFeed(announcements) {
        const feed = document.getElementById('annFeed');
        if (!announcements.length) {
            feed.innerHTML = '<div class="ann-empty"><i class="fas fa-megaphone me-1 opacity-50"></i>No announcements yet. Post one above.</div>';
            return;
        }
        feed.innerHTML = announcements.map(a => `
            <div class="ann-item priority-${escHtml(a.priority)}" id="annItem_${a.id}">
                <button class="ann-delete-btn" onclick="deleteAnnouncement(${a.id})" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
                <div class="ann-item-title">${escHtml(a.title)} ${priorityBadge(a.priority)}</div>
                <div class="ann-item-body">${escHtml(a.body)}</div>
                <div class="ann-item-meta">
                    <i class="fas fa-user-shield me-1"></i>${escHtml(a.poster_name)}
                    &nbsp;·&nbsp;
                    <i class="fas fa-clock me-1"></i>${timeAgoJs(a.created_at)}
                </div>
            </div>`).join('');
    }

    function loadAnnouncements() {
        fetch('announcements.php?action=fetch&limit=20')
            .then(r => r.json())
            .then(d => { if (d.success) renderFeed(d.announcements); })
            .catch(() => {
                document.getElementById('annFeed').innerHTML =
                    '<div class="ann-empty text-danger">Could not load announcements.</div>';
            });
    }

    function postAnnouncement() {
        const title    = document.getElementById('annTitle').value.trim();
        const body     = document.getElementById('annBody').value.trim();
        const priority = document.getElementById('annPriority').value;
        const status   = document.getElementById('annPostStatus');
        const btn      = document.getElementById('annPostBtn');

        if (!title || !body) {
            status.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Title and body are required.</span>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Posting…';
        status.innerHTML = '';

        const fd = new FormData();
        fd.append('action',   'post');
        fd.append('title',    title);
        fd.append('body',     body);
        fd.append('priority', priority);

        fetch('announcements.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    document.getElementById('annTitle').value = '';
                    document.getElementById('annBody').value  = '';
                    document.getElementById('annPriority').value = 'normal';
                    status.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Posted successfully!</span>';
                    loadAnnouncements();
                } else {
                    status.innerHTML = `<span class="text-danger">${escHtml(d.message)}</span>`;
                }
            })
            .catch(() => { status.innerHTML = '<span class="text-danger">Network error.</span>'; })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Post Announcement';
                setTimeout(() => { status.innerHTML = ''; }, 4000);
            });
    }

    function deleteAnnouncement(id) {
        if (!confirm('Remove this announcement?')) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        fetch('announcements.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => { if (d.success) { const el = document.getElementById('annItem_' + id); if (el) el.remove(); } });
    }

    // Load on page ready
    loadAnnouncements();
</script>
</body>
</html>