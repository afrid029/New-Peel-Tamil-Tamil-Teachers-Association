<?php

/**
 * API: Students CRUD
 * Allowed: super_admin, manager, teacher
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/mail.php';
startSecureSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    /* ---------- LIST ---------- */
    case 'list':
        requireRole(['super_admin', 'manager', 'teacher']);
        $db   = getDB();
        $role = currentRole();
        $search = trim($_GET['search'] ?? '');

        $sql = 'SELECT u.id, u.first_name, u.last_name, u.email, u.guardian_first_name, u.guardian_last_name,
                       u.school_id, u.teacher_id,
                       s.name AS school_name, CONCAT(t.first_name, " ", t.last_name) AS teacher_name, u.created_at
                FROM users u
                LEFT JOIN schools s ON u.school_id = s.id
                LEFT JOIN users t ON u.teacher_id = t.id
                WHERE u.role = "student"';
        $params = [];

        // Teachers can only see their own students
        if ($role === 'teacher') {
            $sql .= ' AND u.teacher_id = ?';
            $params[] = currentUserId();
        }

        if ($search !== '') {
            $sql .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.id LIKE ?)';
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        // Count total
        $countSql = preg_replace('/^SELECT .+ FROM /s', 'SELECT COUNT(*) FROM ', $sql);
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Pagination
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;

        $sql .= ' ORDER BY u.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage]);
        break;

    /* ---------- CREATE ---------- */
    case 'create':
        requireRole(['super_admin', 'manager', 'teacher']);
        verifyCsrf();
        $role = currentRole();

        $required = ['email', 'first_name', 'last_name', 'school_id', 'guardian_first_name', 'guardian_last_name'];
        $err = requiredFields($required, $_POST);
        if ($err) jsonResponse(false, $err);

        $email    = trim($_POST['email']);
        $fname    = sanitize($_POST['first_name']);
        $lname    = sanitize($_POST['last_name']);
        $schoolId = (int) $_POST['school_id'];
        $gfname   = sanitize($_POST['guardian_first_name']);
        $glname   = sanitize($_POST['guardian_last_name']);

        if (!validateEmail($email)) jsonResponse(false, 'Invalid email address.');

        // Teacher ID logic
        if ($role === 'teacher') {
            $teacherId = currentUserId();
        } else {
            $teacherId = (int) ($_POST['teacher_id'] ?? 0);
            if (!$teacherId) jsonResponse(false, 'Teacher is required.');
        }

        // Optional manual ID (only for super_admin / manager)
        $manualId = null;
        if (in_array($role, ['super_admin', 'manager']) && !empty($_POST['manual_id'])) {
            $manualId = (int) $_POST['manual_id'];
            if ($manualId <= 0) jsonResponse(false, 'Invalid manual ID.');
        }

        $db = getDB();

        // Email uniqueness: only check against non-student roles
        $exists = $db->prepare('SELECT id FROM users WHERE email = ? AND role != "student"');
        $exists->execute([$email]);
        if ($exists->fetch()) jsonResponse(false, 'Email already in use by a non-student account.');

        // Check manual ID uniqueness
        if ($manualId) {
            $idExists = $db->prepare('SELECT id FROM users WHERE id = ?');
            $idExists->execute([$manualId]);
            if ($idExists->fetch()) jsonResponse(false, 'The entered ID is already in use.');
        }

        // If another student already uses this email, reuse their password (same parent)
        $existingStudent = $db->prepare('SELECT id, password FROM users WHERE email = ? AND role = "student" LIMIT 1');
        $existingStudent->execute([$email]);
        $siblingRow = $existingStudent->fetch();

        $sendEmail = true;
        if ($siblingRow) {
            $hash = $siblingRow['password']; // reuse same password
            $sendEmail = false; // parent already has credentials
            $tempPw = null;
        } else {
            $tempPw = generateTempPassword();
            $hash   = password_hash($tempPw, PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if ($manualId) {
            $stmt = $db->prepare('INSERT INTO users (id, first_name, last_name, email, password, role, school_id, teacher_id, guardian_first_name, guardian_last_name) VALUES (?, ?, ?, ?, ?, "student", ?, ?, ?, ?)');
            $stmt->execute([$manualId, $fname, $lname, $email, $hash, $schoolId, $teacherId, $gfname, $glname]);
            $newId = $manualId;
        } else {
            $stmt = $db->prepare('INSERT INTO users (first_name, last_name, email, password, role, school_id, teacher_id, guardian_first_name, guardian_last_name) VALUES (?, ?, ?, ?, "student", ?, ?, ?, ?)');
            $stmt->execute([$fname, $lname, $email, $hash, $schoolId, $teacherId, $gfname, $glname]);
            $newId = (int) $db->lastInsertId();
        }

        if ($sendEmail && $tempPw) {
            sendWelcomeEmail($email, $fname, $tempPw, 'student', $gfname . ' ' . $glname);
        }

        // Fetch full record for return
        $rec = $db->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.guardian_first_name, u.guardian_last_name,
                    u.school_id, u.teacher_id,
                    s.name AS school_name, CONCAT(t.first_name, " ", t.last_name) AS teacher_name, u.created_at
             FROM users u LEFT JOIN schools s ON u.school_id = s.id LEFT JOIN users t ON u.teacher_id = t.id
             WHERE u.id = ?'
        );
        $rec->execute([$newId]);

        $msg = $sendEmail ? 'Student created successfully. Login credentials sent via email.' : 'Student added under existing parent email.';
        jsonResponse(true, $msg, ['record' => $rec->fetch()]);
        break;

    /* ---------- UPDATE ---------- */
    case 'update':
        requireRole(['super_admin', 'manager', 'teacher']);
        verifyCsrf();
        $id   = (int) ($_POST['id'] ?? 0);
        $role = currentRole();
        if (!$id) jsonResponse(false, 'Invalid ID.');

        $required = ['email', 'first_name', 'last_name', 'school_id', 'guardian_first_name', 'guardian_last_name'];
        $err = requiredFields($required, $_POST);
        if ($err) jsonResponse(false, $err);

        $email    = trim($_POST['email']);
        $fname    = sanitize($_POST['first_name']);
        $lname    = sanitize($_POST['last_name']);
        $schoolId = (int) $_POST['school_id'];
        $gfname   = sanitize($_POST['guardian_first_name']);
        $glname   = sanitize($_POST['guardian_last_name']);

        if (!validateEmail($email)) jsonResponse(false, 'Invalid email address.');

        if ($role === 'teacher') {
            $teacherId = currentUserId();
        } else {
            $teacherId = (int) ($_POST['teacher_id'] ?? 0);
            if (!$teacherId) jsonResponse(false, 'Teacher is required.');
        }

        $db = getDB();
        // Email uniqueness: only block if a non-student has this email
        $dup = $db->prepare('SELECT id FROM users WHERE email = ? AND role != "student" AND id != ?');
        $dup->execute([$email, $id]);
        if ($dup->fetch()) jsonResponse(false, 'Email already in use by a non-student account.');

        // Check record exists
        $checkSql = 'SELECT id FROM users WHERE id = ? AND role = "student"';
        $checkParams = [$id];
        if ($role === 'teacher') {
            $checkSql .= ' AND teacher_id = ?';
            $checkParams[] = currentUserId();
        }
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute($checkParams);
        if (!$checkStmt->fetch()) jsonResponse(false, 'Student not found.');

        // Teachers can only edit their own students
        $sql = 'UPDATE users SET first_name=?, last_name=?, email=?, school_id=?, teacher_id=?, guardian_first_name=?, guardian_last_name=? WHERE id=? AND role="student"';
        $sqlParams = [$fname, $lname, $email, $schoolId, $teacherId, $gfname, $glname, $id];

        if ($role === 'teacher') {
            $sql .= ' AND teacher_id=?';
            $sqlParams[] = currentUserId();
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($sqlParams);

        // Fetch updated record
        $rec = $db->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.guardian_first_name, u.guardian_last_name,
                    u.school_id, u.teacher_id,
                    s.name AS school_name, CONCAT(t.first_name, " ", t.last_name) AS teacher_name, u.created_at
             FROM users u LEFT JOIN schools s ON u.school_id = s.id LEFT JOIN users t ON u.teacher_id = t.id
             WHERE u.id = ?'
        );
        $rec->execute([$id]);
        jsonResponse(true, 'Student updated successfully.', ['record' => $rec->fetch()]);
        break;

    /* ---------- DELETE ---------- */
    case 'delete':
        requireRole(['super_admin', 'manager', 'teacher']);
        verifyCsrf();
        $id   = (int) ($_POST['id'] ?? 0);
        $role = currentRole();
        if (!$id) jsonResponse(false, 'Invalid ID.');

        $db = getDB();
        $sql = 'DELETE FROM users WHERE id = ? AND role = "student"';
        $params = [$id];

        if ($role === 'teacher') {
            $sql .= ' AND teacher_id = ?';
            $params[] = currentUserId();
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() === 0) jsonResponse(false, 'Student not found.');
        jsonResponse(true, 'Student deleted successfully.', ['deleted_id' => $id]);
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
