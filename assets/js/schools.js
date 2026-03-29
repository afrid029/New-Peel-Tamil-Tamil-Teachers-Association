/* Schools JS */
(function () {
  let allSchools = [];

  async function loadSchools(search = "") {
    try {
      const url =
        "api/schools.php?action=list" +
        (search ? "&search=" + encodeURIComponent(search) : "");
      const res = await App.get(url);
      if (res.status) {
        allSchools = res.data;
        renderTable(res.data);
      }
    } catch (e) {
      App.toast("Failed to load schools", "error");
    }
  }

  function renderTable(data) {
    const tbody = document.getElementById("schools-tbody");
    if (!data.length) {
      tbody.innerHTML =
        '<tr><td colspan="4" class="text-center py-8" style="color:var(--text-light);">No schools found.</td></tr>';
      return;
    }
    tbody.innerHTML = data
      .map(
        (s) => `
            <tr>
                <td>${App.esc(s.name)}</td>
                <td>${App.formatDate(s.created_at)}</td>
                <td class="flex gap-2">
                    <button class="btn-secondary btn-sm" onclick="editSchool(${s.id})">Edit</button>
                    <button class="btn-danger btn-sm" onclick="deleteSchool(${s.id})">Delete</button>
                </td>
            </tr>
        `,
      )
      .join("");
  }

  window.resetSchoolForm = function () {
    document.getElementById("school-form").reset();
    document.getElementById("sch-id").value = "";
    document.getElementById("school-modal-title").textContent = "Add School";
  };

  window.editSchool = function (id) {
    const s = allSchools.find((x) => x.id == id);
    if (!s) return;
    document.getElementById("sch-id").value = s.id;
    document.getElementById("sch-name").value = s.name;
    document.getElementById("school-modal-title").textContent = "Edit School";
    App.openModal("school-modal");
  };

  window.deleteSchool = async function (id) {
    if (
      !(await App.confirmAction("Are you sure you want to delete this school?"))
    )
      return;
    try {
      const res = await App.post("api/schools.php", { action: "delete", id });
      App.toast(res.message, res.status ? "success" : "error");
      if (res.status) {
        allSchools = allSchools.filter((x) => x.id != id);
        renderTable(allSchools);
      }
    } catch (e) {
      App.toast("Failed to delete", "error");
    }
  };

  document
    .getElementById("school-form")
    .addEventListener("submit", async function (e) {
      e.preventDefault();
      const btn = document.getElementById("sch-save-btn");
      App.startLoading(btn);
      const id = document.getElementById("sch-id").value;
      try {
        const res = await App.post("api/schools.php", {
          action: id ? "update" : "create",
          id,
          name: document.getElementById("sch-name").value.trim(),
        });
        App.toast(res.message, res.status ? "success" : "error");
        if (res.status) {
          App.closeModal("school-modal");
          if (!id && res.record) {
            allSchools.unshift(res.record);
          } else if (id && res.record) {
            const idx = allSchools.findIndex((x) => x.id == id);
            if (idx !== -1) Object.assign(allSchools[idx], res.record);
          }
          renderTable(allSchools);
        }
      } catch (e) {
        App.toast("Operation failed", "error");
      }
      App.stopLoading(btn);
    });

  let searchTimer;
  document
    .getElementById("school-search")
    .addEventListener("input", function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => loadSchools(this.value.trim()), 300);
    });

  loadSchools();
})();
