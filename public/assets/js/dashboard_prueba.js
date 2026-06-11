const ctx = document.getElementById('chartIngresosGastos');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: ["Ene", "Feb", "Mar", "Abr", "May", "Jun"],
        datasets: [
            {
                label: "Ingresos",
                data: [12000, 12500, 13000, 12800, 14000, 15000],
                borderColor: "#0078d4",
                borderWidth: 3,
                tension: 0.65
            },
            {
                label: "Gastos",
                data: [8000, 8200, 7800, 8500, 9000, 8800],
                borderColor: "#d9534f",
                borderWidth: 3,
                tension: 0.65
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: "bottom" }
        }
    }
});