{{--
    resources/views/admin/partials/proposal_card.blade.php
    Variable: $p = ShowtimeProposal with eager-loaded relations
--}}
<a href="{{ route('admin.proposals.show', $p->first_id) }}" class="mp-card">

    {{-- Status indicator --}}
    <div class="mp-card__status-bar mp-card__status-bar--{{ $p->status }}"></div>

    {{-- Portrait poster thumb --}}
    <div class="mp-card__poster-wrap">
        @if (!empty($p->movie?->portrait_poster))
            <img
                src="{{ asset('images/movies/' . $p->movie->portrait_poster) }}"
                alt="{{ $p->movie?->movie_name }}"
                class="mp-card__poster"
            >
        @else
            <div class="mp-card__poster-ph">🎬</div>
        @endif
    </div>

    {{-- Abstract text --}}
    <div class="mp-card__body">
        <p class="mp-card__headline">
            <strong>{{ $p->manager?->manager_name ?? 'Unknown' }}</strong>'s proposal for
            <strong>{{ $p->movie?->movie_name ?? '—' }}</strong> showtime plan at
            <strong>{{ $p->cinema?->cinema_name ?? '—' }}</strong>
        </p>
        <div class="mp-card__meta">
            <span class="mp-meta-pill">🏟 {{ $p->theatre?->theatre_name ?? '—' }}</span>
            <span class="mp-meta-pill">📅 {{ $p->slot_count }} slot(s)</span>
<span class="mp-meta-pill">⏱ {{ $p->start_time?->format('h:i A') }}</span>
            <span class="mp-meta-pill">{{ $p->created_at?->diffForHumans() }}</span>
        </div>
    </div>

    {{-- Status badge --}}
    <div class="mp-card__badge-wrap">
        <span class="mp-status-badge mp-status-badge--{{ $p->status }}">
            {{ ucfirst($p->status) }}
        </span>
        <span class="mp-card__arrow">→</span>
    </div>

</a>