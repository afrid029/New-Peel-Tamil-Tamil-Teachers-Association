/* Exams JS */
(function () {
  let allExams = [];

  async function loadExams() {
    try {
      const res = await App.get("api/exams.php?action=list");
      if (res.status) {
        allExams = res.data;
        renderTable(res.data);
      }
    } catch (e) {
      App.toast("Failed to load exams", "error");
    }
  }

  function renderTable(data) {
    const tbody = document.getElementById("exams-tbody");
    if (!data.length) {
      tbody.innerHTML =
        '<tr><td colspan="6" class="text-center py-8" style="color:var(--text-light);">No exams found.</td></tr>';
      return;
    }
    tbody.innerHTML = data
      .map(
        (e) => `
            <tr>
                <td>${App.esc(e.name)}</td>
                <td>${App.formatDate(e.registration_start_date)}</td>
                <td>${App.formatDate(e.registration_end_date)}</td>
                <td>${e.exam_date ? App.formatDate(e.exam_date) : "—"}</td>
                <td class="flex gap-2">
                    <button class="btn-secondary btn-sm" onclick="editExam(${e.id})">Edit</button>
                    <button class="btn-danger btn-sm" onclick="deleteExam(${e.id})">Delete</button>
                </td>
            </tr>
        `,
      )
      .join("");
  }

  window.resetExamForm = function () {
    document.getElementById("exam-form").reset();
    document.getElementById("exam-id").value = "";
    document.getElementById("exam-modal-title").textContent = "Create Exam";
  };

  window.editExam = function (id) {
    const e = allExams.find((x) => x.id == id);
    if (!e) return;
    document.getElementById("exam-id").value = e.id;
    document.getElementById("exam-name").value = e.name;
    document.getElementById("exam-start").value = e.registration_start_date;
    document.getElementById("exam-end").value = e.registration_end_date;
    document.getElementById("exam-date").value = e.exam_date || "";
    document.getElementById("exam-modal-title").textContent = "Edit Exam";
    App.openModal("exam-modal");
  };

  window.deleteExam = async function (id) {
    if (
      !(await App.confirmAction(
        "Are you sure you want to delete this exam? All registrations and results will be lost.",
      ))
    )
      return;
    try {
      const res = await App.post("api/exams.php", { action: "delete", id });
      App.toast(res.message, res.status ? "success" : "error");
      if (res.status) {
        allExams = allExams.filter((x) => x.id != id);
        renderTable(allExams);
      }
    } catch (e) {
      App.toast("Failed to delete", "error");
    }
  };

  document
    .getElementById("exam-form")
    .addEventListener("submit", async function (ev) {
      ev.preventDefault();
      const btn = document.getElementById("exam-save-btn");
      App.startLoading(btn);
      const id = document.getElementById("exam-id").value;
      try {
        const res = await App.post("api/exams.php", {
          action: id ? "update" : "create",
          id,
          name: document.getElementById("exam-name").value.trim(),
          registration_start_date: document.getElementById("exam-start").value,
          registration_end_date: document.getElementById("exam-end").value,
          exam_date: document.getElementById("exam-date").value,
        });
        App.toast(res.message, res.status ? "success" : "error");
        if (res.status) {
          App.closeModal("exam-modal");
          if (!id && res.record) {
            allExams.unshift(res.record);
          } else if (id && res.record) {
            const idx = allExams.findIndex((x) => x.id == id);
            if (idx !== -1) Object.assign(allExams[idx], res.record);
          }
          renderTable(allExams);
        }
      } catch (e) {
        App.toast("Operation failed", "error");
      }
      App.stopLoading(btn);
    });

  loadExams();
})();
