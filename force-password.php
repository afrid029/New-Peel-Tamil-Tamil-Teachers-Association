<?php

/**
 * Force Password Change – shown when first_login = 1
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$user = currentUser();
if ((int) $user['first_login'] !== 1) {
    header('Location: dashboard.php');
    exit;
}
$csrf = generateCsrf();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Your Password – <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
</head>

<body class="flex items-center justify-center min-h-screen" style="background:var(--bg);">

    <div class="w-full max-w-md px-4">
        <div class="card" style="padding:36px;">
            <div class="text-center mb-6">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background:var(--accent);">
                    <svg class="w-8 h-8" style="color:var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h1 class="text-xl font-bold" style="color:var(--primary);">Create Your Password</h1>
                <p class="text-sm mt-2" style="color:var(--text-light);">Welcome, <?= htmlspecialchars($user['first_name']) ?>! Please set a new password to continue.</p>
            </div>

            <form id="pwForm">
                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" id="new_password" class="form-input" required minlength="6" placeholder="Minimum 6 characters">
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" class="form-input" required placeholder="Re-enter your password">
                </div>
                <div id="pwError" class="text-sm text-red-600 mb-3 hidden"></div>
                <button type="submit" id="pwBtn" class="btn-primary w-full">Set Password & Continue</button>
            </form>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        document.getElementById('pwForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('pwBtn');
            const errDiv = document.getElementById('pwError');
            errDiv.classList.add('hidden');

            const pw = document.getElementById('new_password').value;
            const cpw = document.getElementById('confirm_password').value;

            if (pw.length < 6) {
                errDiv.textContent = 'Password must be at least 6 characters.';
                errDiv.classList.remove('hidden');
                return;
            }
            if (pw !== cpw) {
                errDiv.textContent = 'Passwords do not match.';
                errDiv.classList.remove('hidden');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Saving...';

            try {
                const res = await App.post('api/auth.php', {
                    action: 'change_password',
                    new_password: pw,
                    confirm_password: cpw
                });
                if (res.status) {
                    window.location.href = 'dashboard.php';
                } else {
                    errDiv.textContent = res.message;
                    errDiv.classList.remove('hidden');
                }
            } catch (err) {
                errDiv.textContent = err.message || 'Something went wrong.';
                errDiv.classList.remove('hidden');
            }
            btn.disabled = false;
            btn.textContent = 'Set Password & Continue';
        });
    </script>
</body>

</html>