document.addEventListener("DOMContentLoaded", () => {
  const addModal = document.getElementById("add-new-item-modal");
  const restockModal = document.getElementById("restock-item-modal");
  const editModal = document.getElementById("edit-item-modal");

  const openAddBtn = document.getElementById("add-new-item-btn");
  const openRestockBtn = document.getElementById("restock-item-btn");
  const closeBtns = document.querySelectorAll(".close-btn, .cancel-modal-btn");
  const tableBody = document.querySelector("#inventory-table tbody");

  const lowStockCountEl = document.getElementById("low-stock-count");
  const criticalStockCountEl = document.getElementById("critical-stock-count");
  const expiringSoonCountEl = document.getElementById("expiring-soon-count");

  openAddBtn?.addEventListener("click", () =>
    addModal.classList.add("is-active")
  );
  openRestockBtn?.addEventListener("click", () =>
    restockModal.classList.add("is-active")
  );

  closeBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      const modal = btn.closest(".modal");
      if (modal) modal.classList.remove("is-active");
    });
  });

  /**
   
   * @param {object} counts
   */
  function updateSummaryCards(counts) {
    if (lowStockCountEl) {
      lowStockCountEl.textContent = counts.low_stock_count ?? 0;
    }
    if (criticalStockCountEl) {
      criticalStockCountEl.textContent = counts.critical_stock_count ?? 0;
    }
    if (expiringSoonCountEl) {
      expiringSoonCountEl.textContent = counts.expiring_soon_count ?? 0;
    }
  }

  async function refreshTable() {
    try {
      const res = await fetch("php/inventory_api.php?action=get_all_data");
      const data = await res.json();

      tableBody.innerHTML = "";

      if (data.success && data.table_html) {
        tableBody.innerHTML = data.table_html;

        if (data.counts) {
          updateSummaryCards(data.counts);
        }
      } else {
        console.error(
          "API failed to return inventory table data:",
          data.message
        );

        tableBody.innerHTML =
          '<tr><td colspan="9" class="empty-state-cell">Failed to load inventory. Check console for details.</td></tr>';
      }
    } catch (err) {
      console.error("Error fetching or processing inventory data:", err);

      tableBody.innerHTML =
        '<tr><td colspan="9" class="empty-state-cell">Connection error. Cannot load inventory data.</td></tr>';
    }
  }

  const addForm = document.getElementById("add-new-item-form");
  addForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(addForm);

    try {
      const res = await fetch("php/add_inventory.php", {
        method: "POST",
        body: formData,
      });
      const data = await res.json();

      alert(data.message);

      if (data.status === "success") {
        addModal.classList.remove("is-active");
        addForm.reset();
        await refreshTable();
      }
    } catch (err) {
      console.error(err);
    }
  });

  document.addEventListener("click", async (e) => {
    const editBtn = e.target.closest(".btn-edit");
    if (editBtn) {
      const itemId = editBtn.dataset.id;
      const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
      if (!row) return;

      document.getElementById("edit_item_name").value =
        row.children[1].textContent;
      document.getElementById("edit_reorder_level").value =
        row.children[4].textContent;

      document.getElementById("edit_item_code").value = itemId;

      if (editModal) editModal.classList.add("is-active");
      return;
    }

    const decBtn = e.target.closest(".btn-dec");
    if (decBtn) {
      const code = decBtn.dataset.id;
      const step = parseInt(decBtn.dataset.step);

      const formData = new FormData();
      formData.append("item_code", code);
      formData.append("quantity_change", step);

      formData.append("action", "adjust_stock");

      try {
        const res = await fetch("php/inventory_api.php", {
          method: "POST",
          body: formData,
        });
        const data = await res.json();

        alert(data.message);

        if (data.success === true) await refreshTable();
      } catch (err) {
        console.error(err);
      }
      return;
    }
  });

  const restockForm = document.getElementById("restock-item-form");
  restockForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(restockForm);

    try {
      const res = await fetch("php/restock_inventory.php", {
        method: "POST",
        body: formData,
      });
      const data = await res.json();

      alert(data.message);

      if (data.status === "success") {
        restockModal.classList.remove("is-active");
        restockForm.reset();
        await refreshTable();
      }
    } catch (err) {
      console.error(err);
    }
  });

  const editForm = document.getElementById("edit-item-form");
  editForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(editForm);

    formData.append("action", "update_item");

    try {
      const res = await fetch("php/inventory_api.php", {
        method: "POST",
        body: formData,
      });
      const data = await res.json();

      alert(data.message);

      if (data.success === true) {
        editModal.classList.remove("is-active");
        await refreshTable();
      }
    } catch (err) {
      console.error("Error submitting edit form:", err);
    }
  });

  const searchInput = document.getElementById("search-inventory-input");
  searchInput?.addEventListener("input", () => {
    const filter = searchInput.value.toLowerCase();
    tableBody.querySelectorAll("tr").forEach((row) => {
      row.style.display = row.textContent.toLowerCase().includes(filter)
        ? ""
        : "none";
    });
  });

  const categoryToggle = document.getElementById("category-filter-toggle");
  const categoryMenu = document.getElementById("category-dropdown-menu");
  const categoryText = document.getElementById("category-filter-text");

  categoryToggle?.addEventListener("click", () =>
    categoryMenu.classList.toggle("active")
  );

  categoryMenu?.querySelectorAll(".dropdown-item").forEach((item) => {
    item.addEventListener("click", () => {
      categoryMenu
        .querySelectorAll(".dropdown-item")
        .forEach((i) => i.classList.remove("active"));
      item.classList.add("active");
      categoryText.textContent = item.textContent;

      const category = item.textContent.toLowerCase();

      tableBody.querySelectorAll("tr").forEach((row) => {
        if (category === "all categories") {
          row.style.display = "";
        } else {
          const catCell = row.children[2].textContent.toLowerCase();

          row.style.display = catCell.includes(category.replace("medical ", ""))
            ? ""
            : "none";
        }
      });
    });
  });

  refreshTable();
});
