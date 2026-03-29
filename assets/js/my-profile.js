/* My Profile JS (Student – edit children info) */
(function () {
  const container = document.getElementById("profile-cards");
  let children = [];

  async function loadChildren() {
    container.innerHTML =
      '<div class="text-center py-8"><span class="spinner"></span></div>';
    try {
      const res = await App.get("api/auth.php?action=children");
      if (res.status && res.data) {
        children = res.data;
      }
    } catch (e) {}
    renderCards();
  }

  function renderCards() {
    if (!children.length) {
      container.innerHTML =
        '<div class="text-center py-8"><p style="color:var(--text-light);">No profiles found.</p></div>';
      return;
    }
    container.innerHTML = children
      .map(
        (c) =>
          `<div class="card mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg" style="color:var(--primary);">${App.esc(c.first_name + " " + c.last_name)}</h3>
                    <p class="text-sm mt-1" style="color:var(--text-light);">School: ${App.esc(c.school_name || "—")}</p>
                    <p class="text-sm" style="color:var(--text-light);">Teacher: ${App.esc(c.teacher_name || "—")}</p>
                    <p class="text-sm" style="color:var(--text-light);">Guardian: ${App.esc((c.guardian_first_name || "") + " " + (c.guardian_last_name || ""))}</p>
                </div>
                <button class="btn-primary btn-sm" onclick="editChild(${c.id})">Edit</button>
            </div>
        </div>`,
      )
      .join("");
  }

  async function loadDropdowns() {
    const schoolSel = document.getElementById("prf-school");
    const teacherSel = document.getElementById("prf-teacher");

    try {
      const res = await App.get("api/schools.php?action=dropdown");
      if (res.status) {
        res.data.forEach((s) => {
          const opt = document.createElement("option");
          opt.value = s.id;
          opt.textContent = s.name;
          schoolSel.appendChild(opt);
        });
      }
    } catch (e) {}

    try {
      const res = await App.get("api/teachers.php?action=dropdown");
      if (res.status) {
        res.data.forEach((t) => {
          const opt = document.createElement("option");
          opt.value = t.id;
          opt.textContent = t.first_name + " " + t.last_name;
          teacherSel.appendChild(opt);
        });
      }
    } catch (e) {}
  }

  window.editChild = function (id) {
    const child = children.find((c) => c.id == id);
    if (!child) return;
    document.getElementById("prf-id").value = child.id;
    document.getElementById("prf-fname").value = child.first_name || "";
    document.getElementById("prf-lname").value = child.last_name || "";
    document.getElementById("prf-school").value = child.school_id || "";
    document.getElementById("prf-teacher").value = child.teacher_id || "";
    document.getElementById("prf-gfname").value =
      child.guardian_first_name || "";
    document.getElementById("prf-glname").value =
      child.guardian_last_name || "";
    document.getElementById("profile-modal-title").textContent =
      "Edit – " + child.first_name + " " + child.last_name;
    App.openModal("profile-modal");
  };

  document
    .getElementById("profile-form")
    .addEventListener("submit", async function (e) {
      e.preventDefault();
      const btn = document.getElementById("prf-save-btn");
      App.startLoading(btn);

      try {
        const res = await App.post("api/auth.php", {
          action: "update_profile",
          student_id: document.getElementById("prf-id").value,
          first_name: document.getElementById("prf-fname").value,
          last_name: document.getElementById("prf-lname").value,
          school_id: document.getElementById("prf-school").value,
          teacher_id: document.getElementById("prf-teacher").value,
          guardian_first_name: document.getElementById("prf-gfname").value,
          guardian_last_name: document.getElementById("prf-glname").value,
        });

        App.toast(res.message, res.status ? "success" : "error");
        if (res.status && res.children) {
          children = res.children;
          renderCards();
          App.closeModal("profile-modal");
        }
      } catch (e) {
        App.toast("Failed to update profile.", "error");
      }

      App.stopLoading(btn);
    });

  loadDropdowns();
  loadChildren();
})();
