<?php
require_once '../header.php';
require_once '../../config/db.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Ingresar Gastos Adicionales</h2>

<a href="/index.php" class="btn btn-secondary mb-3">
    ⬅ Regresar al Inicio
</a>


    <!-- MENSAJE DE GUARDADO -->
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ✔ Gasto adicional guardado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="../procesos/guardar_adicional.php" method="POST">

        <!-- CONTENEDOR DE FILAS -->
        <div id="contenedor-filas">

            <!-- FILA BASE (SE CLONA) -->
            <div class="row fila-adicional mb-3 p-3 border rounded">

                <!-- DESCRIPCIÓN -->
                <div class="col-md-3">
                    <label class="form-label">Descripción</label>
                    <select name="descripcion[]" class="form-select descripcion-select" onchange="asignarCategoria(this)" required>
                        <option value="">Seleccione...</option>

                        <?php
                        $descripciones = [
                            "7-eleven","aldi","amc snacks","atm retiro","bj's","buc-ees","classy nails","dominos pizza","dunkin",
                            "griffins cleaners","lightning","murphy express","plaza mexico","sams club","scooters coffee",
                            "southern nights","standby skipper","wallmart","woody's luch","florida state fair","golden corral",
                            "five guy","universal-hotel","wawa","universal-food","endless sumer resort-food","memories of peru",
                            "chili's grill & bar","classy nails and day spa","cold stone","mardi gras-kiosk","h-mart","oasics",
                            "myung ga","dutch bros","wing house","paranormal cirque","office 365","bbva-dolares","deliciosusness",
                            "harry's","chick-fil-a","little caesars","stella universal","dockside suits","lowes hotels",
                            "Amazon-Shopping","Dollar tree","Publix","Amtrak","Love's","Tu Plaza Kitchen","Save a lot",
                            "The Pour House on Main","El Paso Tienda Mexican Food","Rural King","PeruFresh","Honey Guy",
                            "3 Angels Lawn","Circle k","Apple Care","Longhorn steakhouse","Sonic","Dejavu barber",
                            "Dollar General","Bath and Body","Apple Cash","Kfc","Columbia Restaurant","Joffrey's Coffee",
                            "Gurgling Suitcase store 6702","Geyser Point Bar-Grill","Goods to Go","RaceTrac","Burger King",
                            "Home Depot","Groupon","Sophie’s Cafe","license plate","Whispering","Back of House","Theater",
                            "Dairy Queen Grill","Apple Books","Expedia","Wdw Popcorn","Overtheborder","OverDraft Item Fee",
                            "Via Napolli lake","Katsura Grill","Boulangerie","Somerfest","Floridays Resort Orlando",
                            "The Cheesecake Factory","Ikea","Splitswille","Mobile pet wash", "chipotle", "Progressive Insurance - JE",
                            "Bar Helios", "Helios Hotel", "Artistic Body Work", "Vietaly Auto Spa", "Sunoco", "Humane Society",
                            "Insufficient funds nsf fee","Universal Studios Store", "Amc food court", "Tamales","Parking lot", "Apple - Card", 
                            "Midflorida", "Five Below","Nursing license","AMC Misc", "Family decisions","The Donut Man", "FLORIDADOH-MQA", "Popeyes",
                            "Bryan Mexico", "CEUFAST", "CEBROKER", "Subcription Toyota","Target", "Zaxbys","Cinepolis", "Cinepolis Misc",
                            "Orenge lake", "Universal estudios-HHN", "Crackerbarrel", "AMC-Tickets",
                            
                            
                        ];

                        foreach ($descripciones as $d) {
                            echo "<option value='$d'>$d</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- MONTO -->
                <div class="col-md-2">
                    <label class="form-label">Monto ($)</label>
                    <input type="number" name="monto[]" class="form-control" step="0.01" required>
                </div>

                <!-- CATEGORÍA -->
                <div class="col-md-2">
                    <label class="form-label">Categoría</label>
                    <select name="categoria[]" class="form-select" required>
                        <option value="">Seleccione</option>
                        <option value="Gasolina">Gasolina</option>
                        <option value="Supermercado">Supermercado</option>
                        <option value="Restaurantes">Restaurantes</option>
                        <option value="Cafetería">Cafetería</option>
                        <option value="Cuidado personal">Cuidado personal</option>
                        <option value="Lavandería">Lavandería</option>
                        <option value="Entretenimiento">Entretenimiento</option>
                        <option value="Membresías">Membresías</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Seguros">Seguros</option>
                        <option value="Otros">Otros</option>
                    </select>
                </div>

                <!-- MÉTODO DE PAGO -->
                <div class="col-md-2">
                    <label class="form-label">Método Pago</label>
                    <select name="metodo_pago[]" class="form-select" required>
                        <option value="">Seleccione</option>
                        <option value="Debito">Débito</option>
                        <option value="Credito">Crédito</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                    </select>
                </div>

                <!-- BANCO -->
                <div class="col-md-2">
                    <label class="form-label">Banco</label>
                    <select name="banco_pago[]" class="form-select">
                        <option value="">Seleccione</option>
                        <option value="Bank Of America">Bank Of America</option>
                        <option value="MidFlorida">MidFlorida</option>
                        <option value="Chase">Chase</option>
                        <option value="Universal-Visa">Universal-Visa</option>
                        <option value="Disney">Disney</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <!-- PAYMENT DATE -->
                <div class="col-md-2 mt-3">
                    <label class="form-label">Payment Date</label>
                    <input type="date" name="payment_date[]" class="form-control" required>
                </div>

                <!-- ELIMINAR -->
                <div class="col-md-1 d-flex align-items-end mt-3">
                    <button type="button" class="btn btn-danger w-100" onclick="eliminarFila(this)">X</button>
                </div>

            </div> <!-- FIN FILA BASE -->

        </div> <!-- FIN CONTENEDOR -->

        <button type="button" class="btn btn-success mt-3" onclick="agregarFila()">Agregar Fila</button>
        <button type="submit" class="btn btn-primary mt-3">Guardar Gastos</button>

    </form>
</div>

<!-- SELECT2 PARA BÚSQUEDA -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    $('.descripcion-select').select2({
        placeholder: "Buscar descripción...",
        width: '100%'
    });
});
</script>

<script src="../assets/js/adicionales.js"></script>

<?php require_once '../footer.php'; ?>
