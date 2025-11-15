document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("addStaffModal");
  const openBtn = document.getElementById("addStaffBtn");
  const closeBtn = document.getElementById("closeModalBtn");
  const cancelBtn = document.getElementById("cancelModalBtn");
  const form = document.getElementById("addStaffForm");
  const submitBtn = form.querySelector("button[type='submit']");

  const role = document.getElementById("role");
  const department = document.getElementById("department");
  const departmentGroup = department.closest(".form-group");

  const tableBody = document.getElementById("staffTableBody");
  const search = document.querySelector(".search-input");

  function toggleDepartment() {
    if (role.value === "Admin") {
      departmentGroup.style.display = "none";
      department.removeAttribute("required");
      department.value = "";
    } else {
      departmentGroup.style.display = "block";
      department.setAttribute("required", "required");
    }
  }

  function showModal() {
    form.reset();
    toggleDepartment();
    modal.style.display = "flex";
  }

  function hideModal() {
    modal.style.display = "none";
  }

  if (openBtn) openBtn.addEventListener("click", showModal);
  if (closeBtn) closeBtn.addEventListener("click", hideModal);
  if (cancelBtn) cancelBtn.addEventListener("click", hideModal);
  role.addEventListener("change", toggleDepartment);

  modal.addEventListener("click", (e) => {
    if (e.target === modal) hideModal();
  });

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    if (submitBtn) submitBtn.disabled = true;

    const data = new FormData(form);

    fetch("php/add_staff.php", { method: "POST", body: data })
      .then(async (r) => {
        let json;
        try {
          json = await r.json();
        } catch (err) {
          console.error("Invalid JSON response:", await r.text());
          throw new Error("Invalid response from server");
        }
        return json;
      })
      .then((r) => {
        console.log("Add staff response:", r);
        alert(
          r.message ||
            (r.success ? "Staff added successfully" : "Failed to add staff")
        );
        if (r.success) {
          hideModal();
          form.reset();
          toggleDepartment();
          loadStaff();
        }
      })
      .catch((err) => {
        console.error("Add staff request failed", err);
        alert("Request failed: " + err.message);
      })
      .finally(() => {
        if (submitBtn) submitBtn.disabled = false;
      });
  });

  function loadStaff() {
    fetch("php/fetch_staff.php")
      .then((r) => r.json())
      .then((list) => {
        tableBody.innerHTML = "";
        if (!Array.isArray(list) || !list.length) {
          tableBody.innerHTML = `<tr><td colspan="7" class="empty-state-cell">No staff members found.</td></tr>`;
          return;
        }
        list.forEach((s) => {
          const tr = document.createElement("tr");
          tr.innerHTML = `
            <td>${s.staff_id}</td>
            <td>${s.name}</td>
            <td>${s.role}</td>
            <td>${s.department || ""}</td>
            <td>${s.email}</td>
            <td>${s.status}</td>
            <td>
              <button class="btn-view" data-id="${s.staff_id}">View</button>
              <button class="btn-edit" data-id="${s.staff_id}">Edit</button>
              <button class="btn-delete" data-id="${
                s.staff_id
              }"><i class="fa fa-trash"></i></button>
            </td>`;
          tableBody.appendChild(tr);
        });
      })
      .catch((err) => {
        console.error("Load staff failed", err);
        tableBody.innerHTML = `<tr><td colspan="7" class="empty-state-cell">Failed to load staff.</td></tr>`;
      });
  }

  tableBody.addEventListener("click", (e) => {
    const btn = e.target.closest("button");
    if (!btn) return;

    const id = btn.dataset.id;
    if (!id) return;

    if (btn.classList.contains("btn-delete")) {
      if (!confirm("Delete staff?")) return;
      const f = new FormData();
      f.append("delete_id", id);

      fetch("php/delete_staff.php", { method: "POST", body: f })
        .then((r) => r.json())
        .then((r) => {
          alert(r.message || (r.success ? "Deleted" : "Delete failed"));
          if (r.success) loadStaff();
        })
        .catch((err) => {
          console.error("Delete request failed", err);
          alert("Delete request failed");
        });
      return;
    }

    if (btn.classList.contains("btn-view")) {
      openViewModal(id);
      return;
    }

    if (btn.classList.contains("btn-edit")) {
      openEditModal(id);
      return;
    }
  });

  function openViewModal(id) {
    fetch("php/get_staff.php?id=" + encodeURIComponent(id))
      .then((r) => r.json())
      .then((s) => {
        document.getElementById("view-name").textContent = s.name || "";
        document.getElementById("view-role").textContent = s.role || "";
        document.getElementById("view-dept").textContent =
          s.department || "None";
        document.getElementById("view-email").textContent = s.email || "";
        document.getElementById("view-status").textContent = s.status || "";
        document.getElementById("viewStaffModal").style.display = "flex";
      })
      .catch((err) => {
        console.error("Failed to load staff details", err);
        alert("Failed to load staff details");
      });
  }

  function openEditModal(id) {
    fetch("php/get_staff.php?id=" + encodeURIComponent(id))
      .then((r) => r.json())
      .then((s) => {
        document.getElementById("edit-id").value = s.staff_id || "";
        document.getElementById("edit-name").value = s.name || "";
        document.getElementById("edit-role").value = s.role || "";
        document.getElementById("edit-department").value = s.department || "";
        document.getElementById("edit-email").value = s.email || "";
        document.getElementById("edit-status").value = s.status || "Active";

        const editDeptGroup = document
          .getElementById("edit-department")
          .closest(".form-group");
        editDeptGroup.style.display = s.role === "Admin" ? "none" : "block";

        document.getElementById("editStaffModal").style.display = "flex";
      })
      .catch((err) => {
        console.error("Failed to load edit form", err);
        alert("Failed to load edit form");
      });
  }

  const editForm = document.getElementById("editStaffForm");
  if (editForm) {
    editForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const data = new FormData(editForm);
      fetch("php/update_staff.php", { method: "POST", body: data })
        .then((r) => r.json())
        .then((r) => {
          alert(r.message);
          document.getElementById("editStaffModal").style.display = "none";
          loadStaff();
        })
        .catch((err) => {
          console.error("Update failed", err);
          alert("Update failed");
        });
    });

    const editRole = document.getElementById("edit-role");
    editRole.addEventListener("change", () => {
      const editDeptGroup = document
        .getElementById("edit-department")
        .closest(".form-group");
      editDeptGroup.style.display =
        editRole.value === "Admin" ? "none" : "block";
    });
  }

  if (search) {
    search.addEventListener("input", () => {
      const v = search.value.toLowerCase();
      document.querySelectorAll("#staffTableBody tr").forEach((r) => {
        r.style.display = r.textContent.toLowerCase().includes(v) ? "" : "none";
      });
    });
  }

  toggleDepartment();
  loadStaff();
});
