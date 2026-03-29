/* Exam Registration JS (Student) */
(function () {
  let activeExam = null;
  let allRegistrations = [];
  const childSel = document.getElementById("reg-child");
  const form = document.getElementById("reg-form");
  const already = document.getElementById("reg-already");

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

  function checkChildRegistration() {
    const childId = childSel.value;
    if (!activeExam || !childId) return;
    const isReg = allRegistrations.some(
      (r) => r.exam_id == activeExam.id && r.student_id == childId,
    );
    if (isReg) {
      form.classList.add("hidden");
      already.classList.remove("hidden");
    } else {
      already.classList.add("hidden");
      form.classList.remove("hidden");
      form.reset();
      document.getElementById("reg-exam-name").value = activeExam.name;
      document.getElementById("reg-exam-id").value = activeExam.id;
    }
  }

  async function init() {
    const loading = document.getElementById("reg-loading");
    const noExam = document.getElementById("reg-no-exam");

    await populateChildren();

    try {
      const examRes = await App.get("api/registration.php?action=active_exam");
      loading.classList.add("hidden");

      if (!examRes.status || !examRes.data) {
        noExam.classList.remove("hidden");
        return;
      }

      activeExam = examRes.data;
      document.getElementById("reg-exam-name").value = activeExam.name;
      document.getElementById("reg-exam-id").value = activeExam.id;
      document.getElementById("reg-child-wrapper").classList.remove("hidden");

      // Load all registrations for all children
      const myRegs = await App.get(
        "api/registration.php?action=my_registrations",
      );
      if (myRegs.status && myRegs.data) {
        allRegistrations = myRegs.data;
      }

      // Load exam types
      const typesRes = await App.get("api/exam-types.php?action=dropdown");
      if (typesRes.status && typesRes.data.length) {
        const container = document.getElementById("reg-exam-types");
        container.innerHTML = typesRes.data
          .map(
            (t) =>
              `<label class="checkbox-item">
                        <input type="checkbox" name="exam_types[]" value="${t.id}">
                        ${App.esc(t.name)}
                    </label>`,
          )
          .join("");
      }

      checkChildRegistration();
    } catch (e) {
      loading.classList.add("hidden");
      noExam.classList.remove("hidden");
    }
  }

  childSel.addEventListener("change", checkChildRegistration);

  form.addEventListener("submit", async function (e) {
    e.preventDefault();
    const btn = document.getElementById("reg-submit-btn");
    App.startLoading(btn);

    const checks = document.querySelectorAll(
      'input[name="exam_types[]"]:checked',
    );
    if (!checks.length) {
      App.toast("Please select at least one exam type.", "error");
      App.stopLoading(btn);
      return;
    }

    const params = {
      action: "register",
      exam_id: document.getElementById("reg-exam-id").value,
      student_id: childSel.value,
      grade: document.getElementById("reg-grade").value,
    };

    const body = new URLSearchParams(params);
    const csrf =
      document.querySelector('meta[name="csrf-token"]')?.content || "";
    body.append("csrf_token", csrf);
    checks.forEach((cb) => body.append("exam_types[]", cb.value));

    try {
      const res = await new Promise((resolve, reject) => {
        const req = new XMLHttpRequest();
        req.open("POST", "api/registration.php", true);
        req.setRequestHeader("X-Csrf-Token", csrf);
        req.onload = () => {
          try {
            resolve(JSON.parse(req.responseText));
          } catch (e) {
            reject({ status: false, message: "Error" });
          }
        };
        req.onerror = () => reject({ status: false, message: "Network error" });
        req.send(body);
      });

      App.toast(res.message, res.status ? "success" : "error");
      if (res.status) {
        // Add to local registrations so child selector reflects it
        allRegistrations.push({
          exam_id: activeExam.id,
          student_id: childSel.value,
        });
        checkChildRegistration();
      }
    } catch (e) {
      App.toast(e.message || "Registration failed", "error");
    }

    App.stopLoading(btn);
  });

  init();
})();
