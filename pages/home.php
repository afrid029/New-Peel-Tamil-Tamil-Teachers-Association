<!-- Dashboard Home Page -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <div class="card flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:#ede9fe;">
            <svg class="w-6 h-6" style="color:#6d28d9;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </div>
        <div>
            <p class="text-2xl font-bold" id="stat-students">—</p>
            <p class="text-xs" style="color:var(--text-light);"><?= $role === 'student' ? 'My Children' : 'Students' ?></p>
        </div>
    </div>
    <?php if (in_array($role, ['super_admin', 'manager'])): ?>
        <div class="card flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:#dbeafe;">
                <svg class="w-6 h-6" style="color:#1d4ed8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold" id="stat-schools">—</p>
                <p class="text-xs" style="color:var(--text-light);">Schools</p>
            </div>
        </div>
        <div class="card flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:#d1fae5;">
                <svg class="w-6 h-6" style="color:#047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold" id="stat-exams">—</p>
                <p class="text-xs" style="color:var(--text-light);">Exams</p>
            </div>
        </div>
        <div class="card flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:#fef3c7;">
                <svg class="w-6 h-6" style="color:#b45309;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold" id="stat-teachers">—</p>
                <p class="text-xs" style="color:var(--text-light);">Teachers</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3 class="text-lg font-bold mb-2" style="color:var(--primary);">Welcome, <?= htmlspecialchars($user['guardian_first_name'] ?? $user['first_name']) ?>!</h3>
    <p style="color:var(--text-light);" class="text-sm">
        You are logged in as <strong><?= ucfirst(str_replace('_', ' ', $role)) ?></strong>.
        Use the sidebar to navigate through the application.
    </p>
</div>

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const res = await App.get('api/auth.php?action=dashboard_stats');
            if (res.status && res.data) {
                if (res.data.students !== undefined) document.getElementById('stat-students').textContent = res.data.students;
                if (res.data.schools !== undefined) {
                    const el = document.getElementById('stat-schools');
                    if (el) el.textContent = res.data.schools;
                }
                if (res.data.exams !== undefined) {
                    const el = document.getElementById('stat-exams');
                    if (el) el.textContent = res.data.exams;
                }
                if (res.data.teachers !== undefined) {
                    const el = document.getElementById('stat-teachers');
                    if (el) el.textContent = res.data.teachers;
                }
            }
        } catch (e) {}
    });
</script>