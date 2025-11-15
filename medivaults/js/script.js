document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("loginForm");
  const submitBtn = document.getElementById("submitBtn");
  const errorEl = document.getElementById("error");
  const tabs = document.querySelectorAll(".tab");
  const tabSwitch = document.querySelector(".tab-switch");
  const formTitle = document.getElementById("formTitle");
  let role = "staff";

  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      tabs.forEach((x) => x.classList.remove("active"));
      tab.classList.add("active");
      role = tab.dataset.role.toLowerCase();
      formTitle.textContent = role === "admin" ? "Admin Login" : "Staff Login";
      submitBtn.textContent =
        role === "admin" ? "Login as Admin" : "Login as Staff";
      errorEl.textContent = "";
    });
  });

  loginForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();

    if (!username || !password) {
      errorEl.textContent = "Enter both fields.";
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = "Signing in...";
    errorEl.textContent = "";

    try {
      const res = await fetch("php/api/auth.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, password, role }),
      });

      const data = await res.json();
      if (data.ok) {
        window.location.href = "dashboard.php";
      } else {
        errorEl.textContent = data.error || "Invalid credentials";
      }
    } catch {
      errorEl.textContent = "Network error";
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent =
        role === "admin" ? "Login as Admin" : "Login as Staff";
    }
  });
});
