const ctxCat = document.getElementById("chartCategorias");

new Chart(ctxCat, {
    type: "doughnut",
    data: {
        labels: labelsCategorias,
        datasets: [{
            data: totalesCategorias,
            backgroundColor: [
                "#2563eb", "#ef4444", "#10b981",
                "#f59e0b", "#8b5cf6", "#ec4899", "#14b8a6"
            ],
            borderWidth: 2,
            borderColor: "#ffffff",
            hoverOffset: 12,
            spacing: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            animateRotate: true,
            animateScale: true,
            duration: 1400,
            easing: "easeOutElastic"
        },
        plugins: {
            legend: {
                display: true,
                labels: {
                    font: { size: 11 },
                    usePointStyle: true,
                    pointStyle: "circle",
                    padding: 10
                }
            }
        },
        cutout: "45%"
    }
});