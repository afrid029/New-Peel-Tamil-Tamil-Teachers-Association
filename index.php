<?php

/**
 * Home Page – Public
 * Shows hero, active exam info, notices, and posters (no login required).
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
$loggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="stylesheet" href="assets/css/custom.css">
    <meta name="csrf-token" content="<?= htmlspecialchars(generateCsrf()) ?>">
    <style>
        .home-nav {
            background: var(--white);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 100;
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav class="home-nav">
        <div class="max-w-6xl mx-auto flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <img src="assets/img/logo.png" alt="Logo" class="h-10 w-10 rounded" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect fill=%22%233753a4%22 width=%2240%22 height=%2240%22 rx=%228%22/><text x=%2220%22 y=%2228%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2220%22 font-weight=%22bold%22>N</text></svg>'">
                <div>
                    <h1 class="text-base font-bold" style="color:var(--primary);">NPTTA – Canada</h1>
                    <p class="tamil text-xs" style="color:var(--text-light);">புதிய பீல் தமிழ் ஆசிரியர் சங்கம்</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <?php if ($loggedIn): ?>
                    <a href="dashboard.php" class="btn-primary btn-sm">Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn-primary btn-sm">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-content  2xl:max-w-[90rem] flex flex-col gap-3 2xl:px-[100px] 2xl:gap-6">
            <p class="tamil !text-lg md:!text-3xl 2xl:!text-4xl text-gray-200">புதிய பீல் தமிழ் ஆசிரியர் சங்கம் - கனடா</p>
            <h1 class="!text-2xl md:!text-4xl 2xl:!text-5xl font-bold">New Peel Tamil Teachers Association - Canada</h1>
            <p class="text-lg 2xl:!text-xl text-gray-100" >Exam Registration & Results Portal</p>
            <div class="flex flex-wrap gap-2">
                <?php if (!$loggedIn): ?>
                    <a href="login.php" class="inline-block bg-white text-[#3753a4] font-semibold px-8 py-3 rounded-lg hover:bg-gray-100 transition">Student Login</a>
                <?php else: ?>
                    <a href="dashboard.php" class="inline-block bg-white text-[#3753a4] font-semibold px-8 py-3 rounded-lg hover:bg-gray-100 transition">Go to Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Active Exam Banner -->
    <section id="active-exam-section" class="hidden">
        <div class="exam-section mx-auto  relative z-10">
            <div class="card border-l-4" style="border-color:var(--primary);">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide" style="color:var(--success);">Registration Open</p>
                        <h3 id="active-exam-name" class="text-lg font-bold mt-1" style="color:var(--primary);"></h3>
                        <p id="active-exam-dates" class="text-sm" style="color:var(--text-light);"></p>
                    </div>
                    <?php if ($loggedIn): ?>
                        <a href="dashboard.php?page=registration" class="btn-primary">Register Now</a>
                    <?php else: ?>
                        <a href="login.php" class="btn-primary">Login to Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Notices -->
    <section class="home-section mx-auto" id="notices-section">
        <div class="section-header ">
            <span class="section-icon" style="background:#eef2ff; color:var(--primary);">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </span>
            <div>
                <h2>Notices</h2>
                <p class="section-subtitle">அறிவிப்புகள்</p>
            </div>
        </div>
        <div id="notices-list" class="notices-grid">
            <div class="empty-state-modern">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--primary); opacity:0.3;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <p class="empty-title">No Notices</p>
                <p class="empty-sub">There are no notices at this time. Check back later!</p>
            </div>
        </div>
    </section>

    <!-- Posters -->
    <section class="home-section posters-section !mx-auto" id="posters-section">
        <div class="section-header">
            <span class="section-icon" style="background:#fef3c7; color:#b45309;">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </span>
            <div>
                <h2>Posters</h2>
                <p class="section-subtitle">சுவரொட்டிகள்</p>
            </div>
        </div>
        <div id="posters-list" class="poster-grid">
            <div class="empty-state-modern">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#b45309; opacity:0.3;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="empty-title">No Posters</p>
                <p class="empty-sub">There are no posters to display right now.</p>
            </div>
        </div>
    </section>

    <!-- Poster Lightbox -->
    <div id="poster-lightbox" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.85); cursor:pointer; align-items:center; justify-content:center; flex-direction:column; padding:20px;"
        onclick="this.style.display='none'">
        <button onclick="event.stopPropagation(); document.getElementById('poster-lightbox').style.display='none'"
            style="position:absolute; top:16px; right:20px; background:none; border:none; color:#fff; font-size:2rem; cursor:pointer; z-index:10000;">&times;</button>
        <img id="lightbox-img" src="" alt="" style="max-width:90vw; max-height:80vh; object-fit:contain; border-radius:8px; box-shadow:0 8px 32px rgba(0,0,0,0.5);">
        <p id="lightbox-title" style="color:#fff; margin-top:12px; font-size:1.1rem; text-align:center;"></p>
    </div>

    <!-- Footer -->
    <footer style="background:var(--primary); color: rgba(255,255,255,0.8); text-align:center; padding: 28px 16px;">
        <p class="tamil text-sm mb-1">புதிய பீல் தமிழ் ஆசிரியர் சங்கம் – கனடா</p>
        <p class="text-sm">New Peel Tamil Teachers Association – Canada</p>
        <p class="text-xs mt-2" style="color:rgba(255,255,255,0.5);">&copy; <?= date('Y') ?> NPTTA. All rights reserved.</p>
        <p class="text-sm  text-slate-200/40">Developed by <a class="font-semibold text-white/50 hover:text-white"
                href="https://masspro.ca/en/" target="_blank">
                Mass Production</a></p>
    </footer>

    <script src="assets/js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            // Load active exam
            try {
                const exam = await App.get('api/registration.php?action=active_exam');
                if (exam.status && exam.data) {
                    document.getElementById('active-exam-name').textContent = exam.data.name;
                    document.getElementById('active-exam-dates').textContent =
                        'Registration: ' + App.formatDate(exam.data.registration_start_date) + ' – ' + App.formatDate(exam.data.registration_end_date);
                    document.getElementById('active-exam-section').classList.remove('hidden');
                }
            } catch (e) {}

            // Load public notices
            try {
                const notices = await App.get('api/notices.php?action=public');
                if (notices.status && notices.data && notices.data.length) {
                    let html = '';
                    notices.data.forEach((n, i) => {
                        html += `<div class="notice-card" style="animation-delay:${i * 0.08}s">
                    <div class="notice-card-top">
                        <span class="notice-badge">${App.formatDate(n.created_at)}</span>
                    </div>
                    <h3>${App.esc(n.title)}</h3>
                    <p>${App.esc(n.content)}</p>
                </div>`;
                    });
                    document.getElementById('notices-list').innerHTML = html;
                }
            } catch (e) {}

            // Load public posters
            try {
                const posters = await App.get('api/posters.php?action=public');
                if (posters.status && posters.data && posters.data.length) {
                    let html = '';
                    posters.data.forEach((p, i) => {
                        const imgSrc = `api/serve-image.php?f=posters/${encodeURIComponent(p.image_file)}`;
                        html += `<div class="poster-card" style="animation-delay:${i * 0.08}s">
                    <div class="poster-img-wrap">
                        <img src="${imgSrc}" alt="${App.esc(p.title)}" onclick="openPosterLightbox(this.src, '${App.esc(p.title).replace(/'/g, "\\'")}')">
                        <div class="poster-overlay">
                            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                        </div>
                    </div>
                    <div class="poster-info">
                        <h3>${App.esc(p.title)}</h3>
                        <time>${App.formatDate(p.created_at)}</time>
                    </div>
                </div>`;
                    });
                    document.getElementById('posters-list').innerHTML = html;
                }
            } catch (e) {}
        });

        function openPosterLightbox(src, title) {
            document.getElementById('lightbox-img').src = src;
            document.getElementById('lightbox-title').textContent = title;
            document.getElementById('poster-lightbox').style.display = 'flex';
        }
    </script>
</body>

</html>