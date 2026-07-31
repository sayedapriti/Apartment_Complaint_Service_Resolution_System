<?php
require_once '../includes/config.php';
requireLogin('admin');
$user = currentUser();
$db = getDB();

$viewId = $_GET['view'] ?? null;
$filterStatus = $_GET['status'] ?? '';
$filterCat = $_GET['cat'] ?? '';

// Fetch all categories for filter
$categories = $db->query("SELECT category_name FROM complaint_categories ORDER BY category_name")->fetchAll();

// Build complaint query
$where = '1=1';
$params = [];
if ($filterStatus) {
  $where .= " AND c.complaint_status = ?";
  $params[] = $filterStatus;
}
if ($filterCat) {
  $where .= " AND c.category_name = ?";
  $params[] = $filterCat;
}

$stmt = $db->prepare("
  SELECT c.*,
         u.full_name AS resident_name, rp.apartment_number,
         su.full_name AS staff_name, ssp.staff_type
  FROM complaints c
  JOIN resident_profiles rp ON c.resident_id = rp.resident_id
  JOIN users u ON rp.user_id = u.user_id
  LEFT JOIN service_staff_profiles ssp ON c.assigned_staff_id = ssp.staff_id
  LEFT JOIN users su ON ssp.user_id = su.user_id
  WHERE $where
  ORDER BY c.submitted_at DESC
");
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Single complaint view
$viewComplaint = null;
$timeline = [];
$resolution = null;
if ($viewId) {
  $stmt = $db->prepare("
    SELECT c.*,
           u.full_name AS resident_name, rp.apartment_number, rp.contact_address, u.phone, u.email,
           su.full_name AS staff_name, ssp.staff_type, su.phone AS staff_phone
    FROM complaints c
    JOIN resident_profiles rp ON c.resident_id = rp.resident_id
    JOIN users u ON rp.user_id = u.user_id
    LEFT JOIN service_staff_profiles ssp ON c.assigned_staff_id = ssp.staff_id
    LEFT JOIN users su ON ssp.user_id = su.user_id
    WHERE c.complaint_id = ?
  ");
  $stmt->execute([$viewId]);
  $viewComplaint = $stmt->fetch();

  $ts = $db->prepare("SELECT c.complaint_status AS status_id, c.progress_note, u.full_name AS updated_by_name FROM complaints c LEFT JOIN users u ON c.updated_by = u.user_id WHERE c.complaint_id = ?");
  $ts->execute([$viewId]);
  $timeline = $ts->fetchAll();

  $rs = $db->prepare("SELECT rd.*, u.full_name AS staff_name FROM resolution_details rd JOIN service_staff_profiles ssp ON rd.staff_id = ssp.staff_id JOIN users u ON ssp.user_id = u.user_id WHERE rd.complaint_id = ?");
  $rs->execute([$viewId]);
  $resolution = $rs->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Complaints — ResideHub Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
  <style>
    .filter-bar {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;
    }

    .filter-bar select {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 8px;
      color: var(--text);
      font-family: var(--font-body);
      font-size: 13px;
      padding: 9px 14px;
      outline: none;
      cursor: pointer;
    }

    .detail-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-bottom: 20px;
    }

    .detail-item {}

    .detail-item .dl {
      font-size: 11px;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .8px;
      margin-bottom: 4px;
    }

    .detail-item .dv {
      font-size: 14px;
      font-weight: 500;
    }
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
      <a class="nav-item" href="dashboard.php"><i data-lucide="layout-dashboard" width="18" height="18"></i>
        Dashboard</a>
      <a class="nav-item active" href="complaints.php"><i data-lucide="file-text" width="18" height="18"></i> All
        Complaints</a>
      <a class="nav-item" href="assign.php"><i data-lucide="user-check" width="18" height="18"></i> Assign Staff</a>
      <div class="nav-section">Management</div>
      <a class="nav-item" href="staff.php"><i data-lucide="users" width="18" height="18"></i> Staff Management</a>
      <a class="nav-item" href="tenants.php"><i data-lucide="home" width="18" height="18"></i> Tenants</a>
      <a class="nav-item" href="categories.php"><i data-lucide="tag" width="18" height="18"></i> Categories</a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
          <div class="user-email">Administrator</div>
        </div>
        <button class="logout-btn" onclick="logout()" title="Log out"><i data-lucide="log-out" width="20"
            height="20"></i></button>
      </div>
    </div>
  </aside>

  <div class="main-content">
    <div class="topbar">
      <div>
        <div class="page-title">All Complaints</div>
        <div class="page-sub"><?= count($complaints) ?> total complaints</div>
      </div>
    </div>
    <div class="content-area">

      <!-- FILTERS -->
      <div class="card" style="margin-bottom:24px;">
        <div class="card-body" style="padding:16px 24px;">
          <form method="GET" class="filter-bar">
            <select name="status" onchange="this.form.submit()">
              <option value="">All Statuses</option>
              <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
              <option value="assigned" <?= $filterStatus === 'assigned' ? 'selected' : '' ?>>Assigned</option>
              <option value="in_progress" <?= $filterStatus === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
              <option value="resolved" <?= $filterStatus === 'resolved' ? 'selected' : '' ?>>Resolved</option>
              <option value="closed" <?= $filterStatus === 'closed' ? 'selected' : '' ?>>Closed</option>
            </select>
            <select name="cat" onchange="this.form.submit()">
              <option value="">All Categories</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['category_name']) ?>"
                  <?= $filterCat == $cat['category_name'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['category_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if ($filterStatus || $filterCat): ?>
              <a href="complaints.php" class="btn btn-secondary btn-sm">✕ Clear</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="table-wrap">
          <?php if (empty($complaints)): ?>
            <div class="empty-state">
              <div class="empty-icon">📭</div>
              <div class="empty-title">No complaints found</div>
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
                <?php foreach ($complaints as $c): ?>
                  <tr>
                    <td class="text-muted text-sm">#<?= str_pad($c['complaint_id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td class="fw-600"><?= htmlspecialchars($c['resident_name']) ?></td>
                    <td>
                      <span class="category-badge">
                        <i data-lucide="tag" width="12" height="12"></i>
                        <?= htmlspecialchars($c['category_name'] ?? 'Uncategorized') ?>
                      </span>
                    </td>
                    <td><span
                        class="badge badge-<?= $c['complaint_status'] ?>"><?= str_replace('_', ' ', $c['complaint_status']) ?></span>
                    </td>
                    <td class="text-muted text-sm"><?= date('M d, Y', strtotime($c['submitted_at'])) ?></td>
                    <td style="display:flex;gap:6px;justify-content:flex-end;">
                      <a href="?view=<?= $c['complaint_id'] ?><?= $filterStatus ? "&status=$filterStatus" : '' ?><?= $filterCat ? "&cat=$filterCat" : '' ?>"
                        class="btn btn-secondary btn-sm">View</a>
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
  </div>

  <!-- COMPLAINT DETAIL MODAL -->
  <?php if ($viewComplaint): ?>
    <div class="modal-overlay open" id="detailModal">
      <div class="modal" style="max-width:660px;">
        <div class="modal-header">
          <div>
            <div class="modal-title"><?= htmlspecialchars($viewComplaint['complaint_title']) ?></div>
            <span class="badge badge-<?= $viewComplaint['complaint_status'] ?>" style="margin-top:6px">
              <?= str_replace('_', ' ', $viewComplaint['complaint_status']) ?>
            </span>
          </div>
          <button class="modal-close" onclick="window.location.href='complaints.php'">×</button>
        </div>
        <div class="modal-body">
          <div class="detail-grid">
            <div class="detail-item">
              <div class="dl">Complaint ID</div>
              <div class="dv">#<?= str_pad($viewComplaint['complaint_id'], 4, '0', STR_PAD_LEFT) ?></div>
            </div>
            <div class="detail-item">
              <div class="dl">Submitted</div>
              <div class="dv"><?= date('M d, Y H:i', strtotime($viewComplaint['submitted_at'])) ?></div>
            </div>
            <div class="detail-item">
              <div class="dl">Tenant</div>
              <div class="dv"><?= htmlspecialchars($viewComplaint['resident_name']) ?></div>
            </div>
            <div class="detail-item">
              <div class="dl">Apartment</div>
              <div class="dv"><?= htmlspecialchars($viewComplaint['apartment_number']) ?></div>
            </div>
            <div class="detail-item">
              <div class="dl">Category</div>
              <div class="dv"><?= htmlspecialchars($viewComplaint['category_name']) ?></div>
            </div>
            <?php if ($viewComplaint['staff_name']): ?>
              <div class="detail-item">
                <div class="dl">Assigned Staff</div>
                <div class="dv"><?= htmlspecialchars($viewComplaint['staff_name']) ?>
                  (<?= htmlspecialchars($viewComplaint['staff_type']) ?>)</div>
              </div>
              <div class="detail-item">
                <div class="dl">Assigned At</div>
                <div class="dv"><?= date('M d, Y H:i', strtotime($viewComplaint['assigned_at'])) ?></div>
              </div>
            <?php endif; ?>
          </div>

          <div style="margin-bottom:20px;">
            <div class="dl"
              style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;">
              Description</div>
            <div
              style="background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:14px;font-size:14px;line-height:1.6;">
              <?= nl2br(htmlspecialchars($viewComplaint['complaint_description'])) ?>
            </div>
          </div>

          <?php if ($resolution): ?>
            <div
              style="margin-bottom:20px;background:rgba(52,211,153,0.06);border:1px solid rgba(52,211,153,0.2);border-radius:10px;padding:16px;">
              <div
                style="font-size:12px;color:var(--success);font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;">
                ✅ Resolution</div>
              <div style="font-size:14px;margin-bottom:6px;">
                <?= nl2br(htmlspecialchars($resolution['resolution_description'])) ?></div>
              <div class="text-muted text-sm">Resolved by <?= htmlspecialchars($resolution['staff_name']) ?> on
                <?= date('M d, Y H:i', strtotime($resolution['resolved_at'])) ?></div>
            </div>
          <?php endif; ?>

          <?php if (!empty($timeline)): ?>
            <div>
              <div class="dl"
                style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;">
                Progress Timeline</div>
              <div class="timeline">
                <?php foreach ($timeline as $t): ?>
                  <div class="timeline-item">
                    <div class="timeline-date">Latest Update · <?= htmlspecialchars($t['updated_by_name'] ?? 'System') ?>
                    </div>
                    <div class="timeline-text">
                      <span class="badge badge-<?= $t['status_id'] ?>"><?= str_replace('_', ' ', $t['status_id']) ?></span>
                      <?php if ($t['progress_note']): ?> — <?= htmlspecialchars($t['progress_note']) ?><?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <div style="display:flex;gap:12px;margin-top:24px;">
            <?php if ($viewComplaint['complaint_status'] === 'pending'): ?>
              <a href="assign.php?complaint=<?= $viewComplaint['complaint_id'] ?>" class="btn btn-primary">🎯 Assign
                Staff</a>
            <?php endif; ?>
            <button onclick="window.location.href='complaints.php'" class="btn btn-secondary">Close</button>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <script>
    async function logout() {
      await fetch('../api/auth.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'logout' }) });
      window.location.href = '../index.php';
    }
  </script>
  <script>lucide.createIcons();</script>
</body>

</html>