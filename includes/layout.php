<?php
// ============================================================
// Shared layout helper
// ============================================================

function renderPageStart(string $title, string $activePage = '', string $subtitle = '', string $actionsHtml = ''): void {
    $user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?> – ResideHub</title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/dashboard.css">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
</head>
<body>
<div id="toast-container"></div>

<div class="app-layout">
  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-icon">
        <i data-lucide="building-2" width="20" height="20" style="color:#fff;"></i>
      </div>
      <div class="sidebar-name">Reside<span>Hub</span></div>
    </div>

    <div style="padding:0 24px 12px;margin-top:8px;">
      <?php if ($user && $user['role'] === 'admin'): ?>
        <span class="tag">Admin Panel</span>
      <?php elseif ($user && $user['role'] === 'staff'): ?>
        <span class="tag">Staff Panel</span>
      <?php else: ?>
        <span class="tag">Tenant Portal</span>
      <?php endif; ?>
    </div>

    <nav class="sidebar-nav">
<?php if ($user && $user['role'] === 'admin'): ?>
      <div class="nav-section">Main</div>
      <a href="<?= APP_URL ?>/admin/dashboard.php"
         class="nav-item <?= $activePage==='dashboard'?'active':'' ?>">
        <i data-lucide="layout-dashboard" width="18" height="18"></i> Dashboard
      </a>
      <a href="<?= APP_URL ?>/admin/complaints.php"
         class="nav-item <?= $activePage==='complaints'?'active':'' ?>">
        <i data-lucide="file-text" width="18" height="18"></i> All Complaints
      </a>
      <a href="<?= APP_URL ?>/admin/staff.php"
         class="nav-item <?= $activePage==='assign_staff'?'active':'' ?>">
        <i data-lucide="user-plus" width="18" height="18"></i> Assign Staff
      </a>

      <div class="nav-section">Management</div>
      <a href="<?= APP_URL ?>/admin/staff.php"
         class="nav-item <?= $activePage==='staff'?'active':'' ?>">
        <i data-lucide="users" width="18" height="18"></i> Staff Management
      </a>
      <a href="<?= APP_URL ?>/admin/tenants.php"
         class="nav-item <?= $activePage==='tenants'?'active':'' ?>">
        <i data-lucide="home" width="18" height="18"></i> Tenants
      </a>
      <a href="<?= APP_URL ?>/admin/categories.php"
         class="nav-item <?= $activePage==='categories'?'active':'' ?>">
        <i data-lucide="tag" width="18" height="18"></i> Categories
      </a>

<?php elseif ($user && $user['role'] === 'staff'): ?>
      <div class="nav-section">Main</div>
      <a href="<?= APP_URL ?>/staff/dashboard.php"
         class="nav-item <?= $activePage==='dashboard'?'active':'' ?>">
        <i data-lucide="layout-dashboard" width="18" height="18"></i> Dashboard
      </a>
      <a href="<?= APP_URL ?>/staff/my_tasks.php"
         class="nav-item <?= $activePage==='tasks'?'active':'' ?>">
        <i data-lucide="clipboard-list" width="18" height="18"></i> My Tasks
      </a>

<?php elseif ($user && $user['role'] === 'resident'): ?>
      <div class="nav-section">Main</div>
      <a href="<?= APP_URL ?>/resident/dashboard.php"
         class="nav-item <?= $activePage==='dashboard'?'active':'' ?>">
        <i data-lucide="layout-dashboard" width="18" height="18"></i> Dashboard
      </a>
      <a href="<?= APP_URL ?>/submit.php"
         class="nav-item <?= $activePage==='submit'?'active':'' ?>">
        <i data-lucide="plus-circle" width="18" height="18"></i> New Complaint
      </a>
      <a href="<?= APP_URL ?>/my_complaints.php"
         class="nav-item <?= $activePage==='my_complaints'?'active':'' ?>">
        <i data-lucide="file-text" width="18" height="18"></i> My Complaints
      </a>
<?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($user['name'] ?? '') ?></div>
          <div class="user-email"><?= ucfirst($user['role'] ?? '') ?></div>
        </div>
        <button class="logout-btn" onclick="logout()" title="Log out"><i data-lucide="log-out" width="20" height="20"></i></button>
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main-content">
    <header class="topbar">
      <button class="hamburger" onclick="toggleSidebar()">
        <i data-lucide="menu" width="22" height="22"></i>
      </button>
      <div style="flex:1; margin-left:12px;">
        <div class="page-title"><?= htmlspecialchars($title) ?></div>
        <?php if ($subtitle): ?>
        <div class="page-sub"><?= htmlspecialchars($subtitle) ?></div>
        <?php endif; ?>
      </div>
      <div class="topbar-actions"><?= $actionsHtml ?></div>
    </header>

    <div class="content-area">
<?php
}

function renderPageEnd(): void {
?>
    </div><!-- .content-area -->
  </div><!-- .main-content -->
</div><!-- .app-layout -->

<!-- Status Change Modal (staff/admin) -->
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

<script src="<?= APP_URL ?>/assets/js/app.js?v=<?= time() ?>"></script>
<script>
function logout(){
  fetch('<?= APP_URL ?>/api/auth.php',{
    method:'POST',headers:{'Content-Type':'application/json'},
    body: JSON.stringify({action:'logout'})
  }).finally(()=>{ window.location.href = '<?= APP_URL ?>/index.php'; });
}
</script>
<script>lucide.createIcons();</script>
</body>
</html>
<?php
}

function statusBadgeClass(string $status): string {
    return 'badge-' . htmlspecialchars($status);
}

function priorityBadgeClass(string $priority): string {
    switch (strtolower($priority)) {
        case 'urgent': return 'badge-danger';
        case 'high': return 'badge-warning';
        case 'medium': return 'badge-assigned';
        case 'low':
        default:
            return 'badge-closed';
    }
}
