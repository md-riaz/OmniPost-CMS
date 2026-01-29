<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Content Calendar - {{ config('app.name') }}</title>
    
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filters select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }
        #calendar {
            height: 700px;
        }
        .legend {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 4px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        .back-button {
            padding: 8px 16px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
        }
        .back-button:hover {
            background: #4f46e5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Content Calendar</h1>
            <a href="{{ route('tyro-dashboard.dashboard') }}" class="back-button">← Back to Dashboard</a>
        </div>

        <div class="filters">
            <select id="brand-filter">
                <option value="">All Brands</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                @endforeach
            </select>

            <select id="platform-filter">
                <option value="">All Platforms</option>
                <option value="facebook">Facebook</option>
                <option value="linkedin">LinkedIn</option>
            </select>

            <select id="status-filter">
                <option value="">All Statuses</option>
                <option value="scheduled">Scheduled</option>
                <option value="publishing">Publishing</option>
                <option value="published">Published</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        <div id="calendar"></div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: #1877F2;"></div>
                <span>Facebook</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #0A66C2;"></div>
                <span>LinkedIn</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #DC2626;"></div>
                <span>Failed</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #60A5FA; border: 2px solid #059669;"></div>
                <span>Published</span>
            </div>
        </div>
    </div>

    <!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const brandFilter = document.getElementById('brand-filter');
            const platformFilter = document.getElementById('platform-filter');
            const statusFilter = document.getElementById('status-filter');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                editable: true,
                selectable: true,
                events: function(info, successCallback, failureCallback) {
                    const params = new URLSearchParams({
                        start: info.startStr,
                        end: info.endStr
                    });

                    if (brandFilter.value) params.append('brand_id', brandFilter.value);
                    if (platformFilter.value) params.append('platform', platformFilter.value);
                    if (statusFilter.value) params.append('status', statusFilter.value);

                    fetch('/api/calendar?' + params.toString())
                        .then(response => response.json())
                        .then(data => successCallback(data.events))
                        .catch(error => {
                            console.error('Error loading calendar events:', error);
                            failureCallback(error);
                        });
                },
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url) {
                        window.location.href = info.event.url;
                    }
                },
                eventDrop: function(info) {
                    if (confirm('Reschedule this post to ' + info.event.start.toLocaleString() + '?')) {
                        fetch(`/api/calendar/${info.event.id}/reschedule`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                scheduled_at: info.event.start.toISOString()
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Post rescheduled successfully!');
                            } else {
                                alert('Error: ' + (data.error || 'Failed to reschedule'));
                                info.revert();
                            }
                        })
                        .catch(error => {
                            console.error('Error rescheduling:', error);
                            alert('Failed to reschedule post');
                            info.revert();
                        });
                    } else {
                        info.revert();
                    }
                },
                eventContent: function(arg) {
                    return {
                        html: `<div style="padding: 2px;">
                            <strong>${arg.event.title}</strong><br>
                            <small>${arg.event.extendedProps.brand} • ${arg.event.extendedProps.platform}</small>
                        </div>`
                    };
                }
            });

            calendar.render();

            // Reload calendar when filters change
            [brandFilter, platformFilter, statusFilter].forEach(filter => {
                filter.addEventListener('change', () => calendar.refetchEvents());
            });
        });
    </script>
</body>
</html>
