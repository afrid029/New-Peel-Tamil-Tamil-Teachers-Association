<!-- Posters Management Page -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div></div>
    <button class="btn-primary" onclick="App.openModal('poster-modal'); resetPosterForm();">+ Add Poster</button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table" id="posters-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Posted By</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="posters-tbody">
                <tr>
                    <td colspan="5" class="text-center py-8" style="color:var(--text-light);">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>



<!-- Modal -->
<div class="modal-overlay" id="poster-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="poster-modal-title">Add Poster</h3>
            <button class="modal-close" onclick="App.closeModal('poster-modal')">&times;</button>
        </div>
        <form id="poster-form" enctype="multipart/form-data">
            <input type="hidden" id="pst-id" name="id">
            <div class="form-group">
                <label class="form-label">Title *</label>
                <input type="text" id="pst-title" name="title" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Image * <span class="text-xs font-normal" style="color:var(--text-light);">(JPG, PNG, GIF, WEBP – max 5 MB)</span></label>
                <input type="file" id="pst-image" name="image" class="form-input" accept="image/*">
            </div>
            <div id="pst-preview" class="mb-4 hidden">
                <img id="pst-preview-img" src="" alt="Preview" class="rounded-lg max-h-40 mx-auto">
            </div>
            <div class="flex gap-3 justify-end mt-4">
                <button type="button" class="btn-secondary" onclick="App.closeModal('poster-modal')">Cancel</button>
                <button type="submit" class="btn-primary" id="pst-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>