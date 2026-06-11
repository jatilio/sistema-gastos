document.addEventListener("DOMContentLoaded", () => {

    const modal = document.getElementById('modalGasto');
    const abrirModal = document.getElementById('abrirModal');
    const cerrarModal = document.getElementById('cerrarModal');

    if (abrirModal) {
        abrirModal.addEventListener('click', () => {
            modal.style.display = 'flex';
        });
    }

    if (cerrarModal) {
        cerrarModal.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    }

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

});
