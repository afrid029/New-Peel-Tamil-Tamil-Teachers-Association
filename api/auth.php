<?php

/**
 * API: Authentication
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
startSecureSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    /* ---------- LOGIN ---------- */
    case 'login':
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email === '' || $password === '') {
            jsonResponse(false, 'Email and password are required.');
        }
        $result = loginUser($email, $password);

        // Clean up any password reset rows for this email on successful login
        if ($result['status']) {
            $db = getDB();
            $db->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
        }

        echo json_encode($result);
        break;

    /* ---------- CHANGE PASSWORD (first login) ---------- */
    case 'change_password':
        requireLogin();
        verifyCsrf();
        $newPw  = $_POST['new_password'] ?? '';
        $confPw = $_POST['confirm_password'] ?? '';

        if (strlen($newPw) < 6) {
            jsonResponse(false, 'Password must be at least 6 characters.');
        }
        if ($newPw !== $confPw) {
            jsonResponse(false, 'Passwords do not match.');
        }

        $db = getDB();
        $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);

        // For students: update ALL children under the same email
        if (currentRole() === 'student') {
            $email = currentUser()['email'];
            $stmt = $db->prepare('UPDATE users SET password = ?, first_login = 0 WHERE email = ? AND role = "student"');
            $stmt->execute([$hash, $email]);
        } else {
            $stmt = $db->prepare('UPDATE users SET password = ?, first_login = 0 WHERE id = ?');
            $stmt->execute([$hash, currentUserId()]);
        }

        $_SESSION['user']['first_login'] = 0;
        jsonResponse(true, 'Password updated successfully.');
        break;

    /* ---------- ME ---------- */
    case 'me':
        requireLogin();
        $extra = ['user' => currentUser()];
        if (currentRole() === 'student') {
            $extra['children'] = getSessionChildren();
        }
        jsonResponse(true, 'OK', $extra);
        break;

    /* ---------- CHILDREN LIST (student only – fresh from DB) ---------- */
    case 'children':
        requireRole(['student']);
        $db = getDB();
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
        $childStmt->execute([currentUser()['email']]);
        $children = $childStmt->fetchAll();
        // Also refresh session
        $_SESSION['children'] = $children;
        jsonResponse(true, 'OK', ['data' => $children]);
        break;

    /* ---------- UPDATE PROFILE (student self-edit) ---------- */
    case 'update_profile':
        requireRole(['student']);
        verifyCsrf();

        $childId = (int) ($_POST['student_id'] ?? 0);
        if (!$childId || !isMyChild($childId)) jsonResponse(false, 'Invalid student.');

        $err = requiredFields(['first_name', 'last_name', 'school_id', 'guardian_first_name', 'guardian_last_name'], $_POST);
        if ($err) jsonResponse(false, $err);

        $fname    = sanitize($_POST['first_name']);
        $lname    = sanitize($_POST['last_name']);
        $schoolId = (int) $_POST['school_id'];
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        $gfname   = sanitize($_POST['guardian_first_name']);
        $glname   = sanitize($_POST['guardian_last_name']);

        $db = getDB();
        $stmt = $db->prepare('UPDATE users SET first_name=?, last_name=?, school_id=?, teacher_id=?, guardian_first_name=?, guardian_last_name=? WHERE id=? AND role="student"');
        $stmt->execute([$fname, $lname, $schoolId, $teacherId ?: null, $gfname, $glname, $childId]);

        // Refresh children in session
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
        $childStmt->execute([currentUser()['email']]);
        $_SESSION['children'] = $childStmt->fetchAll();

        jsonResponse(true, 'Profile updated successfully.', ['children' => $_SESSION['children']]);
        break;

    /* ---------- DASHBOARD STATS ---------- */
    case 'dashboard_stats':
        requireLogin();
        $db   = getDB();
        $role = currentRole();
        $data = [];

        if (in_array($role, ['super_admin', 'manager', 'teacher'])) {
            if ($role === 'teacher') {
                $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE role = "student" AND teacher_id = ?');
                $stmt->execute([currentUserId()]);
                $data['students'] = (int) $stmt->fetchColumn();
            } else {
                $data['students']  = (int) $db->query('SELECT COUNT(*) FROM users WHERE role = "student"')->fetchColumn();
                $data['schools']   = (int) $db->query('SELECT COUNT(*) FROM schools')->fetchColumn();
                $data['exams']     = (int) $db->query('SELECT COUNT(*) FROM exams')->fetchColumn();
                $data['teachers']  = (int) $db->query('SELECT COUNT(*) FROM users WHERE role = "teacher"')->fetchColumn();
            }
        } else {
            // Student: count children under the same email
            $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND role = "student"');
            $stmt->execute([currentUser()['email']]);
            $data['students'] = (int) $stmt->fetchColumn();
        }

        jsonResponse(true, 'OK', ['data' => $data]);
        break;

    /* ---------- FORGOT PASSWORD ---------- */
    case 'forgot_password':
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            jsonResponse(false, 'Email is required.');
        }

        $db = getDB();
        $stmt = $db->prepare('SELECT id, first_name, guardian_first_name, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Always show success to prevent email enumeration
        if (!$user) {
            jsonResponse(true, 'If that email exists, a reset link has been sent.');
        }

        // Invalidate any existing unused tokens for this email
        $db->prepare('UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0')->execute([$email]);

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        // date_default_timezone_set('Asia/Dubai');
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Check existing row — rate limit to 5 attempts per token cycle
        $existing = $db->prepare('SELECT id, attempts, expires_at FROM password_resets WHERE email = ? LIMIT 1');
        $existing->execute([$email]);
        $row = $existing->fetch();

        if ($row) {
            // If not expired and already hit 5 attempts, block
            if ($row['attempts'] >= 5 && strtotime($row['expires_at']) > time()) {
                jsonResponse(false, 'Too many reset requests. Please try again later.');
            }

            // If expired, reset attempts; otherwise increment
            $newAttempts = (strtotime($row['expires_at']) > time()) ? $row['attempts'] + 1 : 1;
            $stmt = $db->prepare('UPDATE password_resets SET token = ?, expires_at = ?, used = 0, attempts = ? WHERE id = ?');
            $stmt->execute([$token, $expiresAt, $newAttempts, $row['id']]);
        } else {
            $stmt = $db->prepare('INSERT INTO password_resets (email, token, expires_at, attempts) VALUES (?, ?, ?, 1)');
            $stmt->execute([$email, $token, $expiresAt]);
        }

        // Send email
        require_once __DIR__ . '/../includes/mail.php';
        $displayName = ($user['role'] === 'student' && !empty($user['guardian_first_name']))
            ? $user['guardian_first_name']
            : $user['first_name'];
        $resetUrl = APP_URL . '/reset-password.php?token=' . urlencode($token);
        sendPasswordResetEmail($email, $displayName, $resetUrl);

        jsonResponse(true, 'If that email exists, a reset link has been sent.');
        break;

    /* ---------- RESET PASSWORD ---------- */
    case 'reset_password':
        $token  = trim($_POST['token'] ?? '');
        $newPw  = $_POST['new_password'] ?? '';
        $confPw = $_POST['confirm_password'] ?? '';

        if ($token === '') {
            jsonResponse(false, 'Invalid or missing token.');
        }
        if (strlen($newPw) < 6) {
            jsonResponse(false, 'Password must be at least 6 characters.');
        }
        if ($newPw !== $confPw) {
            jsonResponse(false, 'Passwords do not match.');
        }

        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            jsonResponse(false, 'This reset link is invalid or has expired. Please request a new one.');
        }

        // Update password for all accounts with this email (handles multi-child students)
        $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare('UPDATE users SET password = ?, first_login = 0 WHERE email = ?')->execute([$hash, $reset['email']]);

        // Delete the reset row
        $db->prepare('DELETE FROM password_resets WHERE id = ?')->execute([$reset['id']]);

        jsonResponse(true, 'Password reset successful! Redirecting to login...');
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
