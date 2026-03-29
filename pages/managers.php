<!-- Managers Management Page -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <input type="text" id="manager-search" class="form-input" placeholder="Search managers..." style="max-width:260px;">
    </div>
    <button class="btn-primary" onclick="App.openModal('manager-modal'); resetManagerForm();">+ Add Manager</button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table" id="managers-table">
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="managers-tbody">
                <tr>
                    <td colspan="5" class="text-center py-8" style="color:var(--text-light);">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="manager-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="manager-modal-title">Add Manager</h3>
            <button class="modal-close" onclick="App.closeModal('manager-modal')">&times;</button>
        </div>
        <form id="manager-form">
            <input type="hidden" id="mgr-id" name="id">
            <div class="form-group">
                <label class="form-label">First Name *</label>
                <input type="text" id="mgr-fname" name="first_name" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Last Name *</label>
                <input type="text" id="mgr-lname" name="last_name" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <input type="email" id="mgr-email" name="email" class="form-input" required>
            </div>
            <div class="flex gap-3 justify-end mt-4">
                <button type="button" class="btn-secondary" onclick="App.closeModal('manager-modal')">Cancel</button>
                <button type="submit" class="btn-primary" id="mgr-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>