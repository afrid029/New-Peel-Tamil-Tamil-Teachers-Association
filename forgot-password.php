<?php

/**
 * Forgot Password Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
if (isLoggedIn()) {
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
    <title>Forgot Password – <?= APP_NAME ?></title>
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
                <h1 class="text-lg font-bold" style="color:var(--primary);">Forgot Password</h1>
                <p class="text-sm mt-1" style="color:var(--text-light);">Enter your email and we'll send you a reset link.</p>
            </div>

            <!-- Request Form -->
            <form id="forgotForm" autocomplete="on">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" required placeholder="you@example.com" autofocus>
                </div>
                <div id="forgotMsg" class="text-sm mb-3 hidden"></div>
                <button type="submit" id="forgotBtn" class="btn-primary w-full">Send Reset Link</button>
            </form>

            <p class="text-center text-sm mt-5" style="color:var(--text-light);">
                <a href="login.php" class="hover:underline" style="color:var(--primary);">&larr; Back to Login</a>
            </p>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        document.getElementById('forgotForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('forgotBtn');
            const msgDiv = document.getElementById('forgotMsg');
            msgDiv.classList.add('hidden');
            btn.disabled = true;
            btn.textContent = 'Sending...';

            try {
                const res = await App.post('api/auth.php', {
                    action: 'forgot_password',
                    email: document.getElementById('email').value.trim()
                });

                msgDiv.classList.remove('hidden');
                if (res.status) {
                    msgDiv.style.color = 'var(--success)';
                    msgDiv.textContent = res.message;
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
            btn.textContent = 'Send Reset Link';
        });
    </script>
</body>

</html>