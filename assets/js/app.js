/* =====================================================
   NPTTA – Core JS Utilities
   ===================================================== */

const App = (() => {
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || "";

  /* ---------- XHR helper ---------- */
  function xhr(method, url, data = null, isFormData = false) {
    return new Promise((resolve, reject) => {
      const req = new XMLHttpRequest();
      req.open(method, url, true);
      req.setRequestHeader("X-Csrf-Token", CSRF);
      if (!isFormData && method !== "GET") {
        req.setRequestHeader(
          "Content-Type",
          "application/x-www-form-urlencoded",
        );
      }
      req.onload = function () {
        try {
          const json = JSON.parse(req.responseText);
          resolve(json);
        } catch (e) {
          reject({ status: false, message: "Invalid server response." });
        }
      };
      req.onerror = function () {
        reject({ status: false, message: "Network error. Please try again." });
      };
      req.send(data);
    });
  }

  function get(url) {
    return xhr("GET", url);
  }

  function post(url, params = {}) {
    const body = new URLSearchParams(params);
    body.append("csrf_token", CSRF);
    return xhr("POST", url, body.toString());
  }

  function postFormData(url, formData) {
    formData.append("csrf_token", CSRF);
    return xhr("POST", url, formData, true);
  }

  /* ---------- Toast ---------- */
  function toast(msg, type = "success") {
    let container = document.querySelector(".toast-container");
    if (!container) {
      container = document.createElement("div");
      container.className = "toast-container";
      document.body.appendChild(container);
    }
    const el = document.createElement("div");
    el.className = `toast ${type}`;
    el.textContent = msg;
    container.appendChild(el);
    setTimeout(() => el.remove(), 3500);
  }

  /* ---------- Modal ---------- */
  function openModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.add("active");
  }
  function closeModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.remove("active");
  }
  function closeAllModals() {
    document
      .querySelectorAll(".modal-overlay.active")
      .forEach((m) => m.classList.remove("active"));
  }

  /* ---------- Confirm (graceful modal) ---------- */
  function confirmAction(msg, btnLabel) {
    return new Promise((resolve) => {
      const modal = document.getElementById("confirm-modal");
      if (!modal) {
        resolve(confirm(msg));
        return;
      }
      document.getElementById("confirm-msg").textContent = msg;
      const yesBtn = document.getElementById("confirm-yes");
      const noBtn = document.getElementById("confirm-no");
      yesBtn.textContent = btnLabel || "Delete";
      if (btnLabel) {
        yesBtn.className = "btn-primary";
        yesBtn.style.minWidth = "100px";
      } else {
        yesBtn.className = "btn-danger";
        yesBtn.style.minWidth = "100px";
      }
      modal.classList.add("active");
      function cleanup() {
        modal.classList.remove("active");
        yesBtn.removeEventListener("click", onYes);
        noBtn.removeEventListener("click", onNo);
      }
      function onYes() {
        cleanup();
        resolve(true);
      }
      function onNo() {
        cleanup();
        resolve(false);
      }
      yesBtn.addEventListener("click", onYes);
      noBtn.addEventListener("click", onNo);
    });
  }

  /* ---------- Sidebar toggle ---------- */
  function initSidebar() {
    const toggle = document.getElementById("sidebar-toggle");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebar-overlay");
    if (toggle && sidebar) {
      toggle.addEventListener("click", () => {
        sidebar.classList.toggle("open");
        if (overlay) overlay.classList.toggle("active");
      });
    }
    if (overlay) {
      overlay.addEventListener("click", () => {
        sidebar.classList.remove("open");
        overlay.classList.remove("active");
      });
    }
  }

  /* ---------- Close modal on overlay click ---------- */
  function initModals() {
    document.querySelectorAll(".modal-overlay").forEach((overlay) => {
      overlay.addEventListener("click", function (e) {
        if (e.target === this) this.classList.remove("active");
      });
    });
  }

  /* ---------- Loader ---------- */
  function showLoader(el) {
    el.innerHTML =
      '<div style="text-align:center;padding:40px;"><span class="spinner"></span></div>';
  }

  /* ---------- Action overlay (for buttons during API calls) ---------- */
  function startLoading(btn) {
    if (!btn) return;
    btn._origText = btn.textContent;
    btn.disabled = true;
    btn.innerHTML =
      '<span class="spinner" style="width:14px;height:14px;border-width:2px;vertical-align:middle;color:inherit;"></span> ' +
      btn._origText;
  }
  function stopLoading(btn) {
    if (!btn) return;
    btn.disabled = false;
    btn.textContent = btn._origText || btn.textContent;
  }

  /* ---------- Format date ---------- */
  function formatDate(dateStr) {
    if (!dateStr) return "—";
    // Handle both date-only (YYYY-MM-DD) and datetime strings
    const raw =
      dateStr.length === 10 ? dateStr + "T00:00:00" : dateStr.replace(" ", "T");
    const d = new Date(raw);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString("en-CA", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  }

  /* ---------- Escape HTML ---------- */
  function esc(str) {
    const div = document.createElement("div");
    div.textContent = str || "";
    return div.innerHTML;
  }

  /* ---------- Init ---------- */
  document.addEventListener("DOMContentLoaded", () => {
    initSidebar();
    initModals();
  });

  return {
    get,
    post,
    postFormData,
    toast,
    openModal,
    closeModal,
    closeAllModals,
    confirmAction,
    showLoader,
    startLoading,
    stopLoading,
    formatDate,
    esc,
  };
})();
