<?php

/**
 * Dashboard – main authenticated area
 * Loads page partials based on ?page= parameter
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
startSecureSession();
requirePageLogin();

$user = currentUser();

// Force password change
if ((int) $user['first_login'] === 1) {
    header('Location: force-password.php');
    exit;
}

$role = $user['role'];
$page = $_GET['page'] ?? 'home';
$csrf = generateCsrf();

// Role-based page access
$allowedPages = [
    'super_admin' => ['home', 'managers', 'schools', 'teachers', 'students', 'exams', 'exam-types', 'results', 'manual-entry', 'notices', 'posters'],
    'manager'     => ['home', 'schools', 'teachers', 'students', 'exams', 'results', 'manual-entry', 'notices', 'posters'],
    'teacher'     => ['home', 'students'],
    'student'     => ['home', 'my-profile', 'registration', 'my-results'],
];

$pages = $allowedPages[$role] ?? ['home'];
if (!in_array($page, $pages)) {
    $page = 'home';
}

// Navigation items with icons
$navItems = [
    'home'         => ['label' => 'Dashboard',       'icon' => 'home'],
    'managers'     => ['label' => 'Managers',         'icon' => 'users'],
    'schools'      => ['label' => 'Schools',          'icon' => 'school'],
    'teachers'     => ['label' => 'Teachers',         'icon' => 'user-check'],
    'students'     => ['label' => 'Students',         'icon' => 'user-group'],
    'exams'        => ['label' => 'Exams',            'icon' => 'clipboard'],
    'exam-types'   => ['label' => 'Exam Types',       'icon' => 'list'],
    'results'      => ['label' => 'Results',          'icon' => 'chart'],
    'manual-entry' => ['label' => 'Manual Entry',      'icon' => 'pencil-square'],
    'registration' => ['label' => 'Register for Exam', 'icon' => 'pencil'],
    'my-results'   => ['label' => 'My Results',       'icon' => 'chart'],
    'my-profile'   => ['label' => 'My Profile',       'icon' => 'user-edit'],
    'notices'      => ['label' => 'Notices',          'icon' => 'bell'],
    'posters'      => ['label' => 'Posters',          'icon' => 'image'],
];

// SVG icon map
function navIcon(string $name): string
{
    $icons = [
        'home'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/>',
        'users'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
        'school'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        'user-check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11l2 2 4-4"/>',
        'user-group' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'clipboard'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
        'list'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>',
        'chart'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
        'pencil'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
        'pencil-square' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zM16.862 4.487L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>',
        'bell'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
        'image'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        'user-edit'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11l2 2m0 0l2-2m-2 2V7"/>',
    ];
    return $icons[$name] ?? $icons['home'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
    <script>
        const USER = <?= json_encode($user) ?>;
        const CHILDREN = <?= json_encode($role === 'student' ? getSessionChildren() : []) ?>;
    </script>
</head>

<body>

    <!-- Mobile sidebar overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-800 hidden md:hidden" style="z-index:899;"></div>
    <style>
        #sidebar-overlay.active {
            display: block;
        }
    </style>

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar">
        <div class="!visible lg:!hidden " style="padding:16px 20px; border-bottom:1px solid rgba(255,255,255,0.12);">
            <a href="logout.php" class="flex items-center gap-2 text-sm text-red-200 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
            </a>
        </div>
        <a class="sidebar-brand" href="index.php">
            <img src="assets/img/logo.png" alt="Logo" class="mx-auto rounded"
                onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 44 44%22><rect fill=%22%23fff%22 width=%2244%22 height=%2244%22 rx=%228%22 opacity=%220.15%22/><text x=%2222%22 y=%2232%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2224%22 font-weight=%22bold%22>N</text></svg>'">
            <h2>NPTTA – Canada</h2>
            <p class="tamil-name">புதிய பீல் தமிழ் ஆசிரியர் சங்கம்</p>
        </a>

        <nav class="sidebar-nav">
            <?php foreach ($pages as $p):
                $item = $navItems[$p] ?? null;
                if (!$item) continue;
            ?>
                <a href="dashboard.php?page=<?= $p ?>" class="<?= $page === $p ? 'active' : '' ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= navIcon($item['icon']) ?></svg>
                    <?= htmlspecialchars($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

       <div class="!hidden lg:!block" style="padding:16px 20px; border-top:1px solid rgba(255,255,255,0.12);">
            <a href="logout.php" class="flex items-center gap-2 text-sm text-red-200 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main -->
    <div class="main-content">
        <!-- Top Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button id="sidebar-toggle" class="hamburger">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h2 class="page-header"><?= htmlspecialchars($navItems[$page]['label'] ?? 'Dashboard') ?></h2>
            </div>
            <div class="topbar-right">
                <span class="badge badge-<?= $role === 'super_admin' ? 'admin' : $role ?>"><?= ucfirst(str_replace('_', ' ', $role === 'super_admin' ? 'Super Admin' : ($role === 'student' ? 'Guardian' : $role))) ?></span>
                <span class="text-sm font-medium hidden sm:inline"><?= htmlspecialchars($role === 'student' ? ($user['guardian_first_name'] . ' ' . $user['guardian_last_name']) : ($user['first_name'] . ' ' . $user['last_name'])) ?></span>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <?php
            $pageFile = __DIR__ . '/pages/' . $page . '.php';
            if (file_exists($pageFile)) {
                include $pageFile;
            } else {
                include __DIR__ . '/pages/home.php';
            }
            ?>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div class="modal-overlay" id="confirm-modal">
        <div class="modal-box" style="max-width:400px;text-align:center;">
            <div style="margin-bottom:20px;">
                <svg class="mx-auto" style="width:56px;height:56px;color:var(--danger);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p id="confirm-msg" style="font-size:15px;color:var(--text);margin-bottom:24px;">Are you sure?</p>
            <div class="flex gap-3 justify-center">
                <button class="btn-secondary" id="confirm-no" style="min-width:100px;">Cancel</button>
                <button class="btn-danger" id="confirm-yes" style="min-width:100px;">Delete</button>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <?php
    // Load page-specific JS
    $jsFile = "assets/js/{$page}.js";
    if (file_exists(__DIR__ . '/' . $jsFile)):
    ?>
        <script src="<?= htmlspecialchars($jsFile) ?>"></script>
    <?php endif; ?>
</body>

</html>