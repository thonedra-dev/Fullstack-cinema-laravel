{{--
    resources/views/branch_manager/branch_manager_noti.blade.php
    ─────────────────────────────────────────────────────────────
    Branch manager notification centre.
    Controller: BranchManagerNotificationController@index
    Data:
      $notifications – Collection of manager_notifications rows
                       (noti_id, manager_id, noti_picture, noti_message, tag, created_at)
--}}
@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', 'Notifications')

@section('bm_head_extras')
    @vite(['resources/css/branch_manager_noti.css'])
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
            <div class="bmn-card">

                {{-- Poster thumbnail --}}
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
                        <span class="bmn-card__tag bmn-card__tag--{{ $noti->tag === 'Showtime Approved' ? 'approved' : 'rejected' }}">
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
                    <p class="bmn-card__date">
                        {{ \Carbon\Carbon::parse($noti->created_at)->format('d M Y, h:i A') }}
                    </p>
                </div>

            </div>
        @endforeach
    </div>

@endif

@endsection