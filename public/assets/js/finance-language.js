(() => {
 if (window.financeLanguage) return;
 const pairs = `
Volver|Back
Agregar|Add
Cancelar|Cancel
Inicio|Home
Buscar|Search
Actualizar|Update
Guardar producto|Save product
Consumir Producto|Use Product
Registrar Consumo|Record Usage
Registrar Movimiento|Record Transaction
Cantidad a consumir|Quantity to use
Tipo de movimiento|Transaction type
Stock actual|Current stock
Stock mínimo|Minimum stock
Producto|Product
Productos por vencer|Products expiring soon
Bajo stock|Low stock
Por vencer|Expiring soon
Fecha movimiento|Transaction date
Sin movimientos|No transactions
No hay registros.|No records.
No hay productos registrados.|No products recorded.
Ver detalle|View details
Detalle del Gasto|Expense Details
Detalle de Gasto|Expense Details
Detalles del Gasto|Expense Details
Detalle|Details
Reporte|Report
Gastos Cobrados|Paid Expenses
Gastos del Mes|Monthly Expenses
Pagos|Payments
Consumo de Producto|Product Usage
Control de Tarjetas|Card Management
Detalle de Gastos|Expense Details
Día corte|Closing day
Día pago|Payment day
Exportar a PDF|Export to PDF
Fecha límite|Deadline
Guardar Pago|Save Payment
Movimientos del Producto|Product Transactions
Notas (opcional)|Notes (optional)
Número de Confirmación|Confirmation Number
Pagar Gasto|Pay Expense
Regresar|Back
Reporte de Productos con Bajo Stock|Low Stock Products Report
Resumen de Movimientos|Transaction Summary
Saldo Actual|Current Balance
Tarjeta de Débito|Debit Card
Tarjeta ID|Card ID
Top 10 Gastos Adicionales|Top 10 Additional Expenses
Total General|Grand Total
Último Movimiento|Latest Transaction
Veces|Count
Vencimiento|Due date
Ver|View
Nuevo pago|New scheduled payment
Organiza tus compromisos y genera únicamente los pagos que faltan.|Manage your commitments and generate only missing payments.
Pagos activos|Active payments
Importe previsto del mes|Expected amount for the month
Pagos por generar|Payments to generate
Pagos pausados|Paused payments
Pausado|Paused
Vista previa del mes|Monthly preview
Ver vista previa|View preview
La vista previa no crea registros. Se respetan las frecuencias y los pagos existentes.|The preview creates no records. Frequencies and existing payments are respected.
Ver detalle de pagos|View payment details
No hay pagos activos para este mes.|No active payments for this month.
Ya generado|Already generated
Por generar|To generate
Importe por generar|Amount to generate
Los pagos existentes y los registros ya pagados no se modifican.|Existing payments and paid records are not changed.
Generar pagos faltantes|Generate missing payments
Plantillas de pagos|Payment templates
Buscar por descripción o categoría|Search by description or category
Todas|All
pagos visibles|visible payments
No hay pagos que coincidan con los filtros.|No payments match the filters.
Editar pago|Edit payment
Los cambios se aplican a pagos futuros. Los gastos ya generados conservan sus datos.|Changes apply to future payments. Previously generated expenses retain their data.
Pago guardado correctamente. Los gastos ya generados no se modificaron.|Payment saved successfully. Previously generated expenses were not changed.
Completa todos los campos del pago.|Complete all payment fields.
Revisa la descripción, fecha, frecuencia e importe.|Check the description, date, frequency and amount.
Selecciona una categoría de la lista.|Select a category from the list.
No se encontró el pago.|Payment not found.
Ya existe un pago con esa descripción. Edítalo en la lista.|A payment with that description already exists. Edit it in the list.
No se pudo guardar el pago. Intenta nuevamente.|The payment could not be saved. Please try again.
Mes bloqueado: sus pagos ya fueron generados.|Locked month: its payments have already been generated.
Mes ya generado|Month already generated
Registrar|Record
Organizar|Organize
Consultar|Review
Pagar gastos fijos|Pay fixed expenses
Pagar gastos|Pay bills
Finanzas|Finances
Finanzas personales|Personal finances
Finanzas Personales|Personal Finances
Sistema de Gastos|Expense Tracker
General|Overview
Movimientos|Transactions
Reportes y ajustes|Reports and settings
Resumen financiero|Financial overview
Análisis financiero|Financial analysis
Ingresos mensuales|Monthly income
Ingresos Mensuales|Monthly Income
Ingresar gasto|Record expense
Ingresar Gastos|Record Expenses
Gastos del mes|Monthly expenses
Mis tarjetas|My cards
Mis Tarjetas|My Cards
Gastos de tarjetas|Card expenses
Gastos de Tarjetas|Card Expenses
Gastos adicionales|Additional expenses
Gastos Adicionales|Additional Expenses
Top 10 de gastos|Top 10 expenses
Top 10 Gastos Adicionales por Mes|Top 10 Monthly Additional Expenses
Reportes|Reports
Métodos de pago|Payment methods
Reporte de tarjetas|Card report
Pagos programados|Scheduled payments
Inventario|Inventory
Ver inventario|View inventory
Agregar producto|Add product
Agregar Producto|Add Product
Resumen de inventario|Inventory overview
Cerrar sesión|Sign out
Menú|Menu
Cerrar menú|Close menu
Modo claro|Light mode
Modo oscuro|Dark mode
Iniciar sesión|Sign in
Bienvenido de nuevo|Welcome back
Un espacio para organizar tu dinero.|A place to manage your money.
Usuario|Username
Contraseña|Password
Tu usuario|Your username
Tu contraseña|Your password
Entrar a mis finanzas|Open my finances
Entrar|Sign in
Usuario o contraseña incorrectos|Incorrect username or password
Tu dinero, con claridad|Your money, clearly
Consulta tus ingresos, gastos y próximos compromisos.|Review your income, expenses and upcoming payments.
Ver reportes|View reports
Registrar gastos|Record expenses
Registrar un nuevo gasto|Record a new expense
Gasto guardado correctamente.|Expense saved successfully.
Registrar gasto|Record expense
Ingreso del mes|Monthly income
Gastado (Fijo + Adicional)|Spent (Fixed + Additional)
Saldo disponible|Available balance
Pendientes por pagar|Pending payments
Próximos pagos (10 días)|Upcoming payments (10 days)
No hay pagos próximos.|No upcoming payments.
Pagos vencidos|Overdue payments
No hay pagos vencidos.|No overdue payments.
Tus finanzas, en perspectiva|Your finances, in perspective
Entiende tus gastos y planifica los próximos meses.|Understand your expenses and plan the months ahead.
Mes|Month
Año|Year
Banco|Bank
Todos|All
Todos los meses|All months
Aplicar filtros|Apply filters
Filtrar|Filter
Ingresos|Income
Gastos|Expenses
Ingresos Totales|Total Income
Gastos Totales|Total Expenses
Balance Total|Total Balance
Gastos pagados|Paid expenses
En el período seleccionado|In the selected period
Fijos|Fixed
Fijo|Fixed
Adicionales|Additional
Adicional|Additional
Ingresos menos gastos del período|Income minus expenses for the period
Ahorro del mes|Monthly savings
Ahorro del período|Period savings
Sin ingresos para calcular el porcentaje|No income available to calculate the percentage
Porcentaje de ingresos disponible|Percentage of income remaining
Ingresos vs gastos por mes|Income vs expenses by month
Comparación mensual|Monthly comparison
Comparación anual|Annual comparison
No hay registros del período anterior para comparar.|No records for the previous period to compare.
El gasto se mantuvo igual.|Spending stayed the same.
Distribución por categoría|Spending by category
Participación en los gastos del período. Sin límites de presupuesto definidos.|Share of expenses for the period. No budget limits have been set.
No hay gastos en este período.|No expenses for this period.
Gastos adicionales mensuales|Monthly additional expenses
Cuentas vinculadas|Linked accounts
Disponible|Available
Saldos actuales de tus cuentas.|Current balances of your accounts.
Transacciones recientes|Recent transactions
Últimos movimientos, independientemente del período.|Latest transactions, regardless of the selected period.
Próximos pagos|Upcoming payments
No hay pagos próximos|No upcoming payments
Gastos adicionales por categoría|Additional expenses by category
Sin categoría|Uncategorized
Sin fecha|No date
Movimiento|Transaction
Ingresar Gastos Adicionales|Record Additional Expenses
Regresar al Inicio|Back to Home
Descripción|Description
Monto|Amount
Categoría|Category
Método Pago|Payment Method
Método de pago|Payment method
Fecha de pago|Payment date
Fecha Pago|Payment Date
Seleccione...|Select...
Seleccione|Select
Buscar descripción...|Search descriptions...
Agregar Fila|Add Row
Guardar Gastos|Save Expenses
Quitar esta fila|Remove this row
Gasto adicional guardado correctamente.|Additional expense saved successfully.
Gasolina|Fuel
Supermercado|Groceries
Restaurantes|Restaurants
Cafetería|Coffee shops
Cuidado personal|Personal care
Lavandería|Laundry
Entretenimiento|Entertainment
Membresías|Memberships
Efectivo|Cash
Débito|Debit
Debito|Debit
Crédito|Credit
Credito|Credit
Transferencia|Transfer
Seguros|Insurance
Otros|Other
Otro|Other
Registrar Ingresos|Record Income
Ingresos Mensuales|Monthly Income
Banco origen – 1ª Quincena|Source bank – First half of month
Banco origen – 2ª Quincena|Source bank – Second half of month
Banco origen – Extra|Source bank – Extra income
1ª Quincena|First half of month
2ª Quincena|Second half of month
Extra (opcional)|Extra income (optional)
Guardar Ingresos|Save Income
Volver al Dashboard|Back to Dashboard
Este ingreso se aplicará al mes|This income will apply to
Fecha Vencimiento|Due Date
Fecha de vencimiento|Expiration date
Confirmación|Confirmation
Tarjeta|Card
Acción|Action
Acciones|Actions
Validado|Verified
Pagar|Pay
Guardar|Save
Pagado|Paid
Sí|Yes
Si|Yes
No|No
Pagos del Mes|Monthly Payments
Banco Pago|Payment Bank
Método|Method
Volver al menú principal|Back to main menu
Regresar al Inicio|Back to Home
Mantenimiento de Pagos|Payment Settings
Mantenimiento|Settings
Generar Pagos|Generate Payments
Mes o año inválido.|Invalid month or year.
Guardar Cambios|Save Changes
Frecuencia|Frequency
Mensual|Monthly
Anual|Yearly
Semestral|Every six months
Activo|Active
Activa|Active
Inactivo|Inactive
Inactiva|Inactive
Registrar Nueva Tarjeta|Register New Card
Registrar Gasto de Tarjeta|Record Card Expense
Gastos Registrados|Recorded Expenses
Tarjetas Registradas|Registered Cards
Editar Tarjeta|Edit Card
Nombre de la tarjeta|Card name
Tipo|Type
Marca|Brand
Últimos 4 dígitos|Last 4 digits
Últimos 4|Last 4
Estado|Status
Límite de crédito|Credit limit
Límite|Limit
Saldo inicial|Opening balance
Día de corte|Statement closing day
Día de pago|Payment day
Guardar tarjeta|Save card
Actualizar tarjeta|Update card
Guardar gasto|Save expense
Fecha del gasto|Expense date
Seleccione una tarjeta|Select a card
Seleccione tipo|Select type
Seleccione marca|Select brand
Nombre|Name
Editar|Edit
Eliminar|Delete
Inventario del Hogar|Household Inventory
Productos con Bajo Stock|Low Stock Products
Próximos a Vencer|Expiring Soon
Agregar Producto al Inventario|Add Inventory Product
Editar Producto|Edit Product
Menor Stock|Lowest Stock
Por Vencer por Mes|Expiring by Month
Inventario de Productos|Product Inventory
Productos|Products
Cantidad actual|Current quantity
Cantidad mínima|Minimum quantity
Unidad|Unit
Ubicación|Location
Fecha de compra|Purchase date
Fecha de vencimiento|Expiration date
Fecha|Date
Cantidad|Quantity
Consumir|Use stock
Consumo|Consumption
Entrada|Stock in
Salida|Stock out
Notas|Notes
Volver al Inventario|Back to Inventory
Dashboard del Inventario|Inventory Dashboard
Total de Productos|Total Products
Bajo Stock|Low Stock
Vencidos|Expired
Por Vencer (7 días)|Expiring Soon (7 days)
Movimientos de Inventario|Inventory Transactions
Movimientos del Inventario|Inventory Transactions
Historial de Movimientos|Transaction History
Reporte de Bajo Stock|Low Stock Report
Descargar Bajo Stock|Download Low Stock Report
REPORTE FINANCIERO MENSUAL|MONTHLY FINANCIAL REPORT
Reporte Financiero Mensual|Monthly Financial Report
Generado|Generated
Descargar PDF|Download PDF
TOTAL FIJOS|FIXED TOTAL
TOTAL ADICIONALES|ADDITIONAL TOTAL
TOTAL GENERAL (FIJOS + ADICIONALES)|GRAND TOTAL (FIXED + ADDITIONAL)
FIJOS|FIXED
ADICIONALES (AGRUPADOS)|ADDITIONAL (GROUPED)
Reporte por Método de Pago|Report by Payment Method
Reporte de Tarjetas|Card Report
Total|Total
Enero|January
Febrero|February
Marzo|March
Abril|April
Mayo|May
Junio|June
Julio|July
Agosto|August
Septiembre|September
Octubre|October
Noviembre|November
Diciembre|December
Tarjeta de crédito|Credit card
Vivienda|Housing
Seguro|Insurance
Servicios|Utilities
Comida|Food
Salud|Health
Transporte|Transportation
Educación|Education
Ingresos y gastos mensuales|Monthly income and expenses
Gastos adicionales por mes|Additional expenses by month
Tabla de datos. Desplázate horizontalmente para ver todas las columnas.|Data table. Scroll horizontally to view all columns.
Ene|Jan
Abr|Apr
Ago|Aug
Dic|Dec
`;
 const dictionary = new Map(pairs.trim().split('\n').map(line => line.split('|')));
 const normalized = new Map(Array.from(dictionary, ([a,b]) => [a.toLocaleLowerCase('es'),b]));
 let language; try { language=localStorage.getItem('finance-language')||'es'; } catch { language='es'; }
 if (!['es','en'].includes(language)) language='es';
 const textMemory = new WeakMap(), attributeMemory = new WeakMap();
 const translationCache = new Map();
 function translate(text) { if(language !== 'en') return text; if(translationCache.has(text)) return translationCache.get(text); const result=translateUncached(text); translationCache.set(text,result); return result; }
 function translateUncached(text) {
  if (language !== 'en') return text;
  const spaces = text.match(/^(\s*)([\s\S]*?)(\s*)$/);
  let value = spaces[2];
  const exact = dictionary.get(value) || normalized.get(value.toLocaleLowerCase('es'));
  if (exact) return spaces[1]+exact+spaces[3];
  // Frases variables: fechas, importes y decoración conservan sus valores.
  const phrases=[['Acumulado','Year to date'],['Indicadores de','Figures for'],['Gastos pagados con débito','Expenses paid by debit'],['Fijos por vencimiento y adicionales por fecha de pago.','Fixed expenses by due date and additional expenses by payment date.'],['Enero a','January to'],['Incluye pagos adelantados','Includes advance payments'],['Pagos adelantados','Advance payments'],['Pagados antes del cierre de','Paid before the end of'],['con vencimiento en meses posteriores. Se muestran en su mes de vencimiento.','due in later months. Shown in their due month.'],['Gastos vs','Expenses vs'],['Gastaste','You spent'],['menos.','less.'],['más.','more.'],['del total','of total'],['Solo meses con registros','Only months with records'],['Pagos con débito','Debit payments'],['se aplicará al mes','will apply to'],['ya generado','already generated'],['vence el','due on'],['venció el','was due on'],['Hola,','Hello,'],['Quincena','Pay period']];
  for (const [from,to] of phrases) value=value.replace(new RegExp(from.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'),'giu'),to);
  const entries=Array.from(dictionary).sort((a,b)=>b[0].length-a[0].length);
  for (const [from,to] of entries) {
   const escaped=from.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
   value=value.replace(new RegExp('(?<![\\p{L}\\p{N}])'+escaped+'(?![\\p{L}\\p{N}])','giu'), match=>match===match.toUpperCase() && match.length>3 ? to.toUpperCase() : to);
  }
  value=value.replace(/(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) de (\\d{4})/g, '$1 $2');
  return spaces[1]+value+spaces[3];
 }
 // Formato de presentación: los valores originales de formularios y registros no cambian.
 const acronyms = new Set(['PDF','ID','ATM','AMC','BBVA','KFC','LLC','CEUFAST','CEBROKER','DOH','MQA','NSF','HHN','JE']);
 function polishText(text, element) {
  if (!element || !/[\p{L}]/u.test(text)) return text;
  if (element.closest('[data-no-translate],.analysis-kicker,.eyebrow,.menu-label,input,textarea,[contenteditable=true]')) return text;
  const paymentName=element.closest('.scheduled-card h3,.scheduled-preview-row strong');
  const heading=paymentName ? null : element.closest('h1,h2,h3,h4,h5,label,th,button,.menu-principal a,.navbar-nav a');
  const cell=element.closest('td');
  const header=cell?.closest('table')?.rows[0]?.cells[cell.cellIndex]?.textContent.trim().toLocaleLowerCase() || '';
  const namedCell=cell && ['descripción','description','categoría','category','banco','bank','tarjeta','card','nombre','name'].includes(header);
  const name=paymentName || element.closest('option,.scheduled-card dd,.scheduled-preview-row strong,.analysis-category>div>span,.analysis-list-row span,.t-desc,.left-info p,.alert-list strong') || namedCell;
  if(!heading && !name) return text;
  const trimmed=text.trim();
  if(!trimmed || /^[\d\W]+$/u.test(trimmed)) return text;
  const smallWords=new Set(['de','del','la','las','el','los','y','por','para','con','al','en','of','the','and','vs','to','by','for']);
  let first=true;
  const formatted=trimmed.replace(/[\p{L}]+(?:['’][\p{L}]+)?/gu,word=>{
   const upper=word.toUpperCase();
   if(upper==='MIDFLORIDA') {first=false;return 'MidFlorida';}
   if(acronyms.has(upper)) {first=false;return upper;}
   if(word==='MidFlorida' || word==='RaceTrac' || word==='HeyDude' || word==='PeruFresh') {first=false;return word;}
   const lower=word.toLocaleLowerCase(language==='en'?'en':'es');
   const capitalize=first || !!paymentName || (name && !heading && !smallWords.has(lower));
   first=false;
   return capitalize ? lower.charAt(0).toUpperCase()+lower.slice(1) : lower;
  });
  return text.replace(trimmed,formatted);
 }
 function convertText(node) {
  const parent=node.parentElement;
  if (!parent || parent.closest('script,style,noscript,[data-no-translate],input,textarea,[contenteditable=true]')) return;

  let entry=textMemory.get(node);
  if (!entry || (node.data!==entry.original && node.data!==entry.translated)) entry={original:node.data};
  if(parent.tagName==='OPTION' && parent.dataset.financeOriginalText) entry.original=parent.dataset.financeOriginalText;
  const merchant=parent.closest('.descripcion-select') && parent.tagName==='OPTION' && parent.value!=='';
  const translated=polishText(merchant ? entry.original : translate(entry.original), parent);
  entry.translated=translated;
  textMemory.set(node,entry);
  if (node.data!==translated) node.data=translated;
 }
 const originalTitle = document.title;
 function render() {
  document.title=translate(originalTitle);
  document.documentElement.lang=language;
  document.documentElement.dataset.language=language;
  // Traducir el texto de opciones nunca debe cambiar el valor enviado al servidor.
  document.querySelectorAll('option').forEach(option=>{ if(!option.hasAttribute('value'))option.setAttribute('value',option.textContent); if(!option.dataset.financeOriginalText)option.dataset.financeOriginalText=option.textContent; });
  const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);
  const nodes=[]; while(walker.nextNode()) nodes.push(walker.currentNode);
  nodes.forEach(convertText);
  document.querySelectorAll('[placeholder],[aria-label],[title]').forEach(element=>{
   let attrs=attributeMemory.get(element); if (!attrs) {attrs={}; attributeMemory.set(element,attrs);}
   for (const attr of ['placeholder','aria-label','title']) {
    if (!element.hasAttribute(attr)) continue;
    if (!(attr in attrs)) attrs[attr]=element.getAttribute(attr);
    const value=translate(attrs[attr]); if(element.getAttribute(attr)!==value) element.setAttribute(attr,value);
   }
  });
  document.querySelectorAll('[data-language-select]').forEach(select=>select.value=language);
  document.querySelectorAll('canvas').forEach(canvas=>{
   const chart=window.Chart?.getChart?.(canvas); if(!chart) return;
   if(!chart.$languageOriginal) chart.$languageOriginal={labels:[...chart.data.labels],datasets:chart.data.datasets.map(d=>d.label)};
   chart.data.labels=chart.$languageOriginal.labels.map(label=>typeof label==='string'?(canvas.id==='chartCategorias'?polishText(translate(label),document.createElement('option')):translate(label)):label);
   chart.data.datasets.forEach((d,i)=>{const label=chart.$languageOriginal.datasets[i];if(label) d.label=translate(label);});
   chart.update('none');
  });
 }
 let pending=false;
 function schedule(){if(pending)return;pending=true;requestAnimationFrame(()=>{pending=false;observer.disconnect();render();observer.observe(document.body,{childList:true,subtree:true,characterData:true});});}
 const observer=new MutationObserver(schedule);
 function init(){
  const control=document.createElement('label'); control.className='finance-language-control';control.setAttribute('data-no-translate','');
  control.innerHTML='<span aria-hidden="true">🌐</span><select data-language-select aria-label="Language / Idioma"><option value="es">Español</option><option value="en">English</option></select>';
  const relocate=()=>{const theme=document.querySelector('[data-theme-toggle]');if(theme && control.parentElement!==theme.parentElement) theme.after(control);};
  relocate();if(!control.isConnected)document.body.prepend(control);
  control.querySelector('select').addEventListener('change',event=>{language=event.target.value;try{localStorage.setItem('finance-language',language);}catch{} schedule();});
  window.addEventListener('resize',relocate);
  render();observer.observe(document.body,{childList:true,subtree:true,characterData:true});
  window.addEventListener('load',schedule,{once:true});
 }
 window.financeLanguage={translate,refresh:schedule,get language(){return language;}};
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>requestAnimationFrame(init));else init();
 window.addEventListener('storage',event=>{if(event.key==='finance-language'){language=event.newValue==='en'?'en':'es';schedule();}});
})();