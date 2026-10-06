ALTER TABLE results
    ADD COLUMN placement ENUM('1st', '2nd', '3rd') NULL DEFAULT NULL AFTER marks;