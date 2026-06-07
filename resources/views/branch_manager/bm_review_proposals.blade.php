{{--
    resources/views/branch_manager/bm_review_proposal.blade.php

    READ-ONLY review of a submitted ShowtimeProposal batch.

    Expected controller variables:
      $cinema        — Cinema model
      $movie         — Movie model  (with ->genres eager-loaded)
      $proposal      — ShowtimeProposalStatus model
      $groupedSlots  — Collection keyed by 'Y-m-d' date string.
                       Each value is an array of associative arrays:
                       [
                         'theatre_name' => 'IMAX',
                         'start'        => '07:00 AM',
                         'end'          => '09:05 AM',
                       ]

    Route (add to your manager routes file):
      Route::get('/manager/proposal/review/{movieId}',
                 [BranchManagerReviewProposalController::class, 'show'])
           ->name('manager.proposal.review');
--}}
@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', $movie->movie_name . ' — Proposal Review')

@section('bm_head_extras')
    @vite(['resources/css/bm_review_proposal.css'])
@endsection

@section('bm_content')

{{-- Back link --}}
<a href="{{ route('manager.upcoming') }}" class="bm-back-link">&larr; Back to Upcoming Movies</a>

{{-- ══════════════════════════════════════════════════════
     HERO BANNER
══════════════════════════════════════════════════════ --}}
<div class="rp-hero">
    <div class="rp-hero__img-wrap">
        @if (!empty($movie->landscape_poster))
            <img
                src="{{ asset('images/movies/' . $movie->landscape_poster) }}"
                alt="{{ $movie->movie_name }}"
                class="rp-hero__img"
            >
        @else
            <div class="rp-hero__img-ph">🎬</div>
        @endif
        <div class="rp-hero__overlay">
            <h1 class="rp-hero__title">{{ $movie->movie_name }}</h1>
            <p class="rp-hero__meta">
                @php $rh = intdiv($movie->runtime, 60); $rm = $movie->runtime % 60; @endphp
                @if ($movie->genres->isNotEmpty())
                    {{ $movie->genres->pluck('genre_name')->join(', ') }}&ensp;·&ensp;
                @endif
                {{ $rh > 0 ? $rh . 'h ' : '' }}{{ $rm }}m&ensp;·&ensp;{{ $movie->language }}
            </p>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     PRINTABLE CONTENT  (captured by PDF button)
══════════════════════════════════════════════════════ --}}
<div id="rp-printable">

    {{-- Status banner --}}
    <div class="rp-status-banner rp-status-banner--{{ $proposal->status }}">
        <span class="rp-status-banner__icon">
            @if ($proposal->status === 'pending') ⏳
            @elseif ($proposal->status === 'approved') ✓
            @else ✕
            @endif
        </span>
        <div class="rp-status-banner__body">
            @if ($proposal->status === 'pending')
                <p class="rp-status-banner__label">Pending Admin Approval</p>
                <p class="rp-status-banner__sub">Awaiting review — no changes can be made until a decision is issued.</p>
            @elseif ($proposal->status === 'approved')
                <p class="rp-status-banner__label">Proposal Approved</p>
                <p class="rp-status-banner__sub">This proposal has been approved by the admin.</p>
            @else
                <p class="rp-status-banner__label">Proposal Rejected</p>
                <p class="rp-status-banner__sub">
                    @if ($proposal->admin_note)
                        <strong>Admin note:</strong> {{ $proposal->admin_note }}
                    @else
                        No admin note was provided.
                    @endif
                </p>
            @endif
        </div>
    </div>

    {{-- Meta strip --}}
    <div class="rp-meta-strip">
        <div class="rp-meta-item">
            <span class="rp-meta-item__key">Cinema</span>
            <span class="rp-meta-item__val">{{ $cinema->cinema_name }}</span>
        </div>
        <div class="rp-meta-item">
            <span class="rp-meta-item__key">Submitted</span>
            <span class="rp-meta-item__val">{{ $proposal->created_at->format('d M Y, h:i A') }}</span>
        </div>
        <div class="rp-meta-item">
            <span class="rp-meta-item__key">Total Slots</span>
            <span class="rp-meta-item__val">{{ $proposal->proposals->count() }}</span>
        </div>
        <div class="rp-meta-item">
            <span class="rp-meta-item__key">Total Dates</span>
            <span class="rp-meta-item__val">{{ $groupedSlots->count() }}</span>
        </div>
    </div>

    {{-- Timetable grouped by date --}}
    @if ($groupedSlots->isEmpty())
        <div class="rp-empty">No showtime slots found for this proposal.</div>
    @else
        <div class="rp-timetable">
            @foreach ($groupedSlots as $date => $slots)
                @php $parsed = \Carbon\Carbon::parse($date); @endphp
                <div class="rp-date-block">

                    <div class="rp-date-header">
                        <span class="rp-date-header__dow">{{ $parsed->format('D') }}</span>
                        <span class="rp-date-header__date">{{ $parsed->format('d M Y') }}</span>
                        <span class="rp-date-header__count">
                            {{ count($slots) }}&nbsp;slot{{ count($slots) !== 1 ? 's' : '' }}
                        </span>
                    </div>

                    <div class="rp-slots">
                        @foreach ($slots as $slot)
                            <div class="rp-slot">
                                <span class="rp-slot__theatre">{{ $slot['theatre_name'] }}</span>
                                <span class="rp-slot__times">
                                    <span class="rp-slot__start">{{ $slot['start'] }}</span>
                                    <span class="rp-slot__arrow">→</span>
                                    <span class="rp-slot__end">{{ $slot['end'] }}</span>
                                </span>
                            </div>
                        @endforeach
                    </div>

                </div>
            @endforeach
        </div>
    @endif

</div>{{-- /#rp-printable --}}

{{-- ══════════════════════════════════════════════════════
     ACTION BAR  (not included in PDF)
══════════════════════════════════════════════════════ --}}
<div class="rp-action-bar" id="rp-action-bar">

    <a href="{{ route('manager.upcoming') }}" class="rp-btn rp-btn--ghost">
        ← Back
    </a>

    @if ($proposal->status === 'rejected')
        <a
            href="{{ route('manager.setup.movie', $movie->movie_id) }}"
            class="rp-btn rp-btn--resubmit"
        >
            Re-submit New Proposal
        </a>
    @endif

    <button type="button" id="rp-pdf-btn" class="rp-btn rp-btn--pdf">
        ⬇&ensp;Download as PDF
    </button>

</div>

{{-- Pass movie name to JS for the PDF filename --}}
<span id="rp-movie-name" data-name="{{ $movie->movie_name }}" style="display:none;"></span>

@endsection

@section('bm_foot_scripts')
    @vite(['resources/js/bm_review_proposal.js'])
@endsection