{{--
    resources/views/branch_manager/bm_review_proposal.blade.php
    READ-ONLY review of a submitted ShowtimeProposal batch.
    Data: $cinema, $movie, $proposal, $groupRows, $quota
--}}
@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', $movie->movie_name . ' — Proposal Review')

@section('bm_head_extras')
    @vite(['resources/css/bm_review_proposals.css'])
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    @vite(['resources/js/bm_review_proposals.js'])
@endsection


@section('bm_content')

<a href="{{ route('manager.upcoming') }}" class="bm-back-link">&larr; Back to Upcoming Movies</a>

{{-- ═══════════════ STATUS BANNER ═══════════════ --}}
@php $status = $proposal->status; @endphp

<div class="rp-status-banner rp-status-banner--{{ $status }}">
    <span class="rp-status-icon">
        @if ($status === 'pending') ⏳
        @elseif ($status === 'approved') ✓
        @else ✕
        @endif
    </span>
    <span>
        <strong>{{ ucfirst($status) }}</strong>
        — Submitted {{ $proposal->created_at?->format('d M Y, h:i A') }}
    </span>
</div>

{{-- ═══════════════ PRE-COMPUTE THEATRE GROUPS + JSON ═══════════════ --}}
@php
    $theatreGroups = $groupRows->groupBy('hall_id');

    $theatresJson = $theatreGroups->map(function ($rows, $hallId) {
        $theatre = $rows->first()?->theatre;
        return [
            'hallId'        => $hallId,
            'theatreId'     => $theatre?->theatre_id,
            'theatreName'   => $theatre?->theatre_name ?? 'Theatre',
            'theatrePoster' => $theatre?->theatre_poster
                                ? asset('images/theatres/' . $theatre->theatre_poster)
                                : null,
            'slots' => $rows->map(fn($r) => [
                'date'        => $r->start_datetime?->format('Y-m-d'),
                'dateLabel'   => $r->start_datetime?->format('D, d M Y'),
                'start'       => $r->start_datetime?->format('h:i A'),
                'end'         => $r->end_datetime?->format('h:i A'),
                'start_h'     => (int) $r->start_datetime?->format('h'),
                'start_m'     => (int) $r->start_datetime?->format('i'),
                'start_ampm'  => $r->start_datetime?->format('A'),
                'end_display' => $r->end_datetime?->format('h:i A'),
            ])->values()->all(),
        ];
    })->values();

    $firstTheatreRows    = $theatreGroups->first() ?? collect();
    $firstTheatre        = $firstTheatreRows->first()?->theatre;
    $firstTheatreSlotCnt = $firstTheatreRows->count();
@endphp

{{-- Hidden data bridge for JS --}}
<div id="rp-slot-data" style="display:none;"
         data-theatres='@json($theatresJson)'></div>

{{-- ═══════════════ TWO-COLUMN LAYOUT ═══════════════ --}}
<div class="rp-layout">

    {{-- LEFT: Cinema · Movie · Quota --}}
    <div class="rp-left">

        {{-- Cinema --}}
        <div class="ac-card rp-info-card">
            <div class="ac-card__title rp-section-title">Cinema</div>
            @if ($cinema->cinema_picture)
                <img src="{{ asset('images/cinemas/' . $cinema->cinema_picture) }}"
                     alt="{{ $cinema->cinema_name }}" class="rp-cinema-banner">
            @endif
            <div class="rp-info-row rp-row-highlight">
                <span class="rp-info-label">Cinema Name</span>
                <span class="rp-info-value">{{ $cinema->cinema_name }}</span>
            </div>
            <div class="rp-info-row">
                <span class="rp-info-label">Address</span>
                <span class="rp-info-value">{{ $cinema->cinema_address ?? '—' }}</span>
            </div>
            <div class="rp-info-row">
                <span class="rp-info-label">Contact</span>
                <span class="rp-info-value">{{ $cinema->cinema_contact ?? '—' }}</span>
            </div>
        </div>

        {{-- Movie --}}
        <div class="ac-card rp-info-card">
            <div class="ac-card__title rp-section-title">Movie</div>
            <div class="rp-movie-flex">
                @if (!empty($movie->portrait_poster))
                    <img src="{{ asset('images/movies/' . $movie->portrait_poster) }}"
                         alt="{{ $movie->movie_name }}" class="rp-movie-poster">
                @endif
                <div class="rp-movie-copy">
                    <div class="rp-info-row">
                        <span class="rp-info-label">Title</span>
                        <span class="rp-info-value rp-movie-title">{{ $movie->movie_name }}</span>
                    </div>
                    <div class="rp-info-row">
                        <span class="rp-info-label">Production</span>
                        <span class="rp-info-value">{{ $movie->production_name ?? '—' }}</span>
                    </div>
                    <div class="rp-info-row">
                        <span class="rp-info-label">Runtime</span>
                        <span class="rp-info-value">
                            @php $rt = $movie->runtime ?? 0; $h = intdiv($rt, 60); $m = $rt % 60; @endphp
                            {{ $h > 0 ? $h . 'h ' : '' }}{{ $m }}m
                        </span>
                    </div>
                    <div class="rp-info-row">
                        <span class="rp-info-label">Language</span>
                        <span class="rp-info-value">{{ $movie->language ?? '—' }}</span>
                    </div>
                    @if ($movie->genres->isNotEmpty())
                        <div class="rp-info-row">
                            <span class="rp-info-label">Genres</span>
                            <div class="rp-genre-wrap">
                                @foreach ($movie->genres as $genre)
                                    <span class="ac-badge">{{ $genre->genre_name }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Quota --}}
        @if ($quota)
            <div class="ac-card rp-info-card rp-quota-card">
                <div class="ac-card__title rp-section-title">Admin-Defined Quota</div>
                <div class="rp-quota-grid">
                    <div class="rp-info-row">
                        <span class="rp-info-label">Start Date</span>
                        <span class="rp-info-value">{{ $quota->start_date }}</span>
                    </div>
                    <div class="rp-info-row">
                        <span class="rp-info-label">Max End Date</span>
                        <span class="rp-info-value">{{ $quota->maximum_end_date }}</span>
                    </div>
                    <div class="rp-info-row">
                        <span class="rp-info-label">Slots / Day</span>
                        <span class="rp-info-value">{{ $quota->showtime_slots }}</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Admin note (if rejected) --}}
        @if ($status === 'rejected' && $proposal->admin_note)
            <div class="ac-card rp-info-card" style="border-color:rgba(240,91,91,0.2);">
                <div class="ac-card__title rp-section-title" style="color:var(--danger);">Rejection Note</div>
                <p style="color:#fff;font-size:0.875rem;line-height:1.6;margin:0;">{{ $proposal->admin_note }}</p>
            </div>
        @endif

    </div>{{-- /.rp-left --}}

    {{-- RIGHT: Theatre(s) + Schedule --}}
    <div class="rp-right">

        <div class="ac-card rp-info-card">

            {{-- Theatre selector tabs --}}
            @if ($theatreGroups->count() > 1)
                <div class="rp-theatre-tabs" id="rp-theatre-tabs">
                    @foreach ($theatreGroups as $tId => $tRows)
                        @php $tName = $tRows->first()?->theatre?->theatre_name ?? 'Theatre'; @endphp
                        <button type="button"
                                class="rp-theatre-tab {{ $loop->first ? 'rp-theatre-tab--active' : '' }}"
                                data-theatre-idx="{{ $loop->index }}">
                            🏟 {{ $tName }}
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Theatre info row --}}
            <div class="rp-theatre-info" id="rp-theatre-info">
                @php $tImg = $firstTheatre?->theatre_poster ?? $firstTheatre?->theatre_icon ?? null; @endphp
                <div class="rp-theatre-thumb" id="rp-theatre-thumb">
                    @if ($tImg)
                        <img src="{{ asset('images/theatres/' . $tImg) }}"
                             alt="{{ $firstTheatre?->theatre_name }}"
                             id="rp-theatre-img" class="rp-theatre-img">
                    @else
                        <div class="rp-theatre-ph" id="rp-theatre-ph">🏟</div>
                        <img id="rp-theatre-img" class="rp-theatre-img" style="display:none;" src="" alt="">
                    @endif
                </div>
                <div class="rp-theatre-meta">
                    <span class="rp-info-label">Theatre</span>
                    <span class="rp-theatre-name" id="rp-theatre-name">
                        {{ $firstTheatre?->theatre_name ?? '—' }}
                    </span>
                    <span class="rp-info-label" style="margin-top:8px;">Total Slots</span>
                    <span class="rp-info-value" id="rp-slot-count">
                        {{ $firstTheatreSlotCnt }} slot(s)
                    </span>
                </div>
            </div>

            <div class="rp-card-divider"></div>

            {{-- Schedule header --}}
            <div class="rp-schedule-header">
                <div class="ac-card__title rp-section-title" style="margin-bottom:0;">
                    Proposed Schedule
                </div>
                <button class="rp-maximize-btn" id="rp-maximize-btn" type="button">
                    ⊞ Full List
                </button>
            </div>

            {{-- Clock + Calendar --}}
            <div class="rp-schedule-visual">
                <div class="rp-showtime-panel">
                    <div class="rp-showtime-panel__top">
                        <div>
                            <span class="rp-info-label">Selected Date</span>
                            <div class="rp-showtime-date" id="rp-showtime-date">No date selected</div>
                        </div>
                        <span class="rp-showtime-count" id="rp-showtime-count">0 slots</span>
                    </div>
                    <ul class="rp-showtime-list" id="rp-showtime-list">
                        <li class="rp-showtime-item rp-showtime-item--empty">
                            Select a highlighted date to view proposed showtimes.
                        </li>
                    </ul>
                </div>
                <div class="rp-cal-wrap">
                    <div class="rp-cal-header">
                        <button type="button" class="rp-cal-nav" id="rp-cal-prev">‹</button>
                        <span class="rp-cal-month-label" id="rp-cal-month"></span>
                        <button type="button" class="rp-cal-nav" id="rp-cal-next">›</button>
                    </div>
                    <div class="rp-cal-weekdays">
                        <span>Su</span><span>Mo</span><span>Tu</span><span>We</span>
                        <span>Th</span><span>Fr</span><span>Sa</span>
                    </div>
                    <div class="rp-cal-grid" id="rp-cal-grid"></div>
                </div>
            </div>

        </div>{{-- /.ac-card --}}

    </div>{{-- /.rp-right --}}

</div>{{-- /.rp-layout --}}

{{-- ═══════════════ FULL LIST POPUP ═══════════════ --}}
<div class="rp-popup-overlay" id="rp-popup-overlay" style="display:none;">
    <div class="rp-popup">
        <div class="rp-popup-title">
            All Scheduled Slots — <span id="rp-popup-theatre-name">{{ $firstTheatre?->theatre_name }}</span>
            (<span id="rp-popup-count">{{ $firstTheatreSlotCnt }}</span>)
            <button type="button" class="rp-popup-close" id="rp-popup-close">✕</button>
        </div>
        <div class="rp-slots-list" id="rp-slots-list"></div>
    </div>
</div>

{{-- ═══════════════ ACTION BAR (not in PDF) ═══════════════ --}}
<div class="rp-action-bar" id="rp-action-bar">
    <a href="{{ route('manager.upcoming') }}" class="rp-btn rp-btn--ghost">← Back</a>
    @if ($status === 'rejected')
        <a href="{{ route('manager.setup.movie', $movie->movie_id) }}" class="rp-btn rp-btn--resubmit">
            Re-submit New Proposal
        </a>
    @endif
    <button type="button" id="rp-pdf-btn" class="rp-btn rp-btn--pdf">⬇&ensp;Download as PDF</button>
</div>

<span id="rp-movie-name" data-name="{{ $movie->movie_name }}" style="display:none;"></span>

@endsection

