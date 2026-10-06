-- ============================================================
-- Test data: 5 synthetic students registered under "Tamil exam 2026"
-- ============================================================

-- 1. Make exam 1 registration window open (covers today)
UPDATE exams
SET registration_start_date = '2026-10-01',
    registration_end_date   = '2026-10-31',
    exam_date               = '2026-11-15'
WHERE id = 1;

-- 2. Activate the "read" exam type so we have 3 usable types
UPDATE exam_types SET is_active = 1 WHERE id = 2;

-- 3. Insert 5 synthetic student users under one guardian
--    Using IDs 1003-1007 (above existing 1002)
INSERT INTO users (id, first_name, last_name, email, password, role, guardian_first_name, guardian_last_name, first_login)
VALUES
  (1003, 'Aarav',  'Rajan', 'testparent@example.com', '$2y$10$dummyhashnotusedforloginAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'student', 'Kavitha', 'Rajan', 0),
  (1004, 'Diya',   'Rajan', 'testparent@example.com', '$2y$10$dummyhashnotusedforloginAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'student', 'Kavitha', 'Rajan', 0),
  (1005, 'Kiran',  'Rajan', 'testparent@example.com', '$2y$10$dummyhashnotusedforloginAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'student', 'Kavitha', 'Rajan', 0),
  (1006, 'Meera',  'Rajan', 'testparent@example.com', '$2y$10$dummyhashnotusedforloginAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'student', 'Kavitha', 'Rajan', 0),
  (1007, 'Surya',  'Rajan', 'testparent@example.com', '$2y$10$dummyhashnotusedforloginAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'student', 'Kavitha', 'Rajan', 0);

-- 4. Register all 5 students for exam 1 with different grades
INSERT INTO exam_registrations (exam_id, student_id, grade) VALUES
  (1, 1003, 'JK'),
  (1, 1004, 'SK'),
  (1, 1005, '3'),
  (1, 1006, '5'),
  (1, 1007, '8');

-- 5. Grab the registration IDs we just created
SET @reg1 = (SELECT id FROM exam_registrations WHERE exam_id=1 AND student_id=1003);
SET @reg2 = (SELECT id FROM exam_registrations WHERE exam_id=1 AND student_id=1004);
SET @reg3 = (SELECT id FROM exam_registrations WHERE exam_id=1 AND student_id=1005);
SET @reg4 = (SELECT id FROM exam_registrations WHERE exam_id=1 AND student_id=1006);
SET @reg5 = (SELECT id FROM exam_registrations WHERE exam_id=1 AND student_id=1007);

-- 6. Link exam types (read=2, write=3, dictation=4)
--    Case 1: Aarav  (JK)  → read only
INSERT INTO exam_registration_types (registration_id, exam_type_id) VALUES (@reg1, 2);

--    Case 2: Diya   (SK)  → read + write
INSERT INTO exam_registration_types (registration_id, exam_type_id) VALUES (@reg2, 2), (@reg2, 3);

--    Case 3: Kiran  (3)   → read + write + dictation
INSERT INTO exam_registration_types (registration_id, exam_type_id) VALUES (@reg3, 2), (@reg3, 3), (@reg3, 4);

--    Case 4: Meera  (5)   → write only
INSERT INTO exam_registration_types (registration_id, exam_type_id) VALUES (@reg4, 3);

--    Case 5: Surya  (8)   → dictation + read
INSERT INTO exam_registration_types (registration_id, exam_type_id) VALUES (@reg5, 4), (@reg5, 2);
