<!-- Exam Types Management Page -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div></div>
    <button class="btn-primary" onclick="App.openModal('et-modal'); resetETForm();">+ Add Exam Type</button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table" id="et-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="et-tbody">
                <tr>
                    <td colspan="4" class="text-center py-8" style="color:var(--text-light);">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="et-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="et-modal-title">Add Exam Type</h3>
            <button class="modal-close" onclick="App.closeModal('et-modal')">&times;</button>
        </div>
        <form id="et-form">
            <input type="hidden" id="et-id" name="id">
            <div class="form-group">
                <label class="form-label">Exam Type Name *</label>
                <input type="text" id="et-name" name="name" class="form-input" required>
            </div>
            <div class="flex gap-3 justify-end mt-4">
                <button type="button" class="btn-secondary" onclick="App.closeModal('et-modal')">Cancel</button>
                <button type="submit" class="btn-primary" id="et-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>