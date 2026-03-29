<?php

/**
 * API: Managers CRUD
 * Allowed: super_admin only
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
        requireRole(['super_admin']);
        $db = getDB();
        $search = trim($_GET['search'] ?? '');
        $sql = 'SELECT id, first_name, last_name, email, created_at FROM users WHERE role = "manager"';
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)';
            $like = "%{$search}%";
            $params = [$like, $like, $like];
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- CREATE ---------- */
    case 'create':
        requireRole(['super_admin']);
        verifyCsrf();
        $err = requiredFields(['first_name', 'last_name', 'email'], $_POST);
        if ($err) jsonResponse(false, $err);

        $fname = sanitize($_POST['first_name']);
        $lname = sanitize($_POST['last_name']);
        $email = trim($_POST['email']);

        if (!validateEmail($email)) jsonResponse(false, 'Invalid email address.');

        $db = getDB();
        $exists = $db->prepare('SELECT id FROM users WHERE email = ?');
        $exists->execute([$email]);
        if ($exists->fetch()) jsonResponse(false, 'Email already exists.');

        $tempPw = generateTempPassword();
        $hash   = password_hash($tempPw, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $db->prepare('INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, "manager")');
        $stmt->execute([$fname, $lname, $email, $hash]);
        $newId = (int) $db->lastInsertId();

        sendWelcomeEmail($email, $fname, $tempPw, 'manager');

        jsonResponse(true, 'Manager created successfully.', ['record' => [
            'id' => $newId,
            'first_name' => $fname,
            'last_name' => $lname,
            'email' => $email,
            'created_at' => date('Y-m-d H:i:s')
        ]]);
        break;

    /* ---------- UPDATE ---------- */
    case 'update':
        requireRole(['super_admin']);
        verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) jsonResponse(false, 'Invalid ID.');

        $err = requiredFields(['first_name', 'last_name', 'email'], $_POST);
        if ($err) jsonResponse(false, $err);

        $fname = sanitize($_POST['first_name']);
        $lname = sanitize($_POST['last_name']);
        $email = trim($_POST['email']);

        if (!validateEmail($email)) jsonResponse(false, 'Invalid email address.');

        $db = getDB();
        $dup = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $dup->execute([$email, $id]);
        if ($dup->fetch()) jsonResponse(false, 'Email already in use.');

        // Check record exists
        $exists = $db->prepare('SELECT id FROM users WHERE id = ? AND role = "manager"');
        $exists->execute([$id]);
        if (!$exists->fetch()) jsonResponse(false, 'Manager not found.');

        $stmt = $db->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ? AND role = "manager"');
        $stmt->execute([$fname, $lname, $email, $id]);

        jsonResponse(true, 'Manager updated successfully.', ['record' => [
            'id' => $id,
            'first_name' => $fname,
            'last_name' => $lname,
            'email' => $email
        ]]);
        break;

    /* ---------- DELETE ---------- */
    case 'delete':
        requireRole(['super_admin']);
        verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) jsonResponse(false, 'Invalid ID.');

        $db = getDB();
        $stmt = $db->prepare('DELETE FROM users WHERE id = ? AND role = "manager"');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) jsonResponse(false, 'Manager not found.');
        jsonResponse(true, 'Manager deleted successfully.', ['deleted_id' => $id]);
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
