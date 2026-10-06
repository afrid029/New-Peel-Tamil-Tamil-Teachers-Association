(function () {
  const pdfLibraryUrl =
    "https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.3/html2pdf.bundle.min.js";
  let pdfLibraryPromise;

  function loadPdfLibrary() {
    if (window.html2pdf) return Promise.resolve();
    if (pdfLibraryPromise) return pdfLibraryPromise;

    pdfLibraryPromise = new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = pdfLibraryUrl;
      script.onload = resolve;
      script.onerror = () =>
        reject(new Error("PDF library could not be loaded."));
      document.head.appendChild(script);
    });

    return pdfLibraryPromise;
  }

  function escapeHtml(value) {
    const element = document.createElement("span");
    element.textContent = value == null ? "" : String(value);
    return element.innerHTML;
  }

  function filePart(value) {
    return (
      String(value || "")
        .replace(/[<>:"/\\|?*\x00-\x1f]/g, "-")
        .replace(/\s+/g, " ")
        .trim()
        .replace(/[. ]+$/, "") || "Student"
    );
  }

  function buildAdmissionSheet(student) {
    const fullName = `${student.first_name} ${student.last_name}`.trim();
    const examTypes = Array.isArray(student.exam_types)
      ? student.exam_types
      : [];
    const typeRows = (examTypes.length ? examTypes : ["No exam types assigned"])
      .map(
        (type) => `
          <tr>
            <td style="border:1px solid #555;padding:10px 12px;">${escapeHtml(type)}</td>
            <td style="border:1px solid #555;padding:10px 12px;height:38px;width:35%;"></td>
          </tr>`,
      )
      .join("");

    const sheet = document.createElement("section");
    sheet.style.cssText =
      "box-sizing:border-box;width:186mm;min-height:273mm;padding:8mm;background:#fff;color:#111;font-family:'Noto Sans Tamil',Latha,'Arial Unicode MS',sans-serif;font-size:12pt;";
    sheet.innerHTML = `
      <header style="text-align:center;border-bottom:2px solid #222;padding-bottom:14px;margin-bottom:20px;">
        <div style="font-size:17pt;font-weight:700;line-height:1.6;">புதிய பீல் தமிழ் ஆசிரியர் சங்கம் - கனடா</div>
        <div style="font-size:13pt;font-weight:600;">New Peel Tamil Teachers Association - Canada</div>
      </header>
      <h1 style="text-align:center;font-size:19pt;margin:0 0 8px;">Exam Admission Sheet</h1>
      <h2 style="text-align:center;font-size:16pt;margin:0 0 22px;">${escapeHtml(student.exam_name)}</h2>
      <h3 style="font-size:13pt;margin:0 0 10px;border-bottom:1px solid #999;padding-bottom:5px;">Student Information</h3>
      <table style="width:100%;border-collapse:collapse;margin-bottom:24px;line-height:1.6;">
        <tbody>
          <tr><td style="padding:5px 0;width:50%;"><strong>Student Name:</strong> ${escapeHtml(fullName)}</td><td style="padding:5px 0;"><strong>Student ID:</strong> ${escapeHtml(student.student_id)}</td></tr>
          <tr><td style="padding:5px 0;"><strong>Grade:</strong> ${escapeHtml(student.grade)}</td><td style="padding:5px 0;"><strong>School:</strong> ${escapeHtml(student.school_name || "—")}</td></tr>
          <tr><td style="padding:5px 0;"><strong>Exam Date:</strong> ${escapeHtml(student.exam_date || "Not set")}</td><td style="padding:5px 0;"><strong>Registration No.:</strong> ${escapeHtml(student.registration_id)}</td></tr>
          <tr><td style="padding:5px 0;"><strong>Registered On:</strong> ${escapeHtml(student.registered_on)}</td><td></td></tr>
        </tbody>
      </table>
      <h3 style="font-size:13pt;margin:0 0 10px;border-bottom:1px solid #999;padding-bottom:5px;">Exam Types and Marks</h3>
      <table style="width:100%;border-collapse:collapse;">
        <thead><tr><th style="border:1px solid #555;padding:9px 12px;text-align:left;background:#f1f1f1;">Exam Type</th><th style="border:1px solid #555;padding:9px 12px;text-align:left;background:#f1f1f1;">Marks</th></tr></thead>
        <tbody>${typeRows}</tbody>
      </table>
    `;
    return sheet;
  }

  document.addEventListener("click", async (event) => {
    const button = event.target.closest(".admission-pdf-btn");
    if (!button || button.disabled) return;

    let student;
    try {
      student = JSON.parse(button.dataset.registration);
    } catch (error) {
      if (window.App) App.toast("Could not read student information.", "error");
      return;
    }

    button.disabled = true;
    const originalText = button.textContent;
    button.textContent = "Preparing PDF...";

    const sheet = buildAdmissionSheet(student);
    const renderRoot = document.createElement("div");
    renderRoot.style.cssText =
      "position:absolute;left:-10000px;top:0;width:210mm;background:#fff;";
    renderRoot.appendChild(sheet);
    document.body.appendChild(renderRoot);

    try {
      await loadPdfLibrary();
      if (document.fonts && document.fonts.ready) await document.fonts.ready;

      const studentName = filePart(
        `${student.first_name} ${student.last_name}`,
      );
      const examName = filePart(student.exam_name);
      await window
        .html2pdf()
        .set({
          margin: [10, 12, 10, 12],
          filename: `${studentName}-${examName}.pdf`,
          image: { type: "jpeg", quality: 0.98 },
          html2canvas: { scale: 2, useCORS: true, backgroundColor: "#ffffff" },
          jsPDF: { unit: "mm", format: "a4", orientation: "portrait" },
          pagebreak: { mode: ["css", "legacy"] },
        })
        .from(sheet)
        .save();
    } catch (error) {
      console.error(error);
      if (window.App)
        App.toast("PDF generation failed. Please try again.", "error");
    } finally {
      renderRoot.remove();
      button.disabled = false;
      button.textContent = originalText;
    }
  });
})();
