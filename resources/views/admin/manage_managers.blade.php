{{--
    resources/views/admin/manage_managers.blade.php
    ────────────────────────────────────────────────
    Feature: View all managers and assign them to cinema branches.
    Controller: AdminManagerController@index / @assign / @unassign
    Data injected (logic-free blade):
      $managers    – Manager collection with eager-loaded ->cinemas
      $cinemas     – Cinema collection with ->city
      $assignments – BranchManager collection with ->manager and ->cinema
--}}
@extends('admin.admin_team')

@section('page_title', 'Managers')
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite(['resources/css/manage_managers.css'])
@endsection

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">Branch <span>Managers</span></h1>
    <p class="ac-page-header__sub">Assign managers to cinema branches. Each manager can manage one or more cinemas.</p>
</div>

<div class="mm-layout">

    {{-- ── LEFT: Assign Form ────────────────────────── --}}
    <div class="mm-assign-panel">

        <div class="ac-card">
            <div class="ac-card__title">Assign Manager to Cinema</div>

            <form action="{{ route('admin.managers.assign') }}" method="POST" novalidate>
                @csrf

                <div class="ac-field" style="margin-bottom:16px;">
                    <label for="manager_id">Manager <span class="required">*</span></label>
                    <select
                        id="manager_id"
                        name="manager_id"
                        class="ac-select @error('manager_id') is-invalid @enderror"
                        required
                    >
                        <option value="">— Select manager —</option>
                        @foreach ($managers as $manager)
                            <option
                                value="{{ $manager->manager_id }}"
                                {{ old('manager_id') == $manager->manager_id ? 'selected' : '' }}
                            >
                                {{ $manager->manager_name }}
                                ({{ $manager->manager_email }})
                            </option>
                        @endforeach
                    </select>
                    @error('manager_id') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-field" style="margin-bottom:24px;">
                    <label for="cinema_id">Cinema <span class="required">*</span></label>
                    <select
                        id="cinema_id"
                        name="cinema_id"
                        class="ac-select @error('cinema_id') is-invalid @enderror"
                        required
                    >
                        <option value="">— Select cinema —</option>
                        @foreach ($cinemas as $cinema)
                            <option
                                value="{{ $cinema->cinema_id }}"
                                {{ old('cinema_id') == $cinema->cinema_id ? 'selected' : '' }}
                            >
                                {{ $cinema->cinema_name }}
                                @if ($cinema->city)
                                    — {{ $cinema->city->city_name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('cinema_id') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="ac-btn ac-btn--primary" style="width:100%;justify-content:center;">
                    👤 Assign Manager
                </button>
            </form>
        </div>

        {{-- Manager directory ────────────────────────── --}}
        <div class="ac-card" style="margin-top:20px;">
            <div class="ac-card__title">All Managers</div>

            @if ($managers->isEmpty())
                <div class="ac-empty">
                    <div class="ac-empty__icon">👤</div>
                    <p class="ac-empty__text">No managers found.</p>
                </div>
            @else
                <div class="mm-manager-list">
                    @foreach ($managers as $manager)
                        <div class="mm-manager-row">
                            {{-- Passport pic --}}
                            @if ($manager->manager_passport_pic)
                                <img
                                    src="{{ asset('images/managers/' . $manager->manager_passport_pic) }}"
                                    alt="{{ $manager->manager_name }}"
                                    class="mm-manager-avatar"
                                >
                            @else
                                <div class="mm-manager-avatar mm-manager-avatar--ph">👤</div>
                            @endif

                            <div class="mm-manager-info">
                                <p class="mm-manager-name">{{ $manager->manager_name }}</p>
                                <p class="mm-manager-email">{{ $manager->manager_email }}</p>
                                @if ($manager->cinemas->isNotEmpty())
                                    <div class="mm-manager-cinemas">
                                        @foreach ($manager->cinemas as $c)
                                            <span class="ac-badge">{{ $c->cinema_name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>{{-- /.mm-assign-panel --}}

    {{-- ── RIGHT: Current assignments table ───────────── --}}
    <div class="mm-assignments-panel">

        <div class="ac-card">
            <div class="ac-card__title">Current Branch Assignments</div>

            @if ($assignments->isEmpty())
                <div class="ac-empty">
                    <div class="ac-empty__icon">🏢</div>
                    <p class="ac-empty__text">No assignments yet. Use the form to assign a manager.</p>
                </div>
            @else
                <div class="ac-table-wrap">
                    <table class="ac-table">
                        <thead>
                            <tr>
                                <th>Manager</th>
                                <th>Email</th>
                                <th>Cinema</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assignments as $a)
                            <tr>
                                <td class="td-primary">{{ $a->manager?->manager_name ?? '—' }}</td>
                                <td class="td-mono">{{ $a->manager?->manager_email ?? '—' }}</td>
                                <td>
                                    <span class="ac-badge">{{ $a->cinema?->cinema_name ?? '—' }}</span>
                                </td>
                                <td>
                                    <form
                                        action="{{ route('admin.managers.unassign') }}"
                                        method="POST"
                                        onsubmit="return confirm('Remove this assignment?')"
                                        style="display:inline;"
                                    >
                                        @csrf
                                        <input type="hidden" name="manager_id" value="{{ $a->manager_id }}">
                                        <input type="hidden" name="cinema_id"  value="{{ $a->cinema_id }}">
                                        <button type="submit" class="mm-remove-btn">✕ Remove</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>{{-- /.mm-assignments-panel --}}

</div>{{-- /.mm-layout --}}

@endsection