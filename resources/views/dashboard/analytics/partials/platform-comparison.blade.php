<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Platform Comparison</h2>
    <div class="flex justify-center">
        <canvas id="platformChart" width="300" height="300"></canvas>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const data = @json($data);
    
    const ctx = document.getElementById('platformChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(d => d.platform.charAt(0).toUpperCase() + d.platform.slice(1)),
            datasets: [{
                label: 'Engagement by Platform',
                data: data.map(d => d.engagement),
                backgroundColor: [
                    'rgb(59, 130, 246)',
                    'rgb(99, 102, 241)',
                    'rgb(168, 85, 247)',
                    'rgb(236, 72, 153)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>
