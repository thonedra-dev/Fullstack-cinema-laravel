{{--
    resources/views/admin/movie_proposals.blade.php
    ────────────────────────────────────────────────
    Feature: Abstract list of all showtime proposals from branch managers.
    Controller: AdminMovieProposalController@index
    Data:
      $proposals – ShowtimeProposal collection with ->manager, ->cinema, ->theatre, ->movie
--}}
@extends('admin.admin_team')

@section('page_title', 'Movie Proposals')
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite(['resources/css/movie_proposals.css'])
@endsection

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">Movie <span>Proposals</span></h1>
    <p class="ac-page-header__sub">
        Showtime proposals submitted by branch managers. Click to review and approve.
    </p>
</div>

@if ($proposals->isEmpty())
    <div class="ac-empty" style="padding:80px 20px;">
        <div class="ac-empty__icon">📩</div>
        <p class="ac-empty__text" style="font-size:1rem;">No proposals yet.</p>
        <p class="ac-empty__text" style="margin-top:6px;">Branch managers will submit proposals after configuring showtimes.</p>
    </div>
@else

    {{-- Pending proposals first, then processed --}}
    @php
        $pending  = $proposals->where('status', 'pending');
        $approved = $proposals->where('status', 'approved');
    @endphp

    @if ($pending->isNotEmpty())
        <div class="mp-group-label">
            <span class="mp-group-dot mp-group-dot--pending"></span>
            Pending Review ({{ $pending->count() }})
        </div>
        <div class="mp-list">
            @foreach ($pending as $p)
                @include('admin.partials.proposal_card', ['p' => $p])
            @endforeach
        </div>
    @endif

    @if ($approved->isNotEmpty())
        <div class="mp-group-label" style="margin-top:32px;">
            <span class="mp-group-dot mp-group-dot--approved"></span>
            Approved ({{ $approved->count() }})
        </div>
        <div class="mp-list">
            @foreach ($approved as $p)
                @include('admin.partials.proposal_card', ['p' => $p])
            @endforeach
        </div>
    @endif

@endif

@endsection