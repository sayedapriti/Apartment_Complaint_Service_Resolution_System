<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin('admin');
$user = currentUser();
$db = getDB();

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'delete_category') {
    $catName = $_POST['category_name'] ?? '';
    if ($catName) {
      $db->prepare("DELETE FROM complaint_categories WHERE category_name = ?")->execute([$catName]);
      $success = 'Category removed.';
    }
  } elseif ($action === 'add_category') {
    $catName = sanitize($_POST['category_name'] ?? '');
    if (!$catName) {
      $error = 'Category name is required.';
    } else {
      $db->prepare("INSERT IGNORE INTO complaint_categories (category_name) VALUES (?)")
        ->execute([$catName]);
      $success = "Category '$catName' added.";
    }
  }
}

// Fetch all categories and counts
$cats = $db->query("
    SELECT c.category_name, 
           (SELECT COUNT(*) FROM complaints comp WHERE comp.category_name = c.category_name) AS cnt 
    FROM complaint_categories c 
    ORDER BY c.category_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Complaint Categories — ResideHub Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
<style>
.issue-dropdown {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease, padding 0.3s ease;
}
.issue-dropdown.open {
  max-height: 600px;
  padding-top: 12px;
}
.issue-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.issue-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 13px;
  color: var(--text);
  transition: border-color 0.15s;
}
.issue-item:hover {
  border-color: var(--border2);
}
.issue-item .issue-name {
  display: flex;
  align-items: center;
  gap: 8px;
}
.issue-item .issue-name::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--accent);
  flex-shrink: 0;
}
.issue-delete-btn {
  background: none;
  border: none;
  color: var(--muted);
  cursor: pointer;
  padding: 2px;
  transition: color 0.15s;
  display: flex;
  align-items: center;
}
.issue-delete-btn:hover {
  color: var(--danger);
}
.toggle-issues-btn {
  background: none;
  border: 1px solid var(--border);
  border-radius: 6px;
  color: var(--muted2);
  cursor: pointer;
  padding: 5px 10px;
  font-size: 12px;
  font-family: var(--font-body);
  font-weight: 500;
  transition: all 0.15s;
  display: flex;
  align-items: center;
  gap: 5px;
}
.toggle-issues-btn:hover {
  color: var(--text);
  border-color: var(--accent);
}
.toggle-issues-btn .chevron {
  transition: transform 0.3s ease;
  display: inline-flex;
}
.toggle-issues-btn.open .chevron {
  transform: rotate(180deg);
}
.add-issue-inline {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}
.add-issue-inline input {
  flex: 1;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 7px;
  color: var(--text);
  font-family: var(--font-body);
  font-size: 13px;
  padding: 8px 12px;
  outline: none;
  transition: border-color 0.2s;
}
.add-issue-inline input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(79,124,255,0.15);
}
.add-issue-inline button {
  white-space: nowrap;
}
.no-issues {
  font-size: 12px;
  color: var(--muted);
  text-align: center;
  padding: 10px 0;
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
    <a class="nav-item" href="dashboard.php"><i data-lucide="layout-dashboard" width="18" height="18"></i> Dashboard</a>
    <a class="nav-item" href="complaints.php"><i data-lucide="file-text" width="18" height="18"></i> All Complaints</a>
    <a class="nav-item" href="assign.php"><i data-lucide="user-check" width="18" height="18"></i> Assign Staff</a>
    <div class="nav-section">Management</div>
    <a class="nav-item" href="staff.php"><i data-lucide="users" width="18" height="18"></i> Staff Management</a>
    <a class="nav-item" href="tenants.php"><i data-lucide="home" width="18" height="18"></i> Tenants</a>
    <a class="nav-item active" href="categories.php"><i data-lucide="tag" width="18" height="18"></i> Categories</a>
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
      <div class="page-title">Complaint Categories</div>
      <div class="page-sub">Manage the categories that tenants can choose from when submitting complaints.</div>
    </div>
    <div class="topbar-actions">
      <button class="btn btn-primary" onclick="openModal('add-cat-modal')">
        <i data-lucide="plus" width="16" height="16"></i> Add Category
      </button>
    </div>
  </div>
  <div class="content-area">
    <?php if ($error): ?><div class="alert alert-error" style="margin-bottom:20px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;">
      <?php foreach ($cats as $cat):
        $catName = $cat['category_name'];
      ?>
        <div class="card" style="padding:20px;">
          <!-- Category Header -->
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <div style="width:42px;height:42px;border-radius:12px;background:var(--surface2);display:grid;place-items:center;font-size:20px;flex-shrink:0;">
              <i data-lucide="tag" width="20" height="20"></i>
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-weight:600;font-size:0.92rem;color:var(--text);"><?= htmlspecialchars($catName) ?></div>
              <div style="font-size:0.72rem;color:var(--muted);"><?= $cat['cnt'] ?> complaint(s)</div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div style="display:flex;gap:8px;align-items:center;justify-content:flex-end;">
            <form method="post" onsubmit="return confirm('Remove this category?');" style="margin:0;">
              <input type="hidden" name="action" value="delete_category">
              <input type="hidden" name="category_name" value="<?= htmlspecialchars($catName) ?>">
              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Add Category Modal -->
<div class="modal-overlay" id="add-cat-modal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add Category</span>
      <button class="modal-close" onclick="closeModal('add-cat-modal')">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <form method="post">
      <input type="hidden" name="action" value="add_category">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Category Name</label>
          <input type="text" name="category_name" class="form-control" placeholder="e.g. Plumbing" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('add-cat-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="plus" width="16" height="16"></i> Add
        </button>
      </div>
    </form>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
function toggleIssues(btn, targetId) {
  const dropdown = document.getElementById(targetId);
  dropdown.classList.toggle('open');
  btn.classList.toggle('open');
  lucide.createIcons();
}
async function logout() {
  await fetch('../api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})});
  window.location.href='../index.php';
}
</script>
<script>lucide.createIcons();</script>
</body>
</html>