<?php
require_once __DIR__ . '/../includes/config.php';
$user = requireLogin('resident');
$db = getDB();
$profileId = $user['profile_id'] ?? null;

if (!$profileId) {
    header('Location: ' . APP_URL . '/resident/profile.php?setup=1');
    exit;
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = sanitize($_POST['category_name'] ?? '');
    $desc = sanitize($_POST['description'] ?? '');

    if (!$categoryName || !$desc) {
        $error = 'Please fill out all required fields.';
    } else {
        $stmt = $db->prepare("
            INSERT INTO complaints (resident_id, category_name, complaint_title, complaint_description)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$profileId, $categoryName, $categoryName, $desc]);
        $success = 'Your complaint has been submitted successfully.';
    }
}

// Fetch categories
$categories = $db->query("SELECT category_name FROM complaint_categories ORDER BY category_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>File Complaint — ResideHub</title>
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
    <a class="nav-item" href="dashboard.php"><i data-lucide="layout-dashboard" width="18" height="18"></i> Dashboard</a>
    <a class="nav-item active" href="submit.php"><i data-lucide="plus-circle" width="18" height="18"></i> File Complaint</a>
    <a class="nav-item" href="my_complaints.php"><i data-lucide="file-text" width="18" height="18"></i> My Complaints</a>
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
      <div class="page-title">File a Complaint</div>
      <div class="page-sub">Submit a new maintenance or service request for your apartment.</div>
    </div>
  </div>
  
  <div class="content-area">
    <?php if ($error): ?><div class="alert alert-error mb-20"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success mb-20">
        <?= htmlspecialchars($success) ?>
        <a href="my_complaints.php" style="color:var(--success);text-decoration:underline;margin-left:10px;">View your complaints.</a>
      </div>
    <?php endif; ?>

    <div class="card" style="max-width:800px;">
      <form method="post" id="complaintForm">
        <div class="card-body">
          <div class="form-group mb-20">
            <label class="form-label">Category</label>
            <select name="category_name" class="form-control" required>
              <option value="">-- Select Category --</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['category_name']) ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Detailed Description</label>
            <textarea name="description" class="form-control" rows="6" placeholder="Provide any relevant details to help the service staff..." required></textarea>
          </div>

          <div class="card-footer form-actions-right">
            <button type="submit" class="btn btn-primary" id="submitBtn">
              <i data-lucide="send" width="16" height="16"></i> Submit Complaint
            </button>
          </div>
        </div>
      </form>
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
