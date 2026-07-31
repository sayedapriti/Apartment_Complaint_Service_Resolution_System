<?php
require_once '../includes/config.php';
requireLogin('admin');
$user = currentUser();
$db = getDB();
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $db->prepare("UPDATE service_staff_profiles SET available_status=? WHERE staff_id=?")->execute([$_POST['status'],$_POST['staff_id']]);
        $msg='Status updated.'; $msgType='success';
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM users WHERE user_id=(SELECT user_id FROM service_staff_profiles WHERE staff_id=?)")->execute([$_POST['staff_id']]);
        $msg='Staff member removed.'; $msgType='success';
    }
}

$staff = $db->query("
  SELECT ssp.*, u.full_name, u.email, u.phone, u.created_at AS joined_at,
    (SELECT COUNT(*) FROM complaints c WHERE c.assigned_staff_id=ssp.staff_id) AS total_assigned,
    (SELECT COUNT(*) FROM resolution_details rd WHERE rd.staff_id=ssp.staff_id) AS total_resolved
  FROM service_staff_profiles ssp
  JOIN users u ON ssp.user_id=u.user_id
  ORDER BY u.full_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Staff Management — ResideHub Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
<style>
.staff-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px; }
.staff-card-big {
  background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
  padding:24px;transition:border-color .2s;
}
.staff-card-big:hover { border-color:var(--border2); }
.scard-top { display:flex;align-items:center;gap:14px;margin-bottom:16px; }
.scard-avatar {
  width:52px;height:52px;border-radius:50%;
  background:linear-gradient(135deg,var(--accent),#6b5bff);
  display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:20px;flex-shrink:0;
}
.scard-nums { display:grid;grid-template-columns:1fr 1fr;gap:10px;border-top:1px solid var(--border);padding-top:14px;margin-top:14px; }
.scard-num { text-align:center; }
.scard-num-val { font-family:var(--font-display);font-size:22px;font-weight:800;color:var(--accent); }
.scard-num-label { font-size:11px;color:var(--muted); }
</style>
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
  <div style="padding:0 24px 12px;margin-top:8px;"><span class="tag">Admin Panel</span></div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <a class="nav-item" href="dashboard.php"><i data-lucide="layout-dashboard" width="18" height="18"></i> Dashboard</a>
    <a class="nav-item" href="complaints.php"><i data-lucide="file-text" width="18" height="18"></i> All Complaints</a>
    <a class="nav-item" href="assign.php"><i data-lucide="user-check" width="18" height="18"></i> Assign Staff</a>
    <div class="nav-section">Management</div>
    <a class="nav-item active" href="staff.php"><i data-lucide="users" width="18" height="18"></i> Staff Management</a>
    <a class="nav-item" href="tenants.php"><i data-lucide="home" width="18" height="18"></i> Tenants</a>
    <a class="nav-item" href="categories.php"><i data-lucide="tag" width="18" height="18"></i> Categories</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
      <div class="user-info"><div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div><div class="user-email">Administrator</div></div>
      <button class="logout-btn" onclick="logout()" title="Log out"><i data-lucide="log-out" width="20" height="20"></i></button>
    </div>
  </div>
</aside>

<div class="main-content">
  <div class="topbar">
    <div><div class="page-title">Staff Management</div><div class="page-sub"><?= count($staff) ?> service staff registered</div></div>
  </div>
  <div class="content-area">
    <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>" style="margin-bottom:20px;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <?php if (empty($staff)): ?>
    <div class="empty-state"><div class="empty-icon">👷</div><div class="empty-title">No staff registered</div><div class="empty-sub">Staff can register via the main login page.</div></div>
    <?php else: ?>
    <div class="staff-grid">
      <?php foreach ($staff as $s): ?>
      <div class="staff-card-big">
        <div class="scard-top">
          <div class="scard-avatar"><?= strtoupper(substr($s['full_name'],0,1)) ?></div>
          <div>
            <div class="fw-600" style="font-size:15px;"><?= htmlspecialchars($s['full_name']) ?></div>
            <div class="text-muted text-sm"><?= htmlspecialchars($s['staff_type']) ?></div>
            <div style="margin-top:4px;"><span class="badge badge-<?= $s['available_status'] ?>"><?= str_replace('_',' ',$s['available_status']) ?></span></div>
          </div>
        </div>

        <div class="text-sm text-muted" style="margin-bottom:4px;">📧 <?= htmlspecialchars($s['email']) ?></div>
        <?php if($s['phone']): ?>
        <div class="text-sm text-muted" style="margin-bottom:4px;">📱 <?= htmlspecialchars($s['phone']) ?></div>
        <?php endif; ?>
        <div class="text-sm text-muted">📅 Joined <?= date('M Y', strtotime($s['joined_at'])) ?></div>

        <div class="scard-nums">
          <div class="scard-num"><div class="scard-num-val"><?= $s['total_assigned'] ?></div><div class="scard-num-label">Assigned</div></div>
          <div class="scard-num"><div class="scard-num-val" style="color:var(--success)"><?= $s['total_resolved'] ?></div><div class="scard-num-label">Resolved</div></div>
        </div>

        <div style="display:flex;gap:8px;margin-top:16px;border-top:1px solid var(--border);padding-top:14px;">
          <form method="POST" style="flex:1;">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="staff_id" value="<?= $s['staff_id'] ?>">
            <select name="status" class="form-control" style="font-size:12px;padding:7px 10px;" onchange="this.form.submit()">
              <option value="available" <?= $s['available_status']==='available'?'selected':'' ?>>Available</option>
              <option value="busy" <?= $s['available_status']==='busy'?'selected':'' ?>>Busy</option>
              <option value="off_duty" <?= $s['available_status']==='off_duty'?'selected':'' ?>>Off Duty</option>
            </select>
          </form>
          <form method="POST" onsubmit="return confirm('Remove this staff member?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="staff_id" value="<?= $s['staff_id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">🗑</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
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
