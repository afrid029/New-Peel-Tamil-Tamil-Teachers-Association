<!-- Exam Registration Page (Student only) -->
<div class="max-w-lg mx-auto">
    <div class="card" id="reg-card">
        <div id="reg-loading" class="text-center py-8"><span class="spinner"></span></div>
        <div id="reg-no-exam" class="hidden text-center py-8">
            <svg class="w-16 h-16 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p class="font-semibold" style="color:var(--text-light);">No active exam at this time.</p>
            <p class="text-sm mt-1" style="color:var(--text-light);">Please check back later when registration opens.</p>
        </div>
        <div id="reg-child-wrapper" class="hidden form-group mb-4">
            <label class="form-label">Select Child *</label>
            <select id="reg-child" class="form-input">
                <!-- populated by JS from CHILDREN -->
            </select>
        </div>
        <form id="reg-form" class="hidden">
            <div class="form-group">
                <label class="form-label">Exam</label>
                <input type="text" id="reg-exam-name" class="form-input" disabled>
                <input type="hidden" id="reg-exam-id" name="exam_id">
            </div>
            <div class="form-group">
                <label class="form-label">Grade *</label>
                <select id="reg-grade" name="grade" class="form-input" required>
                    <option value="">Select grade...</option>
                    <option value="JK">JK</option>
                    <option value="SK">SK</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Exam Type * <span class="text-xs font-normal" style="color:var(--text-light);">(select one or more)</span></label>
                <div id="reg-exam-types" class="checkbox-group">
                    <!-- dynamically loaded -->
                </div>
            </div>
            <button type="submit" class="btn-primary w-full mt-2" id="reg-submit-btn">Register</button>
        </form>
        <div id="reg-already" class="hidden text-center py-8">
            <p class="font-semibold" style="color:var(--success);">&#10003; This child is already registered for this exam.</p>
        </div>
    </div>
</div>