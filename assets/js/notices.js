/* Notices JS */
(function () {
  let allNotices = [];

  async function loadNotices() {
    try {
      const res = await App.get("api/notices.php?action=list");
      if (res.status) {
        allNotices = res.data;
        renderTable(res.data);
      }
    } catch (e) {
      App.toast("Failed to load notices", "error");
    }
  }

  function renderTable(data) {
    const tbody = document.getElementById("notices-tbody");
    if (!data.length) {
      tbody.innerHTML =
        '<tr><td colspan="5" class="text-center py-8" style="color:var(--text-light);">No notices found.</td></tr>';
      return;
    }
    tbody.innerHTML = data
      .map(
        (n) => `
            <tr>
                <td>${n.id}</td>
                <td>${App.esc(n.title)}</td>
                <td>${App.esc(n.author)}</td>
                <td>${App.formatDate(n.created_at)}</td>
                <td class="flex gap-2">
                    <button class="btn-secondary btn-sm" onclick="editNotice(${n.id})">Edit</button>
                    <button class="btn-danger btn-sm" onclick="deleteNotice(${n.id})">Delete</button>
                </td>
            </tr>
        `,
      )
      .join("");
  }

  window.resetNoticeForm = function () {
    document.getElementById("notice-form").reset();
    document.getElementById("ntc-id").value = "";
    document.getElementById("notice-modal-title").textContent = "Add Notice";
  };

  window.editNotice = function (id) {
    const n = allNotices.find((x) => x.id == id);
    if (!n) return;
    document.getElementById("ntc-id").value = n.id;
    document.getElementById("ntc-title").value = n.title;
    document.getElementById("ntc-content").value = n.content;
    document.getElementById("notice-modal-title").textContent = "Edit Notice";
    App.openModal("notice-modal");
  };

  window.deleteNotice = async function (id) {
    if (!(await App.confirmAction("Delete this notice?"))) return;
    try {
      const res = await App.post("api/notices.php", { action: "delete", id });
      App.toast(res.message, res.status ? "success" : "error");
      if (res.status) {
        allNotices = allNotices.filter((x) => x.id != id);
        renderTable(allNotices);
      }
    } catch (e) {
      App.toast("Failed to delete", "error");
    }
  };

  document
    .getElementById("notice-form")
    .addEventListener("submit", async function (e) {
      e.preventDefault();
      const btn = document.getElementById("ntc-save-btn");
      App.startLoading(btn);
      const id = document.getElementById("ntc-id").value;
      try {
        const res = await App.post("api/notices.php", {
          action: id ? "update" : "create",
          id,
          title: document.getElementById("ntc-title").value.trim(),
          content: document.getElementById("ntc-content").value.trim(),
        });
        App.toast(res.message, res.status ? "success" : "error");
        if (res.status) {
          App.closeModal("notice-modal");
          if (!id && res.record) {
            allNotices.unshift(res.record);
          } else if (id && res.record) {
            const idx = allNotices.findIndex((x) => x.id == id);
            if (idx !== -1) Object.assign(allNotices[idx], res.record);
          }
          renderTable(allNotices);
        }
      } catch (e) {
        App.toast("Operation failed", "error");
      }
      App.stopLoading(btn);
    });

  loadNotices();
})();
