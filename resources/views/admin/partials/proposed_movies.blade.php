{{--
    resources/views/admin/partials/proposals_content.blade.php
    ────────────────────────────────────────────────
    Rendered by AdminMovieProposalController@index.
    Expects: $proposals
--}}

@if ($proposals->isEmpty())

    <div class="ac-empty" style="padding:80px 20px;">
        <div class="ac-empty__icon">📩</div>
        <p class="ac-empty__text" style="font-size:1rem;">No proposals yet.</p>
        <p class="ac-empty__text" style="margin-top:6px;">
            Branch managers will submit proposals after configuring showtimes.
        </p>
    </div>

@else

    @php
        $pending  = $proposals->where('status', 'pending');
        $approved = $proposals->where('status', 'approved');
        $rejected = $proposals->where('status', 'rejected');
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

    @if ($rejected->isNotEmpty())
        <div class="mp-group-label" style="margin-top:32px;">
            <span class="mp-group-dot mp-group-dot--rejected"></span>
            Rejected ({{ $rejected->count() }})
        </div>
        <div class="mp-list">
            @foreach ($rejected as $p)
                @include('admin.partials.proposal_card', ['p' => $p])
            @endforeach
        </div>
    @endif

@endif