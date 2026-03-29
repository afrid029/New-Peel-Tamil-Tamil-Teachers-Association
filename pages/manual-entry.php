<!-- Manual Entry Page (super_admin, manager) -->
<div class="mb-4">
    <p class="text-sm" style="color:var(--text-light);">Manually register students for exams and enter their marks. Click on a student to begin.</p>
</div>

<!-- Search -->
<div class="flex gap-3 mb-4">
    <div class="flex-1">
        <input type="text" id="me-search" class="form-input" placeholder="Search by name, email or ID..." onkeydown="if(event.key==='Enter'){loadMEStudents(1);}">
    </div>
    <button class="btn-primary" onclick="loadMEStudents(1)">Search</button>
</div>

<!-- Student List -->
<div class="card">
    <div id="me-students-content">
        <div class="empty-state">
            <p>Loading students...</p>
        </div>
    </div>
</div>
<div id="me-pagination" class="flex justify-center gap-2 mt-4"></div>

<!-- View Marks Modal (read-only) -->
<div class="modal-overlay" id="me-view-modal">
    <div class="modal-box" style="max-width:560px;">
        <div class="modal-header">
            <h3 id="me-view-title">View Marks</h3>
            <button class="modal-close" onclick="App.closeModal('me-view-modal')">&times;</button>
        </div>
        <div id="me-view-body" style="max-height:60vh;overflow-y:auto;padding:4px 0;">
            <!-- populated by JS -->
        </div>
        <div class="flex justify-end mt-4">
            <button type="button" class="btn-secondary" onclick="App.closeModal('me-view-modal')">Close</button>
        </div>
    </div>
</div>

<!-- Manual Entry Modal -->
<div class="modal-overlay" id="me-modal">
    <div class="modal-box" style="max-width:560px;">
        <div class="modal-header">
            <h3 id="me-modal-title">Register & Enter Marks</h3>
            <button class="modal-close" onclick="App.closeModal('me-modal')">&times;</button>
        </div>
        <form id="me-form">
            <input type="hidden" id="me-student-id">

            <div class="form-group">
                <label class="form-label">Student</label>
                <input type="text" id="me-student-name" class="form-input" disabled>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Exam *</label>
                    <select id="me-exam" class="form-input" required>
                        <option value="">Select exam...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Grade *</label>
                    <select id="me-grade" class="form-input" required>
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
            </div>

            <div class="form-group">
                <label class="form-label">Exam Types & Marks *</label>
                <div id="me-exam-types">
                    <p class="text-sm" style="color:var(--text-light);">Loading exam types...</p>
                </div>
            </div>

            <div id="me-existing-note" class="hidden" style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:10px 14px;margin-bottom:12px;">
                <p class="text-sm font-semibold" style="color:#92400e;">⚠ This student is already registered for this exam. Saving will update the existing registration.</p>
            </div>

            <div class="flex gap-3 justify-end mt-4">
                <button type="button" class="btn-secondary" onclick="App.closeModal('me-modal')">Cancel</button>
                <button type="submit" class="btn-primary" id="me-save-btn">Save Registration & Marks</button>
            </div>
        </form>
    </div>
</div>