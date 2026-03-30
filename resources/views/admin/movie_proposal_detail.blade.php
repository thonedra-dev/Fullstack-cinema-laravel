{{--
    resources/views/admin/movie_proposal_detail.blade.php
    Controller: AdminMovieProposalController@show / @approve
    Data:
      $first     – The representative ShowtimeProposal row (with ->manager, ->cinema, ->theatre, ->movie.genres)
      $groupRows – All ShowtimeProposal rows in this submission group (ordered by start_datetime)
      $city      – City model
      $quota     – cinema_movie_quotas row (object)
--}}
@extends('admin.admin_team')

@section('page_title', 'Proposal Detail')
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite(['resources/css/movie_proposals.css'])
@endsection

@section('content')

<a href="{{ route('admin.proposals.index') }}" class="vc-back-btn" style="margin-bottom:20px;display:inline-flex;">
    ← Back to Proposals
</a>

{{-- Status banner --}}
@php $groupStatus = $groupRows->contains('status', 'pending') ? 'pending' : 'approved'; @endphp

<div class="mpd-status-banner mpd-status-banner--{{ $groupStatus }}">
    <span class="mpd-status-icon">
        @if ($groupStatus === 'pending') 🕐 @else ✓ @endif
    </span>
    <span>
        <strong>{{ ucfirst($groupStatus) }}</strong>
        — Submitted by {{ $first->manager?->manager_name }}
        · {{ $first->created_at?->format('d M Y, h:i A') }}
    </span>
</div>

@if (session('error'))
    <div class="at-alert at-alert--error" style="margin-top:16px;">
        <span class="at-alert__icon">✕</span> {{ session('error') }}
    </div>
@endif

<div class="mpd-layout">

    {{-- ── LEFT: Cinema + Manager + Movie ──────── --}}
    <div class="mpd-left">

        {{-- Cinema --}}
        <div class="ac-card mpd-info-card">
            <div class="ac-card__title">Cinema</div>

            @if ($first->cinema?->cinema_picture)
                <img
                    src="{{ asset('images/cinemas/' . $first->cinema->cinema_picture) }}"
                    alt="{{ $first->cinema->cinema_name }}"
                    style="width:100%;aspect-ratio:16/7;object-fit:cover;border-radius:var(--radius-sm);margin-bottom:16px;"
                >
            @endif

            <div class="mpd-info-row">
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

        {{-- Movie --}}
        <div class="ac-card mpd-info-card">
            <div class="ac-card__title">Movie</div>

            <div style="display:flex;gap:14px;align-items:flex-start;">
                @if (!empty($first->movie?->portrait_poster))
                    <img
                        src="{{ asset('images/movies/' . $first->movie->portrait_poster) }}"
                        alt="{{ $first->movie->movie_name }}"
                        style="width:70px;aspect-ratio:2/3;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--border);flex-shrink:0;"
                    >
                @endif
                <div style="flex:1;min-width:0;">
                    <div class="mpd-info-row">
                        <span class="mpd-info-label">Title</span>
                        <span class="mpd-info-value" style="font-weight:700;font-size:1rem;">
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
                                $h  = intdiv($rt, 60); $m = $rt % 60;
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
                            <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:2px;">
                                @foreach ($first->movie->genres as $genre)
                                    <span class="ac-badge">{{ $genre->genre_name }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Manager --}}
        <div class="ac-card mpd-info-card">
            <div class="ac-card__title">Branch Manager</div>
            <div class="mpd-info-row">
                <span class="mpd-info-label">Name</span>
                <span class="mpd-info-value">{{ $first->manager?->manager_name ?? '—' }}</span>
            </div>
            <div class="mpd-info-row">
                <span class="mpd-info-label">Email</span>
                <span class="mpd-info-value" style="font-family:monospace;font-size:0.82rem;">
                    {{ $first->manager?->manager_email ?? '—' }}
                </span>
            </div>
        </div>

    </div>{{-- /.mpd-left --}}

    {{-- ── RIGHT: Theatre + Scheduled slots + Quota + Approve ── --}}
    <div class="mpd-right">

        {{-- Theatre --}}
        <div class="ac-card mpd-info-card">
            <div class="ac-card__title">Theatre</div>
            <div style="display:flex;gap:14px;align-items:center;">
                @php $tImg = $first->theatre?->theatre_poster ?? $first->theatre?->theatre_icon ?? null; @endphp
                @if ($tImg)
                    <img src="{{ asset('images/theatres/' . $tImg) }}"
                         alt="{{ $first->theatre?->theatre_name }}"
                         style="width:90px;aspect-ratio:16/9;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--border);flex-shrink:0;">
                @else
                    <div style="width:90px;aspect-ratio:16/9;background:var(--bg-surface);display:flex;align-items:center;justify-content:center;font-size:1.6rem;border-radius:var(--radius-sm);border:1px solid var(--border);flex-shrink:0;">🏟</div>
                @endif
                <p style="font-family:var(--font-heading);font-size:0.95rem;font-weight:700;color:var(--text-primary);">
                    {{ $first->theatre?->theatre_name ?? '—' }}
                </p>
            </div>
        </div>

        {{-- Proposed schedule — one row per date slot ── --}}
        <div class="ac-card mpd-info-card">
            <div class="ac-card__title">
                Proposed Schedule
                <span style="font-size:0.72rem;font-weight:500;color:var(--text-muted);margin-left:8px;">
                    {{ $groupRows->count() }} slot(s)
                </span>
            </div>

            <div class="mpd-slots-list">
                @foreach ($groupRows as $row)
                    <div class="mpd-slot-row">
                        <span class="mpd-slot-date">
                            {{ $row->start_datetime?->format('D, d M Y') }}
                        </span>
                        <span class="mpd-slot-time">
                            {{ $row->start_datetime?->format('h:i A') }}
                            →
                            {{ $row->end_datetime?->format('h:i A') }}
                        </span>
                        <span class="mp-status-badge mp-status-badge--{{ $row->status }}">
                            {{ ucfirst($row->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Admin quota reminder --}}
        @if ($quota)
        <div class="ac-card mpd-info-card mpd-quota-reminder">
            <div class="ac-card__title">Admin-Defined Quota (Reminder)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
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

        {{-- Approve action --}}
        @if ($groupStatus === 'pending')
            <div class="ac-card mpd-approve-card">
                <div class="ac-card__title">Decision</div>
                <p style="font-size:0.875rem;color:var(--text-secondary);margin-bottom:20px;line-height:1.6;">
                    Approving will create <strong>{{ $groupRows->count() }}</strong> showtime(s)
                    in <strong>{{ $first->theatre?->theatre_name }}</strong> and make this movie
                    visible in the admin cinema view and branch manager resources.
                </p>
                <form
                    action="{{ route('admin.proposals.approve', $first->id) }}"
                    method="POST"
                    onsubmit="return confirm('Approve this proposal and create all showtimes?')"
                >
                    @csrf
                    <button type="submit" class="ac-btn ac-btn--primary mpd-approve-btn">
                        ✓ Approve Proposal
                    </button>
                </form>
            </div>
        @endif

    </div>{{-- /.mpd-right --}}

</div>{{-- /.mpd-layout --}}

<style>
/* Slot list in detail page */
.mpd-slots-list { display: flex; flex-direction: column; gap: 8px; }

.mpd-slot-row {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px;
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    flex-wrap: wrap;
}

.mpd-slot-date {
    font-family: var(--font-heading); font-size: 0.82rem;
    font-weight: 700; color: var(--text-primary);
    flex: 1; min-width: 140px;
}

.mpd-slot-time {
    font-family: monospace; font-size: 0.82rem;
    color: var(--accent-light); font-weight: 600;
    white-space: nowrap;
}
</style>

@endsection