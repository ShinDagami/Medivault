const userAvatarWrapper = document.getElementById('userAvatarWrapper');
const userDropdownMenu = document.getElementById('userDropdownMenu');

userAvatarWrapper.addEventListener('click', () => {
    userDropdownMenu.classList.toggle('active');
});

document.addEventListener('click', (e) => {
    if (!userAvatarWrapper.contains(e.target) && !userDropdownMenu.contains(e.target)) {
        userDropdownMenu.classList.remove('active');
    }
});

