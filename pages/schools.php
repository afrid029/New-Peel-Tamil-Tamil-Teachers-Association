<!-- Schools Management Page -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <input type="text" id="school-search" class="form-input" placeholder="Search schools..." style="max-width:260px;">
    </div>
    <button class="btn-primary" onclick="App.openModal('school-modal'); resetSchoolForm();">+ Add School</button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table" id="schools-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="schools-tbody">
                <tr>
                    <td colspan="3" class="text-center py-8" style="color:var(--text-light);">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="school-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="school-modal-title">Add School</h3>
            <button class="modal-close" onclick="App.closeModal('school-modal')">&times;</button>
        </div>
        <form id="school-form">
            <input type="hidden" id="sch-id" name="id">
            <div class="form-group">
                <label class="form-label">School Name *</label>
                <input type="text" id="sch-name" name="name" class="form-input" required>
            </div>
            <div class="flex gap-3 justify-end mt-4">
                <button type="button" class="btn-secondary" onclick="App.closeModal('school-modal')">Cancel</button>
                <button type="submit" class="btn-primary" id="sch-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>