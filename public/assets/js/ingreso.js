document.addEventListener("DOMContentLoaded", function() {

    document.querySelectorAll(".btn-estado").forEach(btn => {

        btn.addEventListener("click", function() {

            let index = this.dataset.index;
            let estadoActual = parseInt(this.dataset.estado);
            let nuevoEstado = estadoActual === 1 ? 0 : 1;

            this.textContent = nuevoEstado === 1 ? "Guardado" : "Pendiente";

            let fila = this.closest("tr");

            if (nuevoEstado === 1) {
                this.classList.add("guardado");
                this.classList.remove("pendiente");
                fila.classList.add("guardado-row");
            } else {
                this.classList.add("pendiente");
                this.classList.remove("guardado");
                fila.classList.remove("guardado-row");
            }

            document.getElementById("estado-" + index).value = nuevoEstado;
            this.dataset.estado = nuevoEstado;

            actualizarContador();
        });

    });

    document.getElementById("marcar-todos").addEventListener("click", function() {
        document.querySelectorAll(".btn-estado").forEach(btn => {
            btn.dataset.estado = 1;
            btn.textContent = "Guardado";
            btn.classList.remove("pendiente");
            btn.classList.add("guardado");

            let index = btn.dataset.index;
            document.getElementById("estado-" + index).value = 1;

            let fila = btn.closest("tr");
            fila.classList.add("guardado-row");
        });

        actualizarContador();
    });

    function actualizarContador() {
        let pendientes = 0;
        let guardados = 0;

        document.querySelectorAll(".btn-estado").forEach(btn => {
            if (parseInt(btn.dataset.estado) === 1) guardados++;
            else pendientes++;
        });

        document.getElementById("pendientes").textContent = pendientes;
        document.getElementById("guardados").textContent = guardados;
    }

    actualizarContador();

});