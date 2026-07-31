<?php
require_once __DIR__ . '/../includes/config.php';
$user = requireLogin('resident');
$db = getDB();
$profileId = $user['profile_id'] ?? null;

if (!$profileId) {
    header('Location: ' . APP_URL . '/resident/profile.php?setup=1');
    exit;
}

// Fetch profile details
$ps = $db->prepare("SELECT apartment_number, contact_address FROM resident_profiles WHERE resident_id = ?");
$ps->execute([$profileId]);
$profile = $ps->fetch();

// Counts by status
$cs = $db->prepare("SELECT complaint_status, COUNT(*) AS cnt FROM complaints WHERE resident_id = ? GROUP BY complaint_status");
$cs->execute([$profileId]);
$statusCounts = [];
while ($r = $cs->fetch()) { $statusCounts[$r['complaint_status']] = $r['cnt']; }
$totalComplaints = array_sum($statusCounts);
$pending = ($statusCounts['pending'] ?? 0) + ($statusCounts['assigned'] ?? 0) + ($statusCounts['in_progress'] ?? 0);
$resolved = ($statusCounts['resolved'] ?? 0) + ($statusCounts['closed'] ?? 0);

// Recent complaints
$recent = $db->prepare("
    SELECT c.* 
    FROM complaints c
    WHERE c.resident_id = ?
    ORDER BY c.submitted_at DESC LIMIT 5
");
$recent->execute([$profileId]);
$recentList = $recent->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Tenant Dashboard — ResideHub</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
<script src="https://unpkg.com/lucide/dist/umd/lucide.min.js"></script>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="sidebar-icon">
      <i data-lucide="building-2" width="20" height="20" style="color:#fff;"></i>
    </div>
    <div class="sidebar-name">Reside<span>Hub</span></div>
  </div>
  <div style="padding:0 24px 12px;margin-top:8px;"><span class="tag">Tenant Portal</span></div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <a class="nav-item active" href="dashboard.php"><i data-lucide="layout-dashboard" width="18" height="18"></i> Dashboard</a>
    <a class="nav-item" href="submit.php"><i data-lucide="plus-circle" width="18" height="18"></i> File Complaint</a>
    <a class="nav-item" href="my_complaints.php"><i data-lucide="file-text" width="18" height="18"></i> My Complaints</a>
    <div class="nav-section">Account</div>
    <a class="nav-item" href="profile.php"><i data-lucide="user" width="18" height="18"></i> My Profile</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
      <div class="user-info"><div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div><div class="user-email">Apt: <?= htmlspecialchars($profile['apartment_number'] ?? 'N/A') ?></div></div>
      <button class="logout-btn" onclick="logout()" title="Log out"><i data-lucide="log-out" width="20" height="20"></i></button>
    </div>
  </div>
</aside>

<div class="main-content">
  <div class="topbar">
    <div>
      <div class="page-title">Welcome back, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?></div>
      <div class="page-sub">Here's the status of your apartment maintenance requests.</div>
    </div>
    <div class="topbar-actions">
      <a href="submit.php" class="btn btn-primary">
        <i data-lucide="plus" width="16" height="16"></i> New Complaint
      </a>
    </div>
  </div>
  
  <div class="content-area">
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(79,124,255,0.1);color:var(--accent);"><i data-lucide="file-text" width="24" height="24"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $totalComplaints ?></div>
          <div class="stat-label">Total Filed</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(240,192,64,0.1);color:var(--gold);"><i data-lucide="clock" width="24" height="24"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $pending ?></div>
          <div class="stat-label">Open & Pending</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(52,211,153,0.1);color:var(--success);"><i data-lucide="check-circle" width="24" height="24"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $resolved ?></div>
          <div class="stat-label">Resolved</div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-top:24px;padding:0;">
      <div class="card-header" style="padding:20px 24px;border-bottom:1px solid var(--border);">
        <h3 style="font-size:16px;font-weight:600;margin:0;">Recent Complaints</h3>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Complaint</th>
              <th>Status</th>
              <th>Submitted</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentList)): ?>
              <tr><td colspan="3" style="text-align:center;padding:30px;color:var(--muted);">You haven't filed any complaints yet.</td></tr>
            <?php else: ?>
              <?php foreach ($recentList as $c): ?>
              <tr>
                <td>
                  <div style="font-weight:500;color:var(--text);margin-bottom:4px;"><?= htmlspecialchars($c['complaint_title']) ?></div>
                </td>
                <td>
                  <span class="badge badge-<?= $c['complaint_status'] ?>"><?= str_replace('_', ' ', $c['complaint_status']) ?></span>
                </td>
                <td style="font-size:13px;color:var(--muted);"><?= date('M d, Y h:i A', strtotime($c['submitted_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if (!empty($recentList)): ?>
      <div style="padding:16px 24px;border-top:1px solid var(--border);text-align:center;">
        <a href="my_complaints.php" style="color:var(--accent);text-decoration:none;font-size:13px;font-weight:500;">View All Complaints →</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
async function logout() {
  await fetch('../api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})});
  window.location.href='../index.php';
}
</script>
<script>lucide.createIcons();</script>
</body>
</html>
