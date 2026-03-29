<?php

/**
 * API: Exams CRUD
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
        $stmt = $db->query('SELECT * FROM exams ORDER BY created_at DESC');
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- CREATE ---------- */
    case 'create':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();
        $err = requiredFields(['name', 'registration_start_date', 'registration_end_date'], $_POST);
        if ($err) jsonResponse(false, $err);

        $name     = sanitize($_POST['name']);
        $startDt  = $_POST['registration_start_date'];
        $endDt    = $_POST['registration_end_date'];
        $examDate = !empty($_POST['exam_date']) ? $_POST['exam_date'] : null;

        $db = getDB();
        $stmt = $db->prepare('INSERT INTO exams (name, registration_start_date, registration_end_date, exam_date) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $startDt, $endDt, $examDate]);
        $newId = (int) $db->lastInsertId();
        jsonResponse(true, 'Exam created successfully.', ['record' => [
            'id' => $newId,
            'name' => $name,
            'registration_start_date' => $startDt,
            'registration_end_date' => $endDt,
            'exam_date' => $examDate,
            'created_at' => date('Y-m-d H:i:s')
        ]]);
        break;

    /* ---------- UPDATE ---------- */
    case 'update':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) jsonResponse(false, 'Invalid ID.');

        $err = requiredFields(['name', 'registration_start_date', 'registration_end_date'], $_POST);
        if ($err) jsonResponse(false, $err);

        $name     = sanitize($_POST['name']);
        $startDt  = $_POST['registration_start_date'];
        $endDt    = $_POST['registration_end_date'];
        $examDate = !empty($_POST['exam_date']) ? $_POST['exam_date'] : null;

        $db = getDB();

        // Check record exists
        $exists = $db->prepare('SELECT id FROM exams WHERE id = ?');
        $exists->execute([$id]);
        if (!$exists->fetch()) jsonResponse(false, 'Exam not found.');

        $stmt = $db->prepare('UPDATE exams SET name=?, registration_start_date=?, registration_end_date=?, exam_date=? WHERE id=?');
        $stmt->execute([$name, $startDt, $endDt, $examDate, $id]);
        jsonResponse(true, 'Exam updated successfully.', ['record' => [
            'id' => $id,
            'name' => $name,
            'registration_start_date' => $startDt,
            'registration_end_date' => $endDt,
            'exam_date' => $examDate
        ]]);
        break;

    /* ---------- DELETE ---------- */
    case 'delete':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) jsonResponse(false, 'Invalid ID.');

        $db = getDB();
        $stmt = $db->prepare('DELETE FROM exams WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) jsonResponse(false, 'Exam not found.');
        jsonResponse(true, 'Exam deleted successfully.', ['deleted_id' => $id]);
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
