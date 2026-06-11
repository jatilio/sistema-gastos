document.addEventListener("DOMContentLoaded", function () {
    const select = document.getElementById("descripcionSelect");
    if (!select) return;

    // Guardar todas las opciones originales
    const opcionesOriginales = Array.from(select.querySelectorAll("option"));

    // Crear input para filtrar
    const input = document.createElement("input");
    input.type = "text";
    input.placeholder = "Escriba para filtrar...";
    input.className = "input-filtro-descripcion";

    // Insertar el input antes del select
    select.parentNode.insertBefore(input, select);

    // Función para ordenar y filtrar
    function actualizarOpciones() {
        const texto = input.value.toLowerCase();

        // Limpiar select
        select.innerHTML = "";

        // Agregar opción inicial
        const opcionInicial = opcionesOriginales.find(op => op.value === "");
        select.appendChild(opcionInicial);

        // Filtrar + ordenar
        opcionesOriginales
            .filter(op => op.value !== "")
            .filter(op => op.text.toLowerCase().includes(texto))
            .sort((a, b) => a.text.localeCompare(b.text))
            .forEach(op => select.appendChild(op));

        // Mantener "Seleccione..." seleccionado
        select.value = "";
    }

    // Ejecutar al escribir
    input.addEventListener("keyup", actualizarOpciones);

    // Ejecutar al cargar la página (ordenar automáticamente)
    actualizarOpciones();
});
