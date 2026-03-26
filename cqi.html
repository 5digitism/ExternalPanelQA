<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - EAP System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<link rel="stylesheet" href="styles.css">

<!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</head>
<body>
<!-- Start collapsed by default -->
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

    <a href="panels.html">
      <i class="fas fa-users"></i> <span>Panels</span>
    </a>

    <a href="cqi.html" class="active">
      <i class="fas fa-tasks me-2"></i> <span>CQI Tracking</span>
    </a>

    <a href="uploadreport.html">
      <i class="fas fa-upload me-2"></i> <span>Upload Report</span>
    </a>

    <hr>

    <a href="logout.php" class="text-warning">
      <i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span>
    </a>
  </div>

  <!-- Content -->
  <div class="content">

    <!-- Header -->
    <div class="navbar-custom d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Welcome,</h4>
      <span class="text-muted">November 23, 2025</span>
    </div>

    <h2 class="mb-4 text-primary">CQI Tracking</h2>

  <div class="content">
    <form method="post" class="border p-3 rounded w-75">
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Panel</label>
          <select name="panel_id" class="form-select" required>
            <?php while ($p = $panels->fetch_assoc()): ?>
              <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="open">Open</option>
            <option value="in-progress">In Progress</option>
            <option value="closed">Closed</option>
          </select>
        </div>
      </div>
      <label class="form-label">Action</label>
      <input name="action" class="form-control mb-3" required>
      <label class="form-label">Comment</label>
      <textarea name="comment" class="form-control mb-3"></textarea>
      <button type="submit" class="btn btn-primary">Add CQI Record</button>
    </form>

    <h4 class="mt-5">All CQI Records</h4>
    <table class="table table-bordered table-striped w-100 mt-3">
      <thead class="table-primary">
        <tr><th>Panel</th><th>Action</th><th>Status</th><th>Date</th></tr>
      </thead>
      <tbody>
        <?php while ($c = $cqis->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($c['panel_name']) ?></td>
          <td><?= htmlspecialchars($c['action']) ?></td>
          <td><?= htmlspecialchars($c['status']) ?></td>
          <td><?= htmlspecialchars($c['created_at']) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function toggleSidebar() {
  document.getElementById('wrapper').classList.toggle('collapsed');
}
</script>
</body>
</html>