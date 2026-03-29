<!-- Exams Management Page -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div></div>
    <button class="btn-primary" onclick="App.openModal('exam-modal'); resetExamForm();">+ Create Exam</button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table" id="exams-table">
            <thead>
                <tr>
                    <th>Exam Name</th>
                    <th>Registration Start</th>
                    <th>Registration End</th>
                    <th>Exam Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="exams-tbody">
                <tr>
                    <td colspan="5" class="text-center py-8" style="color:var(--text-light);">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="exam-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="exam-modal-title">Create Exam</h3>
            <button class="modal-close" onclick="App.closeModal('exam-modal')">&times;</button>
        </div>
        <form id="exam-form">
            <input type="hidden" id="exam-id" name="id">
            <div class="form-group">
                <label class="form-label">Exam Name *</label>
                <input type="text" id="exam-name" name="name" class="form-input" required>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Registration Start Date *</label>
                    <input type="date" id="exam-start" name="registration_start_date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Registration End Date *</label>
                    <input type="date" id="exam-end" name="registration_end_date" class="form-input" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Exam Date <span class="text-xs font-normal" style="color:var(--text-light);">(optional)</span></label>
                <input type="date" id="exam-date" name="exam_date" class="form-input">
            </div>
            <div class="flex gap-3 justify-end mt-4">
                <button type="button" class="btn-secondary" onclick="App.closeModal('exam-modal')">Cancel</button>
                <button type="submit" class="btn-primary" id="exam-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>