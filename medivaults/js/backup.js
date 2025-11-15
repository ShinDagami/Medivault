document.addEventListener("DOMContentLoaded", () => {
  const createBtn = document.getElementById("create-backup-btn");
  const restoreBtn = document.getElementById("restore-backup-btn");
  const downloadLatestBtn = document.getElementById("download-backup-btn");
  const historyList = document.getElementById("backup-history-list");
  const statusMessage = document.getElementById("status-message");

  const restoreModal = document.getElementById("restoreModal");
  const cancelRestoreBtn = document.getElementById("cancelRestoreBtn");
  const confirmRestoreBtn = document.getElementById("confirmRestoreBtn");
  const backupFileSelect = document.getElementById("backupFileSelect");

  function showStatus(message, type) {
    statusMessage.textContent = message;
    statusMessage.className = `status-box status-${type}`;
    statusMessage.classList.remove("hidden");
    setTimeout(() => statusMessage.classList.add("hidden"), 5000);
  }

  function toggleActions(disabled) {
    createBtn.disabled = disabled;
    restoreBtn.disabled = disabled;
    downloadLatestBtn.disabled = disabled;

    document.querySelectorAll(".download-link").forEach((link) => {
      link.style.pointerEvents = disabled ? "none" : "auto";
      link.style.opacity = disabled ? 0.5 : 1;
    });

    createBtn.innerHTML = disabled
      ? '<i class="fas fa-spinner fa-spin"></i> Creating Backup...'
      : '<i class="fas fa-plus-circle"></i> Create Backup Now';
  }

  async function callBackupApi(action, data = {}) {
    const url = `php/backup_api.php?action=${action}`;
    const options = {
      method: data ? "POST" : "GET",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: data ? new URLSearchParams(data).toString() : undefined,
    };
    const response = await fetch(url, options);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    return response.json();
  }

  async function loadBackupHistory() {
    try {
      const result = await callBackupApi("list");
      historyList.innerHTML = "";
      if (result.success && result.backups.length > 0) {
        const backupsToShow = result.backups.slice(0, 5);
        backupsToShow.forEach((backup) => {
          const sizeGB = (backup.size_mb / 1024).toFixed(1) + " GB";
          const dateObj = new Date(backup.created_at);
          const date = dateObj.toLocaleDateString();
          const time = dateObj.toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
          });
          const typeText =
            backup.type.charAt(0).toUpperCase() + backup.type.slice(1);
          const statusClass =
            backup.status === "success"
              ? "success"
              : backup.status === "warning"
              ? "warning"
              : "failed";
          const statusText =
            backup.status === "warning"
              ? "Warning"
              : backup.status.charAt(0).toUpperCase() + backup.status.slice(1);
          const html = `
                        <div class="history-item" data-filename="${backup.filename}">
                            <div class="status-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="history-info">
                                <div class="backup-type-title">${typeText} Backup - ${date}</div>
                                <div class="muted">${date} at ${time}</div>
                            </div>
                            <div class="backup-size">${sizeGB}</div>
                            <div class="backup-status-tag status-tag ${statusClass}">${statusText}</div>
                            <a href="#" class="download-link" data-backup-action="download" title="Download"><i class="fas fa-cloud-download-alt"></i></a>
                        </div>`;
          historyList.insertAdjacentHTML("beforeend", html);
        });
        restoreBtn.disabled = false;
      } else {
        historyList.innerHTML =
          '<div class="empty-state-cell">No backups available.</div>';
        restoreBtn.disabled = true;
      }
    } catch (error) {
      console.error("Load backup history error:", error);
      showStatus("Failed to load backup history.", "error");
    }
  }

  createBtn.addEventListener("click", async () => {
    if (!confirm("Are you sure you want to run a manual database backup now?"))
      return;
    toggleActions(true);

    try {
      const result = await callBackupApi("create");
      if (result.success && result.backup) {
        showStatus(result.message, "success");
        loadBackupHistory();
      } else {
        showStatus(`Error: ${result.message}`, "error");
      }
    } catch (error) {
      console.error("Backup API Error:", error);
      showStatus("A network or server error occurred during backup.", "error");
    } finally {
      toggleActions(false);
    }
  });

  downloadLatestBtn.addEventListener("click", () => {
    window.open(`php/backup_api.php?action=download&filename=latest`, "_blank");
  });

  historyList.addEventListener("click", (e) => {
    const target = e.target.closest(".download-link");
    if (!target) return;
    e.preventDefault();
    const historyItem = target.closest(".history-item");
    const filename = historyItem.dataset.filename;
    if (filename)
      window.open(
        `php/backup_api.php?action=download&filename=${filename}`,
        "_blank"
      );
    else showStatus("File path missing for this backup record.", "error");
  });

  restoreBtn.addEventListener("click", () => {
    backupFileSelect.innerHTML = "";
    document.querySelectorAll(".history-item").forEach((item) => {
      const dateText = item.querySelector(".history-info .muted").textContent;
      const sizeText = item.querySelector(".backup-size").textContent;
      const option = document.createElement("option");
      option.value = item.dataset.filename;
      option.textContent = `${dateText} (${sizeText})`;
      backupFileSelect.appendChild(option);
    });
    if (!backupFileSelect.options.length) {
      showStatus(
        "No successful backup files available for restoration.",
        "info"
      );
      return;
    }
    restoreModal.classList.remove("hidden");
  });

  cancelRestoreBtn.addEventListener("click", () =>
    restoreModal.classList.add("hidden")
  );

  confirmRestoreBtn.addEventListener("click", async () => {
    const selectedFile = backupFileSelect.value;
    if (!selectedFile)
      return showStatus("Please select a file to restore.", "info");
    restoreModal.classList.add("hidden");
    toggleActions(true);
    confirmRestoreBtn.disabled = true;
    showStatus(
      `Attempting to restore database from ${selectedFile}... This may take a moment.`,
      "info"
    );

    try {
      const result = await callBackupApi("restore", { filename: selectedFile });
      showStatus(
        result.success
          ? result.message
          : `Restoration Failed: ${result.message}`,
        result.success ? "success" : "error"
      );
      loadBackupHistory();
    } catch (error) {
      console.error("Restore API Error:", error);
      showStatus(
        "A network or server error occurred during restoration.",
        "error"
      );
    } finally {
      toggleActions(false);
      confirmRestoreBtn.disabled = false;
    }
  });

  restoreModal.addEventListener("click", (e) => {
    if (e.target === restoreModal) restoreModal.classList.add("hidden");
  });

  loadBackupHistory();
});
