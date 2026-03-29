/* Managers JS */
(function () {
  let allManagers = [];

  async function loadManagers(search = "") {
    try {
      const url =
        "api/managers.php?action=list" +
        (search ? "&search=" + encodeURIComponent(search) : "");
      const res = await App.get(url);
      if (res.status) {
        allManagers = res.data;
        renderTable(res.data);
      }
    } catch (e) {
      App.toast("Failed to load managers", "error");
    }
  }

  function renderTable(data) {
    const tbody = document.getElementById("managers-tbody");
    if (!data.length) {
      tbody.innerHTML =
        '<tr><td colspan="6" class="text-center py-8" style="color:var(--text-light);">No managers found.</td></tr>';
      return;
    }
    tbody.innerHTML = data
      .map(
        (m) => `
            <tr>
                <td>${App.esc(m.first_name)}</td>
                <td>${App.esc(m.last_name)}</td>
                <td>${App.esc(m.email)}</td>
                <td>${App.formatDate(m.created_at)}</td>
                <td class="flex gap-2">
                    <button class="btn-secondary btn-sm" onclick="editManager(${m.id})">Edit</button>
                    <button class="btn-danger btn-sm" onclick="deleteManager(${m.id})">Delete</button>
                </td>
            </tr>
        `,
      )
      .join("");
  }

  window.resetManagerForm = function () {
    document.getElementById("manager-form").reset();
    document.getElementById("mgr-id").value = "";
    document.getElementById("manager-modal-title").textContent = "Add Manager";
  };

  window.editManager = function (id) {
    const m = allManagers.find((x) => x.id == id);
    if (!m) return;
    document.getElementById("mgr-id").value = m.id;
    document.getElementById("mgr-fname").value = m.first_name;
    document.getElementById("mgr-lname").value = m.last_name;
    document.getElementById("mgr-email").value = m.email;
    document.getElementById("manager-modal-title").textContent = "Edit Manager";
    App.openModal("manager-modal");
  };

  window.deleteManager = async function (id) {
    if (
      !(await App.confirmAction(
        "Are you sure you want to delete this manager?",
      ))
    )
      return;
    try {
      const res = await App.post("api/managers.php", { action: "delete", id });
      App.toast(res.message, res.status ? "success" : "error");
      if (res.status) {
        allManagers = allManagers.filter((x) => x.id != id);
        renderTable(allManagers);
      }
    } catch (e) {
      App.toast("Failed to delete", "error");
    }
  };

  document
    .getElementById("manager-form")
    .addEventListener("submit", async function (e) {
      e.preventDefault();
      const btn = document.getElementById("mgr-save-btn");
      App.startLoading(btn);
      const id = document.getElementById("mgr-id").value;
      const action = id ? "update" : "create";
      try {
        const res = await App.post("api/managers.php", {
          action,
          id,
          first_name: document.getElementById("mgr-fname").value.trim(),
          last_name: document.getElementById("mgr-lname").value.trim(),
          email: document.getElementById("mgr-email").value.trim(),
        });
        App.toast(res.message, res.status ? "success" : "error");
        if (res.status) {
          App.closeModal("manager-modal");
          if (action === "create" && res.record) {
            allManagers.unshift(res.record);
          } else if (action === "update" && res.record) {
            const idx = allManagers.findIndex((x) => x.id == id);
            if (idx !== -1) Object.assign(allManagers[idx], res.record);
          }
          renderTable(allManagers);
        }
      } catch (e) {
        App.toast("Operation failed", "error");
      }
      App.stopLoading(btn);
    });

  // Search
  let searchTimer;
  document
    .getElementById("manager-search")
    .addEventListener("input", function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => loadManagers(this.value.trim()), 300);
    });

  loadManagers();
})();
