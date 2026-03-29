<?php

/**
 * Authentication & authorisation helpers
 */
require_once __DIR__ . '/db.php';

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly'  => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function isLoggedIn(): bool
{
    startSecureSession();
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    return $_SESSION['user'] ?? null;
}

function currentRole(): ?string
{
    $u = currentUser();
    return $u['role'] ?? null;
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Unauthorized. Please login.']);
        exit;
    }
}

function requireRole(array $roles): void
{
    requireLogin();
    if (!in_array(currentRole(), $roles, true)) {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Access denied.']);
        exit;
    }
}

function requirePageLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
        exit;
    }
}

function generateCsrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function loginUser(string $email, string $password): array
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['status' => false, 'message' => 'Invalid email or password.'];
    }

    startSecureSession();
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user'] = [
        'id'         => (int) $user['id'],
        'first_name' => $user['first_name'],
        'guardian_first_name' => $user['guardian_first_name'] ?? null,
        'guardian_last_name' => $user['guardian_last_name'] ?? null,
        'last_name'  => $user['last_name'],
        'email'      => $user['email'],
        'role'       => $user['role'],
        'first_login' => (int) $user['first_login'],
    ];

    // For students: load all children (students) under the same email
    if ($user['role'] === 'student') {
        $childStmt = $db->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.school_id, u.teacher_id,
                    u.guardian_first_name, u.guardian_last_name,
                    s.name AS school_name, CONCAT(t.first_name, " ", t.last_name) AS teacher_name
             FROM users u
             LEFT JOIN schools s ON u.school_id = s.id
             LEFT JOIN users t ON u.teacher_id = t.id
             WHERE u.email = ? AND u.role = "student"
             ORDER BY u.first_name ASC'
        );
        $childStmt->execute([$email]);
        $_SESSION['children'] = $childStmt->fetchAll();
    } else {
        $_SESSION['children'] = [];
    }

    generateCsrf();

    $result = [
        'status'  => true,
        'message' => 'Login successful.',
        'user'    => $_SESSION['user'],
    ];
    if (!empty($_SESSION['children'])) {
        $result['children'] = $_SESSION['children'];
    }
    return $result;
}

function logoutUser(): void
{
    startSecureSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function jsonResponse(bool $status, string $message, array $extra = []): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

/** Get session children array (student role only) */
function getSessionChildren(): array
{
    return $_SESSION['children'] ?? [];
}

/** Check if a student_id belongs to one of the logged-in parent's children */
function isMyChild(int $studentId): bool
{
    $children = getSessionChildren();
    foreach ($children as $c) {
        if ((int) $c['id'] === $studentId) return true;
    }
    return false;
}

/** Get all child IDs for the logged-in student */
function myChildIds(): array
{
    return array_map(function ($c) {
        return (int) $c['id'];
    }, getSessionChildren());
}
