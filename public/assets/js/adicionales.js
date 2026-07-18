/* ============================
   FILAS DINÁMICAS
============================ */

function agregarFila() {
    const contenedor = document.getElementById("contenedor-filas");
    const fila = document.querySelector(".fila-adicional").cloneNode(true);

    // Limpiar valores
    fila.querySelectorAll("input, select").forEach(i => i.value = "");

    // Reasignar evento
    fila.querySelector('select[name="descripcion[]"]').onchange = function () {
        asignarCategoria(this);
    };

    contenedor.appendChild(fila);
}

function eliminarFila(btn) {
    const filas = document.querySelectorAll(".fila-adicional");
    if (filas.length > 1) btn.parentElement.remove();
}

/* ============================
   ASIGNACIÓN AUTOMÁTICA
============================ */

const categoriasAuto = {
    "7-eleven": "Gasolina",
    "aldi": "Supermercado",
    "amc snacks": "Entretenimiento",
    "atm retiro": "Efectivo",
    "bj's": "Membresías",
    "buc-ees": "Gasolina",
    "classy nails": "Cuidado personal",
    "dominos pizza": "Restaurantes",
    "dunkin": "Cafetería",
    "griffins cleaners": "Lavandería",
    "lightning": "Gasolina",
    "murphy express": "Gasolina",
    "plaza mexico": "Restaurantes",
    "sams club": "Supermercado",
    "scooters coffee": "Cafetería",
    "southern nights": "Entretenimiento",
    "standby skipper": "Entretenimiento",
    "wallmart": "Supermercado",
    "woody's luch": "Restaurantes",
    "florida state fair": "Restaurantes",
    "golden corral": "Restaurantes",
    "five guy": "Restaurantes",
    "universal-hotel": "Entretenimiento",
    "wawa": "Restaurantes",
    "universal-food": "Restaurantes",
    "endless sumer resort-food": "Restaurantes",
    "memories of peru": "Restaurantes",
    "chili's grill & bar": "Restaurantes",
    "classy nails and day spa": "Cuidado personal",
    "cold stone": "Restaurantes",
    "mardi gras-kiosk": "Restaurantes",
    "h-mart": "Supermercado",
    "oasics": "Cuidado Personal",
    "myung ga": "Restaurantes",
    "dutch bros": "Restaurantes",
    "wing house": "Restaurantes",
    "paranormal cirque": "Restaurantes",
    "office 365": "Membresías",
    "bbva-dolares": "Transferencia",
    "deliciosusness": "Restaurantes",
    "harry's": "Restaurantes",
    "chick-fil-a": "Restaurantes",
    "little caesars": "Restaurantes",
    "stella universal": "Entretenimiento",
    "dockside suits": "Entretenimiento",
    "lowes hotels": "Entretenimiento",
    "Amazon-Shopping": "Cuidado personal",
    "Dollar tree": "Supermercado",
    "Publix" : "Supermercado",
    "Amtrak": "Entretenimiento",
    "Love's": "Gasolina",
    "Tu Plaza Kitchen": "Restaurantes",
    "Save a lot":"Supermercado",
    "The Pour House on Main": "Entretenimiento",
    "El Paso Tienda Mexican Food": "Restaurantes",
    "Rural King": "Supermercado",
    "PeruFresh": "Restaurantes",
    "Honey Guy": "Supermercado",
    "3 Angels Lawn": "Cuidado personal",
    "Circle k": "Gasolina",
    "Apple Care": "Membresías",
    "Longhorn steakhouse": "Restaurantes",
    "Sonic": "Restaurantes",
    "Dejavu barber" : "Cuidado personal",
    "Dollar General": "Supermercado",
    "Bath and Body" : "Cuidado personal",
    "Apple Cash": "Efectivo",
    "Kfc": "Restaurantes",
    "Columbia Restaurant": "Restaurantes",
    "Joffrey's Coffee": "Cafetería",
    "Gurgling Suitcase store 6702": "Restaurantes",
    "Geyser Point Bar-Grill": "Restaurantes",
    "Goods to Go": "Restaurantes",
    "RaceTrac":"Gasolina",
    "Burger King" : "Restaurantes",
    "Home Depot": "Restaurantes",
    "Groupon": "Entretenimiento",
    "Sophie’s Cafe": "Cafetería",
    "license plate": "Membresías",
    "Whispering": "Restaurantes",
    "Back of House": "Entretenimiento",
    "Theater": "Entretenimiento",
    "Dairy Queen Grill": "Restaurantes",
    "Apple Books": "Cuidado personal",
    "Expedia": "Entretenimiento",
    "Wdw Popcorn": "Entretenimiento",
    "Overtheborder": "Restaurantes",
    "OverDraft Item Fee": "Efectivo",
    "Via Napolli lake": "Restaurantes",
    "Katsura Grill": "Restaurantes",
    "Boulangerie": "Restaurantes",
    "Somerfest": "Restaurantes",
    "Floridays Resort Orlando": "Entretenimiento",
    "The Cheesecake Factory": "Restaurantes",
    "Ikea": "Supermercado",
    "Splitswille": "Restaurantes",
    "Mobile pet wash": "Cuidado personal",
    "chipotle": "Restaurantes",
    "Progressive Insurance - JE": "Seguros",
    "Bar Helios": "Restaurantes",
    "Helios Hotel": "Entretenimiento",
    "Artistic Body Work": "Otros",
    "Vietaly Auto Spa": "Restaurantes",
    "Sunoco": "Restaurantes",
    "Humane Society": "Otros",
    "Insufficient funds nsf fee": "Efectivo",
    "Universal Studios Store": "Entretenimiento",
    "Amc food court": "Entretenimiento",
    "Tamales": "Restaurantes",
    "Parking lot": "Otros",
    "Apple - Card": "Membresías",
    "Midflorida": "Transferencia",
    "Five Below": "Supermercado",
    "Nursing license": "Membresías",
    "AMC Misc": "Entretenimiento",
    "Family decisions": "Otros",
    "The Donut Man": "Restaurantes",
    "FLORIDADOH-MQA": "Membresías",
    "Popeyes": "Restaurantes",
    "Bryan Mexico": "Otros",
    "CEUFAST": "Membresías",
    "CEBROKER": "Membresías",
    "Subcription Toyota": "Membresías"
};

function asignarCategoria(selectDescripcion) {
    const fila = selectDescripcion.closest(".fila-adicional");

    const categoriaSelect = fila.querySelector('select[name="categoria[]"]');
    const metodoSelect = fila.querySelector('select[name="metodo_pago[]"]');
    const bancoSelect = fila.querySelector('select[name="banco_pago[]"]');

    if (!categoriaSelect || !metodoSelect || !bancoSelect) return;

    const descripcion = selectDescripcion.value.trim().toLowerCase();

    // Categoría automática
    categoriaSelect.value = categoriasAuto[descripcion] || "";

    // Automatización especial para ATM Retiro
    if (descripcion === "atm retiro") {
        metodoSelect.value = "Debito";
        bancoSelect.value = "Bank Of America";
    }
}