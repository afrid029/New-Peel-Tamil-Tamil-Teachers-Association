<!-- My Results Page (Student only) -->
<div class="max-w-2xl mx-auto">
    <div class="card" id="my-results-card">
        <div class="form-group mb-4">
            <label class="form-label">Select Student</label>
            <select id="mr-child" class="form-input">
                <!-- populated by JS from CHILDREN -->
            </select>
        </div>
        <div id="my-results-loading" class="text-center py-8"><span class="spinner"></span></div>
        <div id="my-results-empty" class="hidden empty-state">
            <p>Student has not registered any exams.</p>
        </div>
        <div id="my-results-list" class="hidden"></div>
    </div>
</div>

<!-- Result detail modal -->
<div class="modal-overlay" id="result-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="result-modal-title">Exam Result</h3>
            <button class="modal-close" onclick="App.closeModal('result-modal')">&times;</button>
        </div>
        <div id="result-modal-body">
            <div class="text-center py-6"><span class="spinner"></span></div>
        </div>
    </div>
</div>