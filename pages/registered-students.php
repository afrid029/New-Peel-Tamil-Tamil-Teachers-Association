<?php
$db = getDB();
$grades = ['JK', 'SK', '1', '2', '3', '4', '5', '6', '7', '8'];
$exams = $db->query('SELECT id, name FROM exams ORDER BY created_at DESC')->fetchAll();
$examTypes = $db->query('SELECT id, name FROM exam_types WHERE is_active = 1 ORDER BY name ASC')->fetchAll();

$examId = max(0, (int) ($_GET['exam_id'] ?? 0));
$grade = trim((string) ($_GET['grade'] ?? ''));
if (!in_array($grade, $grades, true)) {
    $grade = '';
}
$examTypeId = max(0, (int) ($_GET['exam_type_id'] ?? 0));
$search = trim((string) ($_GET['search'] ?? ''));
$resultPage = max(1, (int) ($_GET['result_page'] ?? 1));
$perPage = 20;
$total = 0;
$registrations = [];

if ($examId > 0) {
    $conditions = ['er.exam_id = ?'];
    $params = [$examId];

    if ($grade !== '') {
        $conditions[] = 'er.grade = ?';
        $params[] = $grade;
    }
    if ($examTypeId > 0) {
        $conditions[] = 'EXISTS (
            SELECT 1 FROM exam_registration_types filter_type
            WHERE filter_type.registration_id = er.id AND filter_type.exam_type_id = ?
        )';
        $params[] = $examTypeId;
    }
    if ($search !== '') {
        $like = '%' . $search . '%';
        $conditions[] = '(u.first_name LIKE ? OR u.last_name LIKE ? OR CONCAT(u.first_name, CHAR(32), u.last_name) LIKE ? OR e.name LIKE ? OR er.grade LIKE ? OR EXISTS (
            SELECT 1
            FROM exam_registration_types search_type
            JOIN exam_types search_exam_type ON search_exam_type.id = search_type.exam_type_id
            WHERE search_type.registration_id = er.id AND search_exam_type.name LIKE ?
        ))';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }

    $whereClause = implode(' AND ', $conditions);
    $countStmt = $db->prepare(
        "SELECT COUNT(*)
         FROM exam_registrations er
         JOIN users u ON u.id = er.student_id
         JOIN exams e ON e.id = er.exam_id
         WHERE {$whereClause}"
    );
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $totalPages = max(1, (int) ceil($total / $perPage));
    $resultPage = min($resultPage, $totalPages);
    $offset = ($resultPage - 1) * $perPage;
    $dataStmt = $db->prepare(
        "SELECT er.id AS registration_id, er.created_at AS registered_on, er.grade,
            u.id AS student_id, u.first_name, u.last_name, s.name AS school_name,
            e.name AS exam_name, e.exam_date
         FROM exam_registrations er
         JOIN users u ON u.id = er.student_id
         JOIN exams e ON e.id = er.exam_id
         LEFT JOIN schools s ON s.id = u.school_id
         WHERE {$whereClause}
         ORDER BY u.first_name ASC, u.last_name ASC
         LIMIT ? OFFSET ?"
    );
    $dataStmt->execute(array_merge($params, [$perPage, $offset]));
    $registrations = $dataStmt->fetchAll();

    if ($registrations) {
        $registrationIds = array_column($registrations, 'registration_id');
        $placeholders = implode(',', array_fill(0, count($registrationIds), '?'));
        $typeStmt = $db->prepare(
            "SELECT ert.registration_id, et.name
             FROM exam_registration_types ert
             JOIN exam_types et ON et.id = ert.exam_type_id
             WHERE ert.registration_id IN ({$placeholders})
             ORDER BY ert.registration_id, et.name"
        );
        $typeStmt->execute($registrationIds);
        $registrationTypes = [];
        foreach ($typeStmt->fetchAll() as $typeRow) {
            $registrationTypes[$typeRow['registration_id']][] = $typeRow['name'];
        }
    } else {
        $registrationTypes = [];
    }
}

$totalPages = max(1, (int) ceil($total / $perPage));
$paginationUrl = static function (int $targetPage) use ($examId, $grade, $examTypeId, $search): string {
    return 'dashboard.php?' . http_build_query([
        'page' => 'registered-students',
        'exam_id' => $examId,
        'grade' => $grade,
        'exam_type_id' => $examTypeId,
        'search' => $search,
        'result_page' => $targetPage,
    ]);
};
?>

<form method="get" action="dashboard.php" class="grid grid-cols-1 sm:grid-cols-5 gap-4 mb-6">
    <input type="hidden" name="page" value="registered-students">
    <div class="form-group">
        <label class="form-label" for="regstu-exam">Exam *</label>
        <select id="regstu-exam" name="exam_id" class="form-input" required>
            <option value="">Select exam...</option>
            <?php foreach ($exams as $exam): ?>
                <option value="<?= (int) $exam['id'] ?>" <?= $examId === (int) $exam['id'] ? 'selected' : '' ?>><?= htmlspecialchars($exam['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="regstu-grade">Grade</label>
        <select id="regstu-grade" name="grade" class="form-input">
            <option value="">All grades</option>
            <?php foreach ($grades as $gradeOption): ?>
                <option value="<?= htmlspecialchars($gradeOption, ENT_QUOTES, 'UTF-8') ?>" <?= $grade === $gradeOption ? 'selected' : '' ?>><?= htmlspecialchars($gradeOption, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="regstu-exam-type">Exam Type</label>
        <select id="regstu-exam-type" name="exam_type_id" class="form-input">
            <option value="">All exam types</option>
            <?php foreach ($examTypes as $examType): ?>
                <option value="<?= (int) $examType['id'] ?>" <?= $examTypeId === (int) $examType['id'] ? 'selected' : '' ?>><?= htmlspecialchars($examType['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="regstu-search">Search</label>
        <input type="search" id="regstu-search" name="search" class="form-input" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Name, grade, exam type...">
    </div>
    <div class="form-group flex items-end">
        <button type="submit" class="btn-primary w-full">Search</button>
    </div>
</form>

<div class="card">
    <div class="flex justify-between items-center gap-3 mb-4">
        <p class="text-sm" style="color:var(--text-light);">
            <?= $examId > 0 ? number_format($total) . ' student(s) found' : 'Select an exam to view registered students.' ?>
        </p>
    </div>

    <?php if ($examId > 0 && !$registrations): ?>
        <div class="empty-state">
            <p>No registered students found.</p>
        </div>
    <?php elseif ($registrations): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Exam Name</th>
                        <th>Grade</th>
                        <th>Exam Type</th>
                        <th>Print Information</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $registration): ?>
                        <tr>
                            <td><?= htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($registration['exam_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($registration['grade'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(implode(', ', $registrationTypes[$registration['registration_id']] ?? []) ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <button
                                    type="button"
                                    class="btn-secondary btn-sm admission-pdf-btn"
                                    data-registration="<?= htmlspecialchars(json_encode([
                                                            'registration_id' => $registration['registration_id'],
                                                            'registered_on' => $registration['registered_on'],
                                                            'student_id' => $registration['student_id'],
                                                            'first_name' => $registration['first_name'],
                                                            'last_name' => $registration['last_name'],
                                                            'school_name' => $registration['school_name'] ?? '',
                                                            'grade' => $registration['grade'],
                                                            'exam_name' => $registration['exam_name'],
                                                            'exam_date' => $registration['exam_date'] ?? '',
                                                            'exam_types' => $registrationTypes[$registration['registration_id']] ?? [],
                                                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>">Download PDF</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($examId > 0 && $totalPages > 1): ?>
    <nav class="flex justify-center items-center gap-2 mt-4" aria-label="Registered students pages">
        <?php if ($resultPage > 1): ?>
            <a class="btn-secondary btn-sm" href="<?= htmlspecialchars($paginationUrl($resultPage - 1), ENT_QUOTES, 'UTF-8') ?>">&laquo; Prev</a>
        <?php endif; ?>
        <?php for ($pageNumber = max(1, $resultPage - 2); $pageNumber <= min($totalPages, $resultPage + 2); $pageNumber++): ?>
            <a class="<?= $pageNumber === $resultPage ? 'btn-primary' : 'btn-secondary' ?> btn-sm" href="<?= htmlspecialchars($paginationUrl($pageNumber), ENT_QUOTES, 'UTF-8') ?>" <?= $pageNumber === $resultPage ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
        <?php endfor; ?>
        <?php if ($resultPage < $totalPages): ?>
            <a class="btn-secondary btn-sm" href="<?= htmlspecialchars($paginationUrl($resultPage + 1), ENT_QUOTES, 'UTF-8') ?>">Next &raquo;</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>