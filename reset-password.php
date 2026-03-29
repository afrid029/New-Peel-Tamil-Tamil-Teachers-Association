<?php

/**
 * Reset Password Page
 * User arrives here via email link with ?token=...
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$token = $_GET['token'] ?? '';
if ($token === '') {
    header('Location: login.php');
    exit;
}
$csrf = generateCsrf();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password – <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
</head>

<body class="flex items-center justify-center min-h-screen" style="background:var(--bg);">

    <div class="w-full max-w-md px-4">
        <div class="card" style="padding:36px;">
            <!-- Brand -->
            <div class="text-center mb-6">
                <img src="assets/img/logo.png" alt="Logo" class="h-16 mx-auto mb-4 rounded-lg"
                    onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22><rect fill=%22%233753a4%22 width=%2264%22 height=%2264%22 rx=%2212%22/><text x=%2232%22 y=%2244%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2232%22 font-weight=%22bold%22>N</text></svg>'">
                <h1 class="text-lg font-bold" style="color:var(--primary);">Set New Password</h1>
                <p class="text-sm mt-1" style="color:var(--text-light);">Enter your new password below.</p>
            </div>

            <!-- Reset Form -->
            <form id="resetForm" autocomplete="off">
                <input type="hidden" id="token" value="<?= htmlspecialchars($token) ?>">
                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-input" required placeholder="Minimum 6 characters" minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required placeholder="Re-enter password">
                </div>
                <div id="resetMsg" class="text-sm mb-3 hidden"></div>
                <button type="submit" id="resetBtn" class="btn-primary w-full">Reset Password</button>
            </form>

            <p class="text-center text-sm mt-5" style="color:var(--text-light);">
                <a href="login.php" class="hover:underline" style="color:var(--primary);">&larr; Back to Login</a>
            </p>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        document.getElementById('resetForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('resetBtn');
            const msgDiv = document.getElementById('resetMsg');
            const newPw = document.getElementById('new_password').value;
            const confPw = document.getElementById('confirm_password').value;

            msgDiv.classList.add('hidden');

            if (newPw.length < 6) {
                msgDiv.classList.remove('hidden');
                msgDiv.style.color = 'var(--danger)';
                msgDiv.textContent = 'Password must be at least 6 characters.';
                return;
            }
            if (newPw !== confPw) {
                msgDiv.classList.remove('hidden');
                msgDiv.style.color = 'var(--danger)';
                msgDiv.textContent = 'Passwords do not match.';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Resetting...';

            try {
                const res = await App.post('api/auth.php', {
                    action: 'reset_password',
                    token: document.getElementById('token').value,
                    new_password: newPw,
                    confirm_password: confPw
                });

                msgDiv.classList.remove('hidden');
                if (res.status) {
                    msgDiv.style.color = 'var(--success)';
                    msgDiv.textContent = res.message;
                    document.getElementById('resetForm').reset();
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    msgDiv.style.color = 'var(--danger)';
                    msgDiv.textContent = res.message;
                }
            } catch (err) {
                msgDiv.classList.remove('hidden');
                msgDiv.style.color = 'var(--danger)';
                msgDiv.textContent = err.message || 'Something went wrong.';
            }
            btn.disabled = false;
            btn.textContent = 'Reset Password';
        });
    </script>
</body>

</html>