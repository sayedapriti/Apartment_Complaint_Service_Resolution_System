<?php
require_once '../includes/config.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'login':  handleLogin($input); break;
    case 'register': handleRegister($input); break;
    case 'logout': handleLogout(); break;
    default: jsonResponse(['success'=>false,'message'=>'Invalid action.']);
}

function handleLogin($data) {
    $email    = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $role     = $data['role'] ?? 'resident';

    if (!$email || !$password) {
        jsonResponse(['success'=>false,'message'=>'Email and password are required.']);
    }

    // Demo hardcoded credentials
    $demoCredentials = [
        'admin' => ['admin@residehub.com' => 'admin123'],
        'staff' => ['staff@residehub.com' => 'staff123'],
        'resident' => ['tenant@residehub.com' => 'tenant123']
    ];

    // Check if demo credentials match
    if (isset($demoCredentials[$role][$email]) && $demoCredentials[$role][$email] === $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE role = ? ORDER BY user_id ASC LIMIT 1");
        $stmt->execute([$role]);
        $user = $stmt->fetch();
        $validLogin = $user ? true : false;
    } else {
        // Check database credentials
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();
        $validLogin = $user && password_verify($password, $user['password']);
    }

    if (!$user || !$validLogin) {
        jsonResponse(['success'=>false,'message'=>'Invalid credentials or role mismatch.']);
    }

    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['role']      = $user['role'];

    // Get profile ID if resident or staff
    if ($role === 'resident') {
        $s = $db->prepare("SELECT resident_id FROM resident_profiles WHERE user_id = ?");
        $s->execute([$user['user_id']]);
        $p = $s->fetch();
        $_SESSION['profile_id'] = $p['resident_id'] ?? null;
    } elseif ($role === 'staff') {
        $s = $db->prepare("SELECT staff_id FROM service_staff_profiles WHERE user_id = ?");
        $s->execute([$user['user_id']]);
        $p = $s->fetch();
        $_SESSION['profile_id'] = $p['staff_id'] ?? null;
    }

    $redirectMap = [
        'admin'    => SITE_URL . '/admin/dashboard.php',
        'staff'    => SITE_URL . '/staff/dashboard.php',
        'resident' => SITE_URL . '/resident/dashboard.php',
    ];

    jsonResponse(['success'=>true,'redirect'=>$redirectMap[$role]]);
}

function handleRegister($data) {
    $fullName  = trim($data['full_name'] ?? '');
    $email     = trim($data['email'] ?? '');
    $phone     = trim($data['phone'] ?? '');
    $password  = $data['password'] ?? '';
    $role      = $data['role'] ?? 'resident';

    if (!$fullName || !$email || !$password) {
        jsonResponse(['success'=>false,'message'=>'Required fields are missing.']);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success'=>false,'message'=>'Invalid email format.']);
    }
    if (strlen($password) < 6) {
        jsonResponse(['success'=>false,'message'=>'Password must be at least 6 characters.']);
    }
    if ($email === ADMIN_EMAIL) {
        jsonResponse(['success'=>false,'message'=>'This email is reserved.']);
    }
    if (!in_array($role, ['resident','staff'])) {
        jsonResponse(['success'=>false,'message'=>'Invalid role.']);
    }

    $db = getDB();
    $check = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        jsonResponse(['success'=>false,'message'=>'Email already registered.']);
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (full_name,email,password,phone,role) VALUES (?,?,?,?,?)");
    $stmt->execute([$fullName,$email,$hashed,$phone,$role]);
    $userId = $db->lastInsertId();

    if ($role === 'resident') {
        $apt     = trim($data['apartment_number'] ?? '');
        $address = trim($data['contact_address'] ?? '');
        $s = $db->prepare("INSERT INTO resident_profiles (user_id,apartment_number,contact_address) VALUES (?,?,?)");
        $s->execute([$userId,$apt,$address]);
    } elseif ($role === 'staff') {
        $staffType = $data['staff_type'] ?? 'General';
        $s = $db->prepare("INSERT INTO service_staff_profiles (user_id,staff_type) VALUES (?,?)");
        $s->execute([$userId,$staffType]);
    }

    jsonResponse(['success'=>true,'message'=>'Account created successfully.']);
}

function handleLogout() {
    session_destroy();
    jsonResponse(['success'=>true,'redirect'=> SITE_URL . '/index.php']);
}
