/* Posters JS */
(function () {
  let allPosters = [];

  async function loadPosters() {
    try {
      const res = await App.get("api/posters.php?action=list");
      if (res.status) {
        allPosters = res.data;
        renderTable(res.data);
      }
    } catch (e) {
      App.toast("Failed to load posters", "error");
    }
  }

  function renderTable(data) {
    const tbody = document.getElementById("posters-tbody");
    if (!data.length) {
      tbody.innerHTML =
        '<tr><td colspan="6" class="text-center py-8" style="color:var(--text-light);">No posters found.</td></tr>';
      return;
    }
    tbody.innerHTML = data
      .map(
        (p) => `
            <tr>
                <td>${p.id}</td>
                <td>${App.esc(p.title)}</td>
                <td>${App.esc(p.author)}</td>
                <td>${App.formatDate(p.created_at)}</td>
                <td class="flex gap-2">
                    <button class="btn-secondary btn-sm" onclick="editPoster(${p.id})">Edit</button>
                    <button class="btn-danger btn-sm" onclick="deletePoster(${p.id})">Delete</button>
                </td>
            </tr>
        `,
      )
      .join("");
  }

  window.viewPoster = function (id) {
    const p = allPosters.find((x) => x.id == id);
    if (!p) return;
    document.getElementById("lightbox-img").src =
      "api/serve-image.php?f=posters/" + encodeURIComponent(p.image_path);
    document.getElementById("lightbox-title").textContent = p.title;
    App.openModal("lightbox-modal");
  };

  window.resetPosterForm = function () {
    document.getElementById("poster-form").reset();
    document.getElementById("pst-id").value = "";
    document.getElementById("poster-modal-title").textContent = "Add Poster";
    document.getElementById("pst-image").required = true;
  };

  window.editPoster = function (id) {
    const p = allPosters.find((x) => x.id == id);
    if (!p) return;
    document.getElementById("pst-id").value = p.id;
    document.getElementById("pst-title").value = p.title;
    document.getElementById("pst-image").required = false;
    document.getElementById("pst-preview-img").src =
      "api/serve-image.php?f=posters/" + encodeURIComponent(p.image_path);
    document.getElementById("pst-preview").classList.remove("hidden");
    document.getElementById("poster-modal-title").textContent = "Edit Poster";
    App.openModal("poster-modal");
  };

  window.deletePoster = async function (id) {
    if (!(await App.confirmAction("Delete this poster?"))) return;
    try {
      const res = await App.post("api/posters.php", { action: "delete", id });
      App.toast(res.message, res.status ? "success" : "error");
      if (res.status) {
        allPosters = allPosters.filter((x) => x.id != id);
        renderTable(allPosters);
      }
    } catch (e) {
      App.toast("Failed to delete", "error");
    }
  };

  document
    .getElementById("poster-form")
    .addEventListener("submit", async function (e) {
      e.preventDefault();
      const btn = document.getElementById("pst-save-btn");
      App.startLoading(btn);
      const id = document.getElementById("pst-id").value;
      const formData = new FormData();
      formData.append("action", id ? "update" : "create");
      if (id) formData.append("id", id);
      formData.append(
        "title",
        document.getElementById("pst-title").value.trim(),
      );

      const fileInput = document.getElementById("pst-image");
      if (fileInput.files.length) {
        formData.append("image", fileInput.files[0]);
      }

      try {
        const res = await App.postFormData("api/posters.php", formData);
        App.toast(res.message, res.status ? "success" : "error");
        if (res.status) {
          App.closeModal("poster-modal");
          if (!id && res.record) {
            allPosters.unshift(res.record);
          } else if (id && res.record) {
            const idx = allPosters.findIndex((x) => x.id == id);
            if (idx !== -1) {
              allPosters[idx].title = res.record.title;
              if (res.record.image_path)
                allPosters[idx].image_path = res.record.image_path;
            }
          }
          renderTable(allPosters);
        }
      } catch (e) {
        App.toast("Operation failed", "error");
      }
      App.stopLoading(btn);
    });

  loadPosters();
})();
