/* Manual Entry – Register students for exams & enter marks */
(function () {
  let studentsData = [];
  let currentPage = 1;
  let totalPages = 1;
  let totalRecords = 0;
  const perPage = 20;

  let examsCache = [];
  let examTypesCache = [];

  /* ---------- Load students ---------- */
  window.loadMEStudents = async function (page) {
    if (typeof page === "number") currentPage = page;
    else currentPage = 1;

    const search = document.getElementById("me-search").value.trim();
    const container = document.getElementById("me-students-content");
    App.showLoader(container);

    try {
      const url =
        `api/manual-entry.php?action=students&page=${currentPage}&per_page=${perPage}` +
        (search ? `&search=${encodeURIComponent(search)}` : "");
      const res = await App.get(url);
      if (!res.status) {
        container.innerHTML =
          '<div class="empty-state"><p>Failed to load students.</p></div>';
        return;
      }
      studentsData = res.data || [];
      totalRecords = res.total || 0;
      totalPages = Math.max(1, Math.ceil(totalRecords / perPage));
      renderStudents();
      renderPagination();
    } catch (e) {
      container.innerHTML =
        '<div class="empty-state"><p>Failed to load students.</p></div>';
    }
  };

  function renderStudents() {
    const container = document.getElementById("me-students-content");
    if (!studentsData.length) {
      container.innerHTML =
        '<div class="empty-state"><p>No students found.</p></div>';
      return;
    }

    let html =
      '<div class="table-responsive"><table class="data-table"><thead><tr>';
    html +=
      "<th>ID</th><th>Student Name</th><th>Guardian</th><th>School</th><th>Actions</th>";
    html += "</tr></thead><tbody>";

    studentsData.forEach((s) => {
      const name = App.esc(s.first_name) + " " + App.esc(s.last_name);
      const hasRegs = parseInt(s.registration_count) > 0;
      html += `<tr>`;
      html += `<td>${s.id}</td>`;
      html += `<td>${name}</td>`;
      html += `<td>${App.esc(s.guardian_first_name || "")} ${App.esc(s.guardian_last_name || "")}</td>`;
      html += `<td>${App.esc(s.school_name || "—")}</td>`;
      html += `<td class="flex gap-2 flex-wrap">`;
      if (hasRegs) {
        html += `<button class="btn-secondary btn-sm" onclick="viewStudentMarks(${s.id}, '${name.replace(/'/g, "\\'")}')">View</button>`;
        html += `<button class="btn-primary btn-sm" onclick="openMEModal(${s.id}, '${name.replace(/'/g, "\\'")}')">Edit</button>`;
      } else {
        html += `<button class="btn-primary btn-sm" onclick="openMEModal(${s.id}, '${name.replace(/'/g, "\\'")}')">Enter Marks</button>`;
      }
      html += `</td></tr>`;
    });

    html += "</tbody></table></div>";
    container.innerHTML = html;
  }

  function renderPagination() {
    const container = document.getElementById("me-pagination");
    if (!container) return;
    if (totalPages <= 1 && totalRecords <= perPage) {
      container.innerHTML = totalRecords
        ? `<span class="text-sm" style="color:var(--text-light);">Showing ${totalRecords} student${totalRecords !== 1 ? "s" : ""}</span>`
        : "";
      return;
    }

    const from = (currentPage - 1) * perPage + 1;
    const to = Math.min(currentPage * perPage, totalRecords);

    let html = `<span class="text-sm flex items-center" style="color:var(--text-light);margin-right:8px;">Showing ${from}–${to} of ${totalRecords}</span>`;
    html +=
      '<button class="btn-secondary btn-sm" ' +
      (currentPage <= 1 ? "disabled" : "") +
      ' onclick="loadMEStudents(' +
      (currentPage - 1) +
      ')">&laquo; Prev</button>';

    const start = Math.max(1, currentPage - 2);
    const end = Math.min(totalPages, currentPage + 2);
    for (let i = start; i <= end; i++) {
      html +=
        '<button class="' +
        (i === currentPage ? "btn-primary" : "btn-secondary") +
        ' btn-sm" onclick="loadMEStudents(' +
        i +
        ')">' +
        i +
        "</button>";
    }

    html +=
      '<button class="btn-secondary btn-sm" ' +
      (currentPage >= totalPages ? "disabled" : "") +
      ' onclick="loadMEStudents(' +
      (currentPage + 1) +
      ')">Next &raquo;</button>';

    container.innerHTML = html;
  }

  /* ---------- View student marks (read-only) ---------- */
  window.viewStudentMarks = async function (studentId, studentName) {
    document.getElementById("me-view-title").textContent =
      "Marks – " + studentName;
    const body = document.getElementById("me-view-body");
    body.innerHTML =
      '<div style="text-align:center;padding:20px;"><span class="spinner"></span></div>';
    App.openModal("me-view-modal");

    try {
      const res = await App.get(
        `api/manual-entry.php?action=student_registrations&student_id=${studentId}`,
      );
      if (!res.status || !res.data || !res.data.length) {
        body.innerHTML =
          '<p class="text-sm" style="color:var(--text-light);text-align:center;padding:20px;">No registrations found.</p>';
        return;
      }

      let html = "";
      res.data.forEach((reg) => {
        html += `<div style="border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px;margin-bottom:12px;">`;
        html += `<div class="flex justify-between items-center mb-2">`;
        html += `<span class="font-semibold">${App.esc(reg.exam_name)}</span>`;
        html += `<span class="badge badge-teacher" style="font-size:12px;">Grade ${App.esc(reg.grade)}</span>`;
        html += `</div>`;
        if (reg.exam_date) {
          html += `<p class="text-xs mb-2" style="color:var(--text-light);">Exam Date: ${App.formatDate(reg.exam_date)}</p>`;
        }
        html += `<div class="grid grid-cols-2 sm:grid-cols-3 gap-2">`;
        (reg.exam_types || []).forEach((et) => {
          const val =
            et.marks !== null && et.marks !== undefined ? et.marks : "—";
          html += `<div style="background:#f8fafc;border-radius:6px;padding:8px 10px;">`;
          html += `<span class="text-xs" style="color:var(--text-light);">${App.esc(et.exam_type_name)}</span><br>`;
          html += `<span class="font-semibold">${App.esc(String(val))}</span>`;
          html += `</div>`;
        });
        html += `</div></div>`;
      });
      body.innerHTML = html;
    } catch (e) {
      body.innerHTML =
        '<p class="text-sm" style="color:var(--danger);text-align:center;padding:20px;">Failed to load marks.</p>';
    }
  };

  /* ---------- Load exams & exam types ---------- */
  async function loadDropdowns() {
    try {
      const [examsRes, typesRes] = await Promise.all([
        App.get("api/manual-entry.php?action=exams"),
        App.get("api/manual-entry.php?action=exam_types"),
      ]);
      if (examsRes.status) examsCache = examsRes.data || [];
      if (typesRes.status) examTypesCache = typesRes.data || [];
    } catch (e) {}
  }

  /* ---------- Open modal ---------- */
  window.openMEModal = function (studentId, studentName) {
    document.getElementById("me-student-id").value = studentId;
    document.getElementById("me-student-name").value = studentName;
    document.getElementById("me-modal-title").textContent =
      "Enter Marks – " + studentName;
    document.getElementById("me-existing-note").classList.add("hidden");

    // Populate exam dropdown
    const examSel = document.getElementById("me-exam");
    examSel.innerHTML = '<option value="">Select exam...</option>';
    examsCache.forEach((e) => {
      const opt = document.createElement("option");
      opt.value = e.id;
      opt.textContent =
        e.name + (e.exam_date ? " (" + App.formatDate(e.exam_date) + ")" : "");
      examSel.appendChild(opt);
    });

    // Reset grade
    document.getElementById("me-grade").value = "";

    // Render exam types with marks inputs
    renderExamTypeInputs();

    App.openModal("me-modal");
  };

  function renderExamTypeInputs(existingMarks) {
    const container = document.getElementById("me-exam-types");
    if (!examTypesCache.length) {
      container.innerHTML =
        '<p class="text-sm" style="color:var(--text-light);">No active exam types found.</p>';
      return;
    }

    let html = "";
    examTypesCache.forEach((et) => {
      const checked = existingMarks ? existingMarks[et.id] !== undefined : true;
      const val =
        existingMarks &&
        existingMarks[et.id] !== undefined &&
        existingMarks[et.id] !== null
          ? existingMarks[et.id]
          : "";
      html += `<div class="flex items-center gap-3 mb-2" style="padding:8px 12px;background:#f8fafc;border-radius:8px;">`;
      html += `<input type="checkbox" id="me-et-${et.id}" data-et="${et.id}" class="me-et-check" ${checked ? "checked" : ""} style="width:18px;height:18px;accent-color:var(--primary);">`;
      html += `<label for="me-et-${et.id}" class="flex-1 text-sm font-medium" style="cursor:pointer;">${App.esc(et.name)}</label>`;
      html += `<input type="number" step="0.5" min="0" class="form-input me-et-marks" data-et="${et.id}" value="${App.esc(String(val))}" placeholder="Marks" style="width:100px;padding:6px 10px;">`;
      html += `</div>`;
    });
    container.innerHTML = html;
  }

  /* ---------- Check existing registration on exam change ---------- */
  document.getElementById("me-exam").addEventListener("change", checkExisting);

  async function checkExisting() {
    const examId = document.getElementById("me-exam").value;
    const studentId = document.getElementById("me-student-id").value;
    const note = document.getElementById("me-existing-note");
    note.classList.add("hidden");

    if (!examId || !studentId) return;

    try {
      const res = await App.get(
        `api/manual-entry.php?action=check&exam_id=${examId}&student_id=${studentId}`,
      );
      if (res.status && res.existing) {
        note.classList.remove("hidden");
        // Pre-fill grade
        if (res.existing.grade) {
          document.getElementById("me-grade").value = res.existing.grade;
        }
        // Pre-fill marks
        renderExamTypeInputs(res.existing.marks || {});
      } else {
        renderExamTypeInputs();
      }
    } catch (e) {}
  }

  /* ---------- Submit form ---------- */
  document
    .getElementById("me-form")
    .addEventListener("submit", async function (e) {
      e.preventDefault();
      const btn = document.getElementById("me-save-btn");
      App.startLoading(btn);

      const studentId = document.getElementById("me-student-id").value;
      const examId = document.getElementById("me-exam").value;
      const grade = document.getElementById("me-grade").value;

      if (!examId) {
        App.toast("Please select an exam.", "warning");
        App.stopLoading(btn);
        return;
      }
      if (!grade) {
        App.toast("Please select a grade.", "warning");
        App.stopLoading(btn);
        return;
      }

      // Collect checked exam types with marks
      const marksInputs = document.querySelectorAll(".me-et-marks");
      const marksObj = {};
      let hasType = false;
      let invalid = false;

      marksInputs.forEach((inp) => {
        const cb = document.getElementById("me-et-" + inp.dataset.et);
        if (cb && cb.checked) {
          hasType = true;
          const v = inp.value.trim();
          if (v !== "" && Number(v) < 0) {
            invalid = true;
          }
          marksObj[inp.dataset.et] = v !== "" ? v : null;
        }
      });

      if (invalid) {
        App.toast("Marks cannot be negative.", "error");
        App.stopLoading(btn);
        return;
      }
      if (!hasType) {
        App.toast("Please select at least one exam type.", "warning");
        App.stopLoading(btn);
        return;
      }

      try {
        const res = await App.post("api/manual-entry.php", {
          action: "save",
          student_id: studentId,
          exam_id: examId,
          grade: grade,
          marks: JSON.stringify(marksObj),
        });
        App.toast(res.message, res.status ? "success" : "error");
        if (res.status) {
          App.closeModal("me-modal");
          // Refresh list to update View/Edit buttons
          loadMEStudents(currentPage);
        }
      } catch (e) {
        App.toast("Failed to save. Please try again.", "error");
      }

      App.stopLoading(btn);
    });

  /* ---------- Init ---------- */
  loadDropdowns();
  loadMEStudents(1);
})();
