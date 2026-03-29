<!-- Students Management Page -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <input type="text" id="student-search" class="form-input" placeholder="Search students..." style="max-width:260px;">
    </div>
    <button class="btn-primary" onclick="openAddStudentModal();">+ Add Student</button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table" id="students-table">
            <thead>
                <tr>
                    <th>Index</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>School</th>
                    <th>Teacher</th>
                    <th>Guardian</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="students-tbody">
                <tr>
                    <td colspan="7" class="text-center py-8" style="color:var(--text-light);">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div id="students-pagination" class="flex justify-center gap-2 mt-4"></div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="student-modal">
    <div class="modal-box" style="max-width:600px;">
        <div class="modal-header">
            <h3 id="student-modal-title">Add Student</h3>
            <button class="modal-close" onclick="App.closeModal('student-modal')">&times;</button>
        </div>
        <form id="student-form">
            <input type="hidden" id="stu-id" name="id">

            <!-- Manual ID (only super_admin / manager) -->
            <?php if (in_array($role, ['super_admin', 'manager'])): ?>
                <div class="form-group">
                    <label class="form-label">Student ID <span class="text-xs font-normal" style="color:var(--text-light);">(optional – leave blank for auto)</span></label>
                    <input type="number" id="stu-manual-id" name="manual_id" class="form-input" placeholder="e.g. 12345">
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" id="stu-email" name="email" class="form-input" required>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Student First Name *</label>
                    <input type="text" id="stu-fname" name="first_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label"> Student Last Name *</label>
                    <input type="text" id="stu-lname" name="last_name" class="form-input" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">School *</label>
                <select id="stu-school" name="school_id" class="form-input" required>
                    <option value="">Select school...</option>
                </select>
            </div>

            <!-- Teacher dropdown (only super_admin / manager) -->
            <?php if (in_array($role, ['super_admin', 'manager'])): ?>
                <div class="form-group">
                    <label class="form-label">Teacher *</label>
                    <select id="stu-teacher" name="teacher_id" class="form-input" required>
                        <option value="">Select teacher...</option>
                    </select>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Guardian First Name *</label>
                    <input type="text" id="stu-gfname" name="guardian_first_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Guardian Last Name *</label>
                    <input type="text" id="stu-glname" name="guardian_last_name" class="form-input" required>
                </div>
            </div>
            <div class="flex gap-3 justify-end mt-4">
                <button type="button" class="btn-secondary" onclick="App.closeModal('student-modal')">Cancel</button>
                <button type="submit" class="btn-primary" id="stu-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>