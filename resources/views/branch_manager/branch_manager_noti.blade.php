{{--
    resources/views/branch_manager/branch_manager_noti.blade.php
    ─────────────────────────────────────────────────────────────
    Branch manager notification centre.
    Controller: BranchManagerNotificationController@index
    Data:
      $notifications – Collection of manager_notifications rows, each with:
          ->movie_id        (from left join on movies)
          ->proposal_status (null | 'pending' | 'approved' | 'rejected')
                             — attached by controller from showtime_proposal_status

    Card behaviour (handled in branch_manager_noti.js):
      "Movie Assigned"          → clickable only if proposal_status is null
                                  (not yet submitted) → setup_movie_timetable
      "Movie Rejection By Admin"→ always clickable → setup_movie_timetable (resubmit)
      "Showtime Approved"       → always clickable → bm_movie_formation

    Visual:
      If proposal_status === 'rejected' OR tag === 'Movie Rejection By Admin':
        card gets .bmn-card--rejected (bright red tint + border)
      This colours BOTH the original "Movie Assigned" card AND the
      "Movie Rejection By Admin" card for the same movie.
--}}
@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', 'Notifications')

@section('bm_head_extras')
    @vite(['resources/css/branch_manager_noti.css', 'resources/js/branch_manager_noti.js'])
@endsection

@section('bm_content')

<a href="{{ route('manager.home') }}" class="bm-back-link">← Back to Dashboard</a>

<div class="bm-page-header">
    <h1 class="bm-page-header__title">🔔 <span>Notifications</span></h1>
    <p class="bm-page-header__sub">
        System messages and admin notes addressed to you.
    </p>
</div>

@if ($notifications->isEmpty())

    <div class="bmn-empty">
        <div class="bmn-empty__icon">🔕</div>
        <p class="bmn-empty__title">No notifications yet.</p>
        <p class="bmn-empty__sub">You will receive messages here when admin takes action on your proposals.</p>
    </div>

@else

    <div class="bmn-list">
        @foreach ($notifications as $noti)
            @php
                /*
                 * Determine tag CSS modifier.
                 *   approved  → green
                 *   rejected  → bright red (new class)
                 *   assigned  → purple (default)
                 */
                $tagClass = match($noti->tag) {
                    'Showtime Approved'        => 'approved',
                    'Movie Rejection By Admin' => 'rejected',
                    default                    => 'assigned',
                };

                /*
                 * A card gets the "rejected" red highlight when:
                 *   a) it IS the rejection notification itself, OR
                 *   b) it is a "Movie Assigned" card for a movie whose
                 *      proposal was subsequently rejected by admin —
                 *      no point going back to that assignment card anymore.
                 */
                $isRejectedContext = $noti->tag === 'Movie Rejection By Admin'
                    || $noti->proposal_status === 'rejected';
            @endphp

            <div
                class="bmn-card {{ $isRejectedContext ? 'bmn-card--rejected' : '' }}"
                data-movie-id="{{ $noti->movie_id }}"
                data-tag="{{ $noti->tag }}"
                data-proposal-status="{{ $noti->proposal_status ?? '' }}"
            >

                {{-- Thumbnail --}}
                <div class="bmn-card__thumb">
                    @if (!empty($noti->noti_picture))
                        <img
                            src="{{ asset('images/movies/' . $noti->noti_picture) }}"
                            alt="poster"
                            class="bmn-card__thumb-img"
                        >
                    @else
                        <div class="bmn-card__thumb-ph">🔔</div>
                    @endif
                </div>

                {{-- Body --}}
                <div class="bmn-card__body">
                    <div class="bmn-card__header">
                        <span class="bmn-card__tag bmn-card__tag--{{ $tagClass }}">
                            {{ $noti->tag }}
                        </span>
                        @if (!$noti->is_read)
                            <span class="bmn-card__unread-dot" title="Unread"></span>
                        @endif
                        <span class="bmn-card__time">
                            {{ \Carbon\Carbon::parse($noti->created_at)->diffForHumans() }}
                        </span>
                    </div>

                    <p class="bmn-card__message">{{ $noti->noti_message }}</p>

                    {{-- Inline JS feedback message (shown when clicking a stale "Movie Assigned" card) --}}
                   <p class="bmn-card__already-submitted" style="display:none;">
                       ⏳ Your proposal for this movie is currently pending admin review.
                   </p>

                    <p class="bmn-card__date">
                        {{ \Carbon\Carbon::parse($noti->created_at)->format('d M Y, h:i A') }}
                    </p>
                </div>

            </div>
        @endforeach
    </div>

@endif

@endsection