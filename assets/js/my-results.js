/* My Results JS (Student) */
(function () {
  const childSel = document.getElementById("mr-child");
  const loading = document.getElementById("my-results-loading");
  const empty = document.getElementById("my-results-empty");
  const list = document.getElementById("my-results-list");

  async function populateChildren() {
    try {
      const res = await App.get("api/auth.php?action=children");
      if (res.status && res.data) {
        childSel.innerHTML = res.data
          .map(
            (c) =>
              `<option value="${c.id}">${App.esc(c.first_name + " " + c.last_name)}</option>`,
          )
          .join("");
      }
    } catch (e) {}
  }

  async function loadRegistrations() {
    const childId = childSel.value;
    if (!childId) return;

    loading.classList.remove("hidden");
    empty.classList.add("hidden");
    list.classList.add("hidden");

    try {
      const res = await App.get(
        "api/registration.php?action=my_registrations&student_id=" + childId,
      );
      loading.classList.add("hidden");

      if (!res.status || !res.data || !res.data.length) {
        empty.classList.remove("hidden");
        return;
      }

      let html = "";
      res.data.forEach((r) => {
        html += `<div class="flex items-center justify-between p-4 border-b" style="border-color:var(--border);">
                    <div>
                        <h4 class="font-semibold" style="color:var(--primary);">${App.esc(r.exam_name)}</h4>
                        <p class="text-sm" style="color:var(--text-light);">Grade: ${App.esc(r.grade)} &bull; Registered: ${App.formatDate(r.registered_on)}</p>
                        <p class="text-xs mt-1" style="color:var(--text-light);">Types: ${r.exam_types.map((t) => App.esc(t)).join(", ")}</p>
                    </div>
                    <button class="btn-primary btn-sm" onclick="viewResult(${r.registration_id}, '${App.esc(r.exam_name)}')">Result</button>
                </div>`;
      });

      list.innerHTML = html;
      list.classList.remove("hidden");
    } catch (e) {
      loading.classList.add("hidden");
      empty.classList.remove("hidden");
    }
  }

  window.viewResult = async function (regId, examName) {
    document.getElementById("result-modal-title").textContent =
      examName + " – Result";
    const body = document.getElementById("result-modal-body");
    body.innerHTML =
      '<div class="text-center py-6"><span class="spinner"></span></div>';
    App.openModal("result-modal");

    try {
      const res = await App.get(
        "api/results.php?action=view&registration_id=" + regId,
      );
      if (!res.status) {
        body.innerHTML = `<div class="text-center py-6"><p style="color:var(--text-light);">${App.esc(res.message)}</p></div>`;
        return;
      }

      let html =
        '<table class="data-table result-table" style="border-radius:8px;overflow:hidden;">';
      html += "<thead><tr><th>Exam Type</th><th>Marks</th></tr></thead><tbody>";
      res.data.forEach((r) => {
        html += `<tr><td>${App.esc(r.exam_type)}</td><td>${r.marks !== null ? r.marks : "—"}</td></tr>`;
      });
      html += "</tbody></table>";
      body.innerHTML = html;
    } catch (e) {
      body.innerHTML =
        '<div class="text-center py-6"><p style="color:var(--danger);">Failed to load results.</p></div>';
    }
  };

  childSel.addEventListener("change", loadRegistrations);

  populateChildren().then(() => loadRegistrations());
})();
