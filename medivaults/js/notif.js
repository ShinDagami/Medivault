document.addEventListener("DOMContentLoaded", () => {
  const composeModal = document.getElementById("compose-notification-modal");
  const composeBtn = document.getElementById("compose-notification-btn");
  const closeBtns = document.querySelectorAll(
    ".modal .close-btn, .modal .cancel-modal-btn"
  );
  const composeForm = document.getElementById("compose-notification-form");
  const recipientType = document.getElementById("recipient-type");
  const recipientInput = document.getElementById("recipient");
  const subjectGroup = document
    .getElementById("subject")
    .closest(".form-group");
  const notificationType = document.getElementById("notification-type");
  const formError = document.getElementById("compose-form-error");
  const notificationListContainer = document.getElementById(
    "notifications-list-container"
  );
  const statToday = document.getElementById("stat-today");
  const statWeek = document.getElementById("stat-week");
  const statFailed = document.getElementById("stat-failed");

  composeBtn?.addEventListener("click", () => {
    composeModal.classList.add("is-active");
    formError.style.display = "none";
  });
  closeBtns.forEach((btn) =>
    btn.addEventListener("click", () =>
      btn.closest(".modal")?.classList.remove("is-active")
    )
  );
  window.addEventListener("click", (e) => {
    if (e.target === composeModal) composeModal.classList.remove("is-active");
  });

  notificationType?.addEventListener("change", () => {
    subjectGroup.style.display =
      notificationType.value === "email" ? "block" : "none";
  });
  notificationType?.dispatchEvent(new Event("change"));
  recipientType?.addEventListener("change", () => {
    const type = recipientType.value;
    recipientInput.placeholder =
      type === "group"
        ? "e.g., All Staff, All Doctors"
        : type === "custom"
        ? "Enter phone or email"
        : type === "patient_family"
        ? "Enter patient family contact"
        : "Enter name, phone, or email";
  });

  composeForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    formError.style.display = "none";
    const formData = new FormData(composeForm);
    try {
      const res = await fetch("php/notifications_api.php?action=send", {
        method: "POST",
        body: formData,
      });
      const data = await res.json();
      if (data.success) {
        composeForm.reset();
        notificationType.dispatchEvent(new Event("change"));
        composeModal.classList.remove("is-active");
        await loadNotificationHistory();
        await loadStatistics();
      } else {
        formError.textContent = data.message || "Error sending notification";
        formError.style.display = "block";
      }
    } catch (err) {
      console.error(err);
      formError.textContent = "Connection error";
      formError.style.display = "block";
    }
  });

  async function loadNotificationHistory() {
    try {
      const res = await fetch("php/notifications_api.php?action=get_history");
      const data = await res.json();
      if (data.success && Array.isArray(data.notifications)) {
        notificationListContainer.innerHTML = data.notifications.length
          ? ""
          : `<div class="empty-state-cell">No recent notifications found.</div>`;
        data.notifications.forEach((n) =>
          notificationListContainer.appendChild(createNotificationElement(n))
        );
      } else
        notificationListContainer.innerHTML = `<div class="empty-state-cell error">Failed to load notification history.</div>`;
    } catch (err) {
      console.error(err);
      notificationListContainer.innerHTML = `<div class="empty-state-cell error">Failed to load notification history.</div>`;
    }
  }

  function createNotificationElement(item) {
    const el = document.createElement("div");
    el.className = "notification-item";
    const typeClass = item.type === "sms" ? "sms" : "email";
    const typeIcon = item.type === "sms" ? "fa-comment-alt" : "fa-envelope";
    const statusClass = item.status === "failed" ? "failed" : "sent";
    const statusText = item.status === "failed" ? "Failed" : "Sent";
    const recipients = item.recipient
      .split(",")
      .map((r) => r.trim())
      .join(", ");
    el.innerHTML = `
            <div class="notification-icon ${typeClass}"><i class="fas ${typeIcon}"></i></div>
            <div class="notification-content">
                <div class="notification-header">
                    <span class="notification-title">${item.title}</span>
                    <div>
                        <span class="notification-tag ${statusClass}">${statusText}</span>
                        <span class="notification-time" data-timestamp="${
                          item.sent_at
                        }">${getTimeAgo(new Date(item.sent_at))}</span>
                    </div>
                </div>
                <div class="notification-body">${item.body}</div>
                <div class="notification-recipient"><i class="fas fa-user"></i> <span>To: ${recipients} (${
      item.recipient_type
    })</span></div>
            </div>
        `;
    return el;
  }

  function getTimeAgo(date) {
    const now = new Date();
    let diff = Math.floor((now - date) / 1000);
    if (diff < 60) return diff + " seconds ago";
    diff = Math.floor(diff / 60);
    if (diff < 60) return diff + " minutes ago";
    diff = Math.floor(diff / 60);
    if (diff < 24) return diff + " hours ago";
    diff = Math.floor(diff / 24);
    if (diff < 7) return diff + " days ago";
    return date.toLocaleDateString();
  }
  setInterval(
    () =>
      document
        .querySelectorAll(".notification-time")
        .forEach(
          (el) => (el.textContent = getTimeAgo(new Date(el.dataset.timestamp)))
        ),
    30000
  );

  async function loadStatistics() {
    try {
      const res = await fetch("php/notifications_api.php?action=get_history");
      const data = await res.json();
      if (data.success && Array.isArray(data.notifications)) {
        let todayCount = 0,
          weekCount = 0,
          failCount = 0;
        const today = new Date();
        const weekStart = new Date(today);
        weekStart.setDate(today.getDate() - today.getDay() + 1);
        data.notifications.forEach((n) => {
          const d = new Date(n.sent_at);
          if (d.toDateString() === today.toDateString()) todayCount++;
          if (d >= weekStart) weekCount++;
          if (n.status === "failed") failCount++;
        });
        statToday.textContent = todayCount;
        statWeek.textContent = weekCount;
        statFailed.textContent = failCount;
      }
    } catch (err) {
      console.error(err);
    }
  }
  setInterval(loadStatistics, 15000);

  document
    .querySelectorAll(".settings-panel .switch input")
    .forEach((toggle) => {
      toggle.addEventListener("change", async () => {
        const formData = new FormData();
        formData.append("setting_id", toggle.id);
        formData.append("is_enabled", toggle.checked ? "1" : "0");
        try {
          const res = await fetch(
            "php/notifications_api.php?action=update_setting",
            { method: "POST", body: formData }
          );
          const data = await res.json();
          if (!data.success)
            console.error("Failed to update setting", data.message);
        } catch (err) {
          console.error(err);
        }
      });
    });

  loadNotificationHistory();
  loadStatistics();

  setInterval(loadNotificationHistory, 15000);
});
