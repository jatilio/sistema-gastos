/* ============================
   FILAS DINÁMICAS
============================ */

function inicializarDescripciones(contenedor) {
  if (!window.jQuery?.fn?.select2) return;
  window.jQuery(contenedor).find('.descripcion-select').select2({placeholder:'Buscar descripción...',width:'100%'});
}
function agregarFila() {
  const contenedor = document.getElementById('contenedor-filas');
  const fila = contenedor.querySelector('.fila-adicional').cloneNode(true);
  fila.querySelectorAll('.select2-container').forEach(elemento => elemento.remove());
  fila.querySelectorAll('[data-select2-id]').forEach(elemento => elemento.removeAttribute('data-select2-id'));
  fila.querySelectorAll('input, select').forEach(control => {
    control.value='';
    if(control.classList.contains('descripcion-select')) {
      control.classList.remove('select2-hidden-accessible');
      control.removeAttribute('aria-hidden');control.removeAttribute('tabindex');
    }
  });
  contenedor.appendChild(fila);
  inicializarDescripciones(fila);
}
function eliminarFila(btn) {
  const fila=btn.closest('.fila-adicional');
  if(fila && document.querySelectorAll('.fila-adicional').length>1) fila.remove();
}

/* ============================
   ASIGNACIÓN AUTOMÁTICA
============================ */

const categoriasAuto = {
  "7-eleven": "Gasolina",
  aldi: "Supermercado",
  "amc snacks": "Entretenimiento",
  "atm retiro": "Efectivo",
  "bj's": "Membresías",
  "buc-ees": "Gasolina",
  "classy nails": "Cuidado personal",
  "dominos pizza": "Restaurantes",
  dunkin: "Cafetería",
  "griffins cleaners": "Lavandería",
  lightning: "Gasolina",
  "murphy express": "Gasolina",
  "plaza mexico": "Restaurantes",
  "sams club": "Supermercado",
  "scooters coffee": "Cafetería",
  "southern nights": "Entretenimiento",
  "standby skipper": "Entretenimiento",
  wallmart: "Supermercado",
  "woody's luch": "Restaurantes",
  "florida state fair": "Restaurantes",
  "golden corral": "Restaurantes",
  "five guy": "Restaurantes",
  "universal-hotel": "Entretenimiento",
  wawa: "Restaurantes",
  "universal-food": "Restaurantes",
  "endless sumer resort-food": "Restaurantes",
  "memories of peru": "Restaurantes",
  "chili's grill & bar": "Restaurantes",
  "classy nails and day spa": "Cuidado personal",
  "cold stone": "Restaurantes",
  "mardi gras-kiosk": "Restaurantes",
  "h-mart": "Supermercado",
  oasics: "Cuidado Personal",
  "myung ga": "Restaurantes",
  "dutch bros": "Restaurantes",
  "wing house": "Restaurantes",
  "paranormal cirque": "Restaurantes",
  "office 365": "Membresías",
  "bbva-dolares": "Transferencia",
  deliciosusness: "Restaurantes",
  "harry's": "Restaurantes",
  "chick-fil-a": "Restaurantes",
  "little caesars": "Restaurantes",
  "stella universal": "Entretenimiento",
  "dockside suits": "Entretenimiento",
  "lowes hotels": "Entretenimiento",
  "Amazon-Shopping": "Cuidado personal",
  "Dollar tree": "Supermercado",
  Publix: "Supermercado",
  Amtrak: "Entretenimiento",
  "Love's": "Gasolina",
  "Tu Plaza Kitchen": "Restaurantes",
  "Save a lot": "Supermercado",
  "The Pour House on Main": "Entretenimiento",
  "El Paso Tienda Mexican Food": "Restaurantes",
  "Rural King": "Supermercado",
  PeruFresh: "Restaurantes",
  "Honey Guy": "Supermercado",
  "3 Angels Lawn": "Cuidado personal",
  "Circle k": "Gasolina",
  "Apple Care": "Membresías",
  "Longhorn steakhouse": "Restaurantes",
  Sonic: "Restaurantes",
  "Dejavu barber": "Cuidado personal",
  "Dollar General": "Supermercado",
  "Bath and Body": "Cuidado personal",
  "Apple Cash": "Efectivo",
  Kfc: "Restaurantes",
  "Columbia Restaurant": "Restaurantes",
  "Joffrey's Coffee": "Cafetería",
  "Gurgling Suitcase store 6702": "Restaurantes",
  "Geyser Point Bar-Grill": "Restaurantes",
  "Goods to Go": "Restaurantes",
  RaceTrac: "Gasolina",
  "Burger King": "Restaurantes",
  "Home Depot": "Restaurantes",
  Groupon: "Entretenimiento",
  "Sophie’s Cafe": "Cafetería",
  "license plate": "Membresías",
  Whispering: "Restaurantes",
  "Back of House": "Entretenimiento",
  Theater: "Entretenimiento",
  "Dairy Queen Grill": "Restaurantes",
  "Apple Books": "Cuidado personal",
  Expedia: "Entretenimiento",
  "Wdw Popcorn": "Entretenimiento",
  Overtheborder: "Restaurantes",
  "OverDraft Item Fee": "Efectivo",
  "Via Napolli lake": "Restaurantes",
  "Katsura Grill": "Restaurantes",
  Boulangerie: "Restaurantes",
  Somerfest: "Restaurantes",
  "Floridays Resort Orlando": "Entretenimiento",
  "The Cheesecake Factory": "Restaurantes",
  Ikea: "Supermercado",
  Splitswille: "Restaurantes",
  "Mobile pet wash": "Cuidado personal",
  chipotle: "Restaurantes",
  "Progressive Insurance - JE": "Seguros",
  "Bar Helios": "Restaurantes",
  "Helios Hotel": "Entretenimiento",
  "Artistic Body Work": "Otros",
  "Vietaly Auto Spa": "Restaurantes",
  Sunoco: "Restaurantes",
  "Humane Society": "Otros",
  "Insufficient funds nsf fee": "Efectivo",
  "Universal Studios Store": "Entretenimiento",
  "Amc food court": "Entretenimiento",
  Tamales: "Restaurantes",
  "Parking lot": "Otros",
  "Apple - Card": "Membresías",
  Midflorida: "Transferencia",
  "Five Below": "Supermercado",
  "Nursing license": "Membresías",
  "AMC Misc": "Entretenimiento",
  "Family decisions": "Otros",
  "The Donut Man": "Restaurantes",
  "FLORIDADOH-MQA": "Membresías",
  Popeyes: "Restaurantes",
  "Bryan Mexico": "Otros",
  CEUFAST: "Membresías",
  CEBROKER: "Membresías",
  "Subcription Toyota": "Membresías",
  Target: "Supermercado",
  Zaxbys: "Restaurantes",
  Cinepolis: "Entretenimiento",
  "Cinepolis Misc": "Entretenimiento",
  "Orenge lake": "Restaurantes",
  "Universal estudios-HHN": "Entretenimiento",
  Crackerbarrel: "Restaurantes",
  "AMC-Tickets": "Entretenimiento",
  "Pollo Campero": "Restaurantes",
  Shell: "Gasolina",
  "Taco Bell": "Restaurantes",
  "Best Buy Total - Annual": "Membresías",
  "Best Buy": "Supermercado",
  Ebay: "Supermercado",
  "PRESTIGE-BARBERSHOP": "Cuidado personal",
  Amazon: "Membresías",
  "Trader Joe's": "Supermercado",
  Ebisu: "Restaurantes",
  "Hamburger Marys": "Restaurantes",
  goodwill: "Otros",
  "FOURBOUYS LLC": "Entretenimiento",
  SPINX: "Gasolina",
  "DUDLEY'S": "Entretenimiento",
  HeyDude: "Cuidado personal",
  RUE21: "Cuidado personal",
  "PARKERS 80": "Gasolina",
  "Firehouse subs": "Restaurantes",
  Wanderlustifi: "Otros",
  "Other expenses": "Otros",
  "SILVERMOON DRIVE": "Entretenimiento",
  Handyman: "Otros",
  "Ross Store": "Supermercado",
  "Hacienda 1926": "Otros",
  Marshalls: "Supermercado",
  "El Zocalo": "Restaurantes",
  "Viva Sebring": "Restaurantes",
  "Havana Restaurant": "Restaurantes",
  "The Uniform Connection": "Otros",
  Michaels: "Supermercado",
};

function asignarCategoria(selectDescripcion) {
  const fila = selectDescripcion.closest(".fila-adicional");

  const categoriaSelect = fila.querySelector('select[name="categoria[]"]');
  const metodoSelect = fila.querySelector('select[name="metodo_pago[]"]');
  const bancoSelect = fila.querySelector('select[name="banco_pago[]"]');

  if (!categoriaSelect || !metodoSelect || !bancoSelect) return;

  const descripcion = selectDescripcion.value.trim().toLowerCase();

  // Categoría automática
  categoriaSelect.value = Object.entries(categoriasAuto).find(([nombre]) => nombre.trim().toLowerCase() === descripcion)?.[1] || "";

  // Automatización especial para ATM Retiro
  if (descripcion === "atm retiro") {
    metodoSelect.value = "Debito";
    bancoSelect.value = "Bank Of America";
  }
}
