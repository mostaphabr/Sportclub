{{-- Star rating. Usage: @include('SportClub.partials.stars', ['rating' => 4.3, 'count' => 12]) --}}
@php
    $rating = $rating ?? null;
    $count = $count ?? null;
@endphp
<span class="sc-stars text-nowrap" title="{{ $rating !== null ? number_format($rating, 1).' / 5' : 'No ratings yet' }}">
    @if ($rating === null)
        <span class="text-muted small">No ratings yet</span>
    @else
        @for ($i = 1; $i <= 5; $i++)
            @if ($rating >= $i - 0.25)
                <i class="bi bi-star-fill text-warning"></i>
            @elseif ($rating >= $i - 0.75)
                <i class="bi bi-star-half text-warning"></i>
            @else
                <i class="bi bi-star text-warning"></i>
            @endif
        @endfor
        <span class="small fw-semibold ms-1">{{ number_format($rating, 1) }}</span>
        @if ($count !== null)
            <span class="small text-muted">({{ $count }})</span>
        @endif
    @endif
</span>
