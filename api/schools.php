<?php

/**
 * API: Schools CRUD
 * Allowed: super_admin, manager
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
startSecureSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    /* ---------- LIST ---------- */
    case 'list':
        requireRole(['super_admin', 'manager']);
        $db = getDB();
        $search = trim($_GET['search'] ?? '');
        $sql = 'SELECT * FROM schools';
        $params = [];
        if ($search !== '') {
            $sql .= ' WHERE name LIKE ?';
            $params = ["%{$search}%"];
        }
        $sql .= ' ORDER BY name ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- DROPDOWN (for student form) ---------- */
    case 'dropdown':
        requireLogin();
        $db = getDB();
        $stmt = $db->query('SELECT id, name FROM schools ORDER BY name ASC');
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- CREATE ---------- */
    case 'create':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();
        $name = sanitize($_POST['name'] ?? '');
        if ($name === '') jsonResponse(false, 'School name is required.');

        $db = getDB();
        $stmt = $db->prepare('INSERT INTO schools (name) VALUES (?)');
        $stmt->execute([$name]);
        $newId = (int) $db->lastInsertId();
        jsonResponse(true, 'School created successfully.', ['record' => [
            'id' => $newId,
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s')
        ]]);
        break;

    /* ---------- UPDATE ---------- */
    case 'update':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();
        $id   = (int) ($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        if (!$id || $name === '') jsonResponse(false, 'ID and name are required.');

        $db = getDB();

        $sql = $db->prepare('SELECT * FROM schools WHERE id = ?');
        $sql->execute([$id]);
        $school = $sql->fetch();
        if (!$school) jsonResponse(false, 'School not found.');

        $stmt = $db->prepare('UPDATE schools SET name = ? WHERE id = ?');
        $stmt->execute([$name, $id]);
        jsonResponse(true, 'School updated successfully.', ['record' => [
            'id' => $id,
            'name' => $name
        ]]);
        break;

    /* ---------- DELETE ---------- */
    case 'delete':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) jsonResponse(false, 'Invalid ID.');

        $db = getDB();
        $stmt = $db->prepare('DELETE FROM schools WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) jsonResponse(false, 'School not found.');
        jsonResponse(true, 'School deleted successfully.', ['deleted_id' => $id]);
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
