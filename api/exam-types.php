<?php

/**
 * API: Exam Types CRUD
 * Allowed: super_admin only
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
startSecureSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    /* ---------- LIST ---------- */
    case 'list':
        requireRole(['super_admin']);
        $db = getDB();
        $stmt = $db->query('SELECT * FROM exam_types ORDER BY name ASC');
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- DROPDOWN (only active exam types) ---------- */
    case 'dropdown':
        requireLogin();
        $db = getDB();
        $stmt = $db->query('SELECT id, name FROM exam_types WHERE is_active = 1 ORDER BY name ASC');
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- CREATE ---------- */
    case 'create':
        requireRole(['super_admin']);
        verifyCsrf();
        $name = sanitize($_POST['name'] ?? '');
        if ($name === '') jsonResponse(false, 'Exam type name is required.');

        $db = getDB();
        $stmt = $db->prepare('INSERT INTO exam_types (name) VALUES (?)');
        $stmt->execute([$name]);
        $newId = (int) $db->lastInsertId();
        jsonResponse(true, 'Exam type created successfully.', ['record' => [
            'id' => $newId,
            'name' => $name,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]]);
        break;

    /* ---------- UPDATE ---------- */
    case 'update':
        requireRole(['super_admin']);
        verifyCsrf();
        $id   = (int) ($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        if (!$id || $name === '') jsonResponse(false, 'ID and name are required.');

        $db = getDB();

        // Check record exists
        $exists = $db->prepare('SELECT id, is_active FROM exam_types WHERE id = ?');
        $exists->execute([$id]);
        $existing = $exists->fetch();
        if (!$existing) jsonResponse(false, 'Exam type not found.');

        $stmt = $db->prepare('UPDATE exam_types SET name=? WHERE id=?');
        $stmt->execute([$name, $id]);
        jsonResponse(true, 'Exam type updated successfully.', ['record' => [
            'id' => $id,
            'name' => $name,
            'is_active' => (int) $existing['is_active']
        ]]);
        break;

    /* ---------- TOGGLE STATUS ---------- */
    case 'toggle_status':
        requireRole(['super_admin']);
        verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) jsonResponse(false, 'Invalid ID.');

        $db = getDB();
        $row = $db->prepare('SELECT id, name, is_active FROM exam_types WHERE id = ?');
        $row->execute([$id]);
        $et = $row->fetch();
        if (!$et) jsonResponse(false, 'Exam type not found.');

        $newStatus = $et['is_active'] ? 0 : 1;
        $stmt = $db->prepare('UPDATE exam_types SET is_active = ? WHERE id = ?');
        $stmt->execute([$newStatus, $id]);

        $label = $newStatus ? 'activated' : 'deactivated';
        jsonResponse(true, "Exam type {$label} successfully.", ['record' => [
            'id' => $id,
            'name' => $et['name'],
            'is_active' => $newStatus
        ]]);
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
