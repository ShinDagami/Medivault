document.addEventListener("DOMContentLoaded", function () {
  const dropdownBtn = document.getElementById("period-dropdown-btn");
  const dropdownMenu = document.getElementById("period-dropdown-menu");

  if (dropdownBtn && dropdownMenu) {
    dropdownBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      dropdownMenu.style.display =
        dropdownMenu.style.display === "block" ? "none" : "block";
      dropdownBtn.classList.toggle("active");
    });

    document.addEventListener("click", function (event) {
      if (
        !dropdownBtn.contains(event.target) &&
        !dropdownMenu.contains(event.target)
      ) {
        dropdownMenu.style.display = "none";
        dropdownBtn.classList.remove("active");
      }
    });

    dropdownMenu.addEventListener("click", function (event) {
      if (event.target.classList.contains("menu-item")) {
        dropdownMenu.style.display = "none";
        dropdownBtn.classList.remove("active");
      }
    });
  }

  const userAvatar = document.getElementById("userAvatar");
  const userDropdownMenu = document.getElementById("userDropdownMenu");

  if (userAvatar && userDropdownMenu) {
    userAvatar.addEventListener("click", function (e) {
      e.stopPropagation();
      userDropdownMenu.style.display =
        userDropdownMenu.style.display === "block" ? "none" : "block";
    });

    document.addEventListener("click", function (event) {
      if (
        !userAvatar.contains(event.target) &&
        !userDropdownMenu.contains(event.target)
      ) {
        userDropdownMenu.style.display = "none";
      }
    });
  }
});
