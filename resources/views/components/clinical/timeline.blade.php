<div class="card">
    <div class="card-header">
        <h3 class="card-title">Clinical Timeline</h3>
    </div>
    <div class="card-body">
        <div class="timeline">
            @forelse($events as $event)
                <div class="timeline-item">
                    <div class="timeline-marker bg-{{ $event['color'] ?? 'primary' }}"></div>
                    <div class="timeline-content">
                        <p class="timeline-title">{{ $event['title'] }}</p>
                        <p class="timeline-text">{{ $event['description'] }}</p>
                        <p class="timeline-date">{{ $event['date'] }}</p>
                    </div>
                </div>
            @empty
                <p class="text-muted">No events recorded yet.</p>
            @endforelse
        </div>
    </div>
</div>
