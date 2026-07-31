<?php
require_once '../includes/config.php';
requireLogin('admin');
$user = currentUser();
$db = getDB();

// Stats
$totalComplaints = $db->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
$pending         = $db->query("SELECT COUNT(*) FROM complaints WHERE complaint_status='pending'")->fetchColumn();
$inProgress      = $db->query("SELECT COUNT(*) FROM complaints WHERE complaint_status IN ('assigned','in_progress')")->fetchColumn();
$resolved        = $db->query("SELECT COUNT(*) FROM complaints WHERE complaint_status='resolved'")->fetchColumn();
$totalStaff      = $db->query("SELECT COUNT(*) FROM service_staff_profiles")->fetchColumn();
$availableStaff  = $db->query("SELECT COUNT(*) FROM service_staff_profiles WHERE available_status='available'")->fetchColumn();
$totalTenants  = $db->query("SELECT COUNT(*) FROM resident_profiles")->fetchColumn();

// Recent complaints
$recentComplaints = $db->query("
  SELECT c.*, u.full_name AS resident_name,
         rp.apartment_number
  FROM complaints c
  JOIN resident_profiles rp ON c.resident_id = rp.resident_id
  JOIN users u ON rp.user_id = u.user_id
  ORDER BY c.submitted_at DESC LIMIT 8
")->fetchAll();

// Category breakdown
$catStats = $db->query("
  SELECT cc.category_name, COUNT(c.complaint_id) as cnt
  FROM complaint_categories cc
  LEFT JOIN complaints c ON cc.category_name = c.category_name
  GROUP BY cc.category_name
  ORDER BY cnt DESC LIMIT 6
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — ResideHub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<style>
.category-bar { margin-bottom: 14px; }
.cat-label { display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px; }
.cat-track { height: 6px; background: var(--surface2); border-radius: 3px; overflow:hidden; }
.cat-fill { height: 100%; background: linear-gradient(90deg, var(--accent), #6b5bff); border-radius: 3px; transition: width 1s ease; }
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="sidebar-icon">
      <i data-lucide="building-2" width="20" height="20" style="color:#fff;"></i>
    </div>
    <div class="sidebar-name">Reside<span>Hub</span></div>
  </div>
  <div style="padding:0 24px 12px;margin-top:8px;">
    <span class="tag">Admin Panel</span>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <a class="nav-item active" href="dashboard.php"><i data-lucide="layout-dashboard" width="18" height="18"></i> Dashboard</a>
    <a class="nav-item" href="complaints.php"><i data-lucide="file-text" width="18" height="18"></i> All Complaints</a>
    <a class="nav-item" href="assign.php"><i data-lucide="user-check" width="18" height="18"></i> Assign Staff</a>
    <div class="nav-section">Management</div>
    <a class="nav-item" href="staff.php"><i data-lucide="users" width="18" height="18"></i> Staff Management</a>
    <a class="nav-item" href="tenants.php"><i data-lucide="home" width="18" height="18"></i> Tenants</a>
    <a class="nav-item" href="categories.php"><i data-lucide="tag" width="18" height="18"></i> Categories</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
        <div class="user-email">Administrator</div>
      </div>
      <button class="logout-btn" onclick="logout()" title="Log out"><i data-lucide="log-out" width="20" height="20"></i></button>
    </div>
  </div>
</aside>

<!-- MAIN -->
<div class="main-content">
  <div class="topbar">
    <div>
      <div class="page-title">Dashboard Overview</div>
      <div class="page-sub">Welcome back, <?= htmlspecialchars($user['full_name']) ?>. Here's what's happening today.</div>
    </div>
    <div class="topbar-actions">
     <!-- <a href="complaints.php" class="btn btn-primary">+ New Overview</a> -->
    </div>
  </div>

  <div class="content-area">
    <!-- STATS -->
    <div class="stats-grid">
      <div class="stat-card" style="--card-accent:var(--accent)">
        <div class="stat-label">Total Complaints</div>
        <div class="stat-num"><?= $totalComplaints ?></div>
        <div class="stat-change">All time submissions</div>
      </div>
      <div class="stat-card" style="--card-accent:var(--pending)">
        <div class="stat-label">Pending Review</div>
        <div class="stat-num"><?= $pending ?></div>
        <div class="stat-change">Awaiting assignment</div>
      </div>
      <div class="stat-card" style="--card-accent:var(--in_progress)">
        <div class="stat-label">In Progress</div>
        <div class="stat-num"><?= $inProgress ?></div>
        <div class="stat-change">Assigned & working</div>
      </div>
      <div class="stat-card" style="--card-accent:var(--success)">
        <div class="stat-label">Resolved</div>
        <div class="stat-num"><?= $resolved ?></div>
        <div class="stat-change">Successfully closed</div>
      </div>
      <div class="stat-card" style="--card-accent:var(--accent2)">
        <div class="stat-label">Staff Available</div>
        <div class="stat-num"><?= $availableStaff ?>/<?= $totalStaff ?></div>
        <div class="stat-change">Ready to assign</div>
      </div>
      <div class="stat-card" style="--card-accent:var(--gold)">
        <div class="stat-label">Total Tenants</div>
        <div class="stat-num"><?= $totalTenants ?></div>
        <div class="stat-change">Registered profiles</div>
      </div>
    </div>

    <div class="grid-2">
      <!-- RECENT COMPLAINTS -->
      <div class="card" style="grid-column: 1 / -1;">
        <div class="card-header">
          <div class="card-title">Recent Complaints</div>
          <a href="complaints.php" class="btn btn-secondary btn-sm">View All →</a>
        </div>
        <div class="table-wrap">
          <?php if (empty($recentComplaints)): ?>
            <div class="empty-state">
              <div class="empty-icon"><i data-lucide="inbox" width="32" height="32" style="color:var(--muted);"></i></div>
              <div class="empty-title">No complaints yet</div>
              <div class="empty-sub">Complaints submitted by tenants will appear here.</div>
            </div>
          <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Tenant</th>
                <th>Category</th>
                <th>Status</th>
                <th>Date</th>
                <th style="text-align:right;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentComplaints as $c): ?>
              <tr>
                <td class="text-muted text-sm">#<?= str_pad($c['complaint_id'],4,'0',STR_PAD_LEFT) ?></td>
                <td class="fw-600"><?= htmlspecialchars($c['resident_name']) ?></td>
                <td>
                  <span class="category-badge">
                    <i data-lucide="tag" width="12" height="12"></i>
                    <?= htmlspecialchars($c['category_name'] ?? 'Uncategorized') ?>
                  </span>
                </td>
                <td><span class="badge badge-<?= $c['complaint_status'] ?>"><?= str_replace('_',' ',$c['complaint_status']) ?></span></td>
                <td class="text-muted text-sm"><?= date('M d, Y', strtotime($c['submitted_at'])) ?></td>
                <td style="display:flex;gap:6px;justify-content:flex-end;">
                  <a href="complaints.php?view=<?= $c['complaint_id'] ?>" class="btn btn-secondary btn-sm">View</a>
                  <?php if ($c['complaint_status'] === 'pending'): ?>
                  <a href="assign.php?complaint=<?= $c['complaint_id'] ?>" class="btn btn-primary btn-sm">Assign</a>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- CATEGORY BREAKDOWN -->
    <div class="card" style="margin-top:24px;">
      <div class="card-header"><div class="card-title">Complaints by Category</div></div>
      <div class="card-body">
        <?php
        $maxCat = max(array_column($catStats,'cnt') ?: [1]);
        foreach ($catStats as $cat):
          $pct = $maxCat > 0 ? round($cat['cnt']/$maxCat*100) : 0;
        ?>
        <div class="category-bar">
          <div class="cat-label">
            <span><?= htmlspecialchars($cat['category_name']) ?></span>
            <span class="text-muted"><?= $cat['cnt'] ?></span>
          </div>
          <div class="cat-track"><div class="cat-fill" data-w="<?= $pct ?>%" style="width:0"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
// Animate bars
document.querySelectorAll('.cat-fill').forEach(el => {
  setTimeout(() => el.style.width = el.dataset.w, 300);
});

async function logout() {
  await fetch('../api/auth.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({action:'logout'})
  });
  window.location.href = '../index.php';
}
</script>
<script>lucide.createIcons();</script>
</body>
</html>
