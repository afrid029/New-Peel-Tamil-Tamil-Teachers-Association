/* Results Management JS (admin / manager) */
(function () {
  let studentsData = [];
  let currentPage = 1;
  let totalPages = 1;
  const perPage = 20;

  function placementOptions(examTypeId, selectedPlacement) {
    return ["1st", "2nd", "3rd"]
      .map(
        (placement) => `
          <label class="inline-flex items-center gap-1 text-xs" style="cursor:pointer;">
            <input type="checkbox" class="res-placement-check" data-et="${examTypeId}" data-placement="${placement}" ${selectedPlacement === placement ? "checked" : ""}>
            ${placement} Place
          </label>`,
      )
      .join("");
  }

  function placementBadge(placement) {
    const styles = {
      "1st": "background:#fff2c2;color:#946200;",
      "2nd": "background:#e8edf2;color:#56616f;",
      "3rd": "background:#f7e4d8;color:#8a4b25;",
    };
    return styles[placement]
      ? `<span style="display:inline-block;margin-left:6px;padding:2px 7px;border-radius:999px;font-size:11px;font-weight:700;${styles[placement]}">${placement} Place</span>`
      : "";
  }

  document.addEventListener("change", (event) => {
    const checkbox = event.target;
    if (checkbox.matches(".res-placement-check") && checkbox.checked) {
      document
        .querySelectorAll(
          `.res-placement-check[data-et="${checkbox.dataset.et}"]`,
        )
        .forEach((other) => {
          if (other !== checkbox) other.checked = false;
        });
    }
  });

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
        html += `<div><span class="text-xs font-semibold" style="color:var(--text-light);">${App.esc(et.exam_type_name)}</span><br><span class="font-semibold">${App.esc(String(val))}</span>${placementBadge(et.placement)}</div>`;
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
            <div class="flex flex-wrap gap-3 mt-2" aria-label="Place for ${App.esc(et.exam_type_name)}">
              ${placementOptions(et.exam_type_id, et.placement)}
            </div>
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
      const inputs = document.querySelectorAll(
        '#marks-fields input[type="number"][data-et]',
      );
      const marksObj = {};
      const placementsObj = {};
      let hasValue = false;
      let invalidInput = false;
      inputs.forEach((inp) => {
        const v = inp.value.trim();
        const numericMarks = v === "" ? null : Number(v);
        marksObj[inp.dataset.et] = numericMarks;
        const selectedPlacement = document.querySelector(
          `.res-placement-check[data-et="${inp.dataset.et}"]:checked`,
        );
        placementsObj[inp.dataset.et] = selectedPlacement
          ? selectedPlacement.dataset.placement
          : null;
        if (v !== "" && !Number.isFinite(numericMarks)) {
          invalidInput = true;
        } else if (numericMarks !== null) {
          if (numericMarks < 0) {
            invalidInput = true;
          } else {
            hasValue = true;
          }
        }
      });

      if (invalidInput) {
        App.toast("Enter valid, non-negative marks.", "error");
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
          placements: JSON.stringify(placementsObj),
        });
        App.toast(res.message, res.status ? "success" : "error");
        if (res.status) {
          // Update local data
          const student = studentsData.find((s) => s.registration_id == regId);
          if (student) {
            student.exam_types.forEach((et) => {
              if (marksObj[et.exam_type_id] !== undefined) {
                et.marks = marksObj[et.exam_type_id];
                et.placement = placementsObj[et.exam_type_id];
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
