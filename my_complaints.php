<?php
require_once __DIR__ . '/../includes/config.php';
$user = requireLogin('resident');
$db = getDB();
$profileId = $user['profile_id'] ?? null;

if (!$profileId) {
    header('Location: ' . APP_URL . '/resident/profile.php?setup=1');
    exit;
}

$stmt = $db->prepare("
    SELECT c.* 
    FROM complaints c
    WHERE c.resident_id = ?
    ORDER BY c.submitted_at DESC
");
$stmt->execute([$profileId]);
$complaints = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Complaints — ResideHub</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
<script src="https://unpkg.com/lucide/dist/umd/lucide.min.js"></script>
<style>
.complaint-card {
  padding: 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  gap: 24px;
  transition: background 0.2s;
}
.complaint-card:last-child {
  border-bottom: none;
}
.complaint-card:hover {
  background: rgba(255,255,255,0.02);
}
.c-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: var(--surface2);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--accent);
}
.c-main {
  flex: 1;
  min-width: 0;
}
.c-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;
}
.c-title {
  font-size: 16px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 4px;
}
.c-meta {
  font-size: 13px;
  color: var(--muted);
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}
.c-meta span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.c-desc {
  font-size: 14px;
  color: var(--muted2);
  line-height: 1.6;
  background: var(--surface2);
  padding: 16px;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.03);
}
</style>
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
    <a class="nav-item" href="dashboard.php"><i data-lucide="layout-dashboard" width="18" height="18"></i> Dashboard</a>
    <a class="nav-item" href="submit.php"><i data-lucide="plus-circle" width="18" height="18"></i> File Complaint</a>
    <a class="nav-item active" href="my_complaints.php"><i data-lucide="file-text" width="18" height="18"></i> My Complaints</a>
    <div class="nav-section">Account</div>
    <a class="nav-item" href="profile.php"><i data-lucide="user" width="18" height="18"></i> My Profile</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
      <div class="user-info"><div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div><div class="user-email">Tenant</div></div>
      <button class="logout-btn" onclick="logout()" title="Log out"><i data-lucide="log-out" width="20" height="20"></i></button>
    </div>
  </div>
</aside>

<div class="main-content">
  <div class="topbar">
    <div>
      <div class="page-title">My Complaints</div>
      <div class="page-sub">History of all your maintenance and service requests.</div>
    </div>
    <div class="topbar-actions">
      <a href="submit.php" class="btn btn-primary">
        <i data-lucide="plus" width="16" height="16"></i> New Complaint
      </a>
    </div>
  </div>
  
  <div class="content-area">
    <div class="card card-flush">
      <?php if (empty($complaints)): ?>
        <div class="empty-state empty-state-large">
          <div class="empty-icon">📝</div>
          <div class="empty-title">No complaints found</div>
          <div class="empty-sub">You have not submitted any complaints yet.</div>
          <a href="submit.php" class="btn btn-primary mt-20" style="display:inline-block;">File your first complaint</a>
        </div>
      <?php else: ?>
        <?php foreach ($complaints as $c): ?>
          <div class="complaint-card">
            <div class="c-icon">
              <i data-lucide="tool" width="24" height="24"></i>
            </div>
            <div class="c-main">
              <div class="c-header">
                <div>
                  <div class="c-title"><?= htmlspecialchars($c['complaint_title']) ?></div>
                  <div class="c-meta">
                    <span title="Submitted On"><i data-lucide="calendar" width="14" height="14"></i> <?= date('M d, Y', strtotime($c['submitted_at'])) ?></span>
                  </div>
                </div>
                <div>
                  <span class="badge badge-<?= $c['complaint_status'] ?>"><?= str_replace('_', ' ', strtoupper($c['complaint_status'])) ?></span>
                </div>
              </div>
              <div class="c-desc">
                <?= nl2br(htmlspecialchars($c['complaint_description'])) ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
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
