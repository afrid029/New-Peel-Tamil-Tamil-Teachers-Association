<!-- Notices Management Page -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div></div>
    <button class="btn-primary" onclick="App.openModal('notice-modal'); resetNoticeForm();">+ Add Notice</button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table" id="notices-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="notices-tbody">
                <tr>
                    <td colspan="5" class="text-center py-8" style="color:var(--text-light);">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="notice-modal">
    <div class="modal-box" style="max-width:560px;">
        <div class="modal-header">
            <h3 id="notice-modal-title">Add Notice</h3>
            <button class="modal-close" onclick="App.closeModal('notice-modal')">&times;</button>
        </div>
        <form id="notice-form">
            <input type="hidden" id="ntc-id" name="id">
            <div class="form-group">
                <label class="form-label">Title *</label>
                <input type="text" id="ntc-title" name="title" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Content *</label>
                <textarea id="ntc-content" name="content" class="form-input" rows="5" required></textarea>
            </div>
            <div class="flex gap-3 justify-end mt-4">
                <button type="button" class="btn-secondary" onclick="App.closeModal('notice-modal')">Cancel</button>
                <button type="submit" class="btn-primary" id="ntc-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>