/* ============================================================
   MailFlow — Shared UI Components (UI-only)
   ============================================================ */

(function () {
  "use strict";

  /* ---------- Sidebar ---------- */
  function initSidebar() {
    const toggleBtns = document.querySelectorAll("[data-sidebar-toggle]");
    const overlay = document.getElementById("sidebarOverlay");
    const mq = window.matchMedia("(max-width: 991.98px)");

    function isMobile() {
      return mq.matches;
    }

    function closeMobileSidebar() {
      document.body.classList.remove("sidebar-open");
      if (overlay) overlay.classList.remove("show");
    }

    function openMobileSidebar() {
      document.body.classList.add("sidebar-open");
      if (overlay) overlay.classList.add("show");
    }

    toggleBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        if (isMobile()) {
          if (document.body.classList.contains("sidebar-open")) {
            closeMobileSidebar();
          } else {
            openMobileSidebar();
          }
        } else {
          document.body.classList.toggle("sidebar-collapsed");
        }
      });
    });

    if (overlay) {
      overlay.addEventListener("click", closeMobileSidebar);
    }

    mq.addEventListener("change", function () {
      closeMobileSidebar();
      if (isMobile()) {
        document.body.classList.remove("sidebar-collapsed");
      }
    });

    document.querySelectorAll(".sidebar-link").forEach(function (link) {
      link.addEventListener("click", function () {
        if (isMobile()) closeMobileSidebar();
      });
    });
  }

  /* ---------- Tooltips ---------- */
  function initTooltips() {
    if (typeof bootstrap === "undefined") return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      new bootstrap.Tooltip(el);
    });
  }

  /* ---------- Toasts ---------- */
  function ensureToastContainer() {
    let container = document.getElementById("toastContainer");
    if (!container) {
      container = document.createElement("div");
      container.id = "toastContainer";
      container.className = "toast-container-custom";
      document.body.appendChild(container);
    }
    return container;
  }

  function showToast(message, type) {
    type = type || "success";
    const icons = {
      success: "bi-check-lg",
      error: "bi-x-lg",
      danger: "bi-x-lg",
      warning: "bi-exclamation-triangle",
      info: "bi-info-circle",
    };
    const container = ensureToastContainer();
    const toast = document.createElement("div");
    toast.className = "toast-custom " + type;
    toast.setAttribute("role", "alert");
    toast.innerHTML =
      '<div class="toast-icon"><i class="bi ' +
      (icons[type] || icons.info) +
      '"></i></div>' +
      '<div class="toast-body-custom">' +
      message +
      "</div>" +
      '<button type="button" class="toast-close" aria-label="Close"><i class="bi bi-x"></i></button>';

    container.appendChild(toast);

    const remove = function () {
      toast.classList.add("hide");
      setTimeout(function () {
        toast.remove();
      }, 250);
    };

    toast.querySelector(".toast-close").addEventListener("click", remove);
    setTimeout(remove, 4200);
  }

  window.MailFlow = window.MailFlow || {};
  window.MailFlow.showToast = showToast;

  /* ---------- Button loading ---------- */
  function setButtonLoading(btn, loading, loadingText) {
    if (!btn) return;
    if (loading) {
      btn.dataset.originalHtml = btn.innerHTML;
      btn.classList.add("btn-loading");
      btn.disabled = true;
      btn.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>' +
        (loadingText ? " " + loadingText : "");
    } else {
      btn.classList.remove("btn-loading");
      btn.disabled = false;
      if (btn.dataset.originalHtml) {
        btn.innerHTML = btn.dataset.originalHtml;
      }
    }
  }

  window.MailFlow.setButtonLoading = setButtonLoading;

  /* ---------- Confirm dialog ---------- */
  function confirmAction(options) {
    const opts = Object.assign(
      {
        title: "Confirm",
        message: "Are you sure?",
        confirmText: "Confirm",
        cancelText: "Cancel",
        danger: false,
      },
      options || {}
    );

    return new Promise(function (resolve) {
      let modalEl = document.getElementById("confirmModal");
      if (!modalEl) {
        modalEl = document.createElement("div");
        modalEl.id = "confirmModal";
        modalEl.className = "modal fade";
        modalEl.tabIndex = -1;
        modalEl.innerHTML =
          '<div class="modal-dialog modal-dialog-centered">' +
          '<div class="modal-content">' +
          '<div class="modal-header">' +
          '<h5 class="modal-title" id="confirmModalTitle"></h5>' +
          '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
          "</div>" +
          '<div class="modal-body"><p id="confirmModalMessage" class="mb-0"></p></div>' +
          '<div class="modal-footer">' +
          '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="confirmModalCancel"></button>' +
          '<button type="button" class="btn" id="confirmModalOk"></button>' +
          "</div></div></div>";
        document.body.appendChild(modalEl);
      }

      document.getElementById("confirmModalTitle").textContent = opts.title;
      document.getElementById("confirmModalMessage").textContent = opts.message;
      document.getElementById("confirmModalCancel").textContent = opts.cancelText;
      const okBtn = document.getElementById("confirmModalOk");
      okBtn.textContent = opts.confirmText;
      okBtn.className = "btn " + (opts.danger ? "btn-danger" : "btn-primary");

      const modal = new bootstrap.Modal(modalEl);
      let resolved = false;

      function finish(result) {
        if (resolved) return;
        resolved = true;
        resolve(result);
      }

      okBtn.onclick = function () {
        finish(true);
        modal.hide();
      };

      modalEl.addEventListener(
        "hidden.bs.modal",
        function () {
          finish(false);
        },
        { once: true }
      );

      modal.show();
    });
  }

  window.MailFlow.confirm = confirmAction;

  /* ---------- Form validation helpers ---------- */
  function validateForm(form) {
    let valid = true;
    form.querySelectorAll("[required]").forEach(function (field) {
      const value = (field.value || "").trim();
      if (!value) {
        field.classList.add("is-invalid");
        field.classList.remove("is-valid");
        valid = false;
      } else if (field.type === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        field.classList.add("is-invalid");
        field.classList.remove("is-valid");
        valid = false;
      } else {
        field.classList.remove("is-invalid");
        field.classList.add("is-valid");
      }
    });
    return valid;
  }

  window.MailFlow.validateForm = validateForm;

  /* ---------- Skeleton demo helpers ---------- */
  function showSkeletons(show) {
    document.querySelectorAll("[data-skeleton]").forEach(function (el) {
      el.style.display = show ? "" : "none";
    });
    document.querySelectorAll("[data-content]").forEach(function (el) {
      el.style.display = show ? "none" : "";
    });
  }

  window.MailFlow.showSkeletons = showSkeletons;

  /* ---------- Greeting ---------- */
  function getGreeting() {
    const hour = new Date().getHours();
    if (hour < 12) return "Good Morning";
    if (hour < 17) return "Good Afternoon";
    return "Good Evening";
  }

  function initGreeting() {
    const el = document.getElementById("greetingText");
    if (el) {
      el.textContent = getGreeting() + ", Admin";
    }
  }

  /* ---------- Filter pills ---------- */
  function initFilterPills() {
    document.querySelectorAll(".filter-pills").forEach(function (group) {
      group.querySelectorAll(".filter-pill").forEach(function (pill) {
        pill.addEventListener("click", function () {
          group.querySelectorAll(".filter-pill").forEach(function (p) {
            p.classList.remove("active");
          });
          pill.classList.add("active");
          const event = new CustomEvent("filterchange", {
            detail: { filter: pill.dataset.filter || pill.textContent.trim() },
          });
          group.dispatchEvent(event);
        });
      });
    });
  }

  /* ---------- Chart filter buttons ---------- */
  function initChartFilters() {
    document.querySelectorAll(".chart-filters").forEach(function (group) {
      group.querySelectorAll("button").forEach(function (btn) {
        btn.addEventListener("click", function () {
          group.querySelectorAll("button").forEach(function (b) {
            b.classList.remove("active");
          });
          btn.classList.add("active");
          const event = new CustomEvent("rangefilter", {
            detail: { range: btn.dataset.range || btn.textContent.trim() },
          });
          group.dispatchEvent(event);
        });
      });
    });
  }

  /* ---------- Password toggle ---------- */
  function initPasswordToggles() {
    document.querySelectorAll("[data-password-toggle]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const target = document.querySelector(btn.getAttribute("data-password-toggle"));
        if (!target) return;
        const isPassword = target.type === "password";
        target.type = isPassword ? "text" : "password";
        const icon = btn.querySelector("i");
        if (icon) {
          icon.className = isPassword ? "bi bi-eye-slash" : "bi bi-eye";
        }
      });
    });
  }

  /* ---------- Select all checkboxes ---------- */
  function initSelectAll() {
    document.querySelectorAll("[data-select-all]").forEach(function (master) {
      master.addEventListener("change", function () {
        const target = master.getAttribute("data-select-all");
        document.querySelectorAll(target).forEach(function (cb) {
          cb.checked = master.checked;
        });
      });
    });
  }

  /* ---------- Delete with confirm ---------- */
  function initDeleteButtons() {
    document.querySelectorAll("[data-confirm-delete]").forEach(function (btn) {
      btn.addEventListener("click", async function (e) {
        e.preventDefault();
        const name = btn.getAttribute("data-confirm-delete") || "this item";
        const ok = await confirmAction({
          title: "Delete confirmation",
          message: "Are you sure you want to delete " + name + "? This action cannot be undone.",
          confirmText: "Delete",
          danger: true,
        });
        if (ok) {
          const row = btn.closest("tr");
          if (row) {
            row.style.transition = "opacity 0.25s ease";
            row.style.opacity = "0";
            setTimeout(function () {
              row.remove();
            }, 250);
          }
          showToast("Deleted successfully", "success");
        }
      });
    });
  }

  /* ---------- Compose page helpers ---------- */
  function initCompose() {
    const recipientChecks = document.querySelectorAll(".recipient-check");
    const chipsWrap = document.getElementById("recipientChips");
    const countEl = document.getElementById("selectedCount");
    const selectAllRecipients = document.getElementById("selectAllRecipients");

    function updateRecipients() {
      if (!chipsWrap || !countEl) return;
      chipsWrap.innerHTML = "";
      let count = 0;
      recipientChecks.forEach(function (cb) {
        if (cb.checked) {
          count++;
          const name = cb.dataset.name || "Recipient";
          const chip = document.createElement("span");
          chip.className = "chip";
          chip.innerHTML =
            name +
            ' <button type="button" aria-label="Remove"><i class="bi bi-x"></i></button>';
          chip.querySelector("button").addEventListener("click", function () {
            cb.checked = false;
            updateRecipients();
          });
          chipsWrap.appendChild(chip);
        }
      });
      countEl.innerHTML = "<strong>" + count + "</strong> recipients selected";
      if (selectAllRecipients) {
        selectAllRecipients.checked =
          recipientChecks.length > 0 &&
          Array.from(recipientChecks).every(function (c) {
            return c.checked;
          });
      }
    }

    recipientChecks.forEach(function (cb) {
      cb.addEventListener("change", updateRecipients);
    });

    if (selectAllRecipients) {
      selectAllRecipients.addEventListener("change", function () {
        recipientChecks.forEach(function (cb) {
          cb.checked = selectAllRecipients.checked;
        });
        updateRecipients();
      });
    }

    updateRecipients();

    /* Editor toolbar (visual only) */
    document.querySelectorAll(".editor-toolbar button[data-command]").forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        const cmd = btn.getAttribute("data-command");
        const editor = document.getElementById("emailEditor");
        if (editor && cmd) {
          document.execCommand(cmd, false, null);
          editor.focus();
        }
        btn.classList.toggle("active");
      });
    });

    /* Preview modal sync */
    const previewBtn = document.getElementById("btnPreview");
    if (previewBtn) {
      previewBtn.addEventListener("click", function () {
        const subject = document.getElementById("emailSubject");
        const editor = document.getElementById("emailEditor");
        const previewSubject = document.getElementById("previewSubject");
        const previewBody = document.getElementById("previewBody");
        const previewTo = document.getElementById("previewTo");
        const checked = document.querySelectorAll(".recipient-check:checked").length;

        if (previewSubject && subject) previewSubject.textContent = subject.value || "(No subject)";
        if (previewBody && editor) previewBody.innerHTML = editor.innerHTML || editor.value || "";
        if (previewTo) previewTo.textContent = checked + " recipients";
      });
    }

    /* Send */
    const sendBtn = document.getElementById("btnSendEmail");
    const confirmSendBtn = document.getElementById("btnConfirmSend");

    function validateSend() {
      const checked = document.querySelectorAll(".recipient-check:checked").length;
      if (checked === 0) {
        showToast("Please select at least one recipient", "warning");
        return false;
      }
      const subject = document.getElementById("emailSubject");
      if (subject && !subject.value.trim()) {
        subject.classList.add("is-invalid");
        showToast("Please enter an email subject", "warning");
        return false;
      }
      return true;
    }

    if (sendBtn) {
      sendBtn.addEventListener("click", function (event) {
        // Do not disable this native submit button here. Its name/value carries
        // action=send; disabling it before form serialization turns the request
        // into the handler's default draft action.
        if (!validateSend()) {
          event.preventDefault();
        }
      });
    }

    if (confirmSendBtn) {
      confirmSendBtn.addEventListener("click", function () {
        validateSend();
      });
    }

    const saveDraftBtn = document.getElementById("btnSaveDraft");
    if (saveDraftBtn) {
      saveDraftBtn.addEventListener("click", function () {
        setButtonLoading(saveDraftBtn, true, "Saving...");
        setTimeout(function () {
          setButtonLoading(saveDraftBtn, false);
          showToast("Draft saved successfully", "success");
        }, 900);
      });
    }

    const groupSelect = document.getElementById("recipientGroup");
    if (groupSelect) {
      groupSelect.addEventListener("change", function () {
        recipientChecks.forEach(function (cb) {
          cb.checked = true;
        });
        updateRecipients();
      });
    }
  }

  /* ---------- Add Recipient Modal ---------- */
  function initAddRecipient() {
    const form = document.getElementById("addRecipientForm");
    const submitBtn = document.getElementById("btnAddRecipient");
    if (!form || !submitBtn) return;

    submitBtn.addEventListener("click", function () {
      if (!validateForm(form)) {
        showToast("Please fill in all required fields", "warning");
        return;
      }
      setButtonLoading(submitBtn, true, "Adding...");
      setTimeout(function () {
        setButtonLoading(submitBtn, false);
        const modal = bootstrap.Modal.getInstance(document.getElementById("addRecipientModal"));
        if (modal) modal.hide();
        form.reset();
        form.querySelectorAll(".is-valid, .is-invalid").forEach(function (el) {
          el.classList.remove("is-valid", "is-invalid");
        });
        showToast("Recipient added successfully", "success");
      }, 1000);
    });
  }

  /* ---------- Create Group Modal ---------- */
  function initCreateGroup() {
    const form = document.getElementById("createGroupForm");
    const submitBtn = document.getElementById("btnCreateGroup");
    if (!form || !submitBtn) return;

    submitBtn.addEventListener("click", function () {
      if (!validateForm(form)) {
        showToast("Please enter a group name", "warning");
        return;
      }
      setButtonLoading(submitBtn, true, "Creating...");
      setTimeout(function () {
        setButtonLoading(submitBtn, false);
        const modal = bootstrap.Modal.getInstance(document.getElementById("createGroupModal"));
        if (modal) modal.hide();
        form.reset();
        showToast("Group created successfully", "success");
      }, 1000);
    });
  }

  /* ---------- SMTP Settings ---------- */
  function initSmtp() {
    const saveBtn = document.getElementById("btnSaveSmtp");
    const testBtn = document.getElementById("btnTestSmtp");

    if (saveBtn) {
      saveBtn.addEventListener("click", function () {
        const form = document.getElementById("smtpForm");
        if (form && !validateForm(form)) {
          showToast("Please complete all required SMTP fields", "warning");
          return;
        }
        setButtonLoading(saveBtn, true, "Saving...");
        setTimeout(function () {
          setButtonLoading(saveBtn, false);
          showToast("SMTP configuration saved successfully", "success");
        }, 1100);
      });
    }

    if (testBtn) {
      testBtn.addEventListener("click", function () {
        setButtonLoading(testBtn, true, "Testing...");
        setTimeout(function () {
          setButtonLoading(testBtn, false);
          showToast("SMTP connection successful", "success");
        }, 1600);
      });
    }
  }

  /* ---------- Dashboard Charts ---------- */
  function initDashboardCharts() {
    if (typeof Chart === "undefined") return;

    const activityCanvas = document.getElementById("emailActivityChart");
    const statusCanvas = document.getElementById("emailStatusChart");

    let activityChart = null;

    const activityData = {
      "7 Days": {
        labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
        data: [420, 580, 490, 720, 680, 310, 290],
      },
      Today: {
        labels: ["12am", "4am", "8am", "12pm", "4pm", "8pm"],
        data: [12, 8, 45, 120, 98, 56],
      },
      "30 Days": {
        labels: ["W1", "W2", "W3", "W4"],
        data: [2100, 2450, 1980, 2890],
      },
    };

    if (activityCanvas) {
      const ctx = activityCanvas.getContext("2d");
      const gradient = ctx.createLinearGradient(0, 0, 0, 260);
      gradient.addColorStop(0, "rgba(37, 99, 235, 0.22)");
      gradient.addColorStop(1, "rgba(37, 99, 235, 0.01)");

      activityChart = new Chart(ctx, {
        type: "line",
        data: {
          labels: activityData["7 Days"].labels,
          datasets: [
            {
              label: "Emails Sent",
              data: activityData["7 Days"].data,
              borderColor: "#2563EB",
              backgroundColor: gradient,
              borderWidth: 2.5,
              fill: true,
              tension: 0.4,
              pointBackgroundColor: "#2563EB",
              pointBorderColor: "#fff",
              pointBorderWidth: 2,
              pointRadius: 4,
              pointHoverRadius: 6,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: "#111827",
              titleFont: { family: "Plus Jakarta Sans", size: 12 },
              bodyFont: { family: "Plus Jakarta Sans", size: 12 },
              padding: 10,
              cornerRadius: 8,
            },
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: {
                color: "#94A3B8",
                font: { family: "Plus Jakarta Sans", size: 11 },
              },
              border: { display: false },
            },
            y: {
              grid: { color: "#F1F5F9" },
              ticks: {
                color: "#94A3B8",
                font: { family: "Plus Jakarta Sans", size: 11 },
              },
              border: { display: false },
            },
          },
        },
      });

      const filters = document.getElementById("activityFilters");
      if (filters) {
        filters.addEventListener("rangefilter", function (e) {
          const range = e.detail.range;
          const set = activityData[range] || activityData["7 Days"];
          activityChart.data.labels = set.labels;
          activityChart.data.datasets[0].data = set.data;
          activityChart.update();
        });
      }
    }

    if (statusCanvas) {
      new Chart(statusCanvas.getContext("2d"), {
        type: "doughnut",
        data: {
          labels: ["Sent", "Pending", "Failed"],
          datasets: [
            {
              data: [8420, 125, 32],
              backgroundColor: ["#16A34A", "#F59E0B", "#DC2626"],
              borderWidth: 0,
              hoverOffset: 6,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: "72%",
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: "#111827",
              titleFont: { family: "Plus Jakarta Sans", size: 12 },
              bodyFont: { family: "Plus Jakarta Sans", size: 12 },
              padding: 10,
              cornerRadius: 8,
            },
          },
        },
      });
    }
  }

  /* ---------- Logout ---------- */
  function initLogout() {
    document.querySelectorAll("[data-logout]").forEach(function (el) {
      el.addEventListener("click", async function (e) {
        e.preventDefault();
        const ok = await confirmAction({
          title: "Logout",
          message: "Are you sure you want to logout?",
          confirmText: "Logout",
          danger: false,
        });
        if (ok) {
          window.location.href = el.getAttribute("href") || "logout.php";
        }
      });
    });
  }

  /* ---------- Brief skeleton on load (polish) ---------- */
  function initPageLoadSkeleton() {
    const hasSkeleton = document.querySelector("[data-skeleton]");
    if (!hasSkeleton) return;
    showSkeletons(true);
    setTimeout(function () {
      showSkeletons(false);
    }, 600);
  }

  /* ---------- Init ---------- */
  document.addEventListener("DOMContentLoaded", function () {
    initSidebar();
    initTooltips();
    initGreeting();
    initFilterPills();
    initChartFilters();
    initPasswordToggles();
    initSelectAll();
    initDeleteButtons();
    initCompose();
    initAddRecipient();
    initCreateGroup();
    initSmtp();
    initDashboardCharts();
    initLogout();
    initPageLoadSkeleton();
  });
})();
