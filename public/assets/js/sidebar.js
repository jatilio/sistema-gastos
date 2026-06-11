document.addEventListener("DOMContentLoaded", () => {
    const toggles = document.querySelectorAll('.submenu-toggle');

    toggles.forEach(item => {
        item.addEventListener('click', () => {
            item.classList.toggle('active');
        });
    });
});
