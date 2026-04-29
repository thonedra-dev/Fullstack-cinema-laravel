{{--
    resources/views/admin/movie_proposal_detail.blade.php
    ────────────────────────────────────────────────────────
    Controller : AdminMovieProposalController@show / @approve / @reject
    Data:
      $first     – ShowtimeProposalStatus (manager, cinema, movie.genres)
      $groupRows – ShowtimeProposal collection (theatre, ordered by start_datetime)
      $city      – City model
      $quota     – cinema_movie_quotas row (or null)
--}}
@extends('admin.admin_team')

@section('page_title', 'Proposal Detail')
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite(['resources/css/movie_proposals.css', 'resources/js/movie_proposals.js'])
@endsection

@section('content')

{{-- ── Back link ─────────────────────────────────────────── --}}
<a href="{{ route('admin.proposals.index') }}" class="vc-back-btn"
   style="margin-bottom:20px;display:inline-flex;">
    ← Back to Proposals
</a>

{{-- ── Status banner ─────────────────────────────────────── --}}
@php $status = $first->status; @endphp

<div class="mpd-status-banner mpd-status-banner--{{ $status }}">
    <span class="mpd-status-icon">
        @if ($status === 'pending') 🕐 @elseif ($status === 'approved') ✓ @else ✕ @endif
    </span>
    <span>
        <strong>{{ ucfirst($status) }}</strong>
        — Submitted by {{ $first->manager?->manager_name }}
        · {{ $first->created_at?->format('d M Y, h:i A') }}
    </span>
</div>

{{-- ── Session alerts ────────────────────────────────────── --}}
@if (session('error'))
    <div class="at-alert at-alert--error" style="margin-top:16px;">
        <span class="at-alert__icon">✕</span> {{ session('error') }}
    </div>
@endif

{{-- ── Pre-compute theatre groups + JSON payload ────────── --}}
@php
    $theatreGroups = $groupRows->groupBy('theatre_id');

    $theatresJson = $theatreGroups->map(function ($rows, $theatreId) {
        $theatre = $rows->first()?->theatre;
        return [
            'theatreId'     => $theatreId,
            'theatreName'   => $theatre?->theatre_name ?? 'Theatre',
            'theatrePoster' => $theatre?->theatre_poster
                                ? asset('images/theatres/' . $theatre->theatre_poster)
                                : null,
            'slots' => $rows->map(fn ($r) => [
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

    // First theatre for server-side render defaults
    $firstTheatreRows    = $theatreGroups->first() ?? collect();
    $firstTheatre        = $firstTheatreRows->first()?->theatre;
    $firstTheatreSlotCnt = $firstTheatreRows->count();
@endphp

{{-- Hidden data bridge for JS --}}
<div id="mpd-slot-data" style="display:none;"
     data-theatres='{!! json_encode($theatresJson) !!}'></div>

{{-- ── Two-column layout ──────────────────────────────────── --}}
<div class="mpd-layout">

    {{-- ══════════════════════════════════════════════
         LEFT: Cinema · Movie · Branch Manager
    ══════════════════════════════════════════════ --}}
    <div class="mpd-left">

        {{-- Cinema ─────────────────────────────────── --}}
        <div class="ac-card mpd-info-card">
            <div class="ac-card__title mpd-section-title">Cinema</div>

            @if ($first->cinema?->cinema_picture)
                <img src="{{ asset('images/cinemas/' . $first->cinema->cinema_picture) }}"
                     alt="{{ $first->cinema->cinema_name }}"
                     class="mpd-cinema-banner">
            @endif

            <div class="mpd-info-row mpd-row-highlight">
                <span class="mpd-info-label">Cinema Name</span>
                <span class="mpd-info-value">{{ $first->cinema?->cinema_name ?? '—' }}</span>
            </div>
            <div class="mpd-info-row">
                <span class="mpd-info-label">Address</span>
                <span class="mpd-info-value">{{ $first->cinema?->cinema_address ?? '—' }}</span>
            </div>
            <div class="mpd-info-row">
                <span class="mpd-info-label">Contact</span>
                <span class="mpd-info-value">{{ $first->cinema?->cinema_contact ?? '—' }}</span>
            </div>
            <div class="mpd-info-row">
                <span class="mpd-info-label">City</span>
                <span class="mpd-info-value">{{ $city?->city_name ?? '—' }}</span>
            </div>
            <div class="mpd-info-row">
                <span class="mpd-info-label">State</span>
                <span class="mpd-info-value">
                    <span class="ac-badge">{{ $city?->city_state ?? '—' }}</span>
                </span>
            </div>
        </div>

        {{-- Movie ──────────────────────────────────── --}}
        <div class="ac-card mpd-info-card">
            <div class="ac-card__title mpd-section-title">Movie</div>
            <div class="mpd-movie-flex">
                @if (!empty($first->movie?->portrait_poster))
                    <img src="{{ asset('images/movies/' . $first->movie->portrait_poster) }}"
                         alt="{{ $first->movie->movie_name }}"
                         class="mpd-movie-poster">
                @endif
                <div class="mpd-movie-copy">
                    <div class="mpd-info-row">
                        <span class="mpd-info-label">Title</span>
                        <span class="mpd-info-value mpd-movie-title mpd-highlight">
                            {{ $first->movie?->movie_name ?? '—' }}
                        </span>
                    </div>
                    <div class="mpd-info-row">
                        <span class="mpd-info-label">Production</span>
                        <span class="mpd-info-value">{{ $first->movie?->production_name ?? '—' }}</span>
                    </div>
                    <div class="mpd-info-row">
                        <span class="mpd-info-label">Runtime</span>
                        <span class="mpd-info-value">
                            @php
                                $rt = $first->movie?->runtime ?? 0;
                                $h  = intdiv($rt, 60);
                                $m  = $rt % 60;
                            @endphp
                            {{ $h > 0 ? $h . 'h ' : '' }}{{ $m }}m
                        </span>
                    </div>
                    <div class="mpd-info-row">
                        <span class="mpd-info-label">Language</span>
                        <span class="mpd-info-value">{{ $first->movie?->language ?? '—' }}</span>
                    </div>
                    @if ($first->movie?->genres->isNotEmpty())
                        <div class="mpd-info-row">
                            <span class="mpd-info-label">Genres</span>
                            <div class="mpd-genre-wrap">
                                @foreach ($first->movie->genres as $genre)
                                    <span class="ac-badge">{{ $genre->genre_name }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Branch Manager ──────────────────────────── --}}
        <div class="ac-card mpd-info-card mpd-manager-card">
            <div class="ac-card__title mpd-section-title">Branch Manager</div>
            <div class="mpd-info-row">
                <span class="mpd-info-label">Name</span>
                <span class="mpd-info-value">{{ $first->manager?->manager_name ?? '—' }}</span>
            </div>
            <div class="mpd-info-row">
                <span class="mpd-info-label">Email</span>
                <span class="mpd-info-value mpd-email">
                    {{ $first->manager?->manager_email ?? '—' }}
                </span>
            </div>
        </div>

    </div>{{-- /.mpd-left --}}

    {{-- ══════════════════════════════════════════════
         RIGHT: Theatre(s) + Schedule + Quota + Actions
    ══════════════════════════════════════════════ --}}
    <div class="mpd-right">

        {{-- ── Theatre + Schedule (combined) ──────────────── --}}
        <div class="ac-card mpd-info-card">

            {{-- Theatre selector tabs (hidden if only one) ── --}}
            @if ($theatreGroups->count() > 1)
                <div class="mpd-theatre-tabs" id="mpd-theatre-tabs">
                    @foreach ($theatreGroups as $tId => $tRows)
                        @php $tName = $tRows->first()?->theatre?->theatre_name ?? 'Theatre'; @endphp
                        <button type="button"
                                class="mpd-theatre-tab {{ $loop->first ? 'mpd-theatre-tab--active' : '' }}"
                                data-theatre-idx="{{ $loop->index }}">
                            🏟 {{ $tName }}
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Theatre info row ── --}}
            <div class="mpd-theatre-info" id="mpd-theatre-info">
                @php $tImg = $firstTheatre?->theatre_poster ?? $firstTheatre?->theatre_icon ?? null; @endphp

                <div class="mpd-theatre-thumb" id="mpd-theatre-thumb">
                    @if ($tImg)
                        <img src="{{ asset('images/theatres/' . $tImg) }}"
                             alt="{{ $firstTheatre?->theatre_name }}"
                             id="mpd-theatre-img"
                             class="mpd-theatre-img">
                    @else
                        <div class="mpd-theatre-ph" id="mpd-theatre-ph">🏟</div>
                        <img id="mpd-theatre-img" class="mpd-theatre-img" style="display:none;" src="" alt="">
                    @endif
                </div>

                <div class="mpd-theatre-meta">
                    <span class="mpd-info-label">Theatre</span>
                    <span class="mpd-theatre-name" id="mpd-theatre-name">
                        {{ $firstTheatre?->theatre_name ?? '—' }}
                    </span>
                    <span class="mpd-info-label" style="margin-top:8px;">Total Slots</span>
                    <span class="mpd-info-value" id="mpd-slot-count">
                        {{ $firstTheatreSlotCnt }} slot(s)
                    </span>
                </div>
            </div>

            <div class="mpd-card-divider"></div>

            {{-- Schedule header ── --}}
            <div class="mpd-schedule-header">
                <div class="ac-card__title mpd-section-title" style="margin-bottom:0;">
                    Proposed Schedule
                </div>
                <button class="mpd-maximize-btn" id="mpd-maximize-btn" type="button">
                    ⊞ Full List
                </button>
            </div>

            {{-- Clock + Calendar ── --}}
            <div class="mpd-schedule-visual">

                {{-- Static alarm clock --}}
                <div class="mpd-showtime-panel">
                    <div class="mpd-showtime-panel__top">
                        <div>
                            <span class="mpd-info-label">Selected Date</span>
                            <div class="mpd-showtime-date" id="mpd-showtime-date">No date selected</div>
                        </div>
                        <span class="mpd-showtime-count" id="mpd-showtime-count">0 slots</span>
                    </div>
                    <ul class="mpd-showtime-list" id="mpd-showtime-list">
                        <li class="mpd-showtime-item mpd-showtime-item--empty">
                            Select a highlighted date to view proposed showtimes.
                        </li>
                    </ul>
                </div>

                <div class="mpd-clock-wrap" style="display:none;">
                    <div class="mpd-clock">
                        <div class="mpd-clock__digit" id="mpd-clock-h">--</div>
                        <div class="mpd-clock__sep">:</div>
                        <div class="mpd-clock__digit" id="mpd-clock-m">--</div>
                        <div class="mpd-clock__sep">:</div>
                        <div class="mpd-clock__digit mpd-clock__digit--ampm" id="mpd-clock-ampm">--</div>
                    </div>
                    <div class="mpd-clock-end">End: <span id="mpd-clock-end">—</span></div>
                </div>

                {{-- Mini calendar --}}
                <div class="mpd-cal-wrap">
                    <div class="mpd-cal-header">
                        <button type="button" class="mpd-cal-nav" id="mpd-cal-prev">‹</button>
                        <span class="mpd-cal-month-label" id="mpd-cal-month"></span>
                        <button type="button" class="mpd-cal-nav" id="mpd-cal-next">›</button>
                    </div>
                    <div class="mpd-cal-weekdays">
                        <span>Su</span><span>Mo</span><span>Tu</span><span>We</span>
                        <span>Th</span><span>Fr</span><span>Sa</span>
                    </div>
                    <div class="mpd-cal-grid" id="mpd-cal-grid"></div>
                </div>

            </div>{{-- /.mpd-schedule-visual --}}
        </div>{{-- /.ac-card (theatre + schedule) --}}

        {{-- ── Admin-Defined Quota ──────────────────────────── --}}
        @if ($quota)
            <div class="ac-card mpd-info-card mpd-quota-reminder">
                <div class="ac-card__title mpd-section-title">Admin-Defined Quota</div>
                <div class="mpd-quota-grid">
                    <div class="mpd-info-row">
                        <span class="mpd-info-label">Start Date</span>
                        <span class="mpd-info-value">{{ $quota->start_date }}</span>
                    </div>
                    <div class="mpd-info-row">
                        <span class="mpd-info-label">Max End Date</span>
                        <span class="mpd-info-value">{{ $quota->maximum_end_date }}</span>
                    </div>
                    <div class="mpd-info-row">
                        <span class="mpd-info-label">Slots / Day</span>
                        <span class="mpd-info-value">{{ $quota->showtime_slots }}</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Decision panel ─────────────────────────────── --}}
        @if ($status === 'pending')

            <div class="ac-card mpd-approve-card">
                <div class="ac-card__title">Decision</div>
                <p class="mpd-decision-text">
                    Approving will create
                    <strong>{{ $groupRows->count() }}</strong> showtime(s) across
                    <strong>{{ $theatreGroups->count() }}</strong> theatre(s) in
                    <strong>{{ $first->cinema?->cinema_name }}</strong>.
                </p>
                <div class="mpd-decision-actions">
                    <form action="{{ route('admin.proposals.approve', $first->id) }}" method="POST"
                          onsubmit="return confirm('Approve and create all showtimes?')">
                        @csrf
                        <button type="submit" class="ac-btn ac-btn--primary mpd-approve-btn">
                            ✓ Approve Proposal
                        </button>
                    </form>
                    <button type="button" class="mpd-reject-trigger-btn" id="mpd-reject-trigger">
                        ✕ Reject with Note
                    </button>
                </div>
            </div>

        @elseif ($status === 'approved')

            <div class="ac-card" style="border-color:rgba(34,197,94,0.2);background:rgba(34,197,94,0.03);">
                <div class="ac-card__title" style="color:#22c55e;">✓ Approved</div>
                <p class="mpd-decision-text">
                    All {{ $groupRows->count() }} showtime(s) have been added to the schedule.
                </p>
            </div>

        @elseif ($status === 'rejected')

            @if ($first->admin_note)
                <div class="ac-card" style="border-color:rgba(240,91,91,0.2);">
                    <div class="ac-card__title" style="color:var(--danger);">Rejection Note</div>
                    <p class="mpd-decision-text">{{ $first->admin_note }}</p>
                </div>
            @endif

        @endif

    </div>{{-- /.mpd-right --}}

</div>{{-- /.mpd-layout --}}

{{-- ══════════════════════════════════════════════════════════
     FULL LIST POPUP
══════════════════════════════════════════════════════════ --}}
<div class="mpd-popup-overlay" id="mpd-popup-overlay" style="display:none;">
    <div class="mpd-popup">
        <div class="mpd-popup-title">
            All Scheduled Slots — <span id="mpd-popup-theatre-name">{{ $firstTheatre?->theatre_name }}</span>
            (<span id="mpd-popup-count">{{ $firstTheatreSlotCnt }}</span>)
            <button type="button" class="mpd-popup-close" id="mpd-popup-close">✕</button>
        </div>
        <div class="mpd-slots-list" id="mpd-slots-list">
            {{-- Populated by JS on load / theatre switch --}}
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     REJECT MODAL
══════════════════════════════════════════════════════════ --}}
<div class="mpd-reject-overlay" id="mpd-reject-overlay" style="display:none;">
    <div class="mpd-reject-modal">
        <div class="mpd-reject-modal__title">✕ Reject Proposal</div>
        <p class="mpd-reject-modal__sub">
            Provide a reason or feedback for the branch manager.
        </p>
        <form action="{{ route('admin.proposals.reject', $first->id) }}" method="POST">
            @csrf
            <textarea name="admin_note"
                      class="mpd-reject-textarea"
                      placeholder="e.g. Schedule conflicts with peak hours. Please resubmit for weekdays only."
                      required></textarea>
            <button type="submit" class="mpd-reject-btn">✕ Confirm Rejection</button>
        </form>
        <button type="button" id="mpd-reject-cancel" class="mpd-reject-cancel-btn">
            Cancel
        </button>
    </div>
</div>

@endsection
