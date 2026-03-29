<!-- My Profile Page (Student only – edit children info) -->
<div class="max-w-3xl mx-auto">
    <div id="profile-cards">
        <!-- Populated by JS from CHILDREN -->
        <div class="text-center py-8"><span class="spinner"></span></div>
    </div>
</div>

<!-- Edit Child Modal -->
<div class="modal-overlay" id="profile-modal">
    <div class="modal-box" style="max-width:560px;">
        <div class="modal-header">
            <h3 id="profile-modal-title">Edit Profile</h3>
            <button class="modal-close" onclick="App.closeModal('profile-modal')">&times;</button>
        </div>
        <form id="profile-form">
            <input type="hidden" id="prf-id" name="student_id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <input type="text" id="prf-fname" name="first_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <input type="text" id="prf-lname" name="last_name" class="form-input" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">School *</label>
                <select id="prf-school" name="school_id" class="form-input" required>
                    <option value="">Select school...</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Teacher</label>
                <select id="prf-teacher" name="teacher_id" class="form-input">
                    <option value="">Select teacher...</option>
                </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Guardian First Name *</label>
                    <input type="text" id="prf-gfname" name="guardian_first_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Guardian Last Name *</label>
                    <input type="text" id="prf-glname" name="guardian_last_name" class="form-input" required>
                </div>
            </div>
            <div class="flex gap-3 justify-end mt-4">
                <button type="button" class="btn-secondary" onclick="App.closeModal('profile-modal')">Cancel</button>
                <button type="submit" class="btn-primary" id="prf-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>