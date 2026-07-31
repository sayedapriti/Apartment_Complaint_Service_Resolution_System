<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin('admin');
$user = currentUser();
$db = getDB();

$tenants = $db->query("
    SELECT u.user_id, u.full_name, u.email, u.phone, u.created_at,
           rp.apartment_number,
           COUNT(c.complaint_id) AS total_complaints,
           SUM(CASE WHEN c.complaint_status = 'pending' THEN 1 ELSE 0 END) AS open_cnt,
           SUM(CASE WHEN c.complaint_status = 'resolved' THEN 1 ELSE 0 END) AS resolved_cnt
    FROM users u
    LEFT JOIN resident_profiles rp ON rp.user_id = u.user_id
    LEFT JOIN complaints c ON c.resident_id = rp.resident_id
    WHERE u.role = 'resident'
    GROUP BY u.user_id, u.full_name, u.email, u.phone, u.created_at, rp.apartment_number
    ORDER BY u.full_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Tenants — ResideHub Admin</title>
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
  <div style="padding:0 24px 12px;margin-top:8px;"><span class="tag">Admin Panel</span></div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <a class="nav-item" href="dashboard.php"><i data-lucide="layout-dashboard" width="18" height="18"></i> Dashboard</a>
    <a class="nav-item" href="complaints.php"><i data-lucide="file-text" width="18" height="18"></i> All Complaints</a>
    <a class="nav-item" href="assign.php"><i data-lucide="user-check" width="18" height="18"></i> Assign Staff</a>
    <div class="nav-section">Management</div>
    <a class="nav-item" href="staff.php"><i data-lucide="users" width="18" height="18"></i> Staff Management</a>
    <a class="nav-item active" href="tenants.php"><i data-lucide="home" width="18" height="18"></i> Tenants</a>
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
    <div>
      <div class="page-title">Tenant Directory</div>
      <div class="page-sub"><?= count($tenants) ?> registered tenant(s)</div>
    </div>
  </div>
  
  <div class="content-area">
    <div class="card" style="padding:0;">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Tenant</th>
              <th>Apartment</th>
              <th>Contact</th>
              <th>Total</th>
              <th>Open</th>
              <th>Resolved</th>
              <th>Joined</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tenants as $t): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <div
                      style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#6b5bff);display:grid;place-items:center;font-weight:700;color:#fff;flex-shrink:0;">
                      <?= strtoupper(substr($t['full_name'], 0, 1)) ?>
                    </div>
                    <span style="font-weight:600;color:var(--text);"><?= htmlspecialchars($t['full_name']) ?></span>
                  </div>
                </td>
                <td><span class="tag"><?= htmlspecialchars($t['apartment_number'] ?? '—') ?></span></td>
                <td>
                  <div style="font-size:13px;"><?= htmlspecialchars($t['email']) ?></div>
                  <div style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($t['phone'] ?? '—') ?></div>
                </td>
                <td><?= $t['total_complaints'] ?></td>
                <td><span style="color:var(--pending);font-weight:500;"><?= $t['open_cnt'] ?></span></td>
                <td><span style="color:var(--success);font-weight:500;"><?= $t['resolved_cnt'] ?></span></td>
                <td style="font-size:13px;color:var(--muted);"><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                <td>
                  <span class="badge badge-resolved">Active</span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
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