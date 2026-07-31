<?php
require_once __DIR__ . '/../includes/config.php';
$user = requireLogin('staff');
$db = getDB();

$msg = '';
$msgType = '';
$setup = isset($_GET['setup']);

// Check if profile exists
$stmt = $db->prepare("SELECT ssp.*, u.phone AS contact_number FROM users u LEFT JOIN service_staff_profiles ssp ON ssp.user_id = u.user_id WHERE u.user_id = ?");
$stmt->execute([$user['user_id']]);
$profile = $stmt->fetch();
$profileExists = !empty($profile['staff_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $contact = sanitize($_POST['contact_number'] ?? '');
  $staffType = sanitize($_POST['staff_type'] ?? '');
  $status = sanitize($_POST['available_status'] ?? 'available');

  if (!$staffType) {
    $msg = 'Staff Type is required.';
    $msgType = 'error';
  } else {
    // Update phone in users table
    $updateUser = $db->prepare("UPDATE users SET phone = ? WHERE user_id = ?");
    $updateUser->execute([$contact, $user['user_id']]);

    if ($profileExists) {
      $update = $db->prepare("UPDATE service_staff_profiles SET staff_type = ?, available_status = ? WHERE user_id = ?");
      $update->execute([$staffType, $status, $user['user_id']]);
      $msg = 'Profile updated successfully.';
    } else {
      $insert = $db->prepare("INSERT INTO service_staff_profiles (user_id, staff_type, available_status) VALUES (?, ?, ?)");
      $insert->execute([$user['user_id'], $staffType, $status]);
      $msg = 'Profile created successfully.';

      // Re-fetch to get new ID
      $stmt->execute([$user['user_id']]);
      $profile = $stmt->fetch();
      $_SESSION['profile_id'] = $profile['staff_id'];

      if ($setup) {
        header('Location: ' . APP_URL . '/staff/dashboard.php');
        exit;
      }
    }
    $msgType = 'success';

    // Refresh local variable
    $profile['contact_number'] = $contact;
    $profile['staff_type'] = $staffType;
    $profile['available_status'] = $status;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Staff Profile — ResideHub</title>
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
    <div style="padding:0 24px 12px;margin-top:8px;"><span class="tag">Staff Portal</span></div>
    <nav class="sidebar-nav">
      <div class="nav-section">Main</div>
      <a class="nav-item" href="dashboard.php"><i data-lucide="layout-dashboard" width="18" height="18"></i>
        Dashboard</a>
      <a class="nav-item" href="my_tasks.php"><i data-lucide="clipboard-list" width="18" height="18"></i> My Tasks</a>
      <div class="nav-section">Account</div>
      <a class="nav-item active" href="profile.php"><i data-lucide="user" width="18" height="18"></i> My Profile</a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
          <div class="user-email">Service Staff</div>
        </div>
        <button class="logout-btn" onclick="logout()" title="Log out"><i data-lucide="log-out" width="20"
            height="20"></i></button>
      </div>
    </div>
  </aside>

  <div class="main-content">
    <div class="topbar">
      <div>
        <div class="page-title">My Profile</div>
        <div class="page-sub">Manage your service details and availability.</div>
      </div>
    </div>

    <div class="content-area">
      <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>" style="margin-bottom:20px;"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <?php if ($setup && !$msg): ?>
        <div class="alert alert-warning" style="margin-bottom:20px;">
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
            <div class="form-group mb-20">
              <label class="form-label profile-label">Contact Number</label>
              <input type="text" name="contact_number" class="form-control" placeholder="+880..."
                value="<?= htmlspecialchars($profile['contact_number'] ?? '') ?>">
            </div>

            <div class="autolayout-row">
              <div class="form-group mb-0">
                <label class="form-label profile-label">Staff Type <span style="color:var(--danger)">*</span></label>
                <select name="staff_type" class="form-control" required>
                  <?php
                  $types = ['Plumber', 'Electrician', 'HVAC Technician', 'Carpenter', 'Pest Control', 'Cleaner', 'Security', 'General'];
                  $currentType = $profile['staff_type'] ?? 'General';
                  foreach ($types as $t) {
                    $sel = ($t === $currentType) ? 'selected' : '';
                    echo "<option value=\"$t\" $sel>$t</option>";
                  }
                  ?>
                </select>
              </div>
              <div class="form-group mb-0">
                <label class="form-label profile-label">Availability Status</label>
                <select name="available_status" class="form-control">
                  <?php
                  $statuses = ['available' => 'Available', 'busy' => 'Busy', 'off_duty' => 'Off Duty'];
                  $currentStatus = $profile['available_status'] ?? 'available';
                  foreach ($statuses as $val => $label) {
                    $sel = ($val === $currentStatus) ? 'selected' : '';
                    echo "<option value=\"$val\" $sel>$label</option>";
                  }
                  ?>
                </select>
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