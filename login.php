<?php

/**
 * Login Page
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
    <title>Login – <?= APP_NAME ?></title>
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
                <h1 class="text-lg font-bold" style="color:var(--primary);">NPTTA – Canada</h1>
                <p class="tamil text-sm" style="color:var(--text-light);">புதிய பீல் தமிழ் ஆசிரியர் சங்கம்</p>
            </div>

            <!-- Login Form -->
            <form id="loginForm" autocomplete="on">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" required placeholder="you@example.com" autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required placeholder="Enter your password">
                </div>
                <div id="loginError" class="text-sm text-red-600 mb-3 hidden"></div>
                <button type="submit" id="loginBtn" class="btn-primary w-full">Login</button>
            </form>

            <p class="text-center text-sm mt-4">
                <a href="forgot-password.php" class="hover:underline" style="color:var(--primary);">Forgot Password?</a>
            </p>

            <p class="text-center text-sm mt-3" style="color:var(--text-light);">
                <a href="index.php" class="hover:underline" style="color:var(--primary);">&larr; Back to Home</a>
            </p>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('loginBtn');
            const errDiv = document.getElementById('loginError');
            errDiv.classList.add('hidden');
            btn.disabled = true;
            btn.textContent = 'Logging in...';

            try {
                const res = await App.post('api/auth.php', {
                    action: 'login',
                    email: document.getElementById('email').value.trim(),
                    password: document.getElementById('password').value
                });

                if (res.status) {
                    if (res.user && res.user.first_login === 1) {
                        window.location.href = 'force-password.php';
                    } else {
                        window.location.href = 'dashboard.php';
                    }
                } else {
                    errDiv.textContent = res.message;
                    errDiv.classList.remove('hidden');
                }
            } catch (err) {
                errDiv.textContent = err.message || 'Something went wrong.';
                errDiv.classList.remove('hidden');
            }
            btn.disabled = false;
            btn.textContent = 'Login';
        });
    </script>
</body>

</html>