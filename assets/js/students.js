/* Students JS */
(function () {
  let allStudents = [];
  let currentPage = 1;
  let totalPages = 1;
  let currentSearch = "";
  const perPage = 20;
  const isTeacher = USER.role === "teacher";

  async function loadStudents(search = "", page = 1) {
    currentSearch = search;
    currentPage = page;
    const tbody = document.getElementById("students-tbody");
    tbody.innerHTML =
      '<tr><td colspan="7" class="text-center py-8"><span class="spinner"></span></td></tr>';
    try {
      const url =
        "api/students.php?action=list&page=" +
        page +
        "&per_page=" +
        perPage +
        (search ? "&search=" + encodeURIComponent(search) : "");
      const res = await App.get(url);
      if (res.status) {
        allStudents = res.data;
        totalPages = Math.max(1, Math.ceil((res.total || 0) / perPage));
        renderTable(res.data);
        renderPagination();
      }
    } catch (e) {
      App.toast("Failed to load students", "error");
    }
  }

  function renderTable(data) {
    const tbody = document.getElementById("students-tbody");
    if (!data.length) {
      tbody.innerHTML =
        '<tr><td colspan="7" class="text-center py-8" style="color:var(--text-light);">No students found.</td></tr>';
      return;
    }
    tbody.innerHTML = data
      .map(
        (s) => `
            <tr>
                <td>${s.id}</td>
                <td>${App.esc(s.first_name)} ${App.esc(s.last_name)}</td>
                <td>${App.esc(s.email)}</td>
                <td>${App.esc(s.school_name || "—")}</td>
                <td>${App.esc(s.teacher_name || "—")}</td>
                <td>${App.esc(s.guardian_first_name || "")} ${App.esc(s.guardian_last_name || "")}</td>
                <td class="flex gap-2">
                    <button class="btn-secondary btn-sm" onclick="editStudent(${s.id})">Edit</button>
                    <button class="btn-danger btn-sm" onclick="deleteStudent(${s.id})">Delete</button>
                </td>
            </tr>
        `,
      )
      .join("");
  }

  function renderPagination() {
    const container = document.getElementById("students-pagination");
    if (!container || totalPages <= 1) {
      if (container) container.innerHTML = "";
      return;
    }
    let html = "";
    html +=
      '<button class="btn-secondary btn-sm" ' +
      (currentPage <= 1 ? "disabled" : "") +
      ' onclick="goToPage(' +
      (currentPage - 1) +
      ')">&laquo; Prev</button>';

    const start = Math.max(1, currentPage - 2);
    const end = Math.min(totalPages, currentPage + 2);
    for (let i = start; i <= end; i++) {
      html +=
        '<button class="' +
        (i === currentPage ? "btn-primary" : "btn-secondary") +
        ' btn-sm" onclick="goToPage(' +
        i +
        ')">' +
        i +
        "</button>";
    }

    html +=
      '<button class="btn-secondary btn-sm" ' +
      (currentPage >= totalPages ? "disabled" : "") +
      ' onclick="goToPage(' +
      (currentPage + 1) +
      ')">Next &raquo;</button>';

    container.innerHTML = html;
  }

  window.goToPage = function (page) {
    if (page < 1 || page > totalPages) return;
    loadStudents(currentSearch, page);
  };

  async function loadDropdowns() {
    // Schools
    try {
      const res = await App.get("api/schools.php?action=dropdown");
      if (res.status) {
        const sel = document.getElementById("stu-school");
        res.data.forEach((s) => {
          const opt = document.createElement("option");
          opt.value = s.id;
          opt.textContent = s.name;
          sel.appendChild(opt);
        });
      }
    } catch (e) {}

    // Teachers (only for admin/manager)
    if (!isTeacher) {
      try {
        const res = await App.get("api/teachers.php?action=dropdown");
        if (res.status) {
          const sel = document.getElementById("stu-teacher");
          if (sel) {
            res.data.forEach((t) => {
              const opt = document.createElement("option");
              opt.value = t.id;
              opt.textContent = t.first_name + " " + t.last_name;
              sel.appendChild(opt);
            });
          }
        }
      } catch (e) {}
    }
  }

  window.openAddStudentModal = function () {
    document.getElementById("student-form").reset();
    document.getElementById("stu-id").value = "";
    const mid = document.getElementById("stu-manual-id");
    if (mid) mid.value = "";
    document.getElementById("student-modal-title").textContent = "Add Student";
    App.openModal("student-modal");
  };

  window.editStudent = function (id) {
    const s = allStudents.find((x) => x.id == id);
    if (!s) return;
    document.getElementById("stu-id").value = s.id;
    document.getElementById("stu-email").value = s.email;
    document.getElementById("stu-fname").value = s.first_name;
    document.getElementById("stu-lname").value = s.last_name;
    document.getElementById("stu-school").value = s.school_id || "";
    document.getElementById("stu-gfname").value = s.guardian_first_name || "";
    document.getElementById("stu-glname").value = s.guardian_last_name || "";
    const mid = document.getElementById("stu-manual-id");
    if (mid) mid.value = "";
    if (!isTeacher) {
      const tSel = document.getElementById("stu-teacher");
      if (tSel) tSel.value = s.teacher_id || "";
    }
    document.getElementById("student-modal-title").textContent = "Edit Student";
    App.openModal("student-modal");
  };

  window.deleteStudent = async function (id) {
    if (
      !(await App.confirmAction(
        "Are you sure you want to delete this student?",
      ))
    )
      return;
    try {
      const res = await App.post("api/students.php", { action: "delete", id });
      App.toast(res.message, res.status ? "success" : "error");
      if (res.status) {
        allStudents = allStudents.filter((x) => x.id != id);
        renderTable(allStudents);
      }
    } catch (e) {
      App.toast("Failed to delete", "error");
    }
  };

  document
    .getElementById("student-form")
    .addEventListener("submit", async function (e) {
      e.preventDefault();
      const btn = document.getElementById("stu-save-btn");
      App.startLoading(btn);
      const id = document.getElementById("stu-id").value;
      const params = {
        action: id ? "update" : "create",
        id,
        email: document.getElementById("stu-email").value.trim(),
        first_name: document.getElementById("stu-fname").value.trim(),
        last_name: document.getElementById("stu-lname").value.trim(),
        school_id: document.getElementById("stu-school").value,
        guardian_first_name: document.getElementById("stu-gfname").value.trim(),
        guardian_last_name: document.getElementById("stu-glname").value.trim(),
      };

      if (!isTeacher) {
        const tSel = document.getElementById("stu-teacher");
        if (tSel) params.teacher_id = tSel.value;
      }

      const mid = document.getElementById("stu-manual-id");
      if (mid && mid.value.trim()) params.manual_id = mid.value.trim();

      try {
        const res = await App.post("api/students.php", params);
        App.toast(res.message, res.status ? "success" : "error");
        if (res.status) {
          App.closeModal("student-modal");
          if (!id && res.record) {
            allStudents.unshift(res.record);
          } else if (id && res.record) {
            const idx = allStudents.findIndex((x) => x.id == id);
            if (idx !== -1) Object.assign(allStudents[idx], res.record);
          }
          renderTable(allStudents);
        }
      } catch (e) {
        App.toast("Operation failed", "error");
      }
      App.stopLoading(btn);
    });

  let searchTimer;
  document
    .getElementById("student-search")
    .addEventListener("input", function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => loadStudents(this.value.trim(), 1), 300);
    });

  loadDropdowns();
  loadStudents();
})();
