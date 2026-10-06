<?php

/**
 * API: Registered Students
 * View registered students by exam, optionally filtered by grade and exam type
 * Access: super_admin, manager
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
startSecureSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    /* ---------- LIST EXAMS ---------- */
    case 'exams':
        requireRole(['super_admin', 'manager']);
        $db = getDB();
        $stmt = $db->query('SELECT id, name FROM exams ORDER BY created_at DESC');
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- LIST EXAM TYPES ---------- */
    case 'exam_types':
        requireRole(['super_admin', 'manager']);
        $db = getDB();
        $stmt = $db->query('SELECT id, name FROM exam_types WHERE is_active = 1 ORDER BY name ASC');
        jsonResponse(true, 'OK', ['data' => $stmt->fetchAll()]);
        break;

    /* ---------- SEARCH REGISTERED STUDENTS ---------- */
    case 'search':
        requireRole(['super_admin', 'manager']);

        $examId    = (int) ($_GET['exam_id'] ?? 0);
        $grade     = trim($_GET['grade'] ?? '');
        $examType  = (int) ($_GET['exam_type_id'] ?? 0);

        if (!$examId) jsonResponse(false, 'Exam is required.');

        $db = getDB();

        // Build WHERE conditions
        $where  = ['er.exam_id = ?'];
        $params = [$examId];

        if ($grade !== '') {
            $where[]  = 'er.grade = ?';
            $params[] = $grade;
        }

        if ($examType > 0) {
            $where[]  = 'ert.exam_type_id = ?';
            $params[] = $examType;
        }

        $whereClause = implode(' AND ', $where);

        // When filtering by exam type, we need the JOIN always;
        // otherwise make it optional so students without types still show
        $joinType = $examType > 0 ? 'JOIN' : 'LEFT JOIN';

        // Count total (distinct registrations)
        $countSql = "SELECT COUNT(DISTINCT er.id)
                     FROM exam_registrations er
                     {$joinType} exam_registration_types ert ON ert.registration_id = er.id
                     JOIN users u ON er.student_id = u.id
                     JOIN exams e ON er.exam_id = e.id
                     WHERE {$whereClause}";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Pagination
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;

        // Fetch distinct registrations
        $dataSql = "SELECT DISTINCT er.id AS registration_id, er.grade,
                           u.id AS student_id, u.first_name, u.last_name,
                           e.name AS exam_name
                    FROM exam_registrations er
                    {$joinType} exam_registration_types ert ON ert.registration_id = er.id
                    JOIN users u ON er.student_id = u.id
                    JOIN exams e ON er.exam_id = e.id
                    WHERE {$whereClause}
                    ORDER BY u.first_name ASC
                    LIMIT ? OFFSET ?";
        $dataParams = array_merge($params, [$perPage, $offset]);
        $dataStmt = $db->prepare($dataSql);
        $dataStmt->execute($dataParams);
        $rows = $dataStmt->fetchAll();

        // For each registration, get exam type names
        foreach ($rows as &$row) {
            $etStmt = $db->prepare(
                'SELECT et.name
                 FROM exam_registration_types ert
                 JOIN exam_types et ON ert.exam_type_id = et.id
                 WHERE ert.registration_id = ?
                 ORDER BY et.name ASC'
            );
            $etStmt->execute([$row['registration_id']]);
            $row['exam_types'] = $etStmt->fetchAll(PDO::FETCH_COLUMN);
        }
        unset($row);

        jsonResponse(true, 'OK', [
            'data'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ]);
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
