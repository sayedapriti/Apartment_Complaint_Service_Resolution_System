<?php
require_once '../includes/config.php';
requireLogin('admin');
$user = currentUser();
$db = getDB();

$preComplaint = $_GET['complaint'] ?? null;
$msg = '';
$msgType = '';

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $cid = (int)$_POST['complaint_id'];
    $sid = (int)$_POST['staff_id'];
    $assignerId = is_numeric($user['user_id']) ? (int)$user['user_id'] : 1; // Fallback for demo string ID
    
    if ($cid && $sid) {
        $db->prepare("UPDATE complaints SET assigned_staff_id=?, assigned_by=?, assigned_at=NOW(), complaint_status='assigned' WHERE complaint_id=?")->execute([$sid, $assignerId, $cid]);
        $msg = 'Staff assigned successfully!';
        $msgType = 'success';
    }
}

// Pending/assigned complaints
$complaints = $db->query("
  SELECT c.*, u.full_name AS resident_name, rp.apartment_number,
         c.assigned_staff_id AS current_staff_id, su.full_name AS current_staff
  FROM complaints c
  JOIN resident_profiles rp ON c.resident_id=rp.resident_id
  JOIN users u ON rp.user_id=u.user_id
  LEFT JOIN service_staff_profiles ssp ON c.assigned_staff_id=ssp.staff_id
  LEFT JOIN users su ON ssp.user_id=su.user_id
  WHERE c.complaint_status IN ('pending','assigned')
  ORDER BY c.submitted_at DESC
")->fetchAll();

// Staff list
$staff = $db->query("
  SELECT ssp.*, u.full_name, u.email,
         (SELECT COUNT(*) FROM complaints c2 WHERE c2.assigned_staff_id=ssp.staff_id AND c2.complaint_status IN ('assigned','in_progress')) AS active_complaints
  FROM service_staff_profiles ssp
  JOIN users u ON ssp.user_id=u.user_id
  ORDER BY ssp.available_status ASC, u.full_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Assign Staff — ResideHub Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
<style>
.assign-grid { display:grid;grid-template-columns:1fr 380px;gap:24px; }
.staff-list { display:flex;flex-direction:column;gap:10px; }
.staff-card {
  background:var(--surface2);border:2px solid var(--border);border-radius:10px;
  padding:14px 16px;cursor:pointer;transition:all .15s;
  display:flex;align-items:center;gap:14px;
}
.staff-card:hover,.staff-card.selected { border-color:var(--accent); background:rgba(79,124,255,.08); }
.staff-avatar {
  width:40px;height:40px;border-radius:50%;
  background:linear-gradient(135deg,var(--accent),#6b5bff);
  display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:15px;flex-shrink:0;
}
.staff-info { flex:1; }
.staff-name { font-weight:600;font-size:14px; }
.staff-meta { font-size:12px;color:var(--muted);margin-top:2px; }
.complaint-select-btn {
  background:var(--surface2);border:2px solid var(--border);border-radius:10px;
  padding:12px 16px;cursor:pointer;transition:all .15s;text-align:left;width:100%;
  color:var(--text);font-family:var(--font-body);margin-bottom:8px;
}
.complaint-select-btn:hover,.complaint-select-btn.selected{border-color:var(--accent);background:rgba(79,124,255,.08);}
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
    <a class="nav-item active" href="assign.php"><i data-lucide="user-check" width="18" height="18"></i> Assign Staff</a>
    <div class="nav-section">Management</div>
    <a class="nav-item" href="staff.php"><i data-lucide="users" width="18" height="18"></i> Staff Management</a>
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
    <div>
      <div class="page-title">Assign Staff to Complaints</div>
      <div class="page-sub">Select a complaint and assign an available staff member.</div>
    </div>
  </div>
  <div class="content-area">
    <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>" style="margin-bottom:20px;"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="assign" value="1">
      <div class="assign-grid">
        <!-- LEFT: Complaints -->
        <div>
          <div class="card">
            <div class="card-header"><div class="card-title">Pending / Assigned Complaints</div></div>
            <div class="card-body">
              <?php if (empty($complaints)): ?>
              <div class="empty-state"><div class="empty-icon">🎉</div><div class="empty-title">All caught up!</div><div class="empty-sub">No pending complaints to assign.</div></div>
              <?php else: ?>
              <?php foreach ($complaints as $c): ?>
              <button type="button" class="complaint-select-btn <?= $preComplaint==$c['complaint_id']?'selected':'' ?>" onclick="selectComplaint(<?= $c['complaint_id'] ?>,this)">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                  <span class="fw-600" style="font-size:14px;"><?= htmlspecialchars($c['complaint_title']) ?></span>
                  <span class="badge badge-<?= $c['complaint_status'] ?>"><?= str_replace('_',' ',$c['complaint_status']) ?></span>
                </div>
                <div style="font-size:12px;color:var(--muted);">
                  <?= htmlspecialchars($c['category_name']) ?> · <?= htmlspecialchars($c['resident_name']) ?> · Apt <?= htmlspecialchars($c['apartment_number']) ?>
                  <?php if ($c['current_staff']): ?>· <em>Currently: <?= htmlspecialchars($c['current_staff']) ?></em><?php endif; ?>
                </div>
              </button>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- RIGHT: Staff + Submit -->
        <div>
          <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><div class="card-title">Available Staff</div></div>
            <div class="card-body">
              <input type="hidden" name="complaint_id" id="selectedComplaint" value="<?= $preComplaint ?? '' ?>">
              <input type="hidden" name="staff_id" id="selectedStaff" value="">
              <div class="staff-list">
                <?php foreach ($staff as $s): ?>
                <div class="staff-card <?= '' ?>" onclick="selectStaff(<?= $s['staff_id'] ?>,this)">
                  <div class="staff-avatar"><?= strtoupper(substr($s['full_name'],0,1)) ?></div>
                  <div class="staff-info">
                    <div class="staff-name"><?= htmlspecialchars($s['full_name']) ?></div>
                    <div class="staff-meta">
                      <?= htmlspecialchars($s['staff_type']) ?> ·
                      <span class="badge badge-<?= $s['available_status'] ?>" style="font-size:10px;padding:2px 7px;"><?= str_replace('_',' ',$s['available_status']) ?></span>
                    </div>
                    <div class="staff-meta" style="margin-top:2px;"><?= $s['active_complaints'] ?> active complaint<?= $s['active_complaints']!=1?'s':'' ?></div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div id="assignSummary" style="display:none;background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:16px;font-size:13px;">
            <strong>Assignment Preview</strong>
            <div id="summaryText" style="color:var(--muted);margin-top:6px;"></div>
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;" id="assignBtn" disabled>
            🎯 Confirm Assignment
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
let selComplaint = <?= $preComplaint ?? 'null' ?>;
let selStaff = null;
const complaints = <?= json_encode(array_column($complaints, null, 'complaint_id')) ?>;
const staffMap = <?= json_encode(array_column($staff, null, 'staff_id')) ?>;

function selectComplaint(id, el) {
  document.querySelectorAll('.complaint-select-btn').forEach(b=>b.classList.remove('selected'));
  el.classList.add('selected');
  selComplaint = id;
  document.getElementById('selectedComplaint').value = id;
  updateSummary();
}

function selectStaff(id, el) {
  document.querySelectorAll('.staff-card').forEach(c=>c.classList.remove('selected'));
  el.classList.add('selected');
  selStaff = id;
  document.getElementById('selectedStaff').value = id;
  updateSummary();
}

function updateSummary() {
  const btn = document.getElementById('assignBtn');
  const sumDiv = document.getElementById('assignSummary');
  if (selComplaint && selStaff) {
    btn.disabled = false;
    const c = complaints[selComplaint];
    const s = staffMap[selStaff];
    if (c && s) {
      sumDiv.style.display = 'block';
      document.getElementById('summaryText').innerHTML =
        `Complaint <strong>#${String(selComplaint).padStart(4,'0')} - ${c.complaint_title}</strong><br>` +
        `will be assigned to <strong>${s.full_name}</strong> (${s.staff_type})`;
    }
  } else {
    btn.disabled = true;
    sumDiv.style.display = 'none';
  }
}
updateSummary();

async function logout() {
  await fetch('../api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})});
  window.location.href='../index.php';
}
</script>
<script>lucide.createIcons();</script>
</body>
</html>
