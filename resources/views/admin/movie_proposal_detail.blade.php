{{--
    resources/views/admin/movie_proposal_detail.blade.php
    Controller: AdminMovieProposalController@show / @approve / @reject
    Data:
      $first     – Representative ShowtimeProposal row
      $groupRows – All rows in submission group (ordered by start_datetime)
      $city      – City model
      $quota     – cinema_movie_quotas row
--}}
@extends('admin.admin_team')

@section('page_title', 'Proposal Detail')
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite(['resources/css/movie_proposals.css'])
    <style>
    /* ── Calendar + Clock side-by-side ── */
    .mpd-schedule-visual {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 24px;
        align-items: start;
        margin-bottom: 4px;
    }

    /* Static alarm clock */
    .mpd-clock {
        display: inline-flex;
        align-items: center;
        gap: 0;
        background: #111318;
        border: 1px solid #2a2d3a;
        border-radius: 10px;
        padding: 10px 16px;
        box-shadow: inset 0 2px 8px rgba(0,0,0,0.4), 0 4px 16px rgba(0,0,0,0.3);
        flex-shrink: 0;
    }

    .mpd-clock__digit {
        font-family: 'Courier New', monospace;
        font-size: 1.8rem; font-weight: 700;
        color: #e8eaf6;
        background: #1a1d27;
        border: 1px solid #2a2d3a;
        border-radius: 6px;
        padding: 4px 12px;
        min-width: 60px;
        text-align: center;
        letter-spacing: 0.04em;
        line-height: 1;
        transition: color 0.2s ease;
    }

    .mpd-clock__digit--ampm {
        min-width: 48px;
        font-size: 1.3rem;
        color: var(--accent-light);
    }

    .mpd-clock__sep {
        font-size: 1.5rem; font-weight: 700;
        color: var(--text-muted); padding: 0 4px;
        line-height: 1;
    }

    .mpd-clock-end {
        font-size: 0.75rem; color: var(--text-muted);
        margin-top: 6px; text-align: center;
    }

    .mpd-clock-end span { color: var(--accent-light); font-weight: 600; }

    .mpd-clock-wrap { display: flex; flex-direction: column; align-items: center; gap: 6px; }

    /* Mini calendar */
    .mpd-cal-wrap { flex: 1; }

    .mpd-cal-header {
        display: flex; align-items: center;
        justify-content: space-between; margin-bottom: 10px;
    }

    .mpd-cal-month-label {
        font-family: var(--font-heading); font-size: 0.82rem;
        font-weight: 700; color: var(--text-primary);
    }

    .mpd-cal-nav {
        background: none; border: 1px solid var(--border);
        border-radius: var(--radius-sm); color: var(--text-sub);
        font-size: 0.9rem; padding: 1px 8px; cursor: pointer;
        transition: border-color 0.2s, color 0.2s; line-height: 1.4;
    }

    .mpd-cal-nav:hover { border-color: var(--accent); color: var(--accent-light); }

    .mpd-cal-weekdays {
        display: grid; grid-template-columns: repeat(7, 1fr);
        gap: 2px; margin-bottom: 4px;
    }

    .mpd-cal-weekdays span {
        font-size: 0.6rem; font-weight: 700; text-align: center;
        color: var(--text-muted); text-transform: uppercase;
        letter-spacing: 0.04em; padding: 3px 0;
    }

    .mpd-cal-grid {
        display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px;
    }

    .mpd-cal-day {
        aspect-ratio: 1;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.72rem; font-weight: 500;
        border-radius: 50%; cursor: default;
        color: var(--text-muted);
        border: 2px solid transparent;
        user-select: none;
        transition: border-color 0.15s, background 0.15s, color 0.15s;
    }

    .mpd-cal-day--has-slot {
        color: #fff;
        background: rgba(245, 130, 30, 0.15);
        border-color: #f5821e;
        cursor: pointer;
    }

    .mpd-cal-day--has-slot:hover,
    .mpd-cal-day--active {
        background: #f5821e;
        color: #000;
        font-weight: 700;
    }

    .mpd-cal-day--empty { cursor: default; }

    /* Maximize icon + popup */
    .mpd-schedule-header {
        display: flex; align-items: center;
        justify-content: space-between; margin-bottom: 14px;
    }

    .mpd-maximize-btn {
        background: none; border: 1px solid var(--border);
        border-radius: var(--radius-sm); color: var(--text-muted);
        font-size: 0.72rem; font-weight: 600;
        padding: 4px 10px; cursor: pointer;
        display: inline-flex; align-items: center; gap: 5px;
        transition: border-color 0.2s, color 0.2s;
    }

    .mpd-maximize-btn:hover { border-color: var(--accent); color: var(--accent-light); }

    /* Popup overlay */
    .mpd-popup-overlay {
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.65);
        backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center;
        z-index: 600; padding: 20px;
    }

    .mpd-popup {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        box-shadow: 0 24px 64px rgba(0,0,0,0.6);
        width: 100%; max-width: 560px;
        max-height: 80vh; overflow-y: auto;
        padding: 28px;
        animation: mpd-popup-in 0.2s ease;
    }

    @keyframes mpd-popup-in {
        from { opacity: 0; transform: scale(0.95) translateY(8px); }
        to   { opacity: 1; transform: scale(1)    translateY(0);   }
    }

    .mpd-popup-title {
        font-family: var(--font-heading); font-size: 0.95rem;
        font-weight: 700; color: var(--text-primary);
        margin-bottom: 16px; padding-bottom: 12px;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; justify-content: space-between;
    }

    .mpd-popup-close {
        background: none; border: none;
        color: var(--text-muted); font-size: 1rem;
        cursor: pointer; padding: 2px 6px;
        transition: color 0.2s;
    }

    .mpd-popup-close:hover { color: var(--danger); }

    /* Reject modal */
    .mpd-reject-overlay {
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.65); backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center;
        z-index: 600; padding: 20px;
    }

    .mpd-reject-modal {
        background: var(--bg-card); border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        box-shadow: 0 24px 64px rgba(0,0,0,0.6);
        width: 100%; max-width: 440px; padding: 28px;
        animation: mpd-popup-in 0.2s ease;
    }

    .mpd-reject-modal__title {
        font-family: var(--font-heading); font-size: 1rem;
        font-weight: 700; color: var(--text-primary);
        margin-bottom: 16px;
    }

    .mpd-reject-modal textarea {
        width: 100%; background: var(--bg-surface);
        border: 1px solid var(--border); border-radius: var(--radius-sm);
        color: var(--text-primary); font-family: var(--font-ui);
        font-size: 0.875rem; padding: 10px 14px;
        resize: vertical; min-height: 100px; outline: none;
        transition: border-color 0.2s;
    }

    .mpd-reject-modal textarea:focus { border-color: var(--danger); }

    .mpd-reject-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 24px; border-radius: var(--radius-sm);
        font-family: var(--font-heading); font-size: 0.875rem;
        font-weight: 700; cursor: pointer; border: none;
        background: var(--danger); color: #fff;
        transition: background 0.2s; width: 100%;
        justify-content: center; margin-top: 14px;
    }

    .mpd-reject-btn:hover { background: #d94f4f; }

    @media (max-width: 700px) {
        .mpd-schedule-visual { grid-template-columns: 1fr; }
    }
    </style>
@endsection

@section('content')

<a href="{{ route('admin.proposals.index') }}" class="vc-back-btn" style="margin-bottom:20px;display:inline-flex;">
    ← Back to Proposals
</a>

@php $groupStatus = $groupRows->contains('status', 'pending') ? 'pending' :
     ($groupRows->contains('status', 'approved') ? 'approved' : 'rejected'); @endphp

<div class="mpd-status-banner mpd-status-banner--{{ $groupStatus }}">
    <span class="mpd-status-icon">
        @if ($groupStatus === 'pending') 🕐 @elseif ($groupStatus === 'approved') ✓ @else ✕ @endif
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

    {{-- ── LEFT: Cinema + Manager + Movie ─────────── --}}
    <div class="mpd-left">

        <div class="ac-card mpd-info-card">
            <div class="ac-card__title">Cinema</div>
            @if ($first->cinema?->cinema_picture)
                <img src="{{ asset('images/cinemas/' . $first->cinema->cinema_picture) }}"
                     alt="{{ $first->cinema->cinema_name }}"
                     style="width:100%;aspect-ratio:16/7;object-fit:cover;border-radius:var(--radius-sm);margin-bottom:16px;">
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
                <span class="mpd-info-value"><span class="ac-badge">{{ $city?->city_state ?? '—' }}</span></span>
            </div>
        </div>

        <div class="ac-card mpd-info-card">
            <div class="ac-card__title">Movie</div>
            <div style="display:flex;gap:14px;align-items:flex-start;">
                @if (!empty($first->movie?->portrait_poster))
                    <img src="{{ asset('images/movies/' . $first->movie->portrait_poster) }}"
                         alt="{{ $first->movie->movie_name }}"
                         style="width:70px;aspect-ratio:2/3;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--border);flex-shrink:0;">
                @endif
                <div style="flex:1;min-width:0;">
                    <div class="mpd-info-row">
                        <span class="mpd-info-label">Title</span>
                        <span class="mpd-info-value" style="font-weight:700;font-size:1rem;">{{ $first->movie?->movie_name ?? '—' }}</span>
                    </div>
                    <div class="mpd-info-row">
                        <span class="mpd-info-label">Production</span>
                        <span class="mpd-info-value">{{ $first->movie?->production_name ?? '—' }}</span>
                    </div>
                    <div class="mpd-info-row">
                        <span class="mpd-info-label">Runtime</span>
                        <span class="mpd-info-value">
                            @php $rt = $first->movie?->runtime ?? 0; $h = intdiv($rt,60); $m = $rt%60; @endphp
                            {{ $h > 0 ? $h.'h ' : '' }}{{ $m }}m
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

        <div class="ac-card mpd-info-card">
            <div class="ac-card__title">Branch Manager</div>
            <div class="mpd-info-row">
                <span class="mpd-info-label">Name</span>
                <span class="mpd-info-value">{{ $first->manager?->manager_name ?? '—' }}</span>
            </div>
            <div class="mpd-info-row">
                <span class="mpd-info-label">Email</span>
                <span class="mpd-info-value" style="font-family:monospace;font-size:0.82rem;">{{ $first->manager?->manager_email ?? '—' }}</span>
            </div>
        </div>

    </div>

    {{-- ── RIGHT: Theatre + Schedule (clock+cal) + Quota + Actions ── --}}
    <div class="mpd-right">

        <div class="ac-card mpd-info-card">
            <div class="ac-card__title">Theatre</div>
            <div style="display:flex;gap:14px;align-items:center;">
                @php $tImg = $first->theatre?->theatre_poster ?? $first->theatre?->theatre_icon ?? null; @endphp
                @if ($tImg)
                    <img src="{{ asset('images/theatres/' . $tImg) }}"
                         style="width:90px;aspect-ratio:16/9;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--border);flex-shrink:0;">
                @else
                    <div style="width:90px;aspect-ratio:16/9;background:var(--bg-surface);display:flex;align-items:center;justify-content:center;font-size:1.6rem;border-radius:var(--radius-sm);border:1px solid var(--border);flex-shrink:0;">🏟</div>
                @endif
                <p style="font-family:var(--font-heading);font-size:0.95rem;font-weight:700;color:var(--text-primary);">
                    {{ $first->theatre?->theatre_name ?? '—' }}
                </p>
            </div>
        </div>

        {{-- ── Schedule: clock + calendar side by side ── --}}
        <div class="ac-card mpd-info-card">
            <div class="mpd-schedule-header">
                <div class="ac-card__title" style="margin-bottom:0;">
                    Proposed Schedule
                    <span style="font-size:0.72rem;font-weight:500;color:var(--text-muted);margin-left:8px;">
                        {{ $groupRows->count() }} slot(s)
                    </span>
                </div>
                <button class="mpd-maximize-btn" id="mpd-maximize-btn" type="button">
                    ⊞ Full List
                </button>
            </div>

            {{-- Slot data for JS --}}
            <div id="mpd-slot-data" style="display:none;"
                 data-slots='{!! json_encode($groupRows->map(fn($r) => [
                     "date"  => $r->start_datetime?->format("Y-m-d"),
                     "start" => $r->start_datetime?->format("h:i A"),
                     "end"   => $r->end_datetime?->format("h:i A"),
                     "start_h" => (int) $r->start_datetime?->format("h"),
                     "start_m" => (int) $r->start_datetime?->format("i"),
                     "start_ampm" => $r->start_datetime?->format("A"),
                     "end_display" => $r->end_datetime?->format("h:i A"),
                 ])->values()) !!}'>
            </div>

            <div class="mpd-schedule-visual">

                {{-- Static Clock --}}
                <div class="mpd-clock-wrap">
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
        </div>

        {{-- Quota reminder --}}
        @if ($quota)
        <div class="ac-card mpd-info-card mpd-quota-reminder">
            <div class="ac-card__title">Admin-Defined Quota</div>
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

        {{-- Approve + Reject actions (pending only) --}}
        @if ($groupStatus === 'pending')
            <div class="ac-card mpd-approve-card">
                <div class="ac-card__title">Decision</div>
                <p style="font-size:0.875rem;color:var(--text-secondary);margin-bottom:20px;line-height:1.6;">
                    Approving creates <strong>{{ $groupRows->count() }}</strong> showtime(s) in
                    <strong>{{ $first->theatre?->theatre_name }}</strong>.
                </p>
                <div style="display:flex;flex-direction:column;gap:10px;">
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
        @elseif ($groupStatus === 'rejected')
            {{-- Show admin note if rejected --}}
            @php $note = $groupRows->first()?->admin_note; @endphp
            @if ($note)
            <div class="ac-card mpd-info-card" style="border-color:rgba(240,91,91,0.2);">
                <div class="ac-card__title" style="color:var(--danger);">Rejection Note</div>
                <p style="font-size:0.875rem;color:var(--text-secondary);line-height:1.6;">{{ $note }}</p>
            </div>
            @endif
        @endif

    </div>

</div>

{{-- ── Full List Popup ────────────────────────────────── --}}
<div class="mpd-popup-overlay" id="mpd-popup-overlay" style="display:none;">
    <div class="mpd-popup">
        <div class="mpd-popup-title">
            All Scheduled Slots ({{ $groupRows->count() }})
            <button type="button" class="mpd-popup-close" id="mpd-popup-close">✕</button>
        </div>
        <div class="mpd-slots-list">
            @foreach ($groupRows as $row)
                <div class="mpd-slot-row">
                    <span class="mpd-slot-date">{{ $row->start_datetime?->format('D, d M Y') }}</span>
                    <span class="mpd-slot-time">
                        {{ $row->start_datetime?->format('h:i A') }} → {{ $row->end_datetime?->format('h:i A') }}
                    </span>
                    <span class="mp-status-badge mp-status-badge--{{ $row->status }}">{{ ucfirst($row->status) }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Reject Modal ───────────────────────────────────── --}}
<div class="mpd-reject-overlay" id="mpd-reject-overlay" style="display:none;">
    <div class="mpd-reject-modal">
        <div class="mpd-reject-modal__title">✕ Reject Proposal</div>
        <p style="font-size:0.82rem;color:var(--text-secondary);margin-bottom:14px;line-height:1.5;">
            Provide a reason or feedback for the branch manager.
        </p>
        <form action="{{ route('admin.proposals.reject', $first->id) }}" method="POST">
            @csrf
            <textarea name="admin_note" placeholder="e.g. Schedule conflicts with peak hours. Please resubmit for weekdays only." required></textarea>
            <button type="submit" class="mpd-reject-btn">✕ Confirm Rejection</button>
        </form>
        <button type="button" id="mpd-reject-cancel"
                style="width:100%;margin-top:8px;background:none;border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text-muted);font-family:var(--font-heading);font-size:0.82rem;font-weight:600;padding:8px;cursor:pointer;">
            Cancel
        </button>
    </div>
</div>

<style>
.mpd-reject-trigger-btn {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 8px; padding: 10px 24px;
    border-radius: var(--radius-sm); font-family: var(--font-heading);
    font-size: 0.875rem; font-weight: 700; cursor: pointer;
    background: rgba(240,91,91,0.1); border: 1px solid rgba(240,91,91,0.35);
    color: var(--danger); width: 100%;
    transition: background 0.2s, border-color 0.2s;
}
.mpd-reject-trigger-btn:hover { background: rgba(240,91,91,0.2); }

.mpd-slots-list { display: flex; flex-direction: column; gap: 8px; }
.mpd-slot-row {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px; background: var(--bg-surface);
    border: 1px solid var(--border); border-radius: var(--radius-sm); flex-wrap: wrap;
}
.mpd-slot-date {
    font-family: var(--font-heading); font-size: 0.82rem;
    font-weight: 700; color: var(--text-primary); flex: 1; min-width: 140px;
}
.mpd-slot-time {
    font-family: monospace; font-size: 0.82rem;
    color: var(--accent-light); font-weight: 600; white-space: nowrap;
}
</style>

<script>
(function () {
    // Slot data from Blade
    var slots = JSON.parse(document.getElementById('mpd-slot-data').dataset.slots || '[]');
    var slotDates = {};
    slots.forEach(function (s) { slotDates[s.date] = s; });

    var months = ['January','February','March','April','May','June',
                  'July','August','September','October','November','December'];
    var calDate = new Date();

    // Set initial calendar to first slot month if available
    if (slots.length > 0) {
        var first = new Date(slots[0].date);
        calDate = new Date(first.getFullYear(), first.getMonth(), 1);
    }

    function pad(n) { return String(n).padStart(2, '0'); }

    function updateClock(slot) {
        if (!slot) {
            document.getElementById('mpd-clock-h').textContent    = '--';
            document.getElementById('mpd-clock-m').textContent    = '--';
            document.getElementById('mpd-clock-ampm').textContent = '--';
            document.getElementById('mpd-clock-end').textContent  = '—';
            return;
        }
        document.getElementById('mpd-clock-h').textContent    = pad(slot.start_h);
        document.getElementById('mpd-clock-m').textContent    = pad(slot.start_m);
        document.getElementById('mpd-clock-ampm').textContent = slot.start_ampm;
        document.getElementById('mpd-clock-end').textContent  = slot.end_display;
    }

    // Show first slot time on load
    updateClock(slots[0] || null);

    function renderCal() {
        var grid  = document.getElementById('mpd-cal-grid');
        var label = document.getElementById('mpd-cal-month');
        if (!grid) return;

        label.textContent = months[calDate.getMonth()] + ' ' + calDate.getFullYear();
        grid.innerHTML    = '';

        var year  = calDate.getFullYear();
        var month = calDate.getMonth();
        var first = new Date(year, month, 1).getDay();
        var days  = new Date(year, month + 1, 0).getDate();

        for (var e = 0; e < first; e++) {
            var empty = document.createElement('div');
            empty.className = 'mpd-cal-day mpd-cal-day--empty';
            grid.appendChild(empty);
        }

        for (var d = 1; d <= days; d++) {
            var iso    = year + '-' + pad(month + 1) + '-' + pad(d);
            var hasSlot = !!slotDates[iso];
            var el = document.createElement('div');
            el.className   = 'mpd-cal-day' + (hasSlot ? ' mpd-cal-day--has-slot' : '');
            el.textContent = d;

            if (hasSlot) {
                el.dataset.iso = iso;
                el.title       = slotDates[iso].start + ' → ' + slotDates[iso].end;
                el.addEventListener('click', (function (slot) {
                    return function () {
                        document.querySelectorAll('.mpd-cal-day--active')
                            .forEach(function (x) { x.classList.remove('mpd-cal-day--active'); });
                        this.classList.add('mpd-cal-day--active');
                        updateClock(slot);
                    };
                })(slotDates[iso]));
            }

            grid.appendChild(el);
        }
    }

    renderCal();

    document.getElementById('mpd-cal-prev').addEventListener('click', function () {
        calDate.setMonth(calDate.getMonth() - 1); renderCal();
    });
    document.getElementById('mpd-cal-next').addEventListener('click', function () {
        calDate.setMonth(calDate.getMonth() + 1); renderCal();
    });

    // Popup
    var overlay = document.getElementById('mpd-popup-overlay');
    document.getElementById('mpd-maximize-btn').addEventListener('click', function () {
        overlay.style.display = 'flex';
    });
    document.getElementById('mpd-popup-close').addEventListener('click', function () {
        overlay.style.display = 'none';
    });
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.style.display = 'none';
    });

    // Reject modal
    var rejectOverlay = document.getElementById('mpd-reject-overlay');
    var triggerBtn    = document.getElementById('mpd-reject-trigger');
    var cancelBtn     = document.getElementById('mpd-reject-cancel');

    if (triggerBtn) {
        triggerBtn.addEventListener('click', function () {
            rejectOverlay.style.display = 'flex';
        });
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            rejectOverlay.style.display = 'none';
        });
    }
    if (rejectOverlay) {
        rejectOverlay.addEventListener('click', function (e) {
            if (e.target === rejectOverlay) rejectOverlay.style.display = 'none';
        });
    }
})();
</script>

@endsection