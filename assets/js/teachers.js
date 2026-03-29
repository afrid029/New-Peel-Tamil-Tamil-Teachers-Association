/* Teachers JS */
(function () {
  let allTeachers = [];

  async function loadTeachers(search = "") {
    try {
      const url =
        "api/teachers.php?action=list" +
        (search ? "&search=" + encodeURIComponent(search) : "");
      const res = await App.get(url);
      if (res.status) {
        allTeachers = res.data;
        renderTable(res.data);
      }
    } catch (e) {
      App.toast("Failed to load teachers", "error");
    }
  }

  function renderTable(data) {
    const tbody = document.getElementById("teachers-tbody");
    if (!data.length) {
      tbody.innerHTML =
        '<tr><td colspan="6" class="text-center py-8" style="color:var(--text-light);">No teachers found.</td></tr>';
      return;
    }
    tbody.innerHTML = data
      .map(
        (t) => `
            <tr>
                <td>${App.esc(t.first_name)}</td>
                <td>${App.esc(t.last_name)}</td>
                <td>${App.esc(t.email)}</td>
                <td>${App.formatDate(t.created_at)}</td>
                <td class="flex gap-2">
                    <button class="btn-secondary btn-sm" onclick="editTeacher(${t.id})">Edit</button>
                    <button class="btn-danger btn-sm" onclick="deleteTeacher(${t.id})">Delete</button>
                </td>
            </tr>
        `,
      )
      .join("");
  }

  window.resetTeacherForm = function () {
    document.getElementById("teacher-form").reset();
    document.getElementById("tch-id").value = "";
    document.getElementById("teacher-modal-title").textContent = "Add Teacher";
  };

  window.editTeacher = function (id) {
    const t = allTeachers.find((x) => x.id == id);
    if (!t) return;
    document.getElementById("tch-id").value = t.id;
    document.getElementById("tch-fname").value = t.first_name;
    document.getElementById("tch-lname").value = t.last_name;
    document.getElementById("tch-email").value = t.email;
    document.getElementById("teacher-modal-title").textContent = "Edit Teacher";
    App.openModal("teacher-modal");
  };

  window.deleteTeacher = async function (id) {
    if (
      !(await App.confirmAction(
        "Are you sure you want to delete this teacher?",
      ))
    )
      return;
    try {
      const res = await App.post("api/teachers.php", { action: "delete", id });
      App.toast(res.message, res.status ? "success" : "error");
      if (res.status) {
        allTeachers = allTeachers.filter((x) => x.id != id);
        renderTable(allTeachers);
      }
    } catch (e) {
      App.toast("Failed to delete", "error");
    }
  };

  document
    .getElementById("teacher-form")
    .addEventListener("submit", async function (e) {
      e.preventDefault();
      const btn = document.getElementById("tch-save-btn");
      App.startLoading(btn);
      const id = document.getElementById("tch-id").value;
      try {
        const res = await App.post("api/teachers.php", {
          action: id ? "update" : "create",
          id,
          first_name: document.getElementById("tch-fname").value.trim(),
          last_name: document.getElementById("tch-lname").value.trim(),
          email: document.getElementById("tch-email").value.trim(),
        });
        App.toast(res.message, res.status ? "success" : "error");
        if (res.status) {
          App.closeModal("teacher-modal");
          if (!id && res.record) {
            allTeachers.unshift(res.record);
          } else if (id && res.record) {
            const idx = allTeachers.findIndex((x) => x.id == id);
            if (idx !== -1) Object.assign(allTeachers[idx], res.record);
          }
          renderTable(allTeachers);
        }
      } catch (e) {
        App.toast("Operation failed", "error");
      }
      App.stopLoading(btn);
    });

  let searchTimer;
  document
    .getElementById("teacher-search")
    .addEventListener("input", function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => loadTeachers(this.value.trim()), 300);
    });

  loadTeachers();
})();
