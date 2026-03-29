/* Results Management JS (admin / manager) */
(function () {
  let studentsData = [];
  let currentPage = 1;
  let totalPages = 1;
  const perPage = 20;

  async function loadExams() {
    try {
      const res = await App.get("api/results.php?action=exams");
      if (res.status) {
        const sel = document.getElementById("res-exam");
        res.data.forEach((e) => {
          const opt = document.createElement("option");
          opt.value = e.id;
          opt.textContent = e.name;
          sel.appendChild(opt);
        });
      }
    } catch (e) {}
  }

  function renderTable() {
    const container = document.getElementById("results-content");
    if (!studentsData.length) {
      container.innerHTML =
        '<div class="empty-state"><p>No students registered for this exam and grade.</p></div>';
      return;
    }

    let html =
      '<div class="table-responsive"><table class="data-table"><thead><tr>';
    html +=
      "<th>Student ID</th><th>Name</th><th>Actions</th></tr></thead><tbody>";

    studentsData.forEach((s) => {
      html += `<tr id="res-row-${s.registration_id}"><td>${s.student_id}</td><td>${App.esc(s.first_name)} ${App.esc(s.last_name)}</td>`;
      html += `<td class="flex gap-2">`;
      html += `<button class="btn-secondary btn-sm" onclick="viewMarks(${s.registration_id})">View</button>`;
      html += `<button class="btn-primary btn-sm" onclick="openMarksModal(${s.registration_id})">Edit</button>`;
      html += `</td></tr>`;
      // Hidden detail row
      html += `<tr id="res-detail-${s.registration_id}" class="hidden"><td colspan="3" style="background:#f8fafc;padding:12px 16px;">`;
      html += `<div class="grid grid-cols-2 sm:grid-cols-3 gap-3">`;
      (s.exam_types || []).forEach((et) => {
        const val =
          et.marks !== null && et.marks !== undefined ? et.marks : "—";
        html += `<div><span class="text-xs font-semibold" style="color:var(--text-light);">${App.esc(et.exam_type_name)}</span><br><span class="font-semibold">${App.esc(String(val))}</span></div>`;
      });
      html += `</div></td></tr>`;
    });

    html += "</tbody></table></div>";
    container.innerHTML = html;
  }

  window.viewMarks = function (regId) {
    const row = document.getElementById("res-detail-" + regId);
    if (row) row.classList.toggle("hidden");
  };

  window.loadRegisteredStudents = async function (page) {
    const examId = document.getElementById("res-exam").value;
    const grade = document.getElementById("res-grade").value;
    const container = document.getElementById("results-content");

    if (!examId || !grade) {
      App.toast("Please select exam and grade.", "warning");
      return;
    }

    if (typeof page === "number") {
      currentPage = page;
    } else {
      currentPage = 1;
    }

    App.showLoader(container);

    try {
      const res = await App.get(
        `api/results.php?action=registered_students&exam_id=${examId}&grade=${grade}&page=${currentPage}&per_page=${perPage}`,
      );
      if (!res.status || !res.data.length) {
        studentsData = [];
        totalPages = 1;
        renderTable();
        return;
      }
      studentsData = res.data;
      totalPages = Math.max(1, Math.ceil((res.total || 0) / perPage));
      renderTable();
      renderResultsPagination();
    } catch (e) {
      container.innerHTML =
        '<div class="empty-state"><p>Failed to load data.</p></div>';
    }
  };

  function renderResultsPagination() {
    const container = document.getElementById("results-pagination");
    if (!container || totalPages <= 1) {
      if (container) container.innerHTML = "";
      return;
    }
    let html = "";
    html +=
      '<button class="btn-secondary btn-sm" ' +
      (currentPage <= 1 ? "disabled" : "") +
      ' onclick="loadRegisteredStudents(' +
      (currentPage - 1) +
      ')">&laquo; Prev</button>';

    const start = Math.max(1, currentPage - 2);
    const end = Math.min(totalPages, currentPage + 2);
    for (let i = start; i <= end; i++) {
      html +=
        '<button class="' +
        (i === currentPage ? "btn-primary" : "btn-secondary") +
        ' btn-sm" onclick="loadRegisteredStudents(' +
        i +
        ')">' +
        i +
        "</button>";
    }

    html +=
      '<button class="btn-secondary btn-sm" ' +
      (currentPage >= totalPages ? "disabled" : "") +
      ' onclick="loadRegisteredStudents(' +
      (currentPage + 1) +
      ')">Next &raquo;</button>';

    container.innerHTML = html;
  }

  window.openMarksModal = function (regId) {
    const student = studentsData.find((s) => s.registration_id == regId);
    if (!student) return;

    document.getElementById("marks-reg-id").value = regId;
    document.getElementById("marks-modal-title").textContent =
      "Update Marks – " + student.first_name + " " + student.last_name;

    const fieldsDiv = document.getElementById("marks-fields");
    fieldsDiv.innerHTML = (student.exam_types || [])
      .map(
        (et) =>
          `<div class="form-group">
            <label class="form-label">${App.esc(et.exam_type_name)}</label>
            <input type="number" step="0.5" class="form-input"
                   data-et="${et.exam_type_id}"
                   value="${et.marks !== null && et.marks !== undefined ? et.marks : ""}"
                   placeholder="Enter marks">
          </div>`,
      )
      .join("");

    App.openModal("marks-modal");
  };

  document
    .getElementById("marks-form")
    .addEventListener("submit", async function (e) {
      e.preventDefault();
      const btn = document.getElementById("marks-save-btn");
      App.startLoading(btn);

      const regId = document.getElementById("marks-reg-id").value;
      const inputs = document.querySelectorAll("#marks-fields input[data-et]");
      const marksObj = {};
      let hasValue = false;
      let invalidInput = false;
      inputs.forEach((inp) => {
        const v = inp.value.trim();
        if (v !== "" && !isNaN(v)) {
            if(Number(v) < 0) {
                invalidInput = true;
                return;
            }
          marksObj[inp.dataset.et] = v;
          hasValue = true;
        }else {
            marksObj[inp.dataset.et] = null; 
        }
      });

      

      if (invalidInput) {
        App.toast("Marks cannot be negative.", "error");
        App.stopLoading(btn);
        return;
      }
      if (!hasValue) {
        App.toast("Please enter at least one mark.", "warning");
        App.stopLoading(btn);
        return;
      }

      try {
        const res = await App.post("api/results.php", {
          action: "update_bulk",
          registration_id: regId,
          marks: JSON.stringify(marksObj),
        });
        App.toast(res.message, res.status ? "success" : "error");
        if (res.status) {
          // Update local data
          const student = studentsData.find((s) => s.registration_id == regId);
          if (student) {
            student.exam_types.forEach((et) => {
              if (marksObj[et.exam_type_id] !== undefined) {
                et.marks = marksObj[et.exam_type_id];
              }
            });
          }
          renderTable();
          App.closeModal("marks-modal");
        }
      } catch (e) {
        App.toast("Failed to update marks.", "error");
      }

      App.stopLoading(btn);
    });

  loadExams();
})();
