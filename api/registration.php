<?php

/**
 * API: Exam Registration
 * Register: student only
 * Active exam: public (for homepage)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
startSecureSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    /* ---------- ACTIVE EXAM (registration open, based on current date) ---------- */
    case 'active_exam':
        $db = getDB();
        $today = date('Y-m-d');
        $stmt = $db->prepare('SELECT * FROM exams WHERE registration_start_date <= ? AND registration_end_date >= ? ORDER BY registration_start_date ASC LIMIT 1');
        $stmt->execute([$today, $today]);
        $exam = $stmt->fetch();
        jsonResponse(true, 'OK', ['data' => $exam ?: null]);
        break;

    /* ---------- REGISTER ---------- */
    case 'register':
        requireRole(['student']);
        verifyCsrf();

        $examId    = (int) ($_POST['exam_id'] ?? 0);
        $grade     = trim($_POST['grade'] ?? '');
        $examTypes = $_POST['exam_types'] ?? [];

        // Accept student_id from dropdown (multi-child)
        $studentId = (int) ($_POST['student_id'] ?? 0);
        if (!$studentId || !isMyChild($studentId)) jsonResponse(false, 'Please select a valid child.');

        if (!$examId) jsonResponse(false, 'Exam is required.');
        if ($grade === '') jsonResponse(false, 'Grade is required.');
        $validGrades = ['1', '2', '3', '4', '5', '6', '7', '8', 'SK', 'JK'];
        if (!in_array($grade, $validGrades, true)) jsonResponse(false, 'Invalid grade selected.');
        if (empty($examTypes) || !is_array($examTypes)) jsonResponse(false, 'Select at least one exam type.');

        $db = getDB();

        // Check exam is active
        $today = date('Y-m-d');
        $examStmt = $db->prepare('SELECT id FROM exams WHERE id = ? AND registration_start_date <= ? AND registration_end_date >= ?');
        $examStmt->execute([$examId, $today, $today]);
        if (!$examStmt->fetch()) jsonResponse(false, 'Registration is not open for this exam.');

        // Check not already registered
        $dupStmt = $db->prepare('SELECT id FROM exam_registrations WHERE exam_id = ? AND student_id = ?');
        $dupStmt->execute([$examId, $studentId]);
        if ($dupStmt->fetch()) jsonResponse(false, 'This child is already registered for this exam.');

        // Create registration
        $regStmt = $db->prepare('INSERT INTO exam_registrations (exam_id, student_id, grade) VALUES (?, ?, ?)');
        $regStmt->execute([$examId, $studentId, $grade]);
        $regId = (int) $db->lastInsertId();

        // Link exam types
        $typeStmt = $db->prepare('INSERT INTO exam_registration_types (registration_id, exam_type_id) VALUES (?, ?)');
        foreach ($examTypes as $etId) {
            $etId = (int) $etId;
            if ($etId > 0) $typeStmt->execute([$regId, $etId]);
        }

        jsonResponse(true, 'Successfully registered for the exam!');
        break;

    /* ---------- MY REGISTRATIONS (student – for a specific child or all children) ---------- */
    case 'my_registrations':
        requireRole(['student']);
        $db = getDB();

        $childId = (int) ($_GET['student_id'] ?? 0);
        $childIds = myChildIds();

        if ($childId && !isMyChild($childId)) jsonResponse(false, 'Invalid child.');

        $ids = $childId ? [$childId] : $childIds;
        if (empty($ids)) jsonResponse(true, 'OK', ['data' => []]);

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare(
            "SELECT er.id AS registration_id, er.grade, er.created_at AS registered_on,
                    e.id AS exam_id, e.name AS exam_name, e.exam_date,
                    er.student_id, u.first_name AS child_first_name, u.last_name AS child_last_name
             FROM exam_registrations er
             JOIN exams e ON er.exam_id = e.id
             JOIN users u ON er.student_id = u.id
             WHERE er.student_id IN ({$placeholders})
             ORDER BY er.created_at DESC"
        );
        $stmt->execute($ids);
        $registrations = $stmt->fetchAll();

        // Get exam types for each registration
        foreach ($registrations as &$reg) {
            $ts = $db->prepare(
                'SELECT et.name FROM exam_registration_types ert
                 JOIN exam_types et ON ert.exam_type_id = et.id
                 WHERE ert.registration_id = ?'
            );
            $ts->execute([$reg['registration_id']]);
            $reg['exam_types'] = $ts->fetchAll(PDO::FETCH_COLUMN);
        }
        unset($reg);

        jsonResponse(true, 'OK', ['data' => $registrations]);
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
