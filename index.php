<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ResideHub — Apartment Complaint & Service System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <style>
    :root {
      --bg: #0a0d14;
      --surface: #111520;
      --surface2: #161b28;
      --border: rgba(255, 255, 255, 0.07);
      --accent: #4f7cff;
      --accent2: #ff6b4a;
      --gold: #f0c040;
      --text: #e8eaf2;
      --muted: #6b7394;
      --success: #34d399;
      --danger: #f87171;
      --radius: 14px;
      --font-display: 'Inter', sans-serif;
      --font-body: 'Inter', sans-serif;
    }

    *,
    *::before,
    *::after {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: var(--font-body);
      height: 100vh;
      display: flex;
      overflow: hidden;
    }

    /* LEFT PANEL */
    .left-panel {
      flex: 1;
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 60px;
      overflow: hidden;
    }

    .left-panel::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse 80% 60% at 20% 50%, rgba(79, 124, 255, 0.18) 0%, transparent 60%),
        radial-gradient(ellipse 60% 80% at 80% 20%, rgba(255, 107, 74, 0.12) 0%, transparent 60%);
    }

    .grid-bg {
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
      background-size: 50px 50px;
    }

    .brand {
      position: relative;
      z-index: 2;
      margin-bottom: 60px;
    }

    .brand-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }

    .brand-icon {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
    }

    .brand-name {
      font-family: var(--font-display);
      font-size: 28px;
      font-weight: 800;
      letter-spacing: -0.5px;
    }

    .brand-name span {
      color: var(--accent);
    }

    .hero-title {
      font-family: var(--font-display);
      font-size: clamp(36px, 4vw, 56px);
      font-weight: 800;
      line-height: 1.1;
      letter-spacing: -1px;
      margin-bottom: 20px;
    }

    .hero-title em {
      font-style: normal;
      color: var(--accent);
    }

    .hero-sub {
      font-size: 16px;
      color: var(--muted);
      line-height: 1.7;
      max-width: 400px;
      margin-bottom: 50px;
    }

    .stats-row {
      display: flex;
      gap: 40px;
      position: relative;
      z-index: 2;
    }

    .stat {}

    .stat-num {
      font-family: var(--font-display);
      font-size: 32px;
      font-weight: 800;
      color: var(--accent);
      display: block;
    }

    .stat-label {
      font-size: 13px;
      color: var(--muted);
    }

    .floating-cards {
      position: absolute;
      right: -20px;
      bottom: 60px;
      display: flex;
      flex-direction: column;
      gap: 12px;
      z-index: 2;
      opacity: 0.9;
    }

    .fcard {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 12px 16px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 13px;
      animation: floatCard 3s ease-in-out infinite;
      backdrop-filter: blur(10px);
    }

    .fcard:nth-child(2) {
      animation-delay: 1s;
      margin-left: 20px;
    }

    .fcard:nth-child(3) {
      animation-delay: 2s;
    }

    .fcard-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      flex-shrink: 0;
    }

    @keyframes floatCard {

      0%,
      100% {
        transform: translateY(0);
      }

      50% {
        transform: translateY(-6px);
      }
    }

    /* RIGHT PANEL */
    .right-panel {
      width: 480px;
      background: var(--surface);
      border-left: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      padding: 60px 50px;
      position: relative;
      overflow-y: auto;
    }

    .tab-row {
      display: flex;
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 4px;
      margin-bottom: 36px;
      gap: 4px;
    }

    .tab-btn {
      flex: 1;
      padding: 10px;
      border: none;
      border-radius: 7px;
      background: transparent;
      color: var(--muted);
      font-family: var(--font-body);
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
    }

    .tab-btn.active {
      background: var(--accent);
      color: #fff;
      font-weight: 600;
    }

    .panel-title {
      font-family: var(--font-display);
      font-size: 26px;
      font-weight: 700;
      margin-bottom: 6px;
    }

    .panel-sub {
      font-size: 14px;
      color: var(--muted);
      margin-bottom: 32px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      font-size: 12px;
      font-weight: 500;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 8px;
    }

    input,
    select {
      width: 100%;
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 9px;
      color: var(--text);
      font-family: var(--font-body);
      font-size: 14px;
      padding: 13px 16px;
      outline: none;
      transition: border-color 0.2s;
      -webkit-appearance: none;
    }

    input:focus,
    select:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(79, 124, 255, 0.15);
    }

    select option {
      background: #1a2035;
    }

    .btn-primary {
      width: 100%;
      padding: 14px;
      background: #1a2333;
      border: none;
      border-radius: 9px;
      color: #fff;
      font-family: var(--font-display);
      font-size: 15px;
      font-weight: 700;
      letter-spacing: 0.3px;
      cursor: pointer;
      transition: all 0.2s;
      margin-top: 8px;
    }

    .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 16px rgba(26, 35, 51, 0.6);
    }

    .btn-primary:active {
      transform: translateY(0);
    }

    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 24px 0;
    }

    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    .divider span {
      font-size: 12px;
      color: var(--muted);
    }

    .register-link {
      text-align: center;
      font-size: 13px;
      color: var(--muted);
      margin-top: 16px;
    }

    .register-link a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 500;
    }

    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 13px;
      margin-bottom: 20px;
      display: none;
    }

    .alert.error {
      background: rgba(248, 113, 113, 0.1);
      border: 1px solid rgba(248, 113, 113, 0.3);
      color: var(--danger);
    }

    .alert.success {
      background: rgba(52, 211, 153, 0.1);
      border: 1px solid rgba(52, 211, 153, 0.3);
      color: var(--success);
    }

    .form-panel {
      display: none;
    }

    .form-panel.active {
      display: block;
    }

    .row-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .admin-hint {
      background: rgba(240, 192, 64, 0.08);
      border: 1px solid rgba(240, 192, 64, 0.2);
      border-radius: 8px;
      padding: 14px;
      font-size: 12px;
      color: var(--gold);
      margin-bottom: 20px;
      display: flex;
      flex-direction: column;
      gap: 6px;
      align-items: flex-start;
    }

    .input-wrap {
      position: relative;
    }

    .eye-btn {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--muted);
      cursor: pointer;
      padding: 0;
      font-size: 16px;
    }

    .loading {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
      vertical-align: middle;
      margin-right: 6px;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg)
      }
    }
  </style>
</head>

<body>

  <div class="left-panel">
    <div class="grid-bg"></div>

    <div class="brand" style="position:relative;z-index:2;">
      <div class="brand-logo">
        <div class="brand-icon"><i data-lucide="building-2" width="24" height="24"></i></div>
        <div class="brand-name">Reside<span>Hub</span></div>
      </div>
      <h1 class="hero-title">
        Smart <em>Complaint</em><br>Resolution<br>for Modern Living
      </h1>
      <p class="hero-sub">
        A structured digital platform to submit, track, and resolve apartment complaints — connecting tenants,
        management, and service staff seamlessly.
      </p>
    </div>

    <div class="stats-row">
      <div class="stat">
        <span class="stat-num">3</span>
        <span class="stat-label">User Roles</span>
      </div>
      <div class="stat">
        <span class="stat-num">11</span>
        <span class="stat-label">Categories</span>
      </div>
      <div class="stat">
        <span class="stat-num">100%</span>
        <span class="stat-label">Trackable</span>
      </div>
    </div>
  </div>

  <div class="right-panel">
    <div id="alert-box" class="alert"></div>

    <div class="tab-row">
      <button class="tab-btn active" onclick="switchRole('admin')"><i data-lucide="shield-admin" width="16" height="16"
          style="vertical-align:middle;margin-right:6px;"></i>Admin</button>
      <button class="tab-btn" onclick="switchRole('staff')"><i data-lucide="users" width="16" height="16"
          style="vertical-align:middle;margin-right:6px;"></i>Staff</button>
      <button class="tab-btn" onclick="switchRole('resident')"><i data-lucide="home" width="16" height="16"
          style="vertical-align:middle;margin-right:6px;"></i>Tenant</button>
    </div>

    <!-- LOGIN FORM -->
    <div id="login-panel" class="form-panel active">
      <div class="panel-title">Welcome back</div>
      <div class="panel-sub">Sign in to your account to continue.</div>

      <!--
    <div class="admin-hint">
      🔑 Demo Credentials:<br>
      Admin: <strong>admin@residehub.com</strong> / <strong>admin123</strong><br>
      Staff: <strong>staff@residehub.com</strong> / <strong>staff123</strong><br>
      Tenant: <strong>tenant@residehub.com</strong> / <strong>tenant123</strong>
    </div>
    -->

      <form id="loginForm">
        <input type="hidden" name="role" id="roleInput" value="admin">
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" placeholder="your@email.com" required autocomplete="email">
        </div>
        <div class="form-group">
          <label>Password</label>
          <div class="input-wrap">
            <input type="password" name="password" id="loginPass" placeholder="••••••••" required>
            <button type="button" class="eye-btn" onclick="togglePass('loginPass',this)"><i data-lucide="eye" width="16"
                height="16"></i></button>
          </div>
        </div>
        <button type="submit" class="btn-primary" id="loginBtn">Sign In</button>
        <div class="register-link">Don't have an account? <a href="#"
            onclick="switchTab('register'); return false;">Register</a></div>
      </form>
    </div>

    <!-- REGISTER FORM -->
    <div id="register-panel" class="form-panel">
      <div style="text-align:center;margin-bottom:24px;">
        <a href="#" onclick="switchTab('login'); return false;"
          style="color:var(--muted);text-decoration:none;font-size:14px;">← Back to Sign In</a>
      </div>
      <div class="panel-title">Create account</div>
      <div class="panel-sub">Register as a tenant or service staff.</div>

      <form id="registerForm">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="full_name" placeholder="John Doe" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" placeholder="your@email.com" required>
        </div>
        <div class="form-group">
          <label>Role</label>
          <select name="role" id="regRole" onchange="toggleRoleFields()">
            <option value="resident">Tenant</option>
            <option value="staff">Service Staff</option>
          </select>
        </div>
        <div id="resident-fields">
          <div class="row-2">
            <div class="form-group">
              <label>Apartment No.</label>
              <input type="text" name="apartment_number" placeholder="A-201">
            </div>
            <div class="form-group">
              <label>Address</label>
              <input type="text" name="contact_address" placeholder="Block, Building...">
            </div>
          </div>
        </div>
        <div id="staff-fields" style="display:none">
          <div class="row-2">
            <div class="form-group">
              <label>Staff Type</label>
              <select name="staff_type">
                <option value="Plumber">Plumber</option>
                <option value="Electrician">Electrician</option>
                <option value="HVAC Technician">HVAC Technician</option>
                <option value="Carpenter">Carpenter</option>
                <option value="Pest Control">Pest Control</option>
                <option value="Cleaner">Cleaner</option>
                <option value="Security">Security</option>
                <option value="General">General</option>
              </select>
            </div>
            <div class="form-group">
              <label>Contact</label>
              <input type="tel" name="staff_contact" placeholder="+880...">
            </div>
          </div>
        </div>
        <div class="form-group">
          <label>Password</label>
          <div class="input-wrap">
            <input type="password" name="password" id="regPass" placeholder="Min 6 characters" required>
            <button type="button" class="eye-btn" onclick="togglePass('regPass',this)"><i data-lucide="eye" width="16"
                height="16"></i></button>
          </div>
        </div>
        <button type="submit" class="btn-primary" id="regBtn">Create Account →</button>
      </form>
    </div>
  </div>

  <script>
    function switchRole(role) {
      document.getElementById('roleInput').value = role;
      document.querySelectorAll('.tab-btn').forEach((b, i) => {
        const roles = ['admin', 'staff', 'resident'];
        b.classList.toggle('active', roles[i] === role);
      });
    }

    function switchTab(tab) {
      document.getElementById('login-panel').classList.toggle('active', tab === 'login');
      document.getElementById('register-panel').classList.toggle('active', tab === 'register');
      clearAlert();
    }

    function toggleRoleFields() {
      const role = document.getElementById('regRole').value;
      document.getElementById('resident-fields').style.display = role === 'resident' ? '' : 'none';
      document.getElementById('staff-fields').style.display = role === 'staff' ? '' : 'none';
    }

    function togglePass(id, btn) {
      const inp = document.getElementById(id);
      inp.type = inp.type === 'password' ? 'text' : 'password';
      const icon = btn.querySelector('i');
      if (icon) {
        icon.setAttribute('data-lucide', inp.type === 'password' ? 'eye' : 'eye-off');
        lucide.createIcons();
      }
    }

    function showAlert(msg, type = 'error') {
      const el = document.getElementById('alert-box');
      el.textContent = msg;
      el.className = 'alert ' + type;
      el.style.display = 'block';
    }

    function clearAlert() {
      const el = document.getElementById('alert-box');
      el.style.display = 'none';
    }

    document.getElementById('loginForm').addEventListener('submit', async function (e) {
      e.preventDefault();
      clearAlert();
      const btn = document.getElementById('loginBtn');
      btn.innerHTML = '<span class="loading"></span>Signing in...';
      btn.disabled = true;

      const data = Object.fromEntries(new FormData(this));
      try {
        const res = await fetch('api/auth.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'login', ...data })
        });
        const json = await res.json();
        if (json.success) {
          showAlert('Login successful! Redirecting...', 'success');
          setTimeout(() => window.location.href = json.redirect, 800);
        } else {
          showAlert(json.message || 'Login failed.');
          btn.innerHTML = 'Sign In →';
          btn.disabled = false;
          lucide.createIcons();
        }
      } catch (err) {
        showAlert('Server error. Please try again.');
        btn.innerHTML = 'Sign In →';
        btn.disabled = false;
        lucide.createIcons();
      }
    });

    document.getElementById('registerForm').addEventListener('submit', async function (e) {
      e.preventDefault();
      clearAlert();
      const btn = document.getElementById('regBtn');
      btn.innerHTML = '<span class="loading"></span>Creating...';
      btn.disabled = true;

      const data = Object.fromEntries(new FormData(this));
      try {
        const res = await fetch('api/auth.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'register', ...data })
        });
        const json = await res.json();
        if (json.success) {
          showAlert('Account created! Please sign in.', 'success');
          this.reset();
          setTimeout(() => switchTab('login'), 1500);
        } else {
          showAlert(json.message || 'Registration failed.');
        }
      } catch (err) {
        showAlert('Server error. Please try again.');
      }
      btn.innerHTML = 'Create Account →';
      btn.disabled = false;
      lucide.createIcons();
    });
  </script>
  <script>lucide.createIcons();</script>
</body>

</html>