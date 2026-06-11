const ctxAd = document.getElementById("chartAdicionales");

new Chart(ctxAd, {
    type: "bar",
    data: {
        labels: labelsAdic,
        datasets: [
            {
                label: "Gastos Adicionales",
                data: totalesAdic,
                backgroundColor: "rgba(236, 72, 153, 0.35)",
                borderColor: "rgba(236, 72, 153, 0.8)",
                borderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 1300,
            easing: "easeOutQuart",
            delay: (ctx) => ctx.dataIndex * 150
        },
        plugins: {
            legend: {
                position: "bottom",
                labels: {
                    font: { size: 12, weight: "bold" },
                    padding: 10
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { font: { size: 12 } }
            },
            x: {
                ticks: { font: { size: 12 } }
            }
        }
    }
});