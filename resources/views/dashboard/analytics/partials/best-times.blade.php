<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Best Posting Times (Engagement Rate Heatmap)</h2>
    <canvas id="heatmapChart" height="60"></canvas>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const data = @json($data);
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    
    // Create a matrix for the heatmap
    const matrix = Array(7).fill(0).map(() => Array(24).fill(0));
    const counts = Array(7).fill(0).map(() => Array(24).fill(0));
    
    data.forEach(d => {
        matrix[d.day][d.hour] += d.avg_engagement_rate;
        counts[d.day][d.hour] += d.count;
    });
    
    // Calculate averages
    for (let day = 0; day < 7; day++) {
        for (let hour = 0; hour < 24; hour++) {
            if (counts[day][hour] > 0) {
                matrix[day][hour] = matrix[day][hour] / counts[day][hour];
            }
        }
    }
    
    // Find max value for color scaling
    const maxValue = Math.max(...matrix.flat());
    
    // Create bar chart data (showing average engagement by hour across all days)
    const hourlyAverages = Array(24).fill(0);
    const hourlyCounts = Array(24).fill(0);
    
    data.forEach(d => {
        hourlyAverages[d.hour] += d.avg_engagement_rate * d.count;
        hourlyCounts[d.hour] += d.count;
    });
    
    for (let i = 0; i < 24; i++) {
        if (hourlyCounts[i] > 0) {
            hourlyAverages[i] = hourlyAverages[i] / hourlyCounts[i];
        }
    }
    
    const ctx = document.getElementById('heatmapChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Array(24).fill(0).map((_, i) => `${i}:00`),
            datasets: [{
                label: 'Avg Engagement Rate (%)',
                data: hourlyAverages,
                backgroundColor: hourlyAverages.map(val => {
                    const intensity = maxValue > 0 ? val / maxValue : 0;
                    return `rgba(59, 130, 246, ${0.2 + intensity * 0.8})`;
                }),
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Engagement Rate: ${context.parsed.y.toFixed(2)}%`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(1) + '%';
                        }
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });
});
</script>
