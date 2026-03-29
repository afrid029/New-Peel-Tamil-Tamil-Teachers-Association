/* Exam Types JS */
(function () {
  let allTypes = [];

  async function loadTypes() {
    try {
      const res = await App.get("api/exam-types.php?action=list");
      if (res.status) {
        allTypes = res.data;
        renderTable(res.data);
      }
    } catch (e) {
      App.toast("Failed to load exam types", "error");
    }
  }

  function renderTable(data) {
    const tbody = document.getElementById("et-tbody");
    if (!data.length) {
      tbody.innerHTML =
        '<tr><td colspan="5" class="text-center py-8" style="color:var(--text-light);">No exam types found.</td></tr>';
      return;
    }
    tbody.innerHTML = data
      .map((t) => {
        const active = Number(t.is_active);
        const statusBadge = active
          ? '<span class="badge" style="background:#d1fae5;color:#047857;">Active</span>'
          : '<span class="badge" style="background:#fee2e2;color:#dc2626;">Inactive</span>';
        const toggleLabel = active ? "Deactivate" : "Activate";
        const toggleClass = active ? "btn-danger" : "btn-primary";
        return `
            <tr>
                <td>${App.esc(t.name)}</td>
                <td>${statusBadge}</td>
                <td>${App.formatDate(t.created_at)}</td>
                <td class="flex gap-2">
                    <button class="btn-secondary btn-sm" onclick="editET(${t.id})">Edit</button>
                    <button class="${toggleClass} btn-sm" onclick="toggleET(${t.id})">${toggleLabel}</button>
                </td>
            </tr>
          `;
      })
      .join("");
  }

  window.resetETForm = function () {
    document.getElementById("et-form").reset();
    document.getElementById("et-id").value = "";
    document.getElementById("et-modal-title").textContent = "Add Exam Type";
  };

  window.editET = function (id) {
    const t = allTypes.find((x) => x.id == id);
    if (!t) return;
    document.getElementById("et-id").value = t.id;
    document.getElementById("et-name").value = t.name;
    document.getElementById("et-modal-title").textContent = "Edit Exam Type";
    App.openModal("et-modal");
  };

  window.toggleET = async function (id) {
    const t = allTypes.find((x) => x.id == id);
    if (!t) return;
    const active = Number(t.is_active);
    const action = active ? "deactivate" : "activate";
    const btnLabel = active ? "Deactivate" : "Activate";
    if (
      !(await App.confirmAction(
        `Are you sure you want to ${action} this exam type?`,
        btnLabel,
      ))
    )
      return;
    try {
      const res = await App.post("api/exam-types.php", {
        action: "toggle_status",
        id,
      });
      App.toast(res.message, res.status ? "success" : "error");
      if (res.status && res.record) {
        const idx = allTypes.findIndex((x) => x.id == id);
        if (idx !== -1) Object.assign(allTypes[idx], res.record);
        renderTable(allTypes);
      }
    } catch (e) {
      App.toast("Failed to update status", "error");
    }
  };

  document
    .getElementById("et-form")
    .addEventListener("submit", async function (e) {
      e.preventDefault();
      const btn = document.getElementById("et-save-btn");
      App.startLoading(btn);
      const id = document.getElementById("et-id").value;
      try {
        const res = await App.post("api/exam-types.php", {
          action: id ? "update" : "create",
          id,
          name: document.getElementById("et-name").value.trim(),
        });
        App.toast(res.message, res.status ? "success" : "error");
        if (res.status) {
          App.closeModal("et-modal");
          if (!id && res.record) {
            allTypes.unshift(res.record);
          } else if (id && res.record) {
            const idx = allTypes.findIndex((x) => x.id == id);
            if (idx !== -1) Object.assign(allTypes[idx], res.record);
          }
          renderTable(allTypes);
        }
      } catch (e) {
        App.toast("Operation failed", "error");
      }
      App.stopLoading(btn);
    });

  loadTypes();
})();
