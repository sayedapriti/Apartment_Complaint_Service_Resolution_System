<?php
require_once __DIR__ . '/../includes/config.php';
$user = requireLogin('staff');

$db  = getDB();
$sid = $user['profile_id'];

if (!$sid) {
    header('Location: ' . APP_URL . '/staff/profile.php?setup=1');
    exit;
}

$statusFilter = $_GET['status'] ?? '';
$where = [];
$params = [];

if ($sid) {
    $where[] = 'c.assigned_staff_id = ?';
    $params[] = $sid;
}
if ($statusFilter) {
    $where[] = 'c.complaint_status = ?';
    $params[] = $statusFilter;
}
$whereStr = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$allTasks = [];
if ($sid) {
    $tasks = $db->prepare("
        SELECT c.complaint_id AS id, c.complaint_title AS title, c.complaint_description AS description,
               c.complaint_status AS status, c.submitted_at AS created_at,
               c.category_name, u.full_name AS tenant_name, rp.apartment_number AS apartment
        FROM complaints c
        JOIN resident_profiles rp ON c.resident_id=rp.resident_id
        JOIN users u ON rp.user_id=u.user_id
        $whereStr
        ORDER BY c.submitted_at DESC
    ");
    $tasks->execute($params);
    $allTasks = $tasks->fetchAll();
}

function statusBadgeClass(string $status): string {
    return 'badge-' . htmlspecialchars($status);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Tasks — ResideHub</title>
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
    <a class="nav-item" href="dashboard.php"><i data-lucide="layout-dashboard" width="18" height="18"></i> Dashboard</a>
    <a class="nav-item active" href="my_tasks.php"><i data-lucide="clipboard-list" width="18" height="18"></i> My Tasks</a>
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
      <div class="page-title">My Assigned Tasks</div>
      <div class="page-sub"><?= count($allTasks) ?> task(s) found</div>
    </div>
  </div>
  
  <div class="content-area">
    <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
      <?php
      $statuses = [''=> 'All', 'assigned'=>'Assigned','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed'];
      foreach ($statuses as $val => $label):
      ?>
      <a href="?status=<?= $val ?>" class="btn <?= $statusFilter===$val?'btn-primary':'btn-secondary' ?> btn-sm">
        <?= $label ?>
      </a>
      <?php endforeach; ?>
    </div>

    <div class="card" style="padding:0;">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Ticket</th>
              <th>Title</th>
              <th>Category</th>
              <th>Tenant</th>
              <th>Status</th>
              <th>Submitted</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($allTasks)): ?>
            <tr><td colspan="7">
              <div class="empty-state">
                <div class="empty-icon"><i data-lucide="inbox" width="32" height="32" style="color:var(--text-muted);"></i></div>
                <div class="empty-title">No tasks found</div>
              </div>
            </td></tr>
            <?php else: foreach ($allTasks as $task): ?>
            <tr>
              <td style="font-family:monospace;color:var(--muted);">#<?= str_pad($task['id'], 4, '0', STR_PAD_LEFT) ?></td>
              <td style="max-width:200px;">
                <div style="font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($task['title']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars(substr($task['description'],0,60)) ?>…</div>
              </td>
              <td>
                <span style="display:inline-flex;align-items:center;gap:6px;background:var(--surface2);padding:4px 10px;border-radius:6px;font-size:12px;border:1px solid var(--border);">
                  <?= htmlspecialchars($task['category_name']) ?>
                </span>
              </td>
              <td>
                <div style="font-size:13px;font-weight:500;"><?= htmlspecialchars($task['tenant_name']) ?></div>
                <div style="font-size:12px;color:var(--muted);">Apt: <?= htmlspecialchars($task['apartment']??'') ?></div>
              </td>
              <td><span class="badge <?= statusBadgeClass($task['status']) ?>"><?= str_replace('_',' ',$task['status']) ?></span></td>
              <td style="font-size:0.78rem;white-space:nowrap;"><?= date('d M Y', strtotime($task['created_at'])) ?></td>
              <td>
                <button class="btn btn-primary btn-sm" onclick="promptStatusChange(<?= $task['id'] ?>,'<?= $task['status'] ?>')">
                  <i data-lucide="refresh-cw" width="13" height="13"></i> Update
                </button>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
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
        <label class="form-label profile-label">New Status</label>
        <select class="form-control" id="sc_status"></select>
      </div>
      <div class="form-group">
        <label class="form-label profile-label">Remark / Notes</label>
        <textarea class="form-control" id="sc_remark" placeholder="Describe what was done or why…"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('status-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="submitStatusChange()">Update Status</button>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/app.js?v=<?= time() ?>"></script>
<script>
async function logout() {
  await fetch('../api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})});
  window.location.href='../index.php';
}
</script>
<div id="toast-container"></div>
<script>lucide.createIcons();</script>
</body>
</html>
