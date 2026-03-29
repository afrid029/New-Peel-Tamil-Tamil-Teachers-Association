<?php

/**
 * Application Configuration
 */

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'nptta_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// App
define('APP_NAME', 'New Peel Tamil Teachers Association – Canada');
define('APP_NAME_TAMIL', 'புதிய பீல் தமிழ் ஆசிரியர் சங்கம் – கனடா');
define('APP_URL', 'https://nptta.ca');   // change for production
define('APP_ROOT', __DIR__ . '/..');

// SMTP (PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'mafrid029@gmail.com');
define('SMTP_PASS', '');
define('SMTP_FROM', 'mafrid029@gmail.com');
define('SMTP_FROM_NAME', APP_NAME);

// Upload
define('UPLOAD_DIR', APP_ROOT . '/assets/uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB

// Session
define('SESSION_LIFETIME', 3600 * 6); // 6 hours
