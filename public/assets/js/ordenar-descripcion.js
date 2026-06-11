document.addEventListener("DOMContentLoaded", function () {
    const select = document.querySelector('select[name="descripcion[]"]');
    if (!select) return;

    // Guardar la opción inicial
    const opcionInicial = select.querySelector("option[value='']");

    // Obtener todas las demás opciones
    const opciones = Array.from(select.querySelectorAll("option")).slice(1);

    // Ordenarlas alfabéticamente
    opciones.sort((a, b) => a.text.localeCompare(b.text));

    // Limpiar el select
    select.innerHTML = "";

    // Insertar la opción inicial
    select.appendChild(opcionInicial);

    // Insertar las opciones ordenadas
    opciones.forEach(op => select.appendChild(op));

    // Asegurar que "Seleccione..." quede seleccionado por defecto
    select.value = "";
});
