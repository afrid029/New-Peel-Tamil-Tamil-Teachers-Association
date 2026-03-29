<?php

/**
 * API: Manual Exam Registration & Results Entry
 * Allowed: super_admin, manager
 * 
 * Allows admins to register students for exams and enter marks
 * in a single operation (for exams that are already finished).
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
startSecureSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    /* ---------- LIST STUDENTS ---------- */
    case 'students':
        requireRole(['super_admin', 'manager']);
        $db     = getDB();
        $search = trim($_GET['search'] ?? '');

        $sql = 'SELECT u.id, u.first_name, u.last_name, u.email,
                       u.guardian_first_name, u.guardian_last_name,
                       s.name AS school_name,
                       (SELECT COUNT(*) FROM exam_registrations er WHERE er.student_id = u.id) AS registration_count
                FROM users u
                LEFT JOIN schools s ON u.school_id = s.id
                WHERE u.role = "student"';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.id LIKE ?)';
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        // Count
        $countSql  = preg_replace('/^SELECT .+ FROM /s', 'SELECT COUNT(*) FROM ', $sql);
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
        jsonResponse(true, 'OK', [
            'data'     => $stmt->fetchAll(),
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ]);
        break;

    /* ---------- LIST EXAMS ---------- */
    case 'exams':
        requireRole(['super_admin', 'manager']);
        $db   = getDB();
        $stmt = $db->query('SELECT id, name, exam_date FROM exams ORDER BY created_at DESC');
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- ACTIVE EXAM TYPES ---------- */
    case 'exam_types':
        requireRole(['super_admin', 'manager']);
        $db   = getDB();
        $stmt = $db->query('SELECT id, name FROM exam_types WHERE is_active = 1 ORDER BY name ASC');
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- CHECK EXISTING REGISTRATION ---------- */
    case 'check':
        requireRole(['super_admin', 'manager']);
        $examId    = (int) ($_GET['exam_id'] ?? 0);
        $studentId = (int) ($_GET['student_id'] ?? 0);
        if (!$examId || !$studentId) jsonResponse(false, 'Missing parameters.');

        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT er.id AS registration_id, er.grade,
                    GROUP_CONCAT(ert.exam_type_id) AS type_ids
             FROM exam_registrations er
             LEFT JOIN exam_registration_types ert ON ert.registration_id = er.id
             WHERE er.exam_id = ? AND er.student_id = ?
             GROUP BY er.id'
        );
        $stmt->execute([$examId, $studentId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Also fetch marks
            $mStmt = $db->prepare(
                'SELECT exam_type_id, marks FROM results WHERE registration_id = ?'
            );
            $mStmt->execute([$existing['registration_id']]);
            $marks = [];
            while ($row = $mStmt->fetch()) {
                $marks[$row['exam_type_id']] = $row['marks'];
            }
            $existing['marks'] = $marks;
            $existing['type_ids'] = $existing['type_ids'] ? explode(',', $existing['type_ids']) : [];
        }

        jsonResponse(true, 'OK', ['existing' => $existing ?: null]);
        break;

    /* ---------- SAVE (Register + Marks) ---------- */
    case 'save':
        requireRole(['super_admin', 'manager']);
        verifyCsrf();

        $examId    = (int) ($_POST['exam_id'] ?? 0);
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $grade     = trim($_POST['grade'] ?? '');
        $marksJson = $_POST['marks'] ?? '{}';

        if (!$examId)    jsonResponse(false, 'Exam is required.');
        if (!$studentId) jsonResponse(false, 'Student is required.');
        if ($grade === '') jsonResponse(false, 'Grade is required.');

        $marks = json_decode($marksJson, true);
        if (!is_array($marks) || empty($marks)) {
            jsonResponse(false, 'At least one exam type with marks is required.');
        }

        // Validate marks values
        foreach ($marks as $typeId => $value) {
            if ($value !== null && $value !== '' && (float) $value < 0) {
                jsonResponse(false, 'Marks cannot be negative.');
            }
        }

        $db = getDB();

        // Verify exam exists
        $exCheck = $db->prepare('SELECT id FROM exams WHERE id = ?');
        $exCheck->execute([$examId]);
        if (!$exCheck->fetch()) jsonResponse(false, 'Exam not found.');

        // Verify student exists
        $stCheck = $db->prepare('SELECT id FROM users WHERE id = ? AND role = "student"');
        $stCheck->execute([$studentId]);
        if (!$stCheck->fetch()) jsonResponse(false, 'Student not found.');

        $db->beginTransaction();
        try {
            // Check if registration already exists
            $existing = $db->prepare('SELECT id FROM exam_registrations WHERE exam_id = ? AND student_id = ?');
            $existing->execute([$examId, $studentId]);
            $regRow = $existing->fetch();

            if ($regRow) {
                $regId = (int) $regRow['id'];
                // Update grade
                $db->prepare('UPDATE exam_registrations SET grade = ? WHERE id = ?')->execute([$grade, $regId]);
                // Remove old exam type links
                $db->prepare('DELETE FROM exam_registration_types WHERE registration_id = ?')->execute([$regId]);
                // Remove old results for this registration
                $db->prepare('DELETE FROM results WHERE registration_id = ?')->execute([$regId]);
            } else {
                $ins = $db->prepare('INSERT INTO exam_registrations (exam_id, student_id, grade) VALUES (?, ?, ?)');
                $ins->execute([$examId, $studentId, $grade]);
                $regId = (int) $db->lastInsertId();
            }

            // Insert exam_registration_types and results
            $ertStmt = $db->prepare('INSERT INTO exam_registration_types (registration_id, exam_type_id) VALUES (?, ?)');
            $resStmt = $db->prepare('INSERT INTO results (registration_id, exam_type_id, marks) VALUES (?, ?, ?)');

            foreach ($marks as $typeId => $value) {
                $typeId = (int) $typeId;
                $ertStmt->execute([$regId, $typeId]);
                $marksVal = ($value !== null && $value !== '') ? (float) $value : null;
                $resStmt->execute([$regId, $typeId, $marksVal]);
            }

            $db->commit();
            jsonResponse(true, $regRow ? 'Registration & marks updated successfully.' : 'Registration & marks saved successfully.');
        } catch (\Exception $e) {
            $db->rollBack();
            jsonResponse(false, 'Failed to save. Please try again.');
        }
        break;

    /* ---------- STUDENT REGISTRATIONS (all exams + marks) ---------- */
    case 'student_registrations':
        requireRole(['super_admin', 'manager']);
        $studentId = (int) ($_GET['student_id'] ?? 0);
        if (!$studentId) jsonResponse(false, 'Student ID is required.');

        $db = getDB();
        $stmt = $db->prepare(
            'SELECT er.id AS registration_id, er.grade, er.exam_id,
                    e.name AS exam_name, e.exam_date
             FROM exam_registrations er
             JOIN exams e ON er.exam_id = e.id
             WHERE er.student_id = ?
             ORDER BY e.created_at DESC'
        );
        $stmt->execute([$studentId]);
        $registrations = $stmt->fetchAll();

        foreach ($registrations as &$reg) {
            $ts = $db->prepare(
                'SELECT et.id AS exam_type_id, et.name AS exam_type_name, r.marks
                 FROM exam_registration_types ert
                 JOIN exam_types et ON ert.exam_type_id = et.id
                 LEFT JOIN results r ON r.registration_id = ert.registration_id AND r.exam_type_id = ert.exam_type_id
                 WHERE ert.registration_id = ?'
            );
            $ts->execute([$reg['registration_id']]);
            $reg['exam_types'] = $ts->fetchAll();
        }
        unset($reg);

        jsonResponse(true, 'OK', ['data' => $registrations]);
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
