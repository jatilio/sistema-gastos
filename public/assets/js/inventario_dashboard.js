document.addEventListener("DOMContentLoaded", function () {

    /* ===========================
       GRÁFICO: Categorías
       =========================== */
    const catCtx = document.getElementById('categoriasChart');
    if (catCtx) {
        new Chart(catCtx, {
            type: 'pie',
            data: {
                labels: categoriasLabels,
                datasets: [{
                    data: categoriasData,
                    backgroundColor: [
                        '#3498db', '#e74c3c', '#2ecc71',
                        '#f1c40f', '#9b59b6', '#1abc9c'
                    ]
                }]
            }
        });
    }

    /* ===========================
       GRÁFICO: Stock bajo
       =========================== */
    const stockCtx = document.getElementById('stockChart');
    if (stockCtx) {
        new Chart(stockCtx, {
            type: 'bar',
            data: {
                labels: stockLabels,
                datasets: [{
                    label: 'Cantidad',
                    data: stockData,
                    backgroundColor: '#3498db'
                }]
            }
        });
    }

    /* ===========================
       GRÁFICO: Vencimiento por mes
       =========================== */
    const vencerCtx = document.getElementById('vencerMesChart');
    if (vencerCtx) {
        new Chart(vencerCtx, {
            type: 'line',
            data: {
                labels: vencerMesLabels,
                datasets: [{
                    label: 'Productos por vencer',
                    data: vencerMesData,
                    borderColor: '#e74c3c',
                    fill: false,
                    tension: 0.3
                }]
            }
        });
    }

});
