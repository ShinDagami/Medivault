document.addEventListener("DOMContentLoaded", () => {
  const userMenuContainer = document.getElementById("userMenuContainer");
  const userAvatarWrapper = document.getElementById("userAvatar");
  const userDropdownMenu = document.getElementById("userDropdownMenu");

  if (userMenuContainer && userDropdownMenu) {
    userMenuContainer.addEventListener("click", (event) => {
      userDropdownMenu.classList.toggle("active");

      const isExpanded = userDropdownMenu.classList.contains("active");
      event.currentTarget.setAttribute("aria-expanded", isExpanded);

      event.stopPropagation();
    });

    document.addEventListener("click", (event) => {
      if (
        userDropdownMenu.classList.contains("active") &&
        !userMenuContainer.contains(event.target)
      ) {
        userDropdownMenu.classList.remove("active");
        userMenuContainer.setAttribute("aria-expanded", "false");
      }
    });
  }

  const genderDropdown = document.querySelector(".gender-dropdown");
  if (genderDropdown) {
    genderDropdown.addEventListener("click", () => {
      genderDropdown.classList.toggle("active");
    });

    document
      .querySelectorAll(".dropdown-menu1 .dropdown-item")
      .forEach((item) => {
        item.addEventListener("click", (e) => {
          const selectedText = e.target.textContent;
          document.querySelector(".dropdown-toggle span").textContent =
            selectedText;

          document
            .querySelectorAll(".dropdown-menu1 .dropdown-item")
            .forEach((i) => i.classList.remove("active"));
          e.target.classList.add("active");
        });
      });

    document.addEventListener("click", (e) => {
      if (
        genderDropdown.classList.contains("active") &&
        !genderDropdown.contains(e.target)
      ) {
        genderDropdown.classList.remove("active");
      }
    });
  }
});

/**

 * @param {string} message 
 * @param {string} type
 * @param {number} duration
 */
window.showToast = function (message, type = "info", duration = 3000) {
  let toastContainer = document.getElementById("toast-container");
  if (!toastContainer) {
    toastContainer = document.createElement("div");
    toastContainer.id = "toast-container";
    document.body.appendChild(toastContainer);
  }

  const toast = document.createElement("div");
  toast.classList.add("toast", type);

  let icon = "";
  if (type === "success") {
    icon = '<i class="fas fa-check-circle"></i>';
  } else if (type === "error") {
    icon = '<i class="fas fa-times-circle"></i>';
  } else if (type === "warning") {
    icon = '<i class="fas fa-exclamation-triangle"></i>';
  } else {
    icon = '<i class="fas fa-info-circle"></i>';
  }

  toast.innerHTML = `${icon} <span class="toast-message">${message}</span>`;

  toastContainer.appendChild(toast);

  setTimeout(() => {
    toast.classList.add("show");
  }, 10);

  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => {
      toast.remove();
    }, 500);
  }, duration);
};
