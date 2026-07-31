<?php
require_once __DIR__ . '/../includes/config.php';
$user = requireLogin('resident');
$db = getDB();

$msg = '';
$msgType = '';
$setup = isset($_GET['setup']);

// Check if profile exists
$stmt = $db->prepare("SELECT * FROM resident_profiles WHERE user_id = ?");
$stmt->execute([$user['user_id']]);
$profile = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $apt = sanitize($_POST['apartment_number'] ?? '');
  $address = sanitize($_POST['contact_address'] ?? '');

  if (!$apt) {
    $msg = 'Apartment Number is required.';
    $msgType = 'error';
  } else {
    if ($profile) {
      $update = $db->prepare("UPDATE resident_profiles SET apartment_number = ?, contact_address = ? WHERE user_id = ?");
      $update->execute([$apt, $address, $user['user_id']]);
      $msg = 'Profile updated successfully.';
    } else {
      $insert = $db->prepare("INSERT INTO resident_profiles (user_id, apartment_number, contact_address) VALUES (?, ?, ?)");
      $insert->execute([$user['user_id'], $apt, $address]);
      $msg = 'Profile created successfully.';

      // Re-fetch to get new ID
      $stmt->execute([$user['user_id']]);
      $profile = $stmt->fetch();
      $_SESSION['profile_id'] = $profile['resident_id'];

      if ($setup) {
        header('Location: ' . APP_URL . '/resident/dashboard.php');
        exit;
      }
    }
    $msgType = 'success';

    // Refresh local variable
    $profile['apartment_number'] = $apt;
    $profile['contact_address'] = $address;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>My Profile — ResideHub</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
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
      <a class="nav-item" href="dashboard.php"><i data-lucide="layout-dashboard" width="18" height="18"></i>
        Dashboard</a>
      <a class="nav-item" href="submit.php"><i data-lucide="plus-circle" width="18" height="18"></i> File Complaint</a>
      <a class="nav-item" href="my_complaints.php"><i data-lucide="file-text" width="18" height="18"></i> My
        Complaints</a>
      <div class="nav-section">Account</div>
      <a class="nav-item active" href="profile.php"><i data-lucide="user" width="18" height="18"></i> My Profile</a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
          <div class="user-email">Tenant</div>
        </div>
        <button class="logout-btn" onclick="logout()" title="Log out"><i data-lucide="log-out" width="20" height="20"></i></button>
      </div>
    </div>
  </aside>

  <div class="main-content">
    <div class="topbar">
      <div>
        <div class="page-title">My Profile</div>
        <div class="page-sub">Manage your apartment details and contact information.</div>
      </div>
    </div>

    <div class="content-area">
      <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?> mb-20"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <?php if ($setup && !$msg): ?>
        <div class="alert alert-warning mb-20">
          <i data-lucide="alert-circle" width="16" height="16" style="vertical-align:text-bottom;"></i>
          Welcome! Please complete your profile setup before accessing the dashboard.
        </div>
      <?php endif; ?>

      <div class="card profile-card">
        <div class="profile-card-header">
          <h3>Personal Details</h3>
          <p>These details are managed in your main account.</p>
        </div>

        <div class="profile-card-section">
          <div class="autolayout-row">
            <div class="form-group mb-0">
              <label class="form-label profile-label">Full Name</label>
              <div class="form-control profile-static-text"><?= htmlspecialchars($user['full_name']) ?></div>
            </div>
            <div class="form-group mb-0">
              <label class="form-label profile-label">Email Address</label>
              <div class="form-control profile-static-text"><?= htmlspecialchars($user['email']) ?></div>
            </div>
          </div>
        </div>

        <form method="post">
          <div class="profile-card-section">
            <div class="autolayout-row">
              <div class="form-group mb-0">
                <label class="form-label profile-label">Apartment Number <span style="color:var(--danger)">*</span></label>
                <input type="text" name="apartment_number" class="form-control" placeholder="e.g. A-101"
                  value="<?= htmlspecialchars($profile['apartment_number'] ?? '') ?>" required>
              </div>
              <div class="form-group mb-0">
                <label class="form-label profile-label">Contact Address</label>
                <input type="text" name="contact_address" class="form-control" placeholder="Block, Floor, etc."
                  value="<?= htmlspecialchars($profile['contact_address'] ?? '') ?>">
              </div>
            </div>
          </div>
          <div class="profile-card-section autolayout-center">
            <button type="submit" class="btn btn-primary autolayout-btn">Save Profile</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    async function logout() {
      await fetch('../api/auth.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'logout' }) });
      window.location.href = '../index.php';
    }
  </script>
  <script>lucide.createIcons();</script>
</body>

</html>