<?php

/**
 * API: Notices CRUD
 * Manage: super_admin, manager
 * Public: anyone (homepage)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
startSecureSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    /* ---------- PUBLIC LIST (homepage, no auth) ---------- */
    case 'public':
        $db = getDB();
        $stmt = $db->query('SELECT id, title, content, created_at FROM notices ORDER BY created_at DESC LIMIT 20');
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- LIST (dashboard) ---------- */
    case 'list':
        requireRole(['super_admin', 'manager']);
        $db = getDB();
        $stmt = $db->query(
            'SELECT n.*, CONCAT(u.first_name, " ", u.last_name) AS author
             FROM notices n
             JOIN users u ON n.created_by = u.id
             ORDER BY n.created_at DESC'
        );
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- CREATE ---------- */
    case 'create':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();
        $title   = sanitize($_POST['title'] ?? '');
        $content = sanitize($_POST['content'] ?? '');
        if ($title === '' || $content === '') jsonResponse(false, 'Title and content are required.');

        $db = getDB();
        $stmt = $db->prepare('INSERT INTO notices (title, content, created_by) VALUES (?, ?, ?)');
        $stmt->execute([$title, $content, currentUserId()]);
        $newId = (int) $db->lastInsertId();
        $author = currentUser()['first_name'] . ' ' . currentUser()['last_name'];
        jsonResponse(true, 'Notice created successfully.', ['record' => [
            'id' => $newId,
            'title' => $title,
            'content' => $content,
            'author' => $author,
            'created_at' => date('Y-m-d H:i:s')
        ]]);
        break;

    /* ---------- UPDATE ---------- */
    case 'update':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();
        $id      = (int) ($_POST['id'] ?? 0);
        $title   = sanitize($_POST['title'] ?? '');
        $content = sanitize($_POST['content'] ?? '');
        if (!$id || $title === '' || $content === '') jsonResponse(false, 'All fields are required.');

        $db = getDB();

        // Check record exists
        $exists = $db->prepare('SELECT id FROM notices WHERE id = ?');
        $exists->execute([$id]);
        if (!$exists->fetch()) jsonResponse(false, 'Notice not found.');

        $stmt = $db->prepare('UPDATE notices SET title=?, content=? WHERE id=?');
        $stmt->execute([$title, $content, $id]);
        jsonResponse(true, 'Notice updated successfully.', ['record' => [
            'id' => $id,
            'title' => $title,
            'content' => $content
        ]]);
        break;

    /* ---------- DELETE ---------- */
    case 'delete':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) jsonResponse(false, 'Invalid ID.');

        $db = getDB();
        $stmt = $db->prepare('DELETE FROM notices WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) jsonResponse(false, 'Notice not found.');
        jsonResponse(true, 'Notice deleted successfully.', ['deleted_id' => $id]);
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
