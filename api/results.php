<?php

/**
 * API: Results
 * Update: super_admin, manager
 * View: student
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
startSecureSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    /* ---------- LIST EXAMS (for result management page) ---------- */
    case 'exams':
        requireRole(['super_admin', 'manager']);
        $db = getDB();
        $stmt = $db->query('SELECT * FROM exams ORDER BY created_at DESC');
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- REGISTERED STUDENTS by exam + grade ---------- */
    case 'registered_students':
        requireRole(['super_admin', 'manager']);
        $examId = (int) ($_GET['exam_id'] ?? 0);
        $grade  = trim($_GET['grade'] ?? '');
        if (!$examId || $grade === '') jsonResponse(false, 'Exam and grade are required.');

        $db = getDB();

        // Count total
        $countStmt = $db->prepare(
            'SELECT COUNT(*) FROM exam_registrations er WHERE er.exam_id = ? AND er.grade = ?'
        );
        $countStmt->execute([$examId, $grade]);
        $total = (int) $countStmt->fetchColumn();

        // Pagination
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;

        $stmt = $db->prepare(
            'SELECT er.id AS registration_id, er.grade,
                    u.id AS student_id, u.first_name, u.last_name
             FROM exam_registrations er
             JOIN users u ON er.student_id = u.id
             WHERE er.exam_id = ? AND er.grade = ?
             ORDER BY u.first_name ASC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$examId, $grade, $perPage, $offset]);
        $students = $stmt->fetchAll();

        // For each student, get their exam types and any existing results
        foreach ($students as &$s) {
            $etStmt = $db->prepare(
                'SELECT ert.exam_type_id, et.name AS exam_type_name,
                        r.marks, r.placement
                 FROM exam_registration_types ert
                 JOIN exam_types et ON ert.exam_type_id = et.id
                 LEFT JOIN results r ON r.registration_id = ert.registration_id AND r.exam_type_id = ert.exam_type_id
                 WHERE ert.registration_id = ?'
            );
            $etStmt->execute([$s['registration_id']]);
            $s['exam_types'] = $etStmt->fetchAll();
        }
        unset($s);

        jsonResponse(true, 'OK', ['data' => $students, 'total' => $total, 'page' => $page, 'per_page' => $perPage]);
        break;

    /* ---------- UPDATE MARKS (single) ---------- */
    case 'update':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();

        $registrationId = (int) ($_POST['registration_id'] ?? 0);
        $examTypeId     = (int) ($_POST['exam_type_id'] ?? 0);
        $marks          = $_POST['marks'] ?? '';
        $hasPlacement   = array_key_exists('placement', $_POST);
        $placement      = $hasPlacement ? $_POST['placement'] : null;
        $placement      = $placement === '' ? null : $placement;

        if (!$registrationId || !$examTypeId) jsonResponse(false, 'Registration and exam type are required.');
        if ($marks === '' || !is_numeric($marks)) jsonResponse(false, 'Valid marks are required.');
        if ($hasPlacement && $placement !== null && !in_array($placement, ['1st', '2nd', '3rd'], true)) jsonResponse(false, 'Invalid placement.');

        $db = getDB();

        // Upsert result
        $existing = $db->prepare('SELECT id FROM results WHERE registration_id = ? AND exam_type_id = ?');
        $existing->execute([$registrationId, $examTypeId]);

        if ($existing->fetch()) {
            if ($hasPlacement) {
                $stmt = $db->prepare('UPDATE results SET marks = ?, placement = ? WHERE registration_id = ? AND exam_type_id = ?');
                $stmt->execute([$marks, $placement, $registrationId, $examTypeId]);
            } else {
                $stmt = $db->prepare('UPDATE results SET marks = ? WHERE registration_id = ? AND exam_type_id = ?');
                $stmt->execute([$marks, $registrationId, $examTypeId]);
            }
        } else {
            $stmt = $db->prepare('INSERT INTO results (registration_id, exam_type_id, marks, placement) VALUES (?, ?, ?, ?)');
            $stmt->execute([$registrationId, $examTypeId, $marks, $placement]);
        }

        jsonResponse(true, 'Marks updated successfully.');
        break;

    /* ---------- UPDATE MARKS (bulk – all exam types for one registration) ---------- */
    case 'update_bulk':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();

        $registrationId = (int) ($_POST['registration_id'] ?? 0);
        if (!$registrationId) jsonResponse(false, 'Registration ID is required.');

        $marksJson = $_POST['marks'] ?? '{}';
        $marksData = json_decode($marksJson, true);
        $placementsData = json_decode($_POST['placements'] ?? '{}', true);
        if (!is_array($marksData) || !is_array($placementsData) || (empty($marksData) && empty($placementsData))) jsonResponse(false, 'No marks provided.');

        $db = getDB();

        // Verify registration exists
        $regCheck = $db->prepare('SELECT id FROM exam_registrations WHERE id = ?');
        $regCheck->execute([$registrationId]);
        if (!$regCheck->fetch()) jsonResponse(false, 'Registration not found.');

        $examTypeIds = array_unique(array_merge(array_keys($marksData), array_keys($placementsData)));
        foreach ($examTypeIds as $examTypeId) {
            $examTypeId = (int) $examTypeId;
            if ($examTypeId <= 0) continue;
            $hasMarks = array_key_exists($examTypeId, $marksData);
            $hasPlacement = array_key_exists($examTypeId, $placementsData);
            $marks = $hasMarks ? $marksData[$examTypeId] : null;
            $placement = $hasPlacement ? $placementsData[$examTypeId] : null;
            if ($hasMarks && $marks !== null && $marks !== '') {
                if (!is_numeric($marks)) jsonResponse(false, 'Marks must be numeric.');
                if ((float) $marks < 0) jsonResponse(false, 'Marks cannot be negative.');
            }
            if ($hasPlacement && $placement !== null && !in_array($placement, ['1st', '2nd', '3rd'], true)) {
                jsonResponse(false, 'Invalid placement.');
            }
            if (!$hasMarks && !$hasPlacement) continue;

            $existing = $db->prepare('SELECT id FROM results WHERE registration_id = ? AND exam_type_id = ?');
            $existing->execute([$registrationId, $examTypeId]);

            if ($existing->fetch()) {
                if ($hasMarks && $hasPlacement) {
                    $stmt = $db->prepare('UPDATE results SET marks = ?, placement = ? WHERE registration_id = ? AND exam_type_id = ?');
                    $stmt->execute([$marks === '' ? null : $marks, $placement, $registrationId, $examTypeId]);
                } elseif ($hasMarks) {
                    $stmt = $db->prepare('UPDATE results SET marks = ? WHERE registration_id = ? AND exam_type_id = ?');
                    $stmt->execute([$marks === '' ? null : $marks, $registrationId, $examTypeId]);
                } else {
                    $stmt = $db->prepare('UPDATE results SET placement = ? WHERE registration_id = ? AND exam_type_id = ?');
                    $stmt->execute([$placement, $registrationId, $examTypeId]);
                }
            } else {
                $stmt = $db->prepare('INSERT INTO results (registration_id, exam_type_id, marks, placement) VALUES (?, ?, ?, ?)');
                $stmt->execute([$registrationId, $examTypeId, $marks === '' ? null : $marks, $placement]);
            }
        }

        jsonResponse(true, 'All marks updated successfully.');
        break;

    /* ---------- VIEW (student – for a specific registration) ---------- */
    case 'view':
        requireRole(['student']);
        $regId = (int) ($_GET['registration_id'] ?? 0);
        if (!$regId) jsonResponse(false, 'Registration ID is required.');

        $db = getDB();

        // Ensure this registration belongs to one of the parent's children
        $childIds = myChildIds();
        if (empty($childIds)) jsonResponse(false, 'No children found.');
        $placeholders = implode(',', array_fill(0, count($childIds), '?'));
        $regStmt = $db->prepare("SELECT id FROM exam_registrations WHERE id = ? AND student_id IN ({$placeholders})");
        $regStmt->execute(array_merge([$regId], $childIds));
        if (!$regStmt->fetch()) jsonResponse(false, 'Registration not found.');

        $stmt = $db->prepare(
            'SELECT et.name AS exam_type, r.marks, r.placement
             FROM exam_registration_types ert
             JOIN exam_types et ON ert.exam_type_id = et.id
             LEFT JOIN results r ON r.registration_id = ert.registration_id AND r.exam_type_id = ert.exam_type_id
             WHERE ert.registration_id = ?'
        );
        $stmt->execute([$regId]);
        $results = $stmt->fetchAll();

        // Check if any result is updated
        $hasResult = false;
        foreach ($results as $r) {
            if ($r['marks'] !== null) {
                $hasResult = true;
                break;
            }
        }

        if (!$hasResult) {
            jsonResponse(false, 'Results have not been published yet for this exam.');
        }

        jsonResponse(true, 'OK', ['data' => $results]);
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
