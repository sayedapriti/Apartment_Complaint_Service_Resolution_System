<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'apartment_system');

define('ADMIN_EMAIL', 'admin@residehub.com');
define('SITE_NAME', 'ResideHub');
define('BASE_URL', 'http://localhost/apartment-system');
define('APP_URL', BASE_URL);
define('SITE_URL', BASE_URL);

try {
    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

function getDB()
{
    global $db;
    return $db;
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'user_id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'] ?? '',
        'name' => $_SESSION['full_name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'profile_id' => $_SESSION['profile_id'] ?? null,
    ];
}

function requireLogin(string $requiredRole = null): ?array
{
    $user = currentUser();
    if (!$user) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }

    if ($requiredRole && $user['role'] !== $requiredRole) {
        $redirect = SITE_URL . '/index.php';
        if ($user['role'] === 'admin') {
            $redirect = SITE_URL . '/admin/dashboard.php';
        } elseif ($user['role'] === 'staff') {
            $redirect = SITE_URL . '/staff/dashboard.php';
        } else {
            $redirect = SITE_URL . '/resident/dashboard.php';
        }
        header('Location: ' . $redirect);
        exit;
    }

    return $user;
}

function sanitize($value)
{
    return trim(htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'));
}

function jsonResponse(array $data): void
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
