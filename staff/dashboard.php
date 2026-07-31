<?php
require_once __DIR__ . '/../includes/config.php';
$user = requireLogin('staff');

$db  = getDB();
$sid = $user['profile_id'] ?? null;

if (!$sid) {
    header('Location: ' . APP_URL . '/staff/profile.php?setup=1');
    exit;
}

$stats = [
    'assigned'    => 0,
    'in_progress' => 0,
    'resolved'    => 0,
    'total'       => 0,
];

$activeTasks = [];

// Fetch stats
$s = $db->prepare("SELECT
    SUM(CASE WHEN c.complaint_status='assigned' THEN 1 ELSE 0 END) AS assigned,
    SUM(CASE WHEN c.complaint_status='in_progress' THEN 1 ELSE 0 END) AS in_progress,
    SUM(CASE WHEN c.complaint_status='resolved' THEN 1 ELSE 0 END) AS resolved,
    COUNT(*) AS total
    FROM complaints c
    WHERE c.assigned_staff_id=?");
$s->execute([$sid]);
$res = $s->fetch();
if ($res) {
    $stats = [
        'assigned'    => (int)($res['assigned'] ?? 0),
        'in_progress' => (int)($res['in_progress'] ?? 0),
        'resolved'    => (int)($res['resolved'] ?? 0),
        'total'       => (int)($res['total'] ?? 0),
    ];
}

// Fetch active tasks
$tasks = $db->prepare("
    SELECT c.complaint_id AS id, c.complaint_title AS title, c.complaint_description AS description,
           c.complaint_status AS status, c.submitted_at AS created_at,
           c.category_name, u.full_name AS tenant_name, rp.apartment_number AS apartment
    FROM complaints c
    JOIN resident_profiles rp ON c.resident_id=rp.resident_id
    JOIN users u ON rp.user_id=u.user_id
    WHERE c.assigned_staff_id=? AND c.complaint_status IN ('assigned','in_progress')
    ORDER BY c.submitted_at DESC
");
$tasks->execute([$sid]);
$activeTasks = $tasks->fetchAll();

function statusBadgeClass(string $status): string {
    return 'badge-' . htmlspecialchars($status);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Staff Dashboard — ResideHub</title>
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
  <div style="padding:0 24px 12px;margin-top:8px;"><span class="tag">Staff Portal</span></div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <a class="nav-item active" href="dashboard.php"><i data-lucide="layout-dashboard" width="18" height="18"></i> Dashboard</a>
    <a class="nav-item" href="my_tasks.php"><i data-lucide="clipboard-list" width="18" height="18"></i> My Tasks</a>
    <div class="nav-section">Account</div>
    <a class="nav-item" href="profile.php"><i data-lucide="user" width="18" height="18"></i> My Profile</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
      <div class="user-info"><div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div><div class="user-email">Service Staff</div></div>
      <button class="logout-btn" onclick="logout()" title="Log out"><i data-lucide="log-out" width="20" height="20"></i></button>
    </div>
  </div>
</aside>

<div class="main-content">
  <div class="topbar">
    <div>
      <div class="page-title">My Dashboard</div>
      <div class="page-sub">Good day, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>! Here are your assigned tasks.</div>
    </div>
    <div class="topbar-actions">
      <a href="my_tasks.php" class="btn btn-primary">
        <i data-lucide="clipboard-list" width="16" height="16"></i> All My Tasks
      </a>
    </div>
  </div>
  
  <div class="content-area">
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(251,191,36,0.15);color:#fbbf24;"><i data-lucide="clipboard-list" width="20" height="20"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $stats['total'] ?></div>
          <div class="stat-label">Total Assigned</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(99,102,241,0.15);color:#818cf8;"><i data-lucide="alert-circle" width="20" height="20"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $stats['assigned'] ?></div>
          <div class="stat-label">New Assigned</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(6,182,212,0.15);color:#22d3ee;"><i data-lucide="wrench" width="20" height="20"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $stats['in_progress'] ?></div>
          <div class="stat-label">In Progress</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(16,185,129,0.15);color:#34d399;"><i data-lucide="check" width="20" height="20"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $stats['resolved'] ?></div>
          <div class="stat-label">Resolved</div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-top:24px;padding:0;">
      <div class="card-header" style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <h3 style="font-size:16px;font-weight:600;margin:0;">Active Tasks</h3>
        <span style="font-size:13px;color:var(--muted);"><?= count($activeTasks) ?> pending</span>
      </div>

      <?php if (empty($activeTasks)): ?>
      <div class="empty-state" style="padding:60px 20px;">
        <div class="empty-icon">🎉</div>
        <div class="empty-title">All caught up!</div>
        <div class="empty-sub">You have no active tasks right now.</div>
      </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Ticket #</th>
              <th>Complaint</th>
              <th>Category</th>
              <th>Tenant</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($activeTasks as $task): ?>
            <tr>
              <td style="font-family:monospace;color:var(--muted);">#<?= str_pad($task['id'], 4, '0', STR_PAD_LEFT) ?></td>
              <td>
                <div style="font-weight:500;color:var(--text);"><?= htmlspecialchars($task['title']) ?></div>
              </td>
              <td>
                <span style="display:inline-flex;align-items:center;gap:6px;background:var(--surface2);padding:4px 10px;border-radius:6px;font-size:12px;border:1px solid var(--border);">
                  <?= htmlspecialchars($task['category_name']) ?>
                </span>
              </td>
              <td>
                <div style="font-size:13px;font-weight:500;"><?= htmlspecialchars($task['tenant_name']) ?></div>
                <div style="font-size:12px;color:var(--muted);">Apt: <?= htmlspecialchars($task['apartment']) ?></div>
              </td>
              <td><span class="badge badge-<?= $task['status'] ?>"><?= str_replace('_',' ',$task['status']) ?></span></td>
              <td style="font-size:13px;color:var(--muted);"><?= date('M d, Y', strtotime($task['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Status Change Modal -->
<div class="modal-overlay" id="status-modal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Update Complaint Status</span>
      <button class="modal-close" onclick="closeModal('status-modal')">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="sc_complaint_id">
      <div class="form-group">
        <label class="form-label">New Status</label>
        <select class="form-control" id="sc_status"></select>
      </div>
      <div class="form-group">
        <label class="form-label">Remark / Notes</label>
        <textarea class="form-control" id="sc_remark" placeholder="Describe what was done or why…"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('status-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="submitStatusChange()">Update Status</button>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
async function logout() {
  await fetch('../api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})});
  window.location.href='../index.php';
}
</script>
<script>lucide.createIcons();</script>
</body>
</html>
