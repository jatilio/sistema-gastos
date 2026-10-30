const ctx = document.getElementById("chartIngresosGastos");

new Chart(ctx, {
    type: "line",
    data: {
        labels: labelsLine,
        datasets: [
            {
                label: "Ingresos",
                data: dataIngresos,
                borderColor: "#2563eb",
                backgroundColor: "rgba(37, 99, 235, 0.08)",
                borderWidth: 3,
                tension: 0.25,
                pointRadius: 4,
                pointHoverRadius: 7,
                pointBackgroundColor: "#ffffff",
                pointBorderColor: "#2563eb",
                pointBorderWidth: 3,
                fill: false
            },
            {
                label: "Gastos",
                data: dataGastos,
                borderColor: "#ef4444",
                backgroundColor: "rgba(239, 68, 68, 0.08)",
                borderWidth: 3,
                tension: 0.25,
                pointRadius: 4,
                pointHoverRadius: 7,
                pointBackgroundColor: "#ffffff",
                pointBorderColor: "#ef4444",
                pointBorderWidth: 3,
                fill: false
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 1600,
            easing: "easeOutQuart",
            delay: (ctx) => ctx.dataIndex * 120
        },
        plugins: {
            legend: {
                position: "top",
                labels: {
                    font: { size: 13, weight: "bold" },
                    padding: 20
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: "rgba(0,0,0,0.05)" },
                ticks: { font: { size: 12 } }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 12 } }
            }
        }
    }
});