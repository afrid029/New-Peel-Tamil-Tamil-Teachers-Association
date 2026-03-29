<?php

/**
 * API: Posters CRUD (single image)
 * Manage: super_admin, manager
 * Public: anyone (homepage)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
startSecureSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    /* ---------- PUBLIC LIST ---------- */
    case 'public':
        $db = getDB();
        $stmt = $db->query('SELECT id, title, image_path AS image_file, created_at FROM posters ORDER BY created_at DESC LIMIT 20');
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- LIST ---------- */
    case 'list':
        requireRole(['super_admin', 'manager']);
        $db = getDB();
        $stmt = $db->query(
            'SELECT p.id, p.title, CONCAT(u.first_name, " ", u.last_name) AS author, p.created_at
             FROM posters p
             JOIN users u ON p.created_by = u.id
             ORDER BY p.created_at DESC'
        );
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- CREATE ---------- */
    case 'create':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();
        $title = sanitize($_POST['title'] ?? '');
        if ($title === '') jsonResponse(false, 'Title is required.');

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(false, 'Image file is required.');
        }

        $file = $_FILES['image'];
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            jsonResponse(false, 'File size exceeds 5 MB limit.');
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            jsonResponse(false, 'Only JPG, PNG, GIF, and WEBP images are allowed.');
        }

        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'][$mime];
        $filename = 'poster_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destDir  = UPLOAD_DIR . '/posters';

        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        $destPath = $destDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            jsonResponse(false, 'Failed to upload image. Try again.');
        }

        $db = getDB();
        $stmt = $db->prepare('INSERT INTO posters (title, image_path, created_by) VALUES (?, ?, ?)');
        $stmt->execute([$title, $filename, currentUserId()]);
        $newId = (int) $db->lastInsertId();
        $author = currentUser()['first_name'] . ' ' . currentUser()['last_name'];
        jsonResponse(true, 'Poster created successfully.', ['record' => [
            'id' => $newId,
            'title' => $title,
            'image_path' => $filename,
            'author' => $author,
            'created_at' => date('Y-m-d H:i:s')
        ]]);
        break;

    /* ---------- UPDATE ---------- */
    case 'update':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();
        $id    = (int) ($_POST['id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        if (!$id || $title === '') jsonResponse(false, 'ID and title are required.');

        $db = getDB();

        // Check record exists
        $exists = $db->prepare('SELECT id FROM posters WHERE id = ?');
        $exists->execute([$id]);
        if (!$exists->fetch()) jsonResponse(false, 'Poster not found.');

        // Handle optional new image
        $newFilename = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            if ($file['size'] > MAX_UPLOAD_SIZE) jsonResponse(false, 'File size exceeds 5 MB limit.');

            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if (!in_array($mime, $allowed, true)) jsonResponse(false, 'Invalid image type.');

            $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'][$mime];
            $newFilename = 'poster_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destDir = UPLOAD_DIR . '/posters';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);

            if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $newFilename)) {
                jsonResponse(false, 'Failed to upload image.');
            }

            // Delete old image
            $old = $db->prepare('SELECT image_path FROM posters WHERE id = ?');
            $old->execute([$id]);
            $oldFile = $old->fetchColumn();
            if ($oldFile && file_exists($destDir . '/' . $oldFile)) {
                unlink($destDir . '/' . $oldFile);
            }
        }

        if ($newFilename) {
            $stmt = $db->prepare('UPDATE posters SET title=?, image_path=? WHERE id=?');
            $stmt->execute([$title, $newFilename, $id]);
        } else {
            $stmt = $db->prepare('UPDATE posters SET title=? WHERE id=?');
            $stmt->execute([$title, $id]);
        }

        jsonResponse(true, 'Poster updated successfully.', ['record' => [
            'id' => $id,
            'title' => $title,
            'image_path' => $newFilename ?: null
        ]]);
        break;

    /* ---------- DELETE ---------- */
    case 'delete':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) jsonResponse(false, 'Invalid ID.');

        $db = getDB();
        $old = $db->prepare('SELECT image_path FROM posters WHERE id = ?');
        $old->execute([$id]);
        $oldFile = $old->fetchColumn();

        $stmt = $db->prepare('DELETE FROM posters WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) jsonResponse(false, 'Poster not found.');

        // Delete file
        if ($oldFile) {
            $path = UPLOAD_DIR . '/posters/' . $oldFile;
            if (file_exists($path)) unlink($path);
        }

        jsonResponse(true, 'Poster deleted successfully.', ['deleted_id' => $id]);
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
