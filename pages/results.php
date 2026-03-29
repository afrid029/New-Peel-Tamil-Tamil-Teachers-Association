<!-- Results Management Page (super_admin, manager) -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="form-group">
        <label class="form-label">Exam</label>
        <select id="res-exam" class="form-input">
            <option value="">Select exam...</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Grade</label>
        <select id="res-grade" class="form-input">
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
    <div class="form-group flex items-end">
        <button class="btn-primary w-full" onclick="loadRegisteredStudents();">Load Students</button>
    </div>
</div>

<div class="card">
    <div id="results-content">
        <div class="empty-state">
            <p>Select an exam and grade, then click <strong>Load Students</strong> to view registrations.</p>
        </div>
    </div>
</div>

<div id="results-pagination" class="flex justify-center gap-2 mt-4"></div>

<!-- Edit Marks Modal -->
<div class="modal-overlay" id="marks-modal">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-header">
            <h3 id="marks-modal-title">Update Marks</h3>
            <button class="modal-close" onclick="App.closeModal('marks-modal')">&times;</button>
        </div>
        <form id="marks-form">
            <input type="hidden" id="marks-reg-id">
            <div id="marks-fields">
                <!-- dynamically populated -->
            </div>
            <div class="flex gap-3 justify-end mt-4">
                <button type="button" class="btn-secondary" onclick="App.closeModal('marks-modal')">Cancel</button>
                <button type="submit" class="btn-primary" id="marks-save-btn">Save All Marks</button>
            </div>
        </form>
    </div>
</div>